<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../includes/db_helper.php';
require_once __DIR__ . '/../business/process_business.php';

setSecurityHeaders();

// Solo administradores
if (!isAdmin()) {
    header("Location: ../auth/login.php");
    exit();
}

$db      = getDbConnection();
$message = '';
$messageType = '';

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $action = $_POST['action'] ?? '';

    // Eliminar mensaje WT (admin)
    if ($action === 'delete_wt_message' && !empty($_POST['wt_message_id'])) {
        $wmid = (int)$_POST['wt_message_id'];
        try {
            $db->prepare("DELETE FROM wt_messages WHERE id = ?")->execute([$wmid]);
            $message     = 'Mensaje WT eliminado.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message     = 'Error al eliminar mensaje WT.';
            $messageType = 'error';
        }
    }

    // Eliminar negocio (admin puede eliminar cualquiera)
    if ($action === 'delete_business' && !empty($_POST['business_id'])) {
        $bid  = (int)$_POST['business_id'];
        try {
            $db->prepare("DELETE FROM comercios WHERE business_id = ?")->execute([$bid]);
            $db->prepare("DELETE FROM businesses WHERE id = ?")->execute([$bid]);
            $message     = 'Negocio eliminado.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message     = 'Error al eliminar negocio.';
            $messageType = 'error';
        }
    }

    // Cambiar visibilidad (admin)
    if ($action === 'toggle_visibility' && !empty($_POST['business_id'])) {
        $bid  = (int)$_POST['business_id'];
        $stmt = $db->prepare("SELECT visible FROM businesses WHERE id = ?");
        $stmt->execute([$bid]);
        $row  = $stmt->fetch();
        if ($row) {
            $newVal = $row['visible'] ? 0 : 1;
            $db->prepare("UPDATE businesses SET visible = ?, updated_at = NOW() WHERE id = ?")
               ->execute([$newVal, $bid]);
            $message     = $newVal ? 'Negocio publicado.' : 'Negocio ocultado.';
            $messageType = 'success';
        }
    }

    // Eliminar usuario
    if ($action === 'delete_user' && !empty($_POST['uid'])) {
        $uid = (int)$_POST['uid'];
        if ($uid === (int)$_SESSION['user_id']) {
            $message     = 'No puedes eliminarte a ti mismo.';
            $messageType = 'error';
        } else {
            try {
                // Eliminar datos de comercio de sus negocios
                $db->prepare("DELETE c FROM comercios c INNER JOIN businesses b ON c.business_id = b.id WHERE b.user_id = ?")
                   ->execute([$uid]);
                $db->prepare("DELETE FROM businesses WHERE user_id = ?")->execute([$uid]);
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
                $message     = 'Usuario eliminado.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message     = 'Error al eliminar usuario.';
                $messageType = 'error';
            }
        }
    }

    // Cambiar rol admin
    if ($action === 'toggle_admin' && !empty($_POST['uid'])) {
        $uid = (int)$_POST['uid'];
        if ($uid !== (int)$_SESSION['user_id']) {
            $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            $row  = $stmt->fetch();
            if ($row) {
                $newAdmin = $row['is_admin'] ? 0 : 1;
                $db->prepare("UPDATE users SET is_admin = ?, updated_at = NOW() WHERE id = ?")
                   ->execute([$newAdmin, $uid]);
                $message     = $newAdmin ? 'Usuario promovido a admin.' : 'Rol de admin revocado.';
                $messageType = 'success';
            }
        }
    }
}

// Estadísticas
$totalUsers     = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBusinesses = (int)$db->query("SELECT COUNT(*) FROM businesses")->fetchColumn();
$visibleCount   = (int)$db->query("SELECT COUNT(*) FROM businesses WHERE visible = 1")->fetchColumn();
$hiddenCount    = $totalBusinesses - $visibleCount;

// Mensajes WT
$wtMessagesCount = 0;
$wtMessages = [];
try {
    $wtMessagesCount = (int)$db->query("SELECT COUNT(*) FROM wt_messages")->fetchColumn();
    $wtMessages = $db->query("
        SELECT wm.id, wm.entity_type, wm.entity_id, wm.user_name, wm.message,
               DATE_FORMAT(wm.created_at, '%d/%m/%Y %H:%i') AS created_at,
               COALESCE(b.name, CONCAT(wm.entity_type, ' #', wm.entity_id)) AS entity_label
        FROM wt_messages wm
        LEFT JOIN businesses b ON wm.entity_type = 'negocio' AND b.id = wm.entity_id
        ORDER BY wm.id DESC
        LIMIT 100
    ")->fetchAll();
} catch (Exception $e) {
    // Tabla WT aún no creada, ignorar
}

// Listar usuarios con email, nombre de titular y sus negocios
$users = $db->query("
    SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.is_admin, u.created_at,
           GROUP_CONCAT(b.id ORDER BY b.id SEPARATOR ',')   AS business_ids,
           GROUP_CONCAT(b.name ORDER BY b.id SEPARATOR '||') AS business_names
    FROM users u
    LEFT JOIN businesses b ON b.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

// Listar negocios con propietario
$businesses = $db->query("
    SELECT b.*, u.username AS owner,
           (
               SELECT COUNT(*)
               FROM wt_messages wm
               WHERE wm.entity_type = 'negocio' AND wm.entity_id = b.id
           ) AS wt_messages_count
    FROM businesses b
    LEFT JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 100
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - Mapita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <link rel="stylesheet" href="/css/components-buttons.css">
    <link rel="stylesheet" href="/css/components-cards.css">
    <link rel="stylesheet" href="/css/components-forms.css">
    <style>
        body {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            font-family: var(--font-family-base);
            margin: 0;
            padding: 0;
        }
        header {
            background: var(--primary-dark);
            color: var(--text-inverse);
            padding: var(--space-md) var(--space-xl);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
        }
        header h1 { margin: 0; font-size: var(--font-size-lg); }
        header a { color: var(--color-gray-300); text-decoration: none; font-size: var(--font-size-sm); }
        header a:hover { color: var(--text-inverse); }
        
        .container {
            max-width: var(--max-content-width);
            margin: var(--space-xl) auto;
            padding: 0 var(--space-lg);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }
        .stat-card { text-align: center; }
        .stat-card .number { font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--primary); }
        .stat-card .label { color: var(--text-tertiary); font-size: var(--font-size-sm); margin-top: var(--space-xs); }
        
        .section {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: var(--space-xl);
            overflow: hidden;
            border: var(--border-width-thin) solid var(--color-gray-100);
        }
        .section-header {
            background: var(--color-gray-800);
            color: white;
            padding: var(--space-md) var(--space-lg);
            font-size: var(--font-size-md);
            font-weight: var(--font-weight-bold);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: var(--space-md); text-align: left; border-bottom: 1px solid var(--color-gray-100); font-size: var(--font-size-sm); }
        th { background: var(--color-gray-50); font-weight: var(--font-weight-bold); color: var(--text-secondary); }
        tr:hover { background: var(--color-gray-50); }
        
        .message { padding: var(--space-md) var(--space-lg); margin-bottom: var(--space-lg); border-radius: var(--border-radius-md); text-align: center; }
        .success { background: rgba(46, 204, 113, 0.1); color: var(--color-success); border: 1px solid var(--color-success); }
        .error { background: rgba(230, 57, 70, 0.1); color: var(--accent-dark); border: 1px solid var(--accent-light); }
        
        @media (max-width: 768px) {
            header { flex-direction: column; gap: var(--space-md); padding: var(--space-lg); text-align: center; }
            header div { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--space-sm); }
            th, td { padding: var(--space-sm); font-size: var(--font-size-xs); }
            .stats { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<header>
    <h1>🛡️ Panel de Administración</h1>
    <div>
        <span>Usuario: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        &nbsp;|&nbsp;
        <a href="/">🗺️ Mapa</a>
        &nbsp;|&nbsp;
        <a href="/admin/encuestas/dashboard.php">📊 Encuestas</a>
        &nbsp;|&nbsp;
        <a href="/admin/eventos/dashboard.php">🎉 Eventos</a>
        &nbsp;|&nbsp;
        <a href="/admin/trivias/dashboard.php">🎯 Trivias</a>
        &nbsp;|&nbsp;
        <a href="/admin/noticias/dashboard.php">📰 Noticias</a>
        &nbsp;|&nbsp;
        <a href="/admin/ofertas/dashboard.php">🏷️ Ofertas</a>
        &nbsp;|&nbsp;
        <a href="/admin/transmisiones/dashboard.php">📡 En Vivo</a>
        &nbsp;|&nbsp;
        <a href="/admin/limits/dashboard.php">⚙️ Límites</a>
        &nbsp;|&nbsp;
        <a href="/logout">🚪 Salir</a>
    </div>
</header>

<div class="container">
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="stats">
        <div class="stat-card card">
            <div class="number"><?php echo $totalUsers; ?></div>
            <div class="label">Usuarios registrados</div>
        </div>
        <div class="stat-card card">
            <div class="number"><?php echo $totalBusinesses; ?></div>
            <div class="label">Negocios en total</div>
        </div>
        <div class="stat-card card">
            <div class="number" style="color:var(--color-success)"><?php echo $visibleCount; ?></div>
            <div class="label">Negocios visibles</div>
        </div>
        <div class="stat-card card">
            <div class="number" style="color:var(--color-warning)"><?php echo $hiddenCount; ?></div>
            <div class="label">Negocios ocultos</div>
        </div>
        <div class="stat-card card">
            <div class="number" style="color:#3d56c9"><?php echo $wtMessagesCount; ?></div>
            <div class="label">Mensajes WT</div>
        </div>
    </div>

    <!-- Usuarios -->
    <div class="section">
        <div class="section-header" style="display:flex;justify-content:space-between;align-items:center;">
            <span>👥 Usuarios (<?php echo $totalUsers; ?>)</span>
            <a href="/admin/api/search_ui.php?q=" style="font-size:12px;color:#aec6ff;text-decoration:none;">🔍 Búsqueda global anti-fraude</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Usuario</th><th>Titular</th><th>Email</th><th>Negocios</th><th>Rol</th><th>Registrado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td>
                        <?php
                        $titular = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                        echo $titular !== '' ? htmlspecialchars($titular) : '<span style="color:#bbb">—</span>';
                        ?>
                    </td>
                    <td><?php echo $u['email'] ? htmlspecialchars($u['email']) : '<span style="color:#bbb">—</span>'; ?></td>
                    <td>
                        <?php
                        if (!empty($u['business_ids'])) {
                            $bids   = explode(',', $u['business_ids']);
                            $bnames = explode('||', $u['business_names']);
                            foreach ($bids as $i => $bid) {
                                $bname = $bnames[$i] ?? 'Negocio #' . $bid;
                                echo '<a href="../business/view_business.php?id=' . (int)$bid . '" target="_blank" style="font-size:12px;display:block;">'
                                     . htmlspecialchars($bname) . '</a>';
                            }
                        } else {
                            echo '<span style="color:#bbb">—</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $u['is_admin'] ? 'badge-admin' : 'badge-user'; ?>">
                            <?php echo $u['is_admin'] ? 'Admin' : 'Usuario'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars(substr($u['created_at'] ?? '', 0, 10)); ?></td>
                    <td>
                        <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                        <form class="inline" method="post">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="toggle_admin">
                            <input type="hidden" name="uid" value="<?php echo $u['id']; ?>">
                            <button class="btn btn-sm btn-secondary" style="color:var(--color-warning); border-color:var(--color-warning);">
                                <?php echo $u['is_admin'] ? 'Quitar Admin' : 'Hacer Admin'; ?>
                            </button>
                        </form>
                        <form class="inline" method="post" onsubmit="return confirm('¿Eliminar usuario y todos sus negocios?')">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="uid" value="<?php echo $u['id']; ?>">
                            <button class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                        <?php else: ?>
                            <em style="font-size:0.8em;color:#888">Tú mismo</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Negocios -->
    <div class="section">
        <div class="section-header">🏢 Negocios (últimos 100)</div>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Nombre</th><th>Tipo</th><th>Propietario</th><th>Mensajes</th><th>Visibilidad</th><th>Registrado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($businesses as $b): ?>
                <tr>
                    <td><?php echo $b['id']; ?></td>
                    <td>
                        <a href="../business/view_business.php?id=<?php echo $b['id']; ?>" target="_blank">
                            <?php echo htmlspecialchars($b['name']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($b['business_type']); ?></td>
                    <td><?php echo htmlspecialchars($b['owner'] ?? '-'); ?></td>
                    <td>
                        <?php $wtCountBusiness = (int)($b['wt_messages_count'] ?? 0); ?>
                        <?php if ($wtCountBusiness > 0): ?>
                            <a href="#wt-section" title="Ver mensajes WT de este negocio" style="text-decoration:none;font-weight:700;color:#3d56c9;">
                                💬 <?php echo $wtCountBusiness; ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#bbb;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $b['visible'] ? 'badge-visible' : 'badge-hidden'; ?>">
                            <?php echo $b['visible'] ? 'Visible' : 'Oculto'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars(substr($b['created_at'] ?? '', 0, 10)); ?></td>
                    <td>
                        <a href="../business/edit_business.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-secondary" style="color:var(--color-warning); border-color:var(--color-warning);">
                            Editar
                        </a>
                        <form class="inline" method="post">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="toggle_visibility">
                            <input type="hidden" name="business_id" value="<?php echo $b['id']; ?>">
                            <button class="btn btn-sm btn-secondary" style="color:var(--primary); border-color:var(--primary);">
                                <?php echo $b['visible'] ? 'Ocultar' : 'Publicar'; ?>
                            </button>
                        </form>
                        <form class="inline" method="post" onsubmit="return confirm('¿Eliminar este negocio?')">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete_business">
                            <input type="hidden" name="business_id" value="<?php echo $b['id']; ?>">
                            <button class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- WT Mensajes -->
    <div class="section" id="wt-section">
        <div class="section-header" style="display:flex;justify-content:space-between;align-items:center;cursor:pointer;" onclick="toggleWTSection()">
            <span>📻 WT Mensajes (últimos 100)</span>
            <button type="button" id="wt-toggle-btn" style="background:transparent;border:1px solid rgba(255,255,255,0.4);color:#fff;padding:3px 12px;border-radius:6px;cursor:pointer;font-size:12px;">Ocultar</button>
        </div>
        <div id="wt-section-body">
        <?php if (empty($wtMessages)): ?>
            <p style="padding:16px;color:#888;font-size:14px;">No hay mensajes WT registrados.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Entidad</th><th>Usuario</th><th>Mensaje</th><th>Fecha</th><th>Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($wtMessages as $wm): ?>
                <tr>
                    <td><?php echo (int)$wm['id']; ?></td>
                    <td><?php echo htmlspecialchars($wm['entity_label']); ?></td>
                    <td><?php echo htmlspecialchars($wm['user_name']); ?></td>
                    <td style="max-width:260px;word-break:break-word;"><?php echo htmlspecialchars($wm['message']); ?></td>
                    <td><?php echo htmlspecialchars($wm['created_at']); ?></td>
                    <td>
                        <form class="inline" method="post" onsubmit="return confirm('¿Eliminar este mensaje WT?')">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete_wt_message">
                            <input type="hidden" name="wt_message_id" value="<?php echo (int)$wm['id']; ?>">
                            <button class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>
    </div>

    <script>
    (function() {
        var body = document.getElementById('wt-section-body');
        var btn  = document.getElementById('wt-toggle-btn');
        var hidden = localStorage.getItem('wt_section_hidden') === '1';
        if (hidden) { body.style.display = 'none'; btn.textContent = 'Mostrar'; }
        window.toggleWTSection = function() {
            hidden = !hidden;
            body.style.display = hidden ? 'none' : '';
            btn.textContent = hidden ? 'Mostrar' : 'Ocultar';
            localStorage.setItem('wt_section_hidden', hidden ? '1' : '0');
        };
    })();
    </script>

</div>
</body>
</html>
