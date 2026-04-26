<?php
/**
 * Panel Admin: Límites configurables por negocio y por tipo
 *
 * Permite al administrador:
 *  - Ver/editar limits por negocio (images_max, visibility_min_zoom, is_premium)
 *  - Ver/editar defaults globales por tipo de negocio (business_type_limits)
 */

session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';
require_once __DIR__ . '/../../business/process_business.php';

setSecurityHeaders();

if (!isAdmin()) {
    header('Location: ../../auth/login.php');
    exit;
}

$db      = getDbConnection();
$message = '';
$messageType = '';

// ── Acciones POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';

    // Actualizar límites de un negocio específico
    if ($action === 'update_business_limits') {
        $bid      = (int)($_POST['business_id'] ?? 0);
        $imgMax   = $_POST['images_max'] !== '' ? (int)$_POST['images_max'] : null;
        $minZoom  = $_POST['visibility_min_zoom'] !== '' ? (int)$_POST['visibility_min_zoom'] : null;
        $isPremium = (int)(bool)($_POST['is_premium'] ?? 0);

        if ($bid > 0) {
            // Validate ranges
            if ($imgMax !== null)  $imgMax  = max(0, min(50, $imgMax));
            if ($minZoom !== null) $minZoom = max(1, min(20, $minZoom));

            $db->prepare("
                UPDATE businesses
                   SET images_max = ?,
                       visibility_min_zoom = ?,
                       is_premium = ?,
                       updated_at = NOW()
                 WHERE id = ?
            ")->execute([$imgMax, $minZoom, $isPremium, $bid]);
            $message     = 'Configuración del negocio actualizada.';
            $messageType = 'success';
        }
    }

    // Actualizar / insertar límite por tipo de negocio
    if ($action === 'update_type_limits') {
        $btype   = trim($_POST['business_type'] ?? '');
        $imgMax  = max(0, min(50, (int)($_POST['images_max_default'] ?? 2)));
        $minZoom = max(1, min(20, (int)($_POST['visibility_min_zoom_default'] ?? 12)));

        if ($btype !== '') {
            $db->prepare("
                INSERT INTO business_type_limits
                    (business_type, images_max_default, visibility_min_zoom_default)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    images_max_default = VALUES(images_max_default),
                    visibility_min_zoom_default = VALUES(visibility_min_zoom_default)
            ")->execute([$btype, $imgMax, $minZoom]);
            $message     = 'Límites del tipo "' . htmlspecialchars($btype) . '" actualizados.';
            $messageType = 'success';
        }
    }

    // Aplicar zoom/premium a TODOS los negocios de un tipo
    if ($action === 'apply_type_to_all') {
        $btype   = trim($_POST['business_type'] ?? '');
        $minZoom = max(1, min(20, (int)($_POST['visibility_min_zoom'] ?? 12)));
        $isPremium = (int)(bool)($_POST['is_premium'] ?? 0);

        if ($btype !== '') {
            $db->prepare("
                UPDATE businesses
                   SET visibility_min_zoom = ?,
                       is_premium = ?,
                       updated_at = NOW()
                 WHERE business_type = ?
            ")->execute([$minZoom, $isPremium, $btype]);
            $message     = 'Configuración aplicada a todos los negocios de tipo "' . htmlspecialchars($btype) . '".';
            $messageType = 'success';
        }
    }
}

// ── Datos para la vista ──────────────────────────────────────────────────────
// Tipos de negocio disponibles
$allTypes = mapitaAllowedBusinessTypes();

// Límites existentes por tipo (tabla business_type_limits)
$typeLimits = [];
try {
    $rows = $db->query("SELECT * FROM business_type_limits ORDER BY business_type")->fetchAll();
    foreach ($rows as $row) {
        $typeLimits[$row['business_type']] = $row;
    }
} catch (Exception $e) {
    // Tabla no creada aún, ignorar
}

// Negocios con overrides (últimos 200, mostramos los que tienen valores custom)
$businesses = $db->query("
    SELECT b.id, b.name, b.business_type, b.images_max,
           b.visibility_min_zoom, b.is_premium, u.username AS owner
    FROM businesses b
    LEFT JOIN users u ON u.id = b.user_id
    ORDER BY b.name
    LIMIT 200
")->fetchAll();

// Calcular estadísticas
$premiumCount = (int)$db->query("SELECT COUNT(*) FROM businesses WHERE is_premium = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Límites Configurables — Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <link rel="stylesheet" href="/css/components-buttons.css">
    <link rel="stylesheet" href="/css/components-forms.css">
    <style>
        body { background: var(--bg-tertiary); color: var(--text-primary); font-family: var(--font-family-base); margin: 0; }
        header { background: var(--primary-dark); color: #fff; padding: 14px 28px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 1.1rem; }
        header a { color: #aec6ff; text-decoration: none; font-size: 13px; }
        header a:hover { color: #fff; }
        .container { max-width: 1100px; margin: 24px auto; padding: 0 18px; }
        .section { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 28px; overflow: hidden; }
        .section-header { background: #2d3748; color: #fff; padding: 12px 18px; font-weight: 700; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 9px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #f8f9fa; font-weight: 700; color: #555; }
        tr:hover { background: #f8f9fa; }
        .msg { padding: 10px 16px; border-radius: 6px; margin-bottom: 18px; font-size: 13px; }
        .msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .badge-premium { background: #ffc107; color: #333; padding: 2px 7px; border-radius: 10px; font-size: 11px; font-weight: 700; }
        .badge-std     { background: #e9ecef; color: #555; padding: 2px 7px; border-radius: 10px; font-size: 11px; }
        input[type=number] { width: 70px; padding: 4px 6px; border: 1px solid #ced4da; border-radius: 4px; }
        select { padding: 4px 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 12px; }
        .form-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 12px 16px; border-bottom: 1px solid #f0f0f0; }
        .form-row label { font-size: 12px; color: #555; margin-bottom: 0; min-width: 130px; }
        .type-section { padding: 16px; }
        .help { font-size: 11px; color: #888; margin-top: 4px; }
    </style>
</head>
<body>
<header>
    <h1>⚙️ Límites Configurables</h1>
    <div>
        <a href="/admin/dashboard.php">← Dashboard</a>
        &nbsp;|&nbsp;
        <a href="/admin/limits/dashboard.php?type=all">Ver todos los negocios</a>
    </div>
</header>

<div class="container">
    <?php if ($message): ?>
        <div class="msg <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <div style="display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
        <div class="section" style="flex:1;min-width:160px;padding:16px;text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#3d56c9;"><?php echo $premiumCount; ?></div>
            <div style="font-size:12px;color:#888;">Negocios Premium</div>
        </div>
        <div class="section" style="flex:1;min-width:160px;padding:16px;text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#28a745;"><?php echo count($typeLimits); ?></div>
            <div style="font-size:12px;color:#888;">Tipos configurados</div>
        </div>
        <div class="section" style="flex:1;min-width:200px;padding:16px;">
            <div style="font-size:12px;font-weight:700;color:#333;margin-bottom:8px;">📖 Referencia de Zoom</div>
            <div style="font-size:11px;color:#555;line-height:1.6;">
                3-4 → Vista mundial<br>
                6-8 → Vista de país<br>
                10-11 → Vista regional<br>
                12-13 → Vista de ciudad<br>
                15-16 → Vista de barrio
            </div>
        </div>
    </div>

    <!-- Defaults por tipo de negocio -->
    <div class="section">
        <div class="section-header">📋 Defaults por Tipo de Negocio</div>
        <div style="padding:14px 16px;font-size:12px;color:#555;">
            Estos valores se usan cuando un negocio no tiene override individual configurado.
        </div>
        <form method="post">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update_type_limits">
            <div class="form-row">
                <label>Tipo de negocio</label>
                <select name="business_type" required>
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($allTypes as $bt): ?>
                        <option value="<?php echo htmlspecialchars($bt); ?>"><?php echo htmlspecialchars($bt); ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Máx. imágenes</label>
                <input type="number" name="images_max_default" value="2" min="0" max="50">
                <label>Zoom mínimo</label>
                <input type="number" name="visibility_min_zoom_default" value="12" min="1" max="20">
                <button class="btn btn-primary btn-sm" type="submit">Guardar</button>
            </div>
        </form>
        <?php if (!empty($typeLimits)): ?>
        <table>
            <thead>
                <tr><th>Tipo</th><th>Máx. Imágenes</th><th>Zoom Mínimo</th><th>Actualizado</th><th>Acción masiva</th></tr>
            </thead>
            <tbody>
            <?php foreach ($typeLimits as $bt => $lim): ?>
                <tr>
                    <td><?php echo htmlspecialchars($bt); ?></td>
                    <td><?php echo (int)$lim['images_max_default']; ?></td>
                    <td><?php echo (int)$lim['visibility_min_zoom_default']; ?></td>
                    <td><?php echo htmlspecialchars(substr($lim['updated_at'] ?? '', 0, 10)); ?></td>
                    <td>
                        <form method="post" style="display:inline-flex;gap:6px;align-items:center;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="apply_type_to_all">
                            <input type="hidden" name="business_type" value="<?php echo htmlspecialchars($bt); ?>">
                            <input type="number" name="visibility_min_zoom" value="<?php echo (int)$lim['visibility_min_zoom_default']; ?>" min="1" max="20" style="width:55px;">
                            <select name="is_premium" style="font-size:11px;">
                                <option value="0">Normal</option>
                                <option value="1">Premium</option>
                            </select>
                            <button class="btn btn-sm btn-secondary" type="submit"
                                    onclick="return confirm('¿Aplicar estos valores a TODOS los negocios de tipo \'<?php echo addslashes($bt); ?>\'?')">
                                Aplicar a todos
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="padding:14px 16px;color:#888;font-size:13px;">No hay configuraciones por tipo aún. Usá el formulario de arriba para agregar.</p>
        <?php endif; ?>
    </div>

    <!-- Negocios individuales -->
    <div class="section">
        <div class="section-header">🏢 Configuración por Negocio (primeros 200)</div>
        <div style="padding:10px 16px;">
            <input type="text" id="search-biz" placeholder="🔍 Filtrar negocios..." oninput="filterBiz()"
                   style="padding:6px 12px;border:1px solid #ced4da;border-radius:6px;width:260px;font-size:13px;">
        </div>
        <table id="biz-table">
            <thead>
                <tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Propietario</th><th>Imágenes máx</th><th>Zoom mínimo</th><th>Premium</th><th>Acción</th></tr>
            </thead>
            <tbody>
            <?php foreach ($businesses as $b): ?>
                <tr>
                    <td><?php echo $b['id']; ?></td>
                    <td><?php echo htmlspecialchars($b['name']); ?></td>
                    <td><?php echo htmlspecialchars($b['business_type']); ?></td>
                    <td><?php echo htmlspecialchars($b['owner'] ?? '—'); ?></td>
                    <td><?php echo $b['images_max'] !== null ? (int)$b['images_max'] : '<span style="color:#aaa">default</span>'; ?></td>
                    <td><?php echo $b['visibility_min_zoom'] !== null ? (int)$b['visibility_min_zoom'] : '<span style="color:#aaa">default</span>'; ?></td>
                    <td>
                        <?php if ($b['is_premium']): ?>
                            <span class="badge-premium">⭐ Premium</span>
                        <?php else: ?>
                            <span class="badge-std">Normal</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline-flex;gap:6px;align-items:center;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="update_business_limits">
                            <input type="hidden" name="business_id" value="<?php echo $b['id']; ?>">
                            <input type="number" name="images_max" placeholder="img"
                                   value="<?php echo $b['images_max'] !== null ? (int)$b['images_max'] : ''; ?>"
                                   min="0" max="50" style="width:50px;" title="Máx imágenes (vacío=default)">
                            <input type="number" name="visibility_min_zoom" placeholder="zoom"
                                   value="<?php echo $b['visibility_min_zoom'] !== null ? (int)$b['visibility_min_zoom'] : ''; ?>"
                                   min="1" max="20" style="width:50px;" title="Zoom mínimo (vacío=default)">
                            <select name="is_premium" style="font-size:11px;">
                                <option value="0" <?php echo !$b['is_premium'] ? 'selected' : ''; ?>>Normal</option>
                                <option value="1" <?php echo  $b['is_premium'] ? 'selected' : ''; ?>>Premium</option>
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">✓</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="font-size:12px;color:#888;margin-bottom:24px;padding:0 4px;">
        💡 <strong>Tip:</strong> Los negocios <em>premium</em> se recomiendan con zoom mínimo 3–5 para visibilidad global.
        Los negocios normales usan 12 (zoom de ciudad) por defecto.
        El override de imágenes vacío significa usar el default del tipo o el global (2).
    </div>
</div>

<script>
function filterBiz() {
    const q   = document.getElementById('search-biz').value.toLowerCase();
    const trs = document.querySelectorAll('#biz-table tbody tr');
    trs.forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>
