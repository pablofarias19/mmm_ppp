<?php
/**
 * API Galería de Industrias
 *
 * GET  ?industry_id=X           → lista fotos de galería de la industria
 * POST action=upload             → sube 1 foto a la vez (multipart), máx 2 en total
 * POST action=delete             → elimina una foto por nombre
 *
 * Límites: máx 2 fotos de galería · máx 120 KB por foto · JPG / PNG / WebP
 * Las fotos se guardan en uploads/industries/{id}/gallery_{timestamp}.{ext}
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
$method = $_SERVER['REQUEST_METHOD'];

// ── Helper: listar fotos de galería ──────────────────────────────────────────
function listIndustryGalleryPhotos(int $industryId): array {
    $dir    = __DIR__ . '/../uploads/industries/' . $industryId . '/';
    $photos = [];
    if (!is_dir($dir)) return $photos;
    foreach (glob($dir . 'gallery_*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $file) {
        $fname    = basename($file);
        $photos[] = [
            'filename' => $fname,
            'url'      => '/uploads/industries/' . $industryId . '/' . $fname . '?t=' . filemtime($file),
            'size'     => filesize($file),
        ];
    }
    usort($photos, fn($a, $b) => strcmp($a['filename'], $b['filename']));
    return $photos;
}

// ── Verificar propiedad (o admin) ────────────────────────────────────────────
function verifyIndustryOwnership(int $industryId, int $userId): bool {
    if (!empty($_SESSION['is_admin'])) return true;
    try {
        $db = getDbConnection();
        if (!$db) return false;
        $stmt = $db->prepare('SELECT id, user_id FROM industries WHERE id = ?');
        $stmt->execute([$industryId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        return (int)$row['user_id'] === $userId;
    } catch (Exception $e) {
        error_log('upload_industry_gallery verifyOwnership: ' . $e->getMessage());
        return false;
    }
}

// ── GET: listar fotos ─────────────────────────────────────────────────────────
if ($method === 'GET') {
    $industryId = (int)($_GET['industry_id'] ?? 0);
    if ($industryId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    if (!verifyIndustryOwnership($industryId, $userId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permiso.']);
        exit;
    }
    $photos = listIndustryGalleryPhotos($industryId);
    echo json_encode(['success' => true, 'photos' => $photos, 'count' => count($photos)]);
    exit;
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $action     = $_POST['action'] ?? 'upload';
    $industryId = (int)($_POST['industry_id'] ?? 0);

    if ($industryId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    if (!verifyIndustryOwnership($industryId, $userId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permiso.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/industries/' . $industryId . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // ── Eliminar foto ─────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $filename = basename($_POST['filename'] ?? '');
        if (!preg_match('/^gallery_[\w\-]+\.(jpg|jpeg|png|webp)$/i', $filename)) {
            echo json_encode(['success' => false, 'message' => 'Nombre de archivo inválido.']);
            exit;
        }
        $path = $uploadDir . $filename;
        if (file_exists($path)) {
            unlink($path);
            echo json_encode(['success' => true, 'message' => 'Foto eliminada.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Archivo no encontrado.']);
        }
        exit;
    }

    // ── Subir foto ────────────────────────────────────────────────────────────
    if ($action === 'upload') {
        $maxPhotos = 2;
        $maxSize   = 120 * 1024; // 120 KB

        $existing = listIndustryGalleryPhotos($industryId);
        if (count($existing) >= $maxPhotos) {
            echo json_encode([
                'success' => false,
                'message' => 'Ya alcanzaste el máximo de ' . $maxPhotos . ' fotos. Eliminá alguna para agregar una nueva.',
            ]);
            exit;
        }

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['photo']['error'] ?? -1;
            $errMsg  = match($errCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido.',
                UPLOAD_ERR_NO_FILE  => 'No se seleccionó ningún archivo.',
                default             => 'Error al subir el archivo (código ' . $errCode . ').',
            };
            echo json_encode(['success' => false, 'message' => $errMsg]);
            exit;
        }

        $file = $_FILES['photo'];

        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'La imagen no puede superar 120 KB. Comprimila en squoosh.app o tinypng.com antes de subir.']);
            exit;
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!array_key_exists($mimeType, $allowedMime)) {
            echo json_encode(['success' => false, 'message' => 'Formato no permitido. Usá JPG, PNG o WebP.']);
            exit;
        }

        $ext      = $allowedMime[$mimeType];
        $filename = 'gallery_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar la imagen en el servidor.']);
            exit;
        }

        $publicUrl = '/uploads/industries/' . $industryId . '/' . $filename . '?t=' . time();
        echo json_encode([
            'success'   => true,
            'message'   => 'Foto subida correctamente.',
            'filename'  => $filename,
            'url'       => $publicUrl,
            'remaining' => ($maxPhotos - count($existing) - 1),
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
