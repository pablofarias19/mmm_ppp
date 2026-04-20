<?php
/**
 * API Encuestas v2 - Archivo NUEVO con código corregido
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
session_start();

function respond_success($data, $message = "OK") {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}

function respond_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

require_once __DIR__ . '/../core/Database.php';

$encuestasDefault = [
    [
        'id' => 1,
        'titulo' => '📋 Sistema de Encuestas Activado',
        'descripcion' => 'Accede a /admin/ para crear encuestas.',
        'activa' => 1,
        'fecha_creacion' => date('Y-m-d H:i:s')
    ]
];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Exception $e) {
    respond_success($encuestasDefault, "Encuestas fallback - BD no disponible");
}

if ($method === 'GET') {
    try {
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("SELECT * FROM encuestas WHERE id = ? AND activa = 1");
            $stmt->execute([$id]);
            $encuesta = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($encuesta) {
                respond_success($encuesta, "Encuesta obtenida");
            }
        }

        $stmt = $db->prepare("SELECT * FROM encuestas WHERE activa = 1 ORDER BY id DESC");
        $stmt->execute();
        $encuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($encuestas)) {
            respond_success($encuestasDefault, "Encuestas fallback - tabla vacia");
        }

        respond_success($encuestas, "Encuestas obtenidas");

    } catch (PDOException $e) {
        respond_success($encuestasDefault, "Encuestas fallback - error BD");
    }
}

if ($method === 'POST') {
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        respond_error("Solo admins", 403);
    }

    $input = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }

    try {
        if ($action === 'create') {
            $titulo = $input['titulo'] ?? '';
            if (!$titulo) respond_error("Titulo requerido", 400);

            $stmt = $db->prepare("INSERT INTO encuestas (titulo, descripcion, activa, fecha_creacion) VALUES (?, ?, 1, NOW())");
            $stmt->execute([
                $titulo,
                $input['descripcion'] ?? ''
            ]);
            respond_success(['id' => $db->lastInsertId()], "Encuesta creada");
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) respond_error("ID invalido", 400);
            $stmt = $db->prepare("DELETE FROM encuestas WHERE id = ?");
            $stmt->execute([$id]);
            respond_success([], "Encuesta eliminada");
        }
    } catch (PDOException $e) {
        respond_error("Error: " . $e->getMessage(), 500);
    }
}

respond_error("Accion no valida", 405);
