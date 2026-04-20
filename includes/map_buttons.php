<?php
// Asegurarse de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Datos actuales del sistema
$currentUser = 'pablofarias19';
$currentDateTime = '2025-03-28 18:19:12';
?>

<!-- Botones de autenticación y administración -->
<div class="admin-buttons">
    <?php if(!isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='/login'" class="auth-btn login-btn">👤 Iniciar Sesión</button>
    <?php else: ?>
        <div class="user-info">
            <p>Usuario: <?php echo htmlspecialchars($currentUser); ?></p>
            <p>Fecha: <?php echo htmlspecialchars($currentDateTime); ?></p>
        </div>
        <button onclick="window.location.href='/mis-negocios'" class="auth-btn">🏢 Mis Negocios</button>
        <button onclick="window.location.href='/add'" class="auth-btn">➕ Agregar Negocio</button>
        <button onclick="window.location.href='/logout'" class="auth-btn logout-btn">🚪 Cerrar Sesión</button>
    <?php endif; ?>
</div>

<style>
.admin-buttons {
    margin-top: 20px;
    border-top: 1px solid #ddd;
    padding-top: 15px;
}

.user-info {
    font-size: 0.8em;
    color: #666;
    margin-bottom: 10px;
}

.auth-btn {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

.logout-btn {
    background-color: #dc3545;
}

.login-btn {
    background-color: #28a745;
}

.auth-btn:hover {
    opacity: 0.9;
}
</style>