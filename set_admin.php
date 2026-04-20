<?php
/**
 * Script de uso único: activa is_admin para el usuario logueado.
 * ELIMINÁ este archivo del servidor después de usarlo.
 */
session_start();
require_once __DIR__ . '/includes/db_helper.php';

if (!isset($_SESSION['user_id'])) {
    die('❌ Debés estar logueado primero. <a href="/login">Ir al login</a>');
}

$token = $_GET['token'] ?? '';
if ($token !== 'mapita_admin_setup_2026') {
    die('❌ Token inválido.');
}

$db   = getDbConnection();
$stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);

$_SESSION['is_admin'] = 1;

echo '✅ Listo. Tu usuario ahora es administrador.<br>';
echo '<strong>⚠️ Eliminá el archivo set_admin.php del servidor ahora.</strong><br>';
echo '<a href="/mis-negocios">→ Ir a Mis Negocios</a>';
