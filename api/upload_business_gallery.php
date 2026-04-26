<?php
/**
 * API Galería de Negocios
 *
 * GET  ?business_id=X           → lista fotos de galería del negocio
 * POST action=upload             → sube hasta 1 foto (multipart)
 * POST action=delete             → elimina una foto por nombre
 *
 * Límites: máx 2 fotos de galería · máx 120 KB por foto · JPG / PNG / WebP
 * Las fotos se guardan en uploads/businesses/{id}/gallery_{timestamp}.{ext}
 * La og_cover NO se lista aquí.
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

/** Límite global por defecto cuando no hay override ni tipo configurado */
define('GALLERY_DEFAULT_MAX_PHOTOS', 2);

$userId     = (int)$_SESSION['user_id'];
$method     = $_SERVER['REQUEST_METHOD'];

// ── Helper: listar fotos de galería ──────────────────────────────────────────
function listGalleryPhotos(int $businessId): array {
    $dir    = __DIR__ . '/../uploads/businesses/' . $businessId . '/';
    $photos = [];
    if (!is_dir($dir)) return $photos;
    foreach (glob($dir . 'gallery_*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $file) {
        $fname    = basename($file);
        $photos[] = [
            'filename' => $fname,
            'url'      => '/uploads/businesses/' . $businessId . '/' . $fname . '?t=' . filemtime($file),
            'size'     => filesize($file),
        ];
    }
    // Orden cronológico (más antigua primero)
    usort($photos, fn($a, $b) => strcmp($a['filename'], $b['filename']));
    return $photos;
}

// ── Verificar propiedad (o admin) ────────────────────────────────────────────
function verifyOwnership(int $businessId, int $userId): bool {
    if (!empty($_SESSION['is_admin'])) return true;
    try {
        $db = getDbConnection();
        if (!$db) {
            error_log("upload_business_gallery: DB connection failed for user $userId, business $businessId");
            return false;
        }
        $stmt = $db->prepare("SELECT id, user_id FROM businesses WHERE id = ?");
        $stmt->execute([$businessId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            error_log("upload_business_gallery: business $businessId not found");
            return false;
        }
        if ((int)$row['user_id'] !== $userId) {
            error_log("upload_business_gallery: user $userId is not owner of business $businessId (owner={$row['user_id']})");
            return false;
        }
        return true;
    } catch (Exception $e) {
        error_log("upload_business_gallery verifyOwnership: " . $e->getMessage());
        return false;
    }
}

// ── GET: listar fotos ─────────────────────────────────────────────────────────
if ($method === 'GET') {
    $businessId = (int)($_GET['business_id'] ?? 0);
    if ($businessId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    if (!verifyOwnership($businessId, $userId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permiso.']);
        exit;
    }
    $photos = listGalleryPhotos($businessId);
    echo json_encode(['success' => true, 'photos' => $photos, 'count' => count($photos)]);
    exit;
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $action     = $_POST['action'] ?? 'upload';
    $businessId = (int)($_POST['business_id'] ?? 0);

    if ($businessId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }
    if (!verifyOwnership($businessId, $userId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permiso.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/businesses/' . $businessId . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // ── Eliminar foto ─────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $filename = basename($_POST['filename'] ?? '');
        // Solo permitir eliminar fotos de galería (gallery_*)
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
        // Determinar límite de imágenes: override por negocio > default por tipo > global
        $maxPhotos = GALLERY_DEFAULT_MAX_PHOTOS;
        try {
            $db = getDbConnection();
            $brow = $db->prepare("SELECT images_max, business_type FROM businesses WHERE id = ?");
            $brow->execute([$businessId]);
            $bdata = $brow->fetch(PDO::FETCH_ASSOC);
            if ($bdata) {
                if ($bdata['images_max'] !== null) {
                    $maxPhotos = max(0, (int)$bdata['images_max']);
                } else {
                    // Intentar obtener default del tipo
                    try {
                        $tlrow = $db->prepare("SELECT images_max_default FROM business_type_limits WHERE business_type = ?");
                        $tlrow->execute([$bdata['business_type']]);
                        $tl = $tlrow->fetch(PDO::FETCH_ASSOC);
                        if ($tl && $tl['images_max_default'] !== null) {
                            $maxPhotos = max(0, (int)$tl['images_max_default']);
                        }
                    } catch (Exception $e) {
                        error_log("upload_business_gallery: business_type_limits lookup failed: " . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            error_log("upload_business_gallery: images_max lookup failed for business $businessId: " . $e->getMessage());
        }
        $maxSize   = 120 * 1024; // 120 KB

        // Verificar cuántas fotos ya hay
        $existing = listGalleryPhotos($businessId);
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

        $publicUrl = '/uploads/businesses/' . $businessId . '/' . $filename . '?t=' . time();
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
