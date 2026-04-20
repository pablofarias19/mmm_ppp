<?php
/**
 * Mapita - Migration Runner (Un-Click)
 * Acceso protegido por token. Ejecutar UNA VEZ en producción.
 *
 * URL: /run_migration.php?token=TU_TOKEN_SECRETO
 *
 * IMPORTANTE: Eliminar este archivo después de ejecutar la migración.
 */

// ── Configuración de seguridad ─────────────────────────────────────────────
define('MIGRATION_TOKEN', 'mapita_migrate_2026');   // Cambiar antes de subir
define('MIGRATION_FILE',  __DIR__ . '/config/migration.sql');

// ── Verificación de token ──────────────────────────────────────────────────
$token = $_GET['token'] ?? '';
if ($token !== MIGRATION_TOKEN) {
    http_response_code(403);
    die('403 Forbidden — Token inválido. Usar: ?token=TU_TOKEN_SECRETO');
}

// ── Carga de conexión ──────────────────────────────────────────────────────
require_once __DIR__ . '/core/Database.php';

// ── Leer SQL ───────────────────────────────────────────────────────────────
if (!file_exists(MIGRATION_FILE)) {
    die('ERROR: No se encuentra el archivo de migración: ' . MIGRATION_FILE);
}

$sql = file_get_contents(MIGRATION_FILE);
if (!$sql) {
    die('ERROR: El archivo de migración está vacío.');
}

// ── Ejecutar ───────────────────────────────────────────────────────────────
$db = \Core\Database::getInstance()->getConnection();

// Dividir en statements individuales
// Primero eliminar líneas de comentario (-- ...) de cada bloque
$rawParts = explode(';', $sql);
$statements = [];
foreach ($rawParts as $part) {
    // Quitar líneas que son solo comentarios
    $lines = explode("\n", $part);
    $lines = array_filter($lines, fn($l) => !preg_match('/^\s*--/', $l));
    $clean = trim(implode("\n", $lines));
    if (!empty($clean)) {
        $statements[] = $clean;
    }
}

$results  = [];
$errors   = [];
$executed = 0;

foreach ($statements as $stmt) {
    if (empty(trim($stmt))) continue;
    try {
        $db->exec($stmt);
        $executed++;
        // Capturar primera línea como descripción
        $firstLine = strtok($stmt, "\n");
        $results[] = ['sql' => htmlspecialchars(substr($firstLine, 0, 100)), 'ok' => true];
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Ignorar errores "ya existe" en MySQL (idempotente)
        if (str_contains($msg, 'Duplicate column') || str_contains($msg, 'already exists')) {
            $firstLine = strtok($stmt, "\n");
            $results[] = ['sql' => htmlspecialchars(substr($firstLine, 0, 100)), 'ok' => true, 'note' => 'ya existía (OK)'];
        } else {
            $firstLine = strtok($stmt, "\n");
            $results[] = ['sql' => htmlspecialchars(substr($firstLine, 0, 100)), 'ok' => false, 'error' => htmlspecialchars($msg)];
            $errors[]  = $msg;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración - Mapita</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; background: #f5f5f5; }
        h1   { color: #333; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .ok   { color: #27ae60; }
        .err  { color: #e74c3c; }
        .note { color: #f39c12; font-size: 12px; }
        .row  { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .row:last-child { border-bottom: none; }
        .badge { padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .badge-ok  { background: #d4edda; color: #155724; }
        .badge-err { background: #f8d7da; color: #721c24; }
        .badge-note { background: #fff3cd; color: #856404; }
        .summary { font-size: 18px; font-weight: 700; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 12px; margin-top: 20px; font-size: 13px; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🛠️ Mapita — Migración de Base de Datos</h1>

    <div class="card">
        <div class="summary <?php echo empty($errors) ? 'ok' : 'err'; ?>">
            <?php if (empty($errors)): ?>
                ✅ Migración completada — <?php echo $executed; ?> statement(s) ejecutados
            <?php else: ?>
                ⚠️ Completado con <?php echo count($errors); ?> error(es) — <?php echo $executed; ?> ejecutados
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Resultados</h3>
        <?php foreach ($results as $r): ?>
            <div class="row">
                <code><?php echo $r['sql']; ?></code>
                <?php if (!$r['ok']): ?>
                    <div>
                        <span class="badge badge-err">ERROR</span>
                        <div class="err" style="font-size:11px;margin-top:4px;"><?php echo $r['error']; ?></div>
                    </div>
                <?php elseif (!empty($r['note'])): ?>
                    <span class="badge badge-note">SKIP</span>
                <?php else: ?>
                    <span class="badge badge-ok">OK</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="warning">
        ⚠️ <strong>Importante:</strong> Elimina este archivo del servidor después de ejecutar la migración.
        <br>Ruta: <code>run_migration.php</code>
    </div>
</body>
</html>
