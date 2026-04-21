<?php
session_start();

require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../includes/audit_logger.php';

// Registrar logout antes de destruir la sesión
if (!empty($_SESSION['user_id'])) {
    auditLog('logout', 'user', (int)$_SESSION['user_id']);
}

// Destruir la sesión
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redireccionar a la página principal
header("Location: ../views/business/map.php");
exit();