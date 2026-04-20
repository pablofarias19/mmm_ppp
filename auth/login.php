<?php
session_start();

// Redireccionar si ya está autenticado
if (isset($_SESSION['user_id'])) {
   header("Location: /");
    exit();
}

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

// Disable error display in production
ini_set('display_errors', 0);
error_reporting(0);

setSecurityHeaders();

$error = '';
$currentDateTime = getCurrentDateTime();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrfToken();

    // Incluir el archivo de autenticación
    require_once 'auth.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Intentar iniciar sesión
    $result = login($username, $password);
    
    if ($result['success']) {
        // Guardar datos en la sesión
        $_SESSION['user_id']   = $result['user_id'];
        $_SESSION['user_name'] = $result['username'];
        $_SESSION['is_admin']  = $result['is_admin'] ?? 0;
        
        // Regenerar sesión para prevenir session fixation
        session_regenerate_id(true);

        // Redireccionar según rol
        header("Location: /mis-negocios");
        exit();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Sistema de Negocios</title>
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
        .login-container {
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
        .back-link {
            text-align: center;
            margin-top: var(--space-lg);
        }
        .back-link a {
            color: var(--primary);
            text-decoration: none;
            font-size: var(--font-size-sm);
        }
        .back-link a:hover {
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
    <div class="login-container card">
        <h1>Iniciar Sesión</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required placeholder="Tu usuario">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required placeholder="Tu contraseña">
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">Iniciar Sesión</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">Volver al mapa</a> |
            <a href="reset_password.php">¿Olvidaste tu contraseña?</a>
        </div>
        
        <div class="system-info">
            <p>Fecha actual: <?php echo htmlspecialchars($currentDateTime); ?></p>
        </div>
    </div>
</body>
</html>