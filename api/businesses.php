<?php
/**
 * API REST de negocios
 *
 * GET    /api/businesses.php              → lista negocios (pública para visibles)
 * GET    /api/businesses.php?id=N         → detalle de un negocio
 * GET    /api/businesses.php?type=X       → filtrar por tipo
 * GET    /api/businesses.php?q=texto      → buscar por nombre o dirección
 * POST   /api/businesses.php              → crear negocio (requiere sesión)
 * PUT    /api/businesses.php?id=N         → actualizar negocio (requiere sesión + propietario)
 * DELETE /api/businesses.php?id=N         → eliminar negocio (requiere sesión + propietario)
 */

session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Business.php';
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../business/process_business.php';

use App\Models\Business;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ─── Helper: require authenticated session ────────────────────────────────
function requireAuth() {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Se requiere autenticación.']);
        exit;
    }
}

// ─── GET ──────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        if ($id) {
            // Detalle de un negocio específico
            $db   = getDbConnection();
            $stmt = $db->prepare("
                SELECT b.*, c.tipo_comercio, c.horario_apertura, c.horario_cierre,
                       c.dias_cierre, c.categorias_productos
                FROM businesses b
                LEFT JOIN comercios c ON b.id = c.business_id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $negocio = $stmt->fetch();

            if (!$negocio) {
                respond_error('Negocio no encontrado.', 404);
            }

            // Ocultos solo visibles para dueño o admin
            if (!$negocio['visible']) {
                if (empty($_SESSION['user_id'])
                        || !canManageBusiness((int)$_SESSION['user_id'], (int)$id)) {
                    respond_error('Negocio no encontrado.', 404);
                }
            }

            respond_success($negocio, 'Negocio obtenido.');
        }

        // Lista con filtros opcionales
        $db       = getDbConnection();
        $adminReq = isAdmin();
        if ($adminReq) {
            $sql = "SELECT b.*, u.username AS owner_name,
                           c.tipo_comercio, c.horario_apertura, c.horario_cierre,
                           c.dias_cierre, c.categorias_productos
                    FROM businesses b
                    LEFT JOIN users u ON b.user_id = u.id
                    LEFT JOIN comercios c ON b.id = c.business_id
                    WHERE 1=1";
        } else {
            $sql = "SELECT b.*, c.tipo_comercio, c.horario_apertura, c.horario_cierre,
                           c.dias_cierre, c.categorias_productos
                    FROM businesses b
                    LEFT JOIN comercios c ON b.id = c.business_id
                    WHERE b.visible = 1";
        }
        $params = [];

        $type = trim($_GET['type'] ?? '');
        if ($type !== '') {
            $allowed = function_exists('mapitaAllowedBusinessTypes') ? mapitaAllowedBusinessTypes() : [];
            if (in_array($type, $allowed, true)) {
                $sql    .= " AND b.business_type = ?";
                $params[] = $type;
            }
        }

        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $sql    .= " AND (b.name LIKE ? OR b.address LIKE ?)";
            $like    = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY b.created_at DESC LIMIT 500";

        $stmt     = $db->prepare($sql);
        $stmt->execute($params);
        $negocios = $stmt->fetchAll();

        respond_success($negocios, 'Negocios obtenidos correctamente.');

    } catch (Exception $e) {
        respond_error('Error al obtener negocios: ' . $e->getMessage());
    }
}

// ─── POST (crear) ─────────────────────────────────────────────────────────
if ($method === 'POST') {
    requireAuth();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Verificar CSRF si se envía por formulario
    if (!empty($_POST)) {
        verifyCsrfToken($_POST['csrf_token'] ?? '');
    }

    $result = addBusiness($input, $_SESSION['user_id']);
    if ($result['success']) {
        http_response_code(201);
        respond_success(['business_id' => $result['business_id']], $result['message']);
    } else {
        respond_error($result['message']);
    }
}

// ─── PUT (actualizar) ─────────────────────────────────────────────────────
if ($method === 'PUT') {
    requireAuth();

    if (!$id) {
        respond_error('Se requiere el parámetro id.');
    }

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = updateBusiness($id, $input, $_SESSION['user_id']);

    if ($result['success']) {
        respond_success(null, $result['message']);
    } else {
        respond_error($result['message']);
    }
}

// ─── DELETE ───────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    requireAuth();

    if (!$id) {
        respond_error('Se requiere el parámetro id.');
    }

    $result = deleteBusiness($id, $_SESSION['user_id']);

    if ($result['success']) {
        respond_success(null, $result['message']);
    } else {
        respond_error($result['message']);
    }
}

respond_error('Método HTTP no soportado.', 405);
