<?php
/**
 * API Trivias v2 - Archivo NUEVO con código corregido
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

$triviasDefault = [
    [
        'id' => 1,
        'titulo' => '🎯 Sistema de Trivias Activado',
        'descripcion' => 'Accede a /admin/ para crear trivias.',
        'dificultad' => 'medio',
        'tiempo_limite' => 30,
        'activa' => 1
    ]
];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Exception $e) {
    respond_success($triviasDefault, "Trivias fallback - BD no disponible");
}

if ($method === 'GET') {
    try {
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("SELECT * FROM trivias WHERE id = ? AND activa = 1");
            $stmt->execute([$id]);
            $trivia = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($trivia) {
                respond_success($trivia, "Trivia obtenida");
            }
        }

        $stmt = $db->prepare("SELECT * FROM trivias WHERE activa = 1 ORDER BY id DESC");
        $stmt->execute();
        $trivias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($trivias)) {
            respond_success($triviasDefault, "Trivias fallback - tabla vacia");
        }

        respond_success($trivias, "Trivias obtenidas");

    } catch (PDOException $e) {
        respond_success($triviasDefault, "Trivias fallback - error BD");
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

            $stmt = $db->prepare("INSERT INTO trivias (titulo, descripcion, dificultad, tiempo_limite, activa, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
            $stmt->execute([
                $titulo,
                $input['descripcion'] ?? '',
                $input['dificultad'] ?? 'medio',
                (int)($input['tiempo_limite'] ?? 30)
            ]);
            respond_success(['id' => $db->lastInsertId()], "Trivia creada");
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) respond_error("ID invalido", 400);
            $stmt = $db->prepare("DELETE FROM trivias WHERE id = ?");
            $stmt->execute([$id]);
            respond_success([], "Trivia eliminada");
        }
    } catch (PDOException $e) {
        respond_error("Error: " . $e->getMessage(), 500);
    }
}

respond_error("Accion no valida", 405);
