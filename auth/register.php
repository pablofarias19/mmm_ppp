<?php
session_start();

// Redireccionar si ya está autenticado
if (isset($_SESSION['user_id'])) {
    header("Location: ../views/business/map.php");
    exit();
}

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

ini_set('display_errors', 0);
error_reporting(0);

setSecurityHeaders();

$error   = '';
$success = '';
$currentDateTime = getCurrentDateTime();

// Función para registrar un nuevo usuario
function registerUser($username, $email, $phone, $password, $confirmPassword, $isAdmin = 0) {
    try {
        // Validar entradas obligatorias
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
            return ['success' => false, 'message' => 'Por favor complete todos los campos obligatorios'];
        }

        // Validar longitud del usuario
        if (mb_strlen($username) > 50 || !preg_match('/^[\w\.\-]{3,50}$/', $username)) {
            return ['success' => false, 'message' => 'El usuario debe tener entre 3 y 50 caracteres alfanuméricos.'];
        }

        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El correo electrónico no es válido.'];
        }

        // Validar teléfono/WhatsApp (opcional, pero si se ingresa debe tener formato básico)
        if (!empty($phone) && !preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone)) {
            return ['success' => false, 'message' => 'El teléfono/WhatsApp no tiene un formato válido (ej: +5491112345678).'];
        }

        // Verificar que las contraseñas coincidan
        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden'];
        }

        // Validar longitud de la contraseña (mínimo 8 caracteres)
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
        }

        // Validar complejidad de contraseña
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return ['success' => false, 'message' => 'La contraseña debe contener al menos una mayúscula y un número.'];
        }

        // Obtener conexión a la base de datos
        $db = getDbConnection();
        if (!$db) {
            throw new Exception('Error de conexión a la base de datos');
        }

        // Verificar si el usuario ya existe (username o email)
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        $existing = $checkStmt->fetch();
        if ($existing) {
            return ['success' => false, 'message' => 'El nombre de usuario o correo electrónico ya está en uso'];
        }

        // Generar hash de la contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insertar el nuevo usuario con email y teléfono
        $stmt   = $db->prepare("INSERT INTO users (username, password, email, phone, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $result = $stmt->execute([$username, $passwordHash, $email, ($phone ?: null), (int)$isAdmin]);

        if ($result) {
            return ['success' => true, 'message' => 'Usuario registrado correctamente'];
        } else {
            throw new Exception('Error al registrar el usuario');
        }
    } catch (Exception $e) {
        error_log("Error de registro: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del sistema'];
    }
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrfToken();

    $username        = $_POST['username']         ?? '';
    $email           = trim($_POST['email']        ?? '');
    $phone           = trim($_POST['phone']        ?? '');
    $password        = $_POST['password']         ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $result = registerUser($username, $email, $phone, $password, $confirmPassword, 0);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Usuario - Sistema de Negocios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <link rel="stylesheet" href="/css/components-buttons.css">
    <link rel="stylesheet" href="/css/components-cards.css">
    <link rel="stylesheet" href="/css/components-forms.css">
    <style>
        body {
            background-color: var(--bg-tertiary);
            margin: 0;
            padding: var(--space-md);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: var(--font-family-base);
        }
        .register-container {
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: var(--primary);
            margin-bottom: var(--space-xl);
            font-size: var(--font-size-2xl);
        }
        .error {
            background: rgba(230, 57, 70, 0.1);
            color: var(--accent-dark);
            padding: var(--space-md);
            border-radius: var(--border-radius-md);
            margin-bottom: var(--space-lg);
            text-align: center;
            font-size: var(--font-size-sm);
            border: 1px solid var(--accent-light);
        }
        .success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--color-success);
            padding: var(--space-md);
            border-radius: var(--border-radius-md);
            margin-bottom: var(--space-lg);
            text-align: center;
            font-size: var(--font-size-sm);
            border: 1px solid var(--color-success);
        }
        .links {
            text-align: center;
            margin-top: var(--space-lg);
        }
        .links a {
            color: var(--primary);
            text-decoration: none;
            margin: 0 var(--space-sm);
            font-size: var(--font-size-sm);
        }
        .links a:hover {
            text-decoration: underline;
        }
        .system-info {
            text-align: center;
            font-size: var(--font-size-xs);
            color: var(--text-tertiary);
            margin-top: var(--space-xl);
        }
    </style>
</head>
<body>
    <div class="register-container card">
        <h1>Registrar Usuario</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="username">Usuario: <span style="color:var(--accent-dark)">*</span></label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="50" placeholder="Nombre de usuario">
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico: <span style="color:var(--accent-dark)">*</span></label>
                <input type="email" id="email" name="email" required maxlength="255" placeholder="tucorreo@ejemplo.com">
                <small class="form-hint">Se usará para recuperar tu contraseña.</small>
            </div>

            <div class="form-group">
                <label for="phone">Teléfono / WhatsApp: <span style="color:var(--text-tertiary);font-weight:400;">(opcional)</span></label>
                <input type="tel" id="phone" name="phone" maxlength="30" placeholder="+5491112345678">
                <small class="form-hint">Con código de país. Habilita el canal de mensajes WhatsApp.</small>
            </div>

            <div class="form-group">
                <label for="password">Contraseña: <span style="color:var(--accent-dark)">*</span></label>
                <input type="password" id="password" name="password" required placeholder="Contraseña segura">
                <small class="form-hint">Mínimo 8 caracteres, al menos una mayúscula y un número.</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña: <span style="color:var(--accent-dark)">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repetir contraseña">
            </div>

            <button type="submit" class="btn btn-primary btn-full">Registrar Usuario</button>
        </form>
        
        <div class="links">
            <a href="login.php">Iniciar Sesión</a>
            <a href="../index.php">Volver al mapa</a>
        </div>
        
        <div class="system-info">
            <p>Fecha actual: <?php echo htmlspecialchars($currentDateTime); ?></p>
        </div>
    </div>
</body>
</html>