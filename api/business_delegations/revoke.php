<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../delegation_helpers.php';
require_once __DIR__ . '/../../includes/mapita_notifications.php';

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

$bizStmt = $db->prepare('SELECT name, user_id FROM businesses WHERE id = ? LIMIT 1');
$bizStmt->execute([$businessId]);
$business = $bizStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$owner = mapitaGetUserContactById($db, (int)($business['user_id'] ?? 0));
$delegatedUser = delegationUserInfo($db, $delegateUserId);
mapitaSendUserNotificationEmail(
    $owner['email'] ?? null,
    'MAPITA | Confirmación de operación: revocación de delegación',
    'Revocación de delegación de negocio',
    [
        'Negocio' => (string)($business['name'] ?? ('ID ' . $businessId)),
        'Usuario revocado' => (string)($delegatedUser['username'] ?? ('Usuario #' . $delegateUserId)),
        'ID negocio' => (string)$businessId,
        'Fecha' => date('d/m/Y H:i'),
    ]
);

respond_success([
    'business_id' => $businessId,
    'user_id' => $delegateUserId,
    'revoked' => $stmt->rowCount() > 0,
], 'Delegación revocada.');
