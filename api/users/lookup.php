<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../delegation_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond_error('Método HTTP no soportado.', 405);
}

delegationRequireAuthUserId();
$query = trim((string)($_GET['query'] ?? ''));
if ($query === '') {
    respond_error('query es obligatorio.');
}

$db = getDbConnection();
if (!$db || !mapitaTableExists($db, 'users')) {
    respond_error('Tabla users no disponible.', 500);
}

$user = delegationLookupUserByQuery($db, $query);
if (!$user) {
    respond_error('No se encontró usuario con ese username o email.', 404);
}

respond_success([
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
    ],
], 'Usuario encontrado.');
