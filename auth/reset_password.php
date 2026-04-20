<?php
/**
 * Flujo de restablecimiento de contraseña.
 *
 * Pasos:
 *  1. El usuario solicita el restablecimiento ingresando su nombre de usuario.
 *  2. Se genera un token de un solo uso con expiración de 1 hora.
 *  3. El usuario recibe un enlace (mostrado en pantalla ya que el sistema no tiene correo configurado).
 *  4. Con el enlace, el usuario ingresa una nueva contraseña.
 *
 * Nota: Para habilitar el envío por correo real, añadir la llamada a mail()
 *       una vez que el servidor esté configurado con SMTP.
 */

session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';

setSecurityHeaders();

$step    = isset($_GET['token']) ? 'new_password' : 'request';
$message = '';
$messageType = '';
$token   = trim($_GET['token'] ?? '');

// ─── Paso 1: Solicitar restablecimiento ────────────────────────────────────
if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $username = trim($_POST['username'] ?? '');
    if ($username === '') {
        $message     = 'Por favor ingresa tu nombre de usuario.';
        $messageType = 'error';
    } else {
        $db   = getDbConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // Generar token seguro
            $resetToken   = bin2hex(random_bytes(32));
            $expiry       = date('Y-m-d H:i:s', time() + 3600);

            try {
                $db->prepare(
                    "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?"
                )->execute([$resetToken, $expiry, $user['id']]);

                $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                           . '://' . $_SERVER['HTTP_HOST']
                           . dirname($_SERVER['REQUEST_URI'])
                           . '/reset_password.php?token=' . urlencode($resetToken);

                $message     = 'Se generó el enlace de restablecimiento. Compártelo con el usuario o usa el enlace de abajo.';
                $messageType = 'success';
                // En un entorno de producción con SMTP configurado, usar mail() aquí.
                // mail($userEmail, 'Restablecer contraseña', $resetLink);
            } catch (Exception $e) {
                error_log("Error al generar token de restablecimiento: " . $e->getMessage());
                $message     = 'No se pudo generar el enlace. Asegúrate de haber ejecutado la migración SQL (config/migration.sql).';
                $messageType = 'error';
            }
        } else {
            // Mensaje genérico para no revelar si el usuario existe
            $message     = 'Si el usuario existe, se generará un enlace de restablecimiento.';
            $messageType = 'success';
        }
    }
}

// ─── Paso 2: Establecer nueva contraseña ──────────────────────────────────
if ($step === 'new_password') {
    $db      = getDbConnection();
    $userId  = null;
    $tokenOk = false;

    // Verificar token en la base de datos
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if ($user) {
            $userId  = $user['id'];
            $tokenOk = true;
        }
    } catch (Exception $e) {
        error_log("Error al verificar token de restablecimiento: " . $e->getMessage());
    }

    if (!$tokenOk) {
        $message     = 'El enlace de restablecimiento no es válido o ha expirado.';
        $messageType = 'error';
        $step        = 'expired';
    }

    if ($tokenOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();

        $newPassword     = $_POST['new_password']     ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 8) {
            $message     = 'La contraseña debe tener al menos 8 caracteres.';
            $messageType = 'error';
        } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $message     = 'La contraseña debe contener al menos una mayúscula y un número.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message     = 'Las contraseñas no coinciden.';
            $messageType = 'error';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            try {
                $db->prepare(
                    "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL, updated_at = NOW() WHERE id = ?"
                )->execute([$hash, $userId]);
            } catch (Exception $e) {
                $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?")
                   ->execute([$hash, $userId]);
            }

            $message     = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
            $messageType = 'success';
            $step        = 'done';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña - Mapita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 100%; max-width: 420px; }
        h1 { text-align: center; color: #333; margin-bottom: 25px; font-size: 1.5em; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 15px; box-sizing: border-box; }
        button { background: #007bff; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 15px; font-weight: bold; }
        button:hover { background: #0056b3; }
        .message { padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .links   { text-align: center; margin-top: 18px; }
        .links a { color: #007bff; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        .reset-link { word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 0.85em; margin-top: 10px; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔑 Restablecer Contraseña</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($step === 'request'): ?>
        <!-- Formulario de solicitud -->
        <form method="post" action="">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="username">Nombre de usuario</label>
                <input type="text" id="username" name="username" required>
            </div>
            <button type="submit">Solicitar Restablecimiento</button>
        </form>
        <?php if (!empty($resetLink)): ?>
            <div class="reset-link">
                <strong>Enlace de restablecimiento:</strong><br>
                <a href="<?php echo htmlspecialchars($resetLink); ?>"><?php echo htmlspecialchars($resetLink); ?></a>
            </div>
        <?php endif; ?>

    <?php elseif ($step === 'new_password'): ?>
        <!-- Formulario de nueva contraseña -->
        <form method="post" action="">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="new_password">Nueva Contraseña</label>
                <input type="password" id="new_password" name="new_password" required>
                <small style="color:#666;display:block;margin-top:4px;">Mínimo 8 caracteres, al menos una mayúscula y un número.</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Guardar Nueva Contraseña</button>
        </form>

    <?php elseif ($step === 'done'): ?>
        <p style="text-align:center;color:#155724;">✅ Contraseña actualizada.</p>

    <?php elseif ($step === 'expired'): ?>
        <p style="text-align:center;color:#721c24;">❌ El enlace no es válido o ha expirado.</p>
    <?php endif; ?>

    <div class="links">
        <a href="login.php">Iniciar Sesión</a>
        &nbsp;|&nbsp;
        <a href="../index.php">Volver al mapa</a>
    </div>
</div>
</body>
</html>
