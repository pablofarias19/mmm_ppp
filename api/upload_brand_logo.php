<?php
/**
 * API Logo de Marca
 *
 * POST action=upload  brand_id=X  logo=file  → guarda como uploads/brands/{id}/logo.{ext}
 * POST action=delete  brand_id=X             → elimina logo existente
 * GET  ?brand_id=X                           → devuelve info del logo actual
 *
 * Límites: máx 120 KB · JPG / PNG / WebP
 * El logo es la imagen que aparece como icono en el mapa.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../core/helpers.php';

$userId = (int)$_SESSION['user_id'];
$isAdmin = !empty($_SESSION['is_admin']);
$method = $_SERVER['REQUEST_METHOD'];

// ── Verificar propiedad ───────────────────────────────────────────────────────
function verifyBrandOwnership(int $brandId, int $userId, bool $isAdmin): bool {
    try {
        if ($isAdmin) return true;
        return canManageBrand($userId, $brandId);
    } catch (Exception $e) {
        return false;
    }
}

function getLogoInfo(int $brandId): array {
    $dir = __DIR__ . '/../uploads/brands/' . $brandId . '/';
    foreach (['png','jpg','jpeg','webp'] as $ext) {
        $file = $dir . 'logo.' . $ext;
        if (file_exists($file)) {
            return [
                'exists'   => true,
                'url'      => '/uploads/brands/' . $brandId . '/logo.' . $ext . '?t=' . filemtime($file),
                'filename' => 'logo.' . $ext,
            ];
        }
    }
    return ['exists' => false, 'url' => null, 'filename' => null];
}

// ── GET ───────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $brandId = (int)($_GET['brand_id'] ?? 0);
    if ($brandId <= 0) { echo json_encode(['success' => false, 'message' => 'ID inválido.']); exit; }
    if (!verifyBrandOwnership($brandId, $userId, $isAdmin)) {
        http_response_code(403); echo json_encode(['success' => false, 'message' => 'Sin permiso.']); exit;
    }
    echo json_encode(array_merge(['success' => true], getLogoInfo($brandId)));
    exit;
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $action  = $_POST['action']   ?? 'upload';
    $brandId = (int)($_POST['brand_id'] ?? 0);

    if ($brandId <= 0) { echo json_encode(['success' => false, 'message' => 'ID inválido.']); exit; }
    if (!verifyBrandOwnership($brandId, $userId, $isAdmin)) {
        http_response_code(403); echo json_encode(['success' => false, 'message' => 'Sin permiso.']); exit;
    }

    $uploadDir = __DIR__ . '/../uploads/brands/' . $brandId . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // ── Eliminar logo ─────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $deleted = false;
        foreach (['jpg','jpeg','png','webp'] as $ext) {
            $f = $uploadDir . 'logo.' . $ext;
            if (file_exists($f)) { unlink($f); $deleted = true; }
        }
        echo json_encode(['success' => true, 'message' => $deleted ? 'Logo eliminado.' : 'No había logo.']);
        exit;
    }

    // ── Subir logo ────────────────────────────────────────────────────────────
    if ($action === 'upload') {
        $maxSize = 120 * 1024; // 120 KB — el logo es un ícono de mapa, debe ser liviano

        if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['logo']['error'] ?? -1;
            $errMsg  = match($errCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
                UPLOAD_ERR_NO_FILE  => 'No se seleccionó ningún archivo.',
                default             => 'Error al subir el archivo (código ' . $errCode . ').',
            };
            echo json_encode(['success' => false, 'message' => $errMsg]);
            exit;
        }

        $file = $_FILES['logo'];

        if ($file['size'] > $maxSize) {
            $kb = round($file['size'] / 1024);
            echo json_encode(['success' => false, 'message' => "Tu archivo pesa {$kb} KB. El logo del mapa debe pesar máximo 120 KB. Comprimilo gratis en squoosh.app o tinypng.com antes de subir."]);
            exit;
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!array_key_exists($mimeType, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Formato no permitido. Usá JPG, PNG o WebP.']);
            exit;
        }

        $ext  = $allowed[$mimeType];
        $dest = $uploadDir . 'logo.' . $ext;

        // Eliminar logo previo (cualquier extensión)
        foreach (['jpg','jpeg','png','webp'] as $oldExt) {
            $old = $uploadDir . 'logo.' . $oldExt;
            if (file_exists($old) && $old !== $dest) unlink($old);
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar el logo.']);
            exit;
        }

        $publicUrl = '/uploads/brands/' . $brandId . '/logo.' . $ext . '?t=' . time();
        echo json_encode([
            'success' => true,
            'message' => 'Logo subido correctamente.',
            'url'     => $publicUrl,
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
