<?php
/**
 * Upload de adjuntos para inmuebles (planos, proyecto de inversión, fotos)
 *
 * POST /api/upload_inmueble_adjunto.php
 *   FormData:
 *     inmueble_id  (int)
 *     tipo_adjunto (plano|proyecto|foto)
 *     nombre       (string, opcional)
 *     file         (el archivo)
 *
 * DELETE /api/upload_inmueble_adjunto.php?id=N
 *   Elimina el adjunto con ese ID
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../core/helpers.php';

function adj_ok($data = [], $msg = 'OK') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}
function adj_err($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'data' => null, 'message' => $msg]);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) adj_err('Sesión requerida', 401);

$db = getDbConnection();
if (!$db) adj_err('Sin conexión', 500);

if (!mapitaTableExists($db, 'inmueble_adjuntos')) {
    adj_err('Módulo de adjuntos no disponible. Ejecutar migración 029.', 503);
}

$method = $_SERVER['REQUEST_METHOD'];

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $adjId = (int)($_GET['id'] ?? 0);
    if ($adjId <= 0) adj_err('id requerido');

    $st = $db->prepare("SELECT ia.id, ia.url, b.user_id
                         FROM inmueble_adjuntos ia
                         JOIN inmuebles i ON i.id = ia.inmueble_id
                         JOIN businesses b ON b.id = i.business_id
                         WHERE ia.id = ? LIMIT 1");
    $st->execute([$adjId]);
    $row = $st->fetch(\PDO::FETCH_ASSOC);
    if (!$row) adj_err('Adjunto no encontrado', 404);
    if ((int)$row['user_id'] !== $userId && !isAdmin()) adj_err('Sin permisos', 403);

    // Borrar archivo físico si existe
    if ($row['url'] && strpos($row['url'], '/uploads/') === 0) {
        $path = __DIR__ . '/..' . $row['url'];
        if (is_file($path)) @unlink($path);
    }
    $db->prepare("DELETE FROM inmueble_adjuntos WHERE id = ?")->execute([$adjId]);
    adj_ok([], 'Adjunto eliminado');
}

// ── POST (upload) ─────────────────────────────────────────────────────────────
if ($method !== 'POST') adj_err('Método no permitido', 405);

$inmId       = (int)($_POST['inmueble_id'] ?? 0);
$tipoAdj     = trim($_POST['tipo_adjunto'] ?? 'foto');
$nombre      = mb_substr(trim($_POST['nombre'] ?? ''), 0, 255) ?: null;

if ($inmId <= 0) adj_err('inmueble_id requerido');
if (!in_array($tipoAdj, ['plano','proyecto','foto'], true)) adj_err('tipo_adjunto inválido');

// Verificar propiedad del inmueble
$st = $db->prepare("SELECT i.id, b.user_id FROM inmuebles i
                     JOIN businesses b ON b.id = i.business_id
                     WHERE i.id = ? LIMIT 1");
$st->execute([$inmId]);
$row = $st->fetch(\PDO::FETCH_ASSOC);
if (!$row) adj_err('Inmueble no encontrado', 404);
if ((int)$row['user_id'] !== $userId && !isAdmin()) adj_err('Sin permisos', 403);

// Verificar archivo
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errMap = [1=>'Archivo muy grande',2=>'Archivo muy grande',3=>'Subida parcial',
               4=>'No se subió archivo',6=>'Sin carpeta temporal',7=>'Error al escribir',8=>'Extensión PHP'];
    $errCode = (int)($_FILES['file']['error'] ?? 4);
    adj_err($errMap[$errCode] ?? 'Error en la subida');
}

$maxBytes = 10 * 1024 * 1024; // 10 MB
if ($_FILES['file']['size'] > $maxBytes) adj_err('El archivo supera 10 MB');

// Validar MIME type
$allowedMimes = [
    'image/jpeg'       => 'jpg',
    'image/png'        => 'png',
    'image/webp'       => 'webp',
    'application/pdf'  => 'pdf',
];

$finfo    = new \finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($_FILES['file']['tmp_name']);
if (!isset($allowedMimes[$mimeType])) {
    adj_err('Tipo de archivo no permitido. Use JPG, PNG, WEBP o PDF.');
}
$ext = $allowedMimes[$mimeType];

// Directorio de destino
$uploadDir = __DIR__ . '/../uploads/inmuebles/' . $inmId . '/adjuntos/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) adj_err('Error al crear directorio de almacenamiento', 500);
}

$safeName  = $tipoAdj . '_' . uniqid() . '.' . $ext;
$destPath  = $uploadDir . $safeName;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
    adj_err('Error al guardar el archivo', 500);
}

$urlRelativa = '/uploads/inmuebles/' . $inmId . '/adjuntos/' . $safeName;
$fileSize    = (int)$_FILES['file']['size'];

$ins = $db->prepare("INSERT INTO inmueble_adjuntos
                      (inmueble_id, tipo_adjunto, url, nombre, mime_type, file_size)
                      VALUES (?, ?, ?, ?, ?, ?)");
$ins->execute([$inmId, $tipoAdj, $urlRelativa, $nombre, $mimeType, $fileSize]);
$newId = (int)$db->lastInsertId();

adj_ok([
    'id'           => $newId,
    'inmueble_id'  => $inmId,
    'tipo_adjunto' => $tipoAdj,
    'url'          => $urlRelativa,
    'nombre'       => $nombre,
    'mime_type'    => $mimeType,
    'file_size'    => $fileSize,
], 'Adjunto subido correctamente');
