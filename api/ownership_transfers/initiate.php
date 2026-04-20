<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../delegation_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Método HTTP no soportado.', 405);
}

$currentUserId = delegationRequireAuthUserId();
$input         = delegationReadInput();
$entityType    = strtolower(trim((string)($input['entity_type'] ?? '')));
$entityId      = (int)($input['entity_id'] ?? 0);
$toUserId      = (int)($input['to_user_id'] ?? 0);

if (!in_array($entityType, ['business', 'brand'], true) || $entityId <= 0 || $toUserId <= 0) {
    respond_error('entity_type, entity_id y to_user_id son obligatorios.');
}

$db = getDbConnection();
if (!$db || !mapitaTableExists($db, 'ownership_transfers')) {
    respond_error('Falta migración ownership_transfers.', 500);
}

$targetUser = delegationUserInfo($db, $toUserId);
if (!$targetUser) {
    respond_error('to_user_id no existe.');
}
if (delegationIsSuperadminUser($targetUser)) {
    respond_error('El superadmin no se delega por entidad.');
}

$ownerId = $entityType === 'business'
    ? delegationGetBusinessOwnerId($db, $entityId)
    : delegationGetBrandOwnerId($db, $entityId);

if ($ownerId === null) {
    respond_error('Entidad no encontrada.', 404);
}
if (!isAdmin() && $ownerId !== $currentUserId) {
    respond_error('Solo el titular puede iniciar la transferencia.', 403);
}
if ($toUserId === $ownerId) {
    respond_error('El usuario destino ya es titular.');
}

$stmt = $db->prepare("
    SELECT id
    FROM ownership_transfers
    WHERE entity_type = ? AND entity_id = ? AND status = 'pending'
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$entityType, $entityId]);
if ($stmt->fetchColumn()) {
    respond_error('Ya existe una transferencia pendiente para esta entidad.');
}

$stmt = $db->prepare("
    INSERT INTO ownership_transfers (entity_type, entity_id, from_user_id, to_user_id, status, created_at)
    VALUES (?, ?, ?, ?, 'pending', NOW())
");
$stmt->execute([$entityType, $entityId, $ownerId, $toUserId]);

respond_success([
    'transfer_id' => (int)$db->lastInsertId(),
    'entity_type' => $entityType,
    'entity_id' => $entityId,
    'from_user_id' => $ownerId,
    'to_user_id' => $toUserId,
    'status' => 'pending',
], 'Transferencia iniciada. Falta aceptación del usuario destino.');
