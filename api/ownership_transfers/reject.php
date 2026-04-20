<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../delegation_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Método HTTP no soportado.', 405);
}

$currentUserId = delegationRequireAuthUserId();
$input         = delegationReadInput();
$transferId    = (int)($input['transfer_id'] ?? 0);

if ($transferId <= 0) {
    respond_error('transfer_id es obligatorio.');
}

$db = getDbConnection();
if (!$db || !mapitaTableExists($db, 'ownership_transfers')) {
    respond_error('Falta migración ownership_transfers.', 500);
}

$stmt = $db->prepare("
    UPDATE ownership_transfers
    SET status = 'rejected', rejected_at = NOW()
    WHERE id = ? AND status = 'pending' AND to_user_id = ?
");
$stmt->execute([$transferId, $currentUserId]);

if ($stmt->rowCount() <= 0) {
    respond_error('No existe transferencia pendiente para este usuario.', 404);
}

respond_success([
    'transfer_id' => $transferId,
    'status' => 'rejected',
], 'Transferencia rechazada.');
