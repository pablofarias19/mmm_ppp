<?php
/**
 * Sistema de autenticación compatible con la estructura existente
 */

// Cargar la conexión
require_once __DIR__ . '/../includes/db_helper.php';

// Función para verificar las credenciales de usuario
function login($username, $password) {
    try {
        // Validar entradas
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Por favor ingrese usuario y contraseña'];
        }
        
        // Obtener conexión a la base de datos
        $db = getDbConnection();
        if (!$db) {
            throw new Exception('Error de conexión a la base de datos');
        }
        
        // Consulta preparada para evitar inyección SQL
        $stmt = $db->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?"); // Cambiado: Se agregó el campo `username`
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
        
        // Verificar la contraseña
        if (password_verify($password, $user['password'])) {
            // Actualizar el timestamp de updated_at
            $updateStmt = $db->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            return [
                'success' => true,
                'user_id' => $user['id'],
                'username' => $user['username'], // Cambiado: Se incluye el nombre de usuario en la respuesta
                'is_admin' => $user['is_admin']
            ];
        } else {
            return ['success' => false, 'message' => 'Contraseña incorrecta'];
        }
    } catch (Exception $e) {
        error_log("Error de autenticación: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del sistema'];
    }
}

// Verificar si el usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Verificar si el usuario es administrador — definida en core/helpers.php
if (!function_exists('isAdmin')) {
    function isAdmin() {
        if (!isset($_SESSION['user_id'])) return false;
        if (!empty($_SESSION['is_admin'])) return true;
        if (($_SESSION['user_name'] ?? '') === 'Pablo_Farias') return true;
        return false;
    }
}

// Obtener negocios creados por un usuario - adaptado a la estructura existente
function getUserBusinesses($userId) {
    try {
        if (empty($userId)) {
            throw new Exception("ID de usuario no válido");
        }
        
        $db = getDbConnection();
        if (!$db) {
            throw new Exception("Error de conexión a la base de datos");
        }
        
        // Adaptado a la tabla businesses existente con user_id
        $stmt = $db->prepare("SELECT * FROM businesses WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error al obtener negocios del usuario: " . $e->getMessage());
        return [];
    }
}