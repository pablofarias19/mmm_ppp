<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../delegation_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Método HTTP no soportado.', 405);
}

$currentUserId  = delegationRequireAuthUserId();
$input          = delegationReadInput();
$businessId     = (int)($input['business_id'] ?? 0);
$delegateUserId = (int)($input['user_id'] ?? $input['delegate_user_id'] ?? 0);
$password       = $input['password'] ?? '';

if ($businessId <= 0 || $delegateUserId <= 0) {
    respond_error('business_id y user_id son obligatorios.');
}

$db = getDbConnection();
if (!$db || !mapitaTableExists($db, 'business_delegations')) {
    respond_error('Falta migración business_delegations.', 500);
}
delegationRequirePasswordConfirmation($db, $currentUserId, $password);

$ownerId = delegationGetBusinessOwnerId($db, $businessId);
if ($ownerId === null) {
    respond_error('Negocio no encontrado.', 404);
}
if (!isAdmin() && $ownerId !== $currentUserId) {
    respond_error('Solo el titular puede revocar delegaciones.', 403);
}

$stmt = $db->prepare('DELETE FROM business_delegations WHERE business_id = ? AND user_id = ?');
$stmt->execute([$businessId, $delegateUserId]);

respond_success([
    'business_id' => $businessId,
    'user_id' => $delegateUserId,
    'revoked' => $stmt->rowCount() > 0,
], 'Delegación revocada.');
