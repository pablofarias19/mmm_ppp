<?php
/**
 * API de reseñas
 *
 * GET    /api/reviews.php?business_id=N  → listar reseñas de un negocio
 * POST   /api/reviews.php                → crear/actualizar reseña (requiere sesión)
 * DELETE /api/reviews.php?business_id=N  → eliminar propia reseña (requiere sesión)
 */

session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../models/Review.php';

use App\Models\Review;

header('Content-Type: application/json');

$method     = $_SERVER['REQUEST_METHOD'];
$businessId = isset($_GET['business_id']) ? (int)$_GET['business_id'] : 0;
$reviewId   = isset($_GET['review_id'])   ? (int)$_GET['review_id']   : 0;

function requireAuthReviews() {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Se requiere autenticación.']);
        exit;
    }
}

if ($method === 'GET') {
    if (!$businessId) {
        respond_error('Se requiere el parámetro business_id.');
    }
    $reviews = Review::getByBusiness($businessId);
    $avg     = Review::getAverage($businessId);
    respond_success(['reviews' => $reviews, 'average' => $avg], 'Reseñas obtenidas.');
}

if ($method === 'POST') {
    requireAuthReviews();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = trim($input['action'] ?? '');

    // Edit existing review by ID
    if ($action === 'update' && $reviewId > 0) {
        $rating  = (int)($input['rating']  ?? 0);
        $comment = trim($input['comment']  ?? '');
        if (!$rating) respond_error('Se requiere rating.');
        $result = Review::updateById($reviewId, (int)$_SESSION['user_id'], isAdmin(), $rating, $comment);
        $result['success'] ? respond_success(null, $result['message']) : respond_error($result['message']);
    }

    $bId    = (int)($input['business_id'] ?? 0);
    $rating = (int)($input['rating']      ?? 0);
    $comment = trim($input['comment']     ?? '');

    if (!$bId || !$rating) {
        respond_error('Se requiere business_id y rating.');
    }

    $result = Review::upsert($bId, (int)$_SESSION['user_id'], $rating, $comment);
    if ($result['success']) {
        respond_success(null, $result['message']);
    } else {
        respond_error($result['message']);
    }
}

if ($method === 'DELETE') {
    requireAuthReviews();

    // Admin/owner delete by review ID
    if ($reviewId > 0) {
        $result = Review::deleteById($reviewId, (int)$_SESSION['user_id'], isAdmin());
        $result['success'] ? respond_success(null, $result['message']) : respond_error($result['message'], 403);
    }

    if (!$businessId) {
        respond_error('Se requiere business_id o review_id.');
    }

    $result = Review::delete($businessId, (int)$_SESSION['user_id']);
    if ($result['success']) {
        respond_success(null, $result['message']);
    } else {
        respond_error($result['message']);
    }
}

respond_error('Método HTTP no soportado.', 405);
