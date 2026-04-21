<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../delegation_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Método HTTP no soportado.', 405);
}

$currentUserId   = delegationRequireAuthUserId();
$input           = delegationReadInput();
$brandId         = (int)($input['brand_id'] ?? 0);
$delegateUserId  = (int)($input['user_id'] ?? $input['delegate_user_id'] ?? 0);
$password        = $input['password'] ?? '';

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
    respond_error('Solo el titular puede delegar esta marca.', 403);
}
if ($delegateUserId === $ownerId) {
    respond_error('El titular ya tiene control de la marca.');
}

$targetUser = delegationUserInfo($db, $delegateUserId);
if (!$targetUser) {
    respond_error('El usuario delegado no existe.');
}
if (delegationIsSuperadminUser($targetUser)) {
    respond_error('El superadmin no se delega por entidad.');
}

$stmt = $db->prepare('SELECT 1 FROM brand_delegations WHERE brand_id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$brandId, $delegateUserId]);
$alreadyDelegated = (bool)$stmt->fetchColumn();
$delegatedAdmins  = delegationCountBrandAdmins($db, $brandId);
if ($delegatedAdmins >= MAX_DELEGATED_ADMINS_PER_ENTITY && !$alreadyDelegated) {
    respond_error('Solo se permiten hasta ' . MAX_DELEGATED_ADMINS_PER_ENTITY . ' admins delegados por marca.');
}

try {
    $stmt = $db->prepare("
        INSERT INTO brand_delegations (brand_id, user_id, role, created_by)
        VALUES (?, ?, 'admin', ?)
    ");
    $stmt->execute([$brandId, $delegateUserId, $currentUserId]);
} catch (PDOException $e) {
    // SQLSTATE 23000 cubre constraint violations y 1062 identifica duplicado en MySQL/MariaDB.
    $isDuplicate = $e->getCode() === '23000' || ((int)($e->errorInfo[1] ?? 0) === 1062);
    if (!$isDuplicate) {
        throw $e;
    }
    $stmt = $db->prepare("
        UPDATE brand_delegations
        SET role = 'admin', created_by = ?
        WHERE brand_id = ? AND user_id = ?
    ");
    $stmt->execute([$currentUserId, $brandId, $delegateUserId]);
}

respond_success([
    'brand_id' => $brandId,
    'user_id' => $delegateUserId,
    'role' => 'admin',
], 'Delegación administrativa creada.');
