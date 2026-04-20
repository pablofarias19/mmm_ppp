<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../delegation_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond_error('Método HTTP no soportado.', 405);
}

$currentUserId = delegationRequireAuthUserId();
$businessId    = (int)($_GET['business_id'] ?? 0);

if ($businessId <= 0) {
    respond_error('business_id es obligatorio.');
}

$db = getDbConnection();
if (!$db || !mapitaTableExists($db, 'business_delegations')) {
    respond_error('Falta migración business_delegations.', 500);
}

$ownerId = delegationGetBusinessOwnerId($db, $businessId);
if ($ownerId === null) {
    respond_error('Negocio no encontrado.', 404);
}
if (!isAdmin() && !canManageBusiness($currentUserId, $businessId)) {
    respond_error('Sin permisos para listar delegaciones.', 403);
}

$stmt = $db->prepare("
    SELECT d.business_id, d.user_id, d.role, d.created_at, d.created_by,
           u.username, u.email
    FROM business_delegations d
    INNER JOIN users u ON u.id = d.user_id
    WHERE d.business_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$businessId]);

respond_success([
    'business_id' => $businessId,
    'owner_user_id' => $ownerId,
    'delegations' => $stmt->fetchAll(PDO::FETCH_ASSOC),
], 'Delegaciones obtenidas.');
