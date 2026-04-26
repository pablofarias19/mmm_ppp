<?php
/**
 * Búsqueda global admin anti-fraude
 *
 * GET /admin/api/search.php?q=texto[&type=users|businesses|brands][&page=N]
 *
 * Devuelve resultados agrupados por tipo, paginados.
 * Acceso restringido solo a administradores.
 *
 * Busca por coincidencia parcial (LIKE %q%) en:
 *   - usuarios: username, email, first_name, last_name
 *   - negocios: name, address, business_type
 *   - marcas (brands): nombre
 *   - marcas (marcas): nombre
 */

session_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

// Solo administradores
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso restringido.']);
    exit;
}

$q    = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

if ($q === '') {
    echo json_encode(['success' => false, 'message' => 'Se requiere parámetro q.']);
    exit;
}

$like = '%' . $q . '%';
$db   = getDbConnection();

$results = [
    'users'      => [],
    'businesses' => [],
    'brands'     => [],
    'marcas'     => [],
    'query'      => $q,
    'page'       => $page,
];

// ── Usuarios ────────────────────────────────────────────────────────────────
if ($type === 'all' || $type === 'users') {
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, u.first_name, u.last_name,
               u.is_admin, u.created_at,
               GROUP_CONCAT(b.name ORDER BY b.id SEPARATOR ', ') AS business_names
        FROM users u
        LEFT JOIN businesses b ON b.user_id = u.id
        WHERE u.username    LIKE ?
           OR u.email       LIKE ?
           OR u.first_name  LIKE ?
           OR u.last_name   LIKE ?
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$like, $like, $like, $like, $perPage, $offset]);
    $results['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Negocios ─────────────────────────────────────────────────────────────────
if ($type === 'all' || $type === 'businesses') {
    $stmt = $db->prepare("
        SELECT b.id, b.name, b.business_type, b.address, b.visible,
               b.created_at, u.username AS owner_username,
               u.email AS owner_email,
               u.first_name AS owner_first_name,
               u.last_name  AS owner_last_name
        FROM businesses b
        LEFT JOIN users u ON u.id = b.user_id
        WHERE b.name          LIKE ?
           OR b.address       LIKE ?
           OR b.business_type LIKE ?
           OR u.username      LIKE ?
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$like, $like, $like, $like, $perPage, $offset]);
    $results['businesses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Brands (tabla brands) ───────────────────────────────────────────────────
if ($type === 'all' || $type === 'brands') {
    $stmt = $db->prepare("
        SELECT b.id, b.nombre, b.rubro, b.ubicacion, b.visible, b.created_at,
               u.username AS owner_username
        FROM brands b
        LEFT JOIN users u ON u.id = b.user_id
        WHERE b.nombre  LIKE ?
           OR b.rubro   LIKE ?
           OR u.username LIKE ?
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$like, $like, $like, $perPage, $offset]);
    $results['brands'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Marcas (tabla marcas legacy) ────────────────────────────────────────────
if ($type === 'all' || $type === 'marcas') {
    try {
        $stmt = $db->prepare("
            SELECT m.id, m.nombre, m.rubro, m.ubicacion, m.estado, m.created_at,
                   u.username AS owner_username
            FROM marcas m
            LEFT JOIN users u ON u.id = m.usuario_id
            WHERE m.nombre   LIKE ?
               OR m.rubro    LIKE ?
               OR u.username LIKE ?
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$like, $like, $like, $perPage, $offset]);
        $results['marcas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // tabla no existe o error — ignorar
        $results['marcas'] = [];
    }
}

$total = count($results['users']) + count($results['businesses'])
       + count($results['brands']) + count($results['marcas']);

echo json_encode([
    'success' => true,
    'total'   => $total,
    'page'    => $page,
    'results' => $results,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
