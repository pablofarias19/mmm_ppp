<?php
/**
 * upload_og_photo.php — Sube o elimina la foto OG de un negocio
 *
 * POST  multipart/form-data
 *   business_id  int    ID del negocio (requerido)
 *   og_photo     file   Archivo de imagen (requerido para acción 'upload')
 *   action       string 'upload' | 'delete' (default: upload)
 *
 * La foto se guarda como uploads/businesses/{id}/og_cover.{ext}
 * y reemplaza cualquier og_cover previo.
 * Límite: 200 KB · JPG / PNG / WebP
 */

session_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}

require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../core/helpers.php';

$userId     = (int)$_SESSION['user_id'];
$businessId = (int)($_POST['business_id'] ?? 0);
$brandId    = (int)($_POST['brand_id']    ?? 0);
$action     = $_POST['action'] ?? 'upload';

// ── Determinar si es negocio o marca ─────────────────────────────────────────
$isBrand = ($brandId > 0 && $businessId === 0);
$entityId = $isBrand ? $brandId : $businessId;

if ($entityId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

// ── Verificar propiedad ───────────────────────────────────────────────────────
try {
    $db = getDbConnection();
    if ($isBrand) {
        $isOwner = false;
        if (!empty($_SESSION['is_admin'])) {
            $isOwner = true;
        } else {
            // brands table uses user_id, marcas table uses usuario_id
            $stmt = $db->prepare("SELECT id FROM brands WHERE id = ? AND user_id = ?");
            $stmt->execute([$brandId, $userId]);
            if ($stmt->fetch()) {
                $isOwner = true;
            } else {
                $stmt = $db->prepare("SELECT id FROM marcas WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$brandId, $userId]);
                if ($stmt->fetch()) $isOwner = true;
            }
        }
        if (!$isOwner) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permiso para esta marca.']);
            exit;
        }
        $uploadDir  = __DIR__ . '/../uploads/brands/' . $brandId . '/';
        $publicBase = '/uploads/brands/' . $brandId . '/';
    } else {
        $isOwner = !empty($_SESSION['is_admin']);
        if (!$isOwner) {
            $stmt = $db->prepare("SELECT id FROM businesses WHERE id = ? AND user_id = ?");
            $stmt->execute([$businessId, $userId]);
            $isOwner = (bool)$stmt->fetch();
        }
        if (!$isOwner) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permiso para este negocio.']);
            exit;
        }
        $uploadDir  = __DIR__ . '/../uploads/businesses/' . $businessId . '/';
        $publicBase = '/uploads/businesses/' . $businessId . '/';
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
    exit;
}

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ── Eliminar foto OG ──────────────────────────────────────────────────────────
if ($action === 'delete') {
    $deleted = false;
    foreach (['jpg','jpeg','png','webp'] as $ext) {
        $f = $uploadDir . 'og_cover.' . $ext;
        if (file_exists($f)) { unlink($f); $deleted = true; }
    }
    echo json_encode(['success' => true, 'message' => $deleted ? 'Foto OG eliminada.' : 'No había foto OG.']);
    exit;
}

// Fijar $publicBase para delete (ya definido arriba)
$publicBase = $isBrand
    ? '/uploads/brands/'    . $brandId    . '/'
    : '/uploads/businesses/' . $businessId . '/';

// ── Subir nueva foto OG ───────────────────────────────────────────────────────
if (empty($_FILES['og_photo']) || $_FILES['og_photo']['error'] !== UPLOAD_ERR_OK) {
    $errMsg = match($_FILES['og_photo']['error'] ?? -1) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
        UPLOAD_ERR_NO_FILE  => 'No se seleccionó ningún archivo.',
        default             => 'Error al subir el archivo.',
    };
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

$file    = $_FILES['og_photo'];
$maxSize = 200 * 1024; // 200 KB

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'La imagen no puede superar 200 KB. Comprimila antes de subir (podés usar squoosh.app o tinypng.com).']);
    exit;
}

// Verificar MIME real
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!array_key_exists($mimeType, $allowedMime)) {
    echo json_encode(['success' => false, 'message' => 'Formato no permitido. Usá JPG, PNG o WebP.']);
    exit;
}

$ext     = $allowedMime[$mimeType];
$newName = 'og_cover.' . $ext;
$dest    = $uploadDir . $newName;

// Eliminar og_cover previo (cualquier extensión)
foreach (['jpg','jpeg','png','webp'] as $oldExt) {
    $old = $uploadDir . 'og_cover.' . $oldExt;
    if (file_exists($old) && $old !== $dest) unlink($old);
}

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar la imagen.']);
    exit;
}

$publicPath = $publicBase . $newName;
echo json_encode([
    'success' => true,
    'message' => 'Foto OG guardada correctamente.',
    'path'    => $publicPath,
    'preview' => $publicPath . '?t=' . time(),
]);
