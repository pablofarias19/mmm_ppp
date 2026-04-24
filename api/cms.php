<?php
/**
 * api/cms.php — CMS multilingüe para contenido técnico/avanzado
 *
 * GET  ?slug=&lang=       Público — devuelve la mejor traducción disponible.
 * POST action=create_page  Admin — crea una página.
 * PUT  action=upsert_translation  Admin — crea/actualiza traducción.
 * PUT  action=upsert_glossary     Admin — crea/actualiza término de glosario.
 *
 * Fallback chain (lang): lang exacto → base (it-IT→it) → en → es
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

function respond_success($data = null, $message = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}
function respond_error($message = 'Error', $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$db = null;
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (\Throwable $e) {
    error_log('cms api db: ' . $e->getMessage());
    respond_error('Base de datos no disponible', 503);
}

// ── GET: lectura pública ──────────────────────────────────────────────────────
if ($method === 'GET') {
    $slug = trim($_GET['slug'] ?? '');
    if ($slug === '') {
        // Listar páginas publicadas
        $stmt = $db->query(
            "SELECT id, slug, module, status, created_at, updated_at
               FROM cms_pages
              ORDER BY module, slug"
        );
        respond_success($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    // Obtener página por slug
    $stmt = $db->prepare(
        "SELECT id, slug, module, status, created_at, updated_at
           FROM cms_pages
          WHERE slug = ?"
    );
    $stmt->execute([$slug]);
    $page = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$page) {
        respond_error('Página no encontrada', 404);
    }

    $requestedLang = trim($_GET['lang'] ?? 'es');
    $baseLang      = strtolower(explode('-', $requestedLang)[0]);

    // Fallback chain: exact → base → en → es
    $chain = array_unique(array_filter([
        $requestedLang,
        $baseLang !== $requestedLang ? $baseLang : null,
        'en',
        'es',
    ]));

    $translation  = null;
    $resolvedLang = null;
    foreach ($chain as $candidate) {
        $ts = $db->prepare(
            "SELECT lang, title, body_md, summary, is_machine_draft, review_status, updated_at
               FROM cms_page_translations
              WHERE page_id = ? AND lang = ?
              LIMIT 1"
        );
        $ts->execute([$page['id'], $candidate]);
        $row = $ts->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $translation  = $row;
            $resolvedLang = $candidate;
            break;
        }
    }

    respond_success([
        'page'          => $page,
        'translation'   => $translation,
        'resolved_lang' => $resolvedLang,
        'requested_lang'=> $requestedLang,
    ]);
}

// ── Operaciones de escritura (sólo admin) ────────────────────────────────────
if ($method === 'POST' || $method === 'PUT') {
    if (!isAdmin()) {
        respond_error('Acceso denegado', 403);
    }

    // CSRF: verificar token enviado en header o body
    $ct    = $_SERVER['CONTENT_TYPE'] ?? '';
    $input = stripos($ct, 'application/json') !== false
        ? (json_decode(file_get_contents('php://input'), true) ?: [])
        : $_POST;

    verifyCsrfToken($input['csrf_token'] ?? null);

    $action = $input['action'] ?? ($_GET['action'] ?? '');

    // ── POST: crear página ───────────────────────────────────────────────────
    if ($method === 'POST' && $action === 'create_page') {
        $slug   = trim($input['slug'] ?? '');
        $module = trim($input['module'] ?? 'advanced');
        $status = in_array($input['status'] ?? '', ['draft','published'], true)
                  ? $input['status'] : 'draft';

        if ($slug === '') {
            respond_error('El campo slug es obligatorio', 422);
        }
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            respond_error('slug sólo puede contener letras minúsculas, números y guiones', 422);
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO cms_pages (slug, module, status) VALUES (?, ?, ?)"
            );
            $stmt->execute([$slug, $module, $status]);
            respond_success(['id' => (int)$db->lastInsertId()], 'Página creada');
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                respond_error('El slug ya existe', 409);
            }
            error_log('cms create_page: ' . $e->getMessage());
            respond_error('Error al crear la página', 500);
        }
    }

    // ── PUT: upsert traducción ───────────────────────────────────────────────
    if ($method === 'PUT' && $action === 'upsert_translation') {
        $pageId   = (int)($input['page_id'] ?? 0);
        $lang     = trim($input['lang'] ?? '');
        $title    = trim($input['title'] ?? '');
        $bodyMd   = $input['body_md'] ?? '';
        $summary  = $input['summary'] ?? null;
        $isMachine= (int)($input['is_machine_draft'] ?? 0);
        $review   = in_array($input['review_status'] ?? '', ['needs_review','reviewed','legal_verified'], true)
                    ? $input['review_status'] : 'needs_review';

        if ($pageId <= 0 || $lang === '' || $title === '') {
            respond_error('Campos obligatorios: page_id, lang, title', 422);
        }

        // Verificar que la página existe
        $chk = $db->prepare("SELECT id FROM cms_pages WHERE id = ? LIMIT 1");
        $chk->execute([$pageId]);
        if (!$chk->fetch()) {
            respond_error('Página no encontrada', 404);
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO cms_page_translations
                     (page_id, lang, title, body_md, summary, is_machine_draft, review_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     title = VALUES(title),
                     body_md = VALUES(body_md),
                     summary = VALUES(summary),
                     is_machine_draft = VALUES(is_machine_draft),
                     review_status = VALUES(review_status),
                     updated_at = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$pageId, $lang, $title, $bodyMd, $summary, $isMachine, $review]);
            respond_success([], 'Traducción guardada');
        } catch (\PDOException $e) {
            error_log('cms upsert_translation: ' . $e->getMessage());
            respond_error('Error al guardar la traducción', 500);
        }
    }

    // ── PUT: upsert término de glosario ──────────────────────────────────────
    if ($method === 'PUT' && $action === 'upsert_glossary') {
        $domain    = trim($input['domain'] ?? '');
        $termKey   = trim($input['term_key'] ?? '');
        $lang      = trim($input['lang'] ?? '');
        $term      = trim($input['term'] ?? '');
        $defMd     = $input['definition_md'] ?? '';
        $notesMd   = $input['notes_md'] ?? null;

        if ($domain === '' || $termKey === '' || $lang === '' || $term === '') {
            respond_error('Campos obligatorios: domain, term_key, lang, term', 422);
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO cms_glossary_terms
                     (domain, term_key, lang, term, definition_md, notes_md)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     term = VALUES(term),
                     definition_md = VALUES(definition_md),
                     notes_md = VALUES(notes_md),
                     updated_at = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$domain, $termKey, $lang, $term, $defMd, $notesMd]);
            respond_success([], 'Término guardado');
        } catch (\PDOException $e) {
            error_log('cms upsert_glossary: ' . $e->getMessage());
            respond_error('Error al guardar el término', 500);
        }
    }

    respond_error('Acción no válida', 400);
}

respond_error('Método no permitido', 405);
