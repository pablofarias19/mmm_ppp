<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../delegation_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Método HTTP no soportado.', 405);
}

$currentUserId  = delegationRequireAuthUserId();
$input          = delegationReadInput();
$brandId        = (int)($input['brand_id'] ?? 0);
$delegateUserId = (int)($input['user_id'] ?? $input['delegate_user_id'] ?? 0);
$password       = $input['password'] ?? '';

if ($brandId <= 0 || $delegateUserId <= 0) {
    respond_error('brand_id y user_id son obligatorios.');
}

$db = getDbConnection();
if (!$db || !mapitaTableExists($db, 'brand_delegations')) {
    respond_error('Falta migración brand_delegations.', 500);
}
delegationRequirePasswordConfirmation($db, $currentUserId, $password);

$ownerId = delegationGetBrandOwnerId($db, $brandId);
if ($ownerId === null) {
    respond_error('Marca no encontrada.', 404);
}
if (!isAdmin() && $ownerId !== $currentUserId) {
    respond_error('Solo el titular puede revocar delegaciones.', 403);
}

$stmt = $db->prepare('DELETE FROM brand_delegations WHERE brand_id = ? AND user_id = ?');
$stmt->execute([$brandId, $delegateUserId]);

respond_success([
    'brand_id' => $brandId,
    'user_id' => $delegateUserId,
    'revoked' => $stmt->rowCount() > 0,
], 'Delegación revocada.');
