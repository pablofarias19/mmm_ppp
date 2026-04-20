<?php
/**
 * Archivo auxiliar de base de datos
 * Proporciona funciones simplificadas para acceder a la base de datos
 */

// Incluir correctamente los archivos necesarios con la estructura existente
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

/**
 * Obtiene una conexión a la base de datos utilizando la clase Database existente
 * @return PDO La conexión PDO a la base de datos
 */
if (!function_exists('getDbConnection')) {
    function getDbConnection() {
        try {
            return \Core\Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Obtiene la fecha y hora actual en formato UTC
 * @return string Fecha y hora actual formateada
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Obtiene el nombre de usuario actual de la sesión activa.
 * @return string Nombre de usuario actual
 */
function getCurrentUsername() {
    return $_SESSION['user_name'] ?? '';
}

/**
 * Función para responder con éxito (usando la función existente si está disponible)
 */
function respondSuccess($data, $message = "Operación exitosa") {
    if (function_exists('respond_success')) {
        return respond_success($data, $message);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

/**
 * Función para responder con error (usando la función existente si está disponible)
 */
function respondError($message = "Ha ocurrido un error") {
    if (function_exists('respond_error')) {
        return respond_error($message);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}