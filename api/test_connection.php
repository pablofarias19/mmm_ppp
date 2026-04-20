<?php
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Database.php';

use Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) as total FROM businesses");
    $row = $stmt->fetch();

    respond_success($row, "Conexión exitosa. Hay {$row['total']} negocios en la base.");
} catch (Exception $e) {
    respond_error("Error al conectar con la base: " . $e->getMessage());
}
