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
    SELECT id, entity_type, entity_id, from_user_id, to_user_id, status
    FROM ownership_transfers
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$transferId]);
$transfer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transfer) {
    respond_error('Transferencia no encontrada.', 404);
}
if ($transfer['status'] !== 'pending') {
    respond_error('La transferencia ya no está pendiente.');
}
if ((int)$transfer['to_user_id'] !== $currentUserId) {
    respond_error('Solo el usuario destino puede aceptar.', 403);
}

try {
    $db->beginTransaction();

    $entityId   = (int)$transfer['entity_id'];
    $fromUserId = (int)$transfer['from_user_id'];
    $toUserId   = (int)$transfer['to_user_id'];
    $affected = 0;
    if ($transfer['entity_type'] === 'business') {
        $stmt = $db->prepare('UPDATE businesses SET user_id = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
        $stmt->execute([$toUserId, $entityId, $fromUserId]);
        $affected = $stmt->rowCount();
    } else {
        if (mapitaTableExists($db, 'brands')) {
            $stmt = $db->prepare('UPDATE brands SET user_id = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
            $stmt->execute([$toUserId, $entityId, $fromUserId]);
            $affected += $stmt->rowCount();
        }
        if (mapitaTableExists($db, 'marcas')) {
            $sql = mapitaColumnExists($db, 'marcas', 'updated_at')
                ? 'UPDATE marcas SET usuario_id = ?, updated_at = NOW() WHERE id = ? AND usuario_id = ?'
                : 'UPDATE marcas SET usuario_id = ? WHERE id = ? AND usuario_id = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute([$toUserId, $entityId, $fromUserId]);
            $affected += $stmt->rowCount();
        }
    }

    if ($affected <= 0) {
        throw new RuntimeException('No se pudo actualizar la titularidad (owner cambió o entidad inválida).');
    }

    $stmt = $db->prepare("UPDATE ownership_transfers SET status = 'accepted', accepted_at = NOW() WHERE id = ?");
    $stmt->execute([$transferId]);

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    respond_error('No se pudo aceptar la transferencia: ' . $e->getMessage());
}

respond_success([
    'transfer_id' => $transferId,
    'entity_type' => $transfer['entity_type'],
    'entity_id' => (int)$transfer['entity_id'],
    'new_owner_user_id' => (int)$transfer['to_user_id'],
    'status' => 'accepted',
], 'Transferencia aceptada y titularidad actualizada.');
