<?php
if (session_status() == PHP_SESSION_NONE) session_start();

ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../business/process_business.php';

setSecurityHeaders();

$userId      = (int)$_SESSION['user_id'];
$currentUser = $_SESSION['user_name'];
$message     = '';
$messageType = '';

// ── Eliminar negocio ──────────────────────────────────────────────────────────
if (isset($_POST['delete_business'], $_POST['business_id'])) {
    verifyCsrfToken();
    $result      = deleteBusiness($_POST['business_id'], $userId);
    $message     = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}

// ── Toggle visibilidad ────────────────────────────────────────────────────────
if (isset($_POST['toggle_visibility'], $_POST['business_id'])) {
    verifyCsrfToken();
    $result      = toggleBusinessVisibility($_POST['business_id'], $userId);
    $message     = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}

// ── Obtener negocios ──────────────────────────────────────────────────────────
$isAdmin = isAdmin();
try {
    $db   = getDbConnection();
    if ($isAdmin) {
        $stmt = $db->prepare("SELECT b.*, u.username as owner_name FROM businesses b LEFT JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT * FROM businesses WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
    }
    $businesses = $stmt->fetchAll();
} catch (Exception $e) {
    $businesses  = [];
    $message     = "Error al obtener negocios: " . $e->getMessage();
    $messageType = "error";
}

// ── Contar marcas del usuario ─────────────────────────────────────────────────
$marcasCount = 0;
try {
    $sm = $db->prepare("SELECT COUNT(*) FROM brands WHERE user_id = ?");
    $sm->execute([$userId]);
    $marcasCount = (int)$sm->fetchColumn();
} catch (Exception $e) {}

// Emojis por tipo de negocio
$tipoEmojis = [
    'restaurante'  => '🍽️', 'cafeteria'   => '☕',  'bar'         => '🍺',
    'panaderia'    => '🥐',  'heladeria'   => '🍦',  'pizzeria'    => '🍕',
    'supermercado' => '🛒',  'comercio'    => '🛍️', 'indumentaria'=> '👕',
    'ferreteria'   => '🔧',  'electronica' => '📱',  'muebleria'   => '🛋️',
    'floristeria'  => '💐',  'libreria'    => '📖',  'farmacia'    => '💊',
    'hospital'     => '🏥',  'odontologia' => '🦷',  'veterinaria' => '🐾',
    'optica'       => '👓',  'salon_belleza'=> '💇', 'barberia'    => '💈',
    'spa'          => '💆',  'gimnasio'    => '💪',  'banco'       => '🏦',
    'inmobiliaria' => '🏠',  'seguros'     => '🛡️', 'abogado'     => '⚖️',
    'contador'     => '📊',  'taller'      => '🔩',  'construccion'=> '🏗️',
    'academia'     => '🎓',  'escuela'     => '🏫',  'hotel'       => '🏨',
    'turismo'      => '✈️',  'cine'        => '🎬',  'otros'       => '📍',
];
$tipoLabels = [
    'restaurante'  => 'Restaurante',   'cafeteria'    => 'Cafetería',
    'bar'          => 'Bar / Pub',     'panaderia'    => 'Panadería',
    'heladeria'    => 'Heladería',     'pizzeria'     => 'Pizzería',
    'supermercado' => 'Supermercado',  'comercio'     => 'Tienda / Local',
    'indumentaria' => 'Indumentaria',  'ferreteria'   => 'Ferretería',
    'electronica'  => 'Tecnología',    'muebleria'    => 'Mueblería',
    'floristeria'  => 'Floristería',   'libreria'     => 'Librería',
    'farmacia'     => 'Farmacia',      'hospital'     => 'Clínica / Hospital',
    'odontologia'  => 'Odontología',   'veterinaria'  => 'Veterinaria',
    'optica'       => 'Óptica',        'salon_belleza'=> 'Peluquería',
    'barberia'     => 'Barbería',      'spa'          => 'Spa / Estética',
    'gimnasio'     => 'Gimnasio',      'banco'        => 'Banco / Financiera',
    'inmobiliaria' => 'Inmobiliaria',  'seguros'      => 'Seguros',
    'abogado'      => 'Estudio Jurídico','contador'   => 'Contaduría',
    'taller'       => 'Taller Mecánico','construccion'=> 'Construcción',
    'academia'     => 'Academia',      'escuela'      => 'Escuela / Jardín',
    'hotel'        => 'Hotel',         'turismo'      => 'Agencia de Turismo',
    'cine'         => 'Cine / Teatro', 'otros'        => 'Otro',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Negocios — Mapita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f2f7;
            color: #1a202c;
            min-height: 100vh;
        }

        /* ── HEADER ───────────────────────────────────────── */
        .header {
            background: linear-gradient(135deg, #1B3B6F 0%, #0d2247 100%);
            color: white;
            padding: 0 28px;
            display: flex;
            align-items: center;
            gap: 16px;
            height: 64px;
            box-shadow: 0 4px 20px rgba(0,0,0,.25);
            position: sticky;
            top: 0;
            z-index: 1000; /* por encima de Leaflet (máx 700) */
        }
        .header-logo {
            font-size: 1.4em;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header-logo span { opacity: .6; font-weight: 400; font-size: .7em; margin-left: 6px; }
        .header-nav {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .header-nav a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: .84em;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
            white-space: nowrap;
        }
        .btn-nav-ghost {
            color: rgba(255,255,255,.85);
            border: 1.5px solid rgba(255,255,255,.3);
        }
        .btn-nav-ghost:hover { background: rgba(255,255,255,.1); color: white; }
        .btn-nav-primary {
            background: #f59e0b;
            color: #1a202c;
        }
        .btn-nav-primary:hover { background: #d97706; }
        .btn-nav-teal {
            background: #0ea5e9;
            color: white;
        }
        .btn-nav-teal:hover { background: #0284c7; }

        /* ── MAIN ─────────────────────────────────────────── */
        .main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 20px 60px;
        }

        /* ── PAGE TITLE ───────────────────────────────────── */
        .page-title {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }
        .page-title h1 {
            font-size: 1.6em;
            font-weight: 800;
            color: #1B3B6F;
        }
        .page-title .count-badge {
            background: #1B3B6F;
            color: white;
            font-size: .75em;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .user-tag {
            margin-left: auto;
            font-size: .78em;
            color: #9ca3af;
        }

        /* ── MENSAJE ──────────────────────────────────────── */
        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 600;
            font-size: .9em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* ── STATS ROW ────────────────────────────────────── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            border-left: 4px solid #1B3B6F;
        }
        .stat-card.green { border-left-color: #10b981; }
        .stat-card.amber { border-left-color: #f59e0b; }
        .stat-card.teal  { border-left-color: #0ea5e9; }
        .stat-num  { font-size: 1.8em; font-weight: 800; color: #1a202c; line-height: 1; }
        .stat-label{ font-size: .75em; color: #6b7280; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }

        /* ── GRID DE CARDS ────────────────────────────────── */
        .business-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        /* ── BUSINESS CARD ────────────────────────────────── */
        .biz-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow .2s, transform .2s;
        }
        .biz-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.12); transform: translateY(-2px); }

        /* Card header con color de tipo */
        .card-header {
            padding: 18px 20px 14px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            border-bottom: 1px solid #f3f4f6;
        }
        .card-emoji {
            width: 48px; height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1B3B6F, #2d5ea8);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5em;
            flex-shrink: 0;
        }
        .card-title-block { flex: 1; min-width: 0; }
        .card-name {
            font-size: 1em;
            font-weight: 800;
            color: #1a202c;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-type-badge {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 10px;
            background: #eef2ff;
            color: #1B3B6F;
            border-radius: 20px;
            font-size: .72em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .card-visibility {
            flex-shrink: 0;
            width: 10px; height: 10px;
            border-radius: 50%;
            margin-top: 4px;
        }
        .vis-on  { background: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.2); }
        .vis-off { background: #d1d5db; }

        /* Card body */
        .card-body {
            padding: 14px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .card-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .83em;
            color: #4b5563;
        }
        .card-info .icon { font-size: 1em; flex-shrink: 0; }
        .card-info.muted { color: #9ca3af; font-style: italic; }

        .badges-row {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 6px;
        }
        .feature-badge {
            padding: 3px 9px;
            border-radius: 20px;
            font-size: .72em;
            font-weight: 700;
            background: #f3f4f6;
            color: #374151;
        }
        .feature-badge.delivery { background: #dbeafe; color: #1d4ed8; }
        .feature-badge.card     { background: #d1fae5; color: #065f46; }
        .feature-badge.hidden   { background: #fef3c7; color: #92400e; }

        /* Card actions */
        .card-actions {
            padding: 14px 20px;
            border-top: 1px solid #f3f4f6;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: .8em;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all .18s;
            white-space: nowrap;
            line-height: 1;
        }
        .btn:hover { transform: translateY(-1px); filter: brightness(1.08); }
        .btn:active { transform: translateY(0); }

        /* Colores de botones — todos con alto contraste */
        .btn-ver      { background: #1B3B6F; color: white; }
        .btn-edit     { background: #0ea5e9; color: white; }
        .btn-show     { background: #10b981; color: white; }
        .btn-hide     { background: #6b7280; color: white; }
        .btn-delete   { background: #ef4444; color: white; }

        /* ── EMPTY STATE ──────────────────────────────────── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }
        .empty-state .icon { font-size: 3.5em; margin-bottom: 16px; }
        .empty-state h2 { color: #1B3B6F; font-size: 1.2em; margin-bottom: 8px; }
        .empty-state p { color: #6b7280; font-size: .9em; margin-bottom: 20px; }
        .empty-state a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 12px 24px;
            background: #1B3B6F;
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: .9em;
        }

        @media (max-width: 600px) {
            .header { padding: 0 16px; height: auto; flex-wrap: wrap; padding: 12px 16px; gap: 10px; }
            .header-nav { gap: 6px; }
            .header-nav a { padding: 7px 12px; font-size: .78em; }
            .business-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .page-title { flex-wrap: wrap; }
            .user-tag { display: none; }
        }
    </style>
</head>
<body>

<!-- ── HEADER ─────────────────────────────────────────────────────────────── -->
<header class="header">
    <div class="header-logo">
        🗺️ MAPITA <span>mis negocios</span>
    </div>
    <nav class="header-nav">
        <a href="/" class="btn-nav-ghost">← Mapa principal</a>
        <a href="/add" class="btn-nav-primary">➕ Nuevo negocio</a>
        <a href="/brand_form" class="btn-nav-teal">🏷️ Nueva marca</a>
        <?php if (isAdmin()): ?>
        <a href="/admin" style="background:#7c3aed;color:white;padding:8px 16px;border-radius:8px;font-size:.82em;font-weight:700;text-decoration:none;">⚙️ Admin</a>
        <?php endif; ?>
    </nav>
</header>

<!-- ── MAIN ───────────────────────────────────────────────────────────────── -->
<div class="main">

    <!-- Título -->
    <div class="page-title">
        <h1>Mis Negocios</h1>
        <span class="count-badge"><?php echo count($businesses); ?> publicado<?php echo count($businesses) !== 1 ? 's' : ''; ?></span>
        <div class="user-tag">👤 <?php echo htmlspecialchars($currentUser); ?> · <?php echo date('d/m/Y H:i'); ?></div>
    </div>

    <!-- Mensaje -->
    <?php if ($message): ?>
    <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <?php echo $messageType === 'success' ? '✅' : '❌'; ?>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <?php
    $visibles  = count(array_filter($businesses, fn($b) => $b['visible']));
    $ocultos   = count($businesses) - $visibles;
    $delivery  = count(array_filter($businesses, fn($b) => !empty($b['has_delivery'])));
    ?>
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-num"><?php echo count($businesses); ?></div>
            <div class="stat-label">Negocios totales</div>
        </div>
        <div class="stat-card green">
            <div class="stat-num"><?php echo $visibles; ?></div>
            <div class="stat-label">Visibles en mapa</div>
        </div>
        <?php if ($ocultos > 0): ?>
        <div class="stat-card amber">
            <div class="stat-num"><?php echo $ocultos; ?></div>
            <div class="stat-label">Ocultos</div>
        </div>
        <?php endif; ?>
        <div class="stat-card teal">
            <div class="stat-num"><?php echo $marcasCount; ?></div>
            <div class="stat-label">Marcas registradas</div>
        </div>
    </div>

    <!-- Grid de negocios -->
    <div class="business-grid">

        <?php if (empty($businesses)): ?>
        <div class="empty-state">
            <div class="icon">🏪</div>
            <h2>Todavía no publicaste ningún negocio</h2>
            <p>Registrá tu negocio y aparecé en el mapa para que te encuentren clientes.</p>
            <a href="/add">➕ Publicar mi primer negocio</a>
        </div>

        <?php else: ?>
        <?php foreach ($businesses as $b):
            $tipo  = $b['business_type'] ?? 'otros';
            $emoji = $tipoEmojis[$tipo]  ?? '📍';
            $label = $tipoLabels[$tipo]  ?? ucfirst($tipo);
            $comercioData = getComercioData($b['id']);
            // Contador de solicitudes pendientes de disponibles
            $dispOrdenesCount = 0;
            if (!empty($b['disponibles_activo'])) {
                try {
                    if (mapitaTableExists($db, 'disponibles_solicitudes')) {
                        $stOrd = $db->prepare("SELECT COUNT(*) FROM disponibles_solicitudes WHERE business_id = ? AND estado = 'pendiente'");
                        $stOrd->execute([$b['id']]);
                        $dispOrdenesCount = (int)$stOrd->fetchColumn();
                    }
                } catch (Exception $e) { $dispOrdenesCount = 0; }
            }
        ?>
        <div class="biz-card">

            <!-- Card header -->
            <div class="card-header">
                <div class="card-emoji"><?php echo $emoji; ?></div>
                <div class="card-title-block">
                    <div class="card-name" title="<?php echo htmlspecialchars($b['name']); ?>">
                        <?php echo htmlspecialchars($b['name']); ?>
                    </div>
                    <span class="card-type-badge"><?php echo htmlspecialchars($label); ?></span>
                    <?php if (!empty($b['disponibles_activo'])): ?>
                    <span style="font-size:10px;font-weight:700;background:#fef3c7;color:#92400e;
                                 border:1px solid #fcd34d;border-radius:10px;padding:2px 7px;margin-top:3px;display:inline-block;">
                        📦 Disponibles<?php echo $dispOrdenesCount > 0 ? ' 🔔' . $dispOrdenesCount : ''; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($isAdmin && !empty($b['owner_name'])): ?>
                    <span style="font-size:11px;color:#9ca3af;">👤 <?php echo htmlspecialchars($b['owner_name']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-visibility <?php echo $b['visible'] ? 'vis-on' : 'vis-off'; ?>"
                     title="<?php echo $b['visible'] ? 'Visible en mapa' : 'Oculto'; ?>"></div>
            </div>

            <!-- Card body -->
            <div class="card-body">

                <?php if (!empty($b['address'])): ?>
                <div class="card-info">
                    <span class="icon">📍</span>
                    <?php echo htmlspecialchars($b['address']); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($b['phone'])): ?>
                <div class="card-info">
                    <span class="icon">📞</span>
                    <?php echo htmlspecialchars($b['phone']); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($b['email'])): ?>
                <div class="card-info">
                    <span class="icon">📧</span>
                    <?php echo htmlspecialchars($b['email']); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($b['website'])): ?>
                <div class="card-info">
                    <span class="icon">🌐</span>
                    <a href="<?php echo htmlspecialchars($b['website']); ?>" target="_blank"
                       style="color:#0ea5e9;font-weight:600;text-decoration:none;">
                        <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $b['website'])); ?>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($comercioData['horario_apertura']) && !empty($comercioData['horario_cierre'])): ?>
                <div class="card-info">
                    <span class="icon">🕐</span>
                    <?php echo htmlspecialchars(substr($comercioData['horario_apertura'],0,5)); ?>
                    –
                    <?php echo htmlspecialchars(substr($comercioData['horario_cierre'],0,5)); ?>
                    <?php if (!empty($comercioData['dias_cierre'])): ?>
                    <span style="color:#9ca3af;font-size:.9em;">· Cierra: <?php echo htmlspecialchars($comercioData['dias_cierre']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (empty($b['address']) && empty($b['phone']) && empty($b['email'])): ?>
                <div class="card-info muted">Sin datos de contacto cargados aún</div>
                <?php endif; ?>

                <!-- Feature badges -->
                <div class="badges-row">
                    <?php if (!empty($b['has_delivery'])): ?>
                    <span class="feature-badge delivery">🚚 Delivery</span>
                    <?php endif; ?>
                    <?php if (!empty($b['has_card_payment'])): ?>
                    <span class="feature-badge card">💳 Tarjeta</span>
                    <?php endif; ?>
                    <?php if (!empty($b['is_franchise'])): ?>
                    <span class="feature-badge">🔗 Franquicia</span>
                    <?php endif; ?>
                    <?php if (!$b['visible']): ?>
                    <span class="feature-badge hidden">👁️ Oculto</span>
                    <?php endif; ?>
                    <?php
                    // Rango de precio
                    $precio = intval($b['price_range'] ?? 3);
                    if ($precio > 0) echo '<span class="feature-badge">' . str_repeat('$', $precio) . '</span>';
                    ?>
                </div>

            </div><!-- /card-body -->

            <!-- Card actions -->
            <div class="card-actions">
                <a href="/view?id=<?php echo $b['id']; ?>" class="btn btn-ver">👁️ Ver</a>
                <a href="/edit?id=<?php echo $b['id']; ?>" class="btn btn-edit">✏️ Editar</a>
                <a href="/panel-disponibles?id=<?php echo $b['id']; ?>"
                   class="btn"
                   style="background:<?php echo $dispOrdenesCount > 0 ? '#f59e0b' : '#374151'; ?>;color:white;"
                   title="Panel de Disponibles<?php echo $dispOrdenesCount > 0 ? ' — ' . $dispOrdenesCount . ' solicitud(es) pendiente(s)' : ''; ?>">
                    📦<?php echo $dispOrdenesCount > 0 ? ' 🔔' . $dispOrdenesCount : ''; ?>
                </a>

                <form method="post" style="margin:0;">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="business_id" value="<?php echo $b['id']; ?>">
                    <button type="submit" name="toggle_visibility" class="btn <?php echo $b['visible'] ? 'btn-hide' : 'btn-show'; ?>">
                        <?php echo $b['visible'] ? '🙈 Ocultar' : '🌐 Publicar'; ?>
                    </button>
                </form>

                <form method="post" style="margin:0;margin-left:auto;">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="business_id" value="<?php echo $b['id']; ?>">
                    <button type="submit" name="delete_business" class="btn btn-delete"
                            onclick="return confirm('¿Eliminar «<?php echo addslashes(htmlspecialchars($b['name'])); ?>»? Esta acción no se puede deshacer.')">
                        🗑️ Eliminar
                    </button>
                </form>
            </div>

        </div><!-- /biz-card -->
        <?php endforeach; ?>
        <?php endif; ?>

    </div><!-- /business-grid -->
</div><!-- /main -->

</body>
</html>
