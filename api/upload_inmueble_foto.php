<?php
/**
 * Upload/delete de foto de portada para el popup de un inmueble
 *
 * POST /api/upload_inmueble_foto.php  (multipart)
 *   inmueble_id  (int)
 *   action       upload|delete
 *   file         el archivo (solo para action=upload)
 *
 * Límites: máx 120 KB · JPG / PNG / WebP
 * Guarda en: uploads/inmuebles/{inmueble_id}/cover.{ext}
 * Actualiza: inmuebles.foto_url
 *
 * Responde JSON: { success, message, url }
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../core/helpers.php';

function foto_ok($url = null, $msg = 'OK') {
    echo json_encode(['success' => true, 'message' => $msg, 'url' => $url]);
    exit;
}
function foto_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg, 'url' => null]);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) foto_err('Sesión requerida', 401);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') foto_err('Método no permitido', 405);

$db = getDbConnection();
if (!$db) foto_err('Sin conexión a la base de datos', 500);

if (!mapitaTableExists($db, 'inmuebles')) {
    foto_err('Tabla inmuebles no disponible', 503);
}

$inmId  = (int)($_POST['inmueble_id'] ?? 0);
$action = trim($_POST['action'] ?? 'upload');

if ($inmId <= 0) foto_err('inmueble_id requerido');
if (!in_array($action, ['upload', 'delete'], true)) foto_err('action inválida (upload|delete)');

// Verificar propiedad del inmueble
$st = $db->prepare("SELECT i.id, i.foto_url, b.user_id
                     FROM inmuebles i
                     JOIN businesses b ON b.id = i.business_id
                     WHERE i.id = ? LIMIT 1");
$st->execute([$inmId]);
$row = $st->fetch(\PDO::FETCH_ASSOC);
if (!$row) foto_err('Inmueble no encontrado', 404);
if ((int)$row['user_id'] !== $userId && !isAdmin()) foto_err('Sin permisos', 403);

$uploadDir = __DIR__ . '/../uploads/inmuebles/' . $inmId . '/';

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    if ($row['foto_url']) {
        $path = __DIR__ . '/..' . preg_replace('/\?.*$/', '', $row['foto_url']);
        if (is_file($path)) @unlink($path);
    }
    $db->prepare("UPDATE inmuebles SET foto_url = NULL WHERE id = ?")->execute([$inmId]);
    foto_ok(null, 'Foto eliminada');
}

// ── UPLOAD ────────────────────────────────────────────────────────────────────
const FOTO_MAX_BYTES = 120 * 1024; // 120 KB

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errMap = [1 => 'Archivo muy grande', 2 => 'Archivo muy grande', 3 => 'Subida parcial',
               4 => 'No se subió archivo', 6 => 'Sin carpeta temporal',
               7 => 'Error al escribir', 8 => 'Extensión PHP bloqueada'];
    $errCode = (int)($_FILES['file']['error'] ?? 4);
    foto_err($errMap[$errCode] ?? 'Error en la subida');
}

if ($_FILES['file']['size'] > FOTO_MAX_BYTES) {
    foto_err('La foto supera el máximo permitido de 120 KB. Por favor reducí el tamaño de la imagen.');
}

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
$finfo    = new \finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($_FILES['file']['tmp_name']);
if (!isset($allowedMimes[$mimeType])) {
    foto_err('Formato no permitido. Usá JPG, PNG o WebP.');
}
$ext = $allowedMimes[$mimeType];

// Crear directorio si no existe
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        foto_err('Error al crear directorio de almacenamiento', 500);
    }
}

// Borrar foto anterior si existe
if ($row['foto_url']) {
    $oldPath = __DIR__ . '/..' . preg_replace('/\?.*$/', '', $row['foto_url']);
    if (is_file($oldPath)) @unlink($oldPath);
}

$filename = 'cover.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
    foto_err('Error al guardar el archivo', 500);
}

$urlPublica = '/uploads/inmuebles/' . $inmId . '/' . $filename . '?t=' . time();
$db->prepare("UPDATE inmuebles SET foto_url = ? WHERE id = ?")->execute([$urlPublica, $inmId]);

foto_ok($urlPublica, 'Foto subida correctamente');
