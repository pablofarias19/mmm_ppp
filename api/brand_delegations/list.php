<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../delegation_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond_error('Método HTTP no soportado.', 405);
}

$currentUserId = delegationRequireAuthUserId();
$brandId       = (int)($_GET['brand_id'] ?? 0);

if ($brandId <= 0) {
    respond_error('brand_id es obligatorio.');
}

$db = getDbConnection();
if (!$db || !mapitaTableExists($db, 'brand_delegations')) {
    respond_error('Falta migración brand_delegations.', 500);
}

$ownerId = delegationGetBrandOwnerId($db, $brandId);
if ($ownerId === null) {
    respond_error('Marca no encontrada.', 404);
}
if (!isAdmin() && !canManageBrand($currentUserId, $brandId)) {
    respond_error('Sin permisos para listar delegaciones.', 403);
}

$stmt = $db->prepare("
    SELECT d.brand_id, d.user_id, d.role, d.created_at, d.created_by,
           u.username, u.email
    FROM brand_delegations d
    INNER JOIN users u ON u.id = d.user_id
    WHERE d.brand_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$brandId]);

respond_success([
    'brand_id' => $brandId,
    'owner_user_id' => $ownerId,
    'delegations' => $stmt->fetchAll(PDO::FETCH_ASSOC),
], 'Delegaciones obtenidas.');
