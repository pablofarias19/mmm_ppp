<?php
/**
 * views/industry/form.php
 * Formulario unificado de Industrias — Nueva / Editar
 * Tabla: industries
 */
if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

setSecurityHeaders();

$userId  = (int)$_SESSION['user_id'];
$isAdmin = isAdmin();
$industryId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$editing    = $industryId !== null;
$industry   = null;
$message    = '';
$msgType    = '';

// ── Load for editing ─────────────────────────────────────────────────────────
if ($editing) {
    try {
        $db   = getDbConnection();
        $stmt = $db->prepare(
            'SELECT i.*, s.name AS sector_name
             FROM industries i
             LEFT JOIN industrial_sectors s ON s.id = i.industrial_sector_id
             WHERE i.id = ? LIMIT 1'
        );
        $stmt->execute([$industryId]);
        $industry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$industry) {
            header('Location: /industrias');
            exit;
        }

        // Only owner or admin can edit
        if (!$isAdmin && (int)$industry['user_id'] !== $userId) {
            header('Location: /industrias');
            exit;
        }
    } catch (Exception $e) {
        error_log('Error cargando industria: ' . $e->getMessage());
    }
}

// ── Load sectors for dropdown ─────────────────────────────────────────────────
$sectors = [];
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT id, name, type FROM industrial_sectors WHERE status = 'activo' ORDER BY name ASC");
    $sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // sectors may not exist yet
}

// ── Load user's businesses for dropdown ───────────────────────────────────────
$businesses = [];
try {
    $db = getDbConnection();
    if ($isAdmin) {
        $stmt = $db->query("SELECT id, name FROM businesses ORDER BY name ASC LIMIT 200");
    } else {
        $stmt = $db->prepare("SELECT id, name FROM businesses WHERE user_id = ? ORDER BY name ASC LIMIT 200");
        $stmt->execute([$userId]);
    }
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* table may not exist */ }

// ── Load user's brands for dropdown ───────────────────────────────────────────
$brands = [];
try {
    $db = getDbConnection();
    if ($isAdmin) {
        $stmt = $db->query("SELECT id, nombre AS name FROM brands ORDER BY nombre ASC LIMIT 200");
    } else {
        $stmt = $db->prepare("SELECT id, nombre AS name FROM brands WHERE user_id = ? ORDER BY nombre ASC LIMIT 200");
        $stmt->execute([$userId]);
    }
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* table may not exist */ }

// ── Process POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    // ── Delete ────────────────────────────────────────────────────────────────
    if (isset($_POST['delete_industry']) && $editing) {
        try {
            $db = getDbConnection();
            if (!$isAdmin) {
                $chk = $db->prepare('SELECT user_id FROM industries WHERE id = ? LIMIT 1');
                $chk->execute([$industryId]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);
                if (!$row || (int)$row['user_id'] !== $userId) {
                    throw new Exception('No tenés permiso para eliminar esta industria.');
                }
                $stmt = $db->prepare('DELETE FROM industries WHERE id = ? AND user_id = ?');
                $stmt->execute([$industryId, $userId]);
            } else {
                $stmt = $db->prepare('DELETE FROM industries WHERE id = ?');
                $stmt->execute([$industryId]);
            }
            header('Location: /industrias?deleted=1');
            exit;
        } catch (Exception $e) {
            $message = 'Error al eliminar: ' . htmlspecialchars($e->getMessage());
            $msgType = 'error';
        }
    }

    // ── Save (create or update) ───────────────────────────────────────────────
    if (!isset($_POST['delete_industry'])) {
        $name              = trim($_POST['name']              ?? '');
        $description       = trim($_POST['description']       ?? '');
        $website           = trim($_POST['website']           ?? '');
        $contact_email     = trim($_POST['contact_email']     ?? '');
        $contact_phone     = trim($_POST['contact_phone']     ?? '');
        $country           = trim($_POST['country']           ?? '');
        $region            = trim($_POST['region']            ?? '');
        $city              = trim($_POST['city']              ?? '');
        $certifications    = trim($_POST['certifications']    ?? '');
        $naics_code        = trim($_POST['naics_code']        ?? '');
        $isic_code         = trim($_POST['isic_code']         ?? '');
        $employees_range   = $_POST['employees_range']        ?? '';
        $annual_revenue    = $_POST['annual_revenue']         ?? '';
        $status            = $_POST['status']                 ?? 'borrador';
        $industrial_sector_id = !empty($_POST['industrial_sector_id']) ? (int)$_POST['industrial_sector_id'] : null;
        $business_id       = !empty($_POST['business_id'])   ? (int)$_POST['business_id']   : null;
        $brand_id          = !empty($_POST['brand_id'])       ? (int)$_POST['brand_id']       : null;

        $errors = [];
        if ($name === '') $errors[] = 'El nombre es obligatorio.';
        if ($name !== '' && mb_strlen($name) > 255) $errors[] = 'El nombre es demasiado largo (máx. 255 caracteres).';
        if ($contact_email && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'El email de contacto no es válido.';
        if ($website && !filter_var($website, FILTER_VALIDATE_URL)) $errors[] = 'La URL del sitio web no es válida.';
        $validStatuses = ['borrador', 'activa', 'archivada'];
        if (!in_array($status, $validStatuses, true)) $errors[] = 'Estado inválido.';

        if ($errors) {
            $message = implode(' ', $errors);
            $msgType = 'error';
        } else {
            try {
                $db   = getDbConnection();
                $data = [
                    'industrial_sector_id' => $industrial_sector_id,
                    'business_id'          => $business_id,
                    'brand_id'             => $brand_id,
                    'name'                 => mb_substr($name, 0, 255),
                    'description'          => $description ?: null,
                    'website'              => $website ?: null,
                    'contact_email'        => $contact_email ?: null,
                    'contact_phone'        => $contact_phone ?: null,
                    'country'              => $country ?: null,
                    'region'               => $region ?: null,
                    'city'                 => $city ?: null,
                    'employees_range'      => $employees_range ?: null,
                    'annual_revenue'       => $annual_revenue ?: null,
                    'certifications'       => $certifications ?: null,
                    'naics_code'           => $naics_code ?: null,
                    'isic_code'            => $isic_code ?: null,
                    'status'               => $status,
                ];

                if ($editing) {
                    if (!$isAdmin) {
                        $chk = $db->prepare('SELECT user_id FROM industries WHERE id = ? LIMIT 1');
                        $chk->execute([$industryId]);
                        $row = $chk->fetch(PDO::FETCH_ASSOC);
                        if (!$row || (int)$row['user_id'] !== $userId) {
                            throw new Exception('No tenés permiso para editar esta industria.');
                        }
                    }
                    // Build UPDATE
                    $fields = [];
                    $params = [];
                    foreach ($data as $col => $val) {
                        $fields[] = "$col = ?";
                        $params[] = $val;
                    }
                    $params[] = $industryId;
                    $db->prepare('UPDATE industries SET ' . implode(', ', $fields) . ' WHERE id = ?')
                       ->execute($params);
                    $message = '✅ Industria actualizada correctamente.';
                    $msgType = 'success';

                    // Reload
                    $stmt = $db->prepare(
                        'SELECT i.*, s.name AS sector_name FROM industries i
                         LEFT JOIN industrial_sectors s ON s.id = i.industrial_sector_id
                         WHERE i.id = ? LIMIT 1'
                    );
                    $stmt->execute([$industryId]);
                    $industry = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $data['user_id'] = $userId;
                    $fields  = array_keys($data);
                    $placeholders = array_fill(0, count($fields), '?');
                    $stmt = $db->prepare(
                        'INSERT INTO industries (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')'
                    );
                    $stmt->execute(array_values($data));
                    $newId = (int)$db->lastInsertId();
                    header('Location: /industrias?saved=1');
                    exit;
                }
            } catch (Exception $e) {
                error_log('Error guardando industria: ' . $e->getMessage());
                $message = 'Error al guardar: ' . htmlspecialchars($e->getMessage());
                $msgType = 'error';
            }
        }
    }
}

$csrfToken = generateCsrfToken();

// Helpers for form values (prefer POST on error, then existing row)
function fieldVal(string $key, ?array $row, $default = ''): string {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$key])) {
        return htmlspecialchars($_POST[$key]);
    }
    if ($row && isset($row[$key]) && $row[$key] !== null) {
        return htmlspecialchars((string)$row[$key]);
    }
    return htmlspecialchars((string)$default);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editing ? 'Editar Industria' : 'Nueva Industria'; ?> — Mapita</title>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f2f7;
            color: #1a202c;
            min-height: 100vh;
        }

        /* ── HEADER ─────────────────────────────────────────── */
        .header {
            background: linear-gradient(135deg, #1B3B6F 0%, #0d2247 100%);
            color: white;
            padding: 0 28px;
            display: flex;
            align-items: center;
            gap: 14px;
            height: 64px;
            box-shadow: 0 4px 20px rgba(0,0,0,.25);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-logo { font-size: 1.4em; font-weight: 800; letter-spacing: -0.5px; }
        .header-nav { margin-left: auto; display: flex; align-items: center; gap: 10px; }
        .header-nav a {
            padding: 7px 15px;
            border-radius: 8px;
            font-size: .84em;
            font-weight: 600;
            text-decoration: none;
            color: rgba(255,255,255,.85);
            border: 1.5px solid rgba(255,255,255,.3);
            transition: background .2s;
        }
        .header-nav a:hover { background: rgba(255,255,255,.1); color: white; }

        /* ── MAIN ───────────────────────────────────────────── */
        .main { max-width: 780px; margin: 40px auto; padding: 0 20px 60px; }

        /* ── FORM CARD ──────────────────────────────────────── */
        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,.09);
            overflow: hidden;
        }
        .form-card-header {
            background: linear-gradient(135deg, #1B3B6F 0%, #2563eb 100%);
            color: white;
            padding: 24px 28px;
        }
        .form-card-header h1 { font-size: 1.35em; font-weight: 800; }
        .form-card-header p  { opacity: .8; font-size: .88em; margin-top: 4px; }
        .form-card-body { padding: 28px; }

        /* ── SECTIONS ───────────────────────────────────────── */
        .section-title {
            font-size: .78em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6366f1;
            border-bottom: 1px solid #e0e7ff;
            padding-bottom: 6px;
            margin: 24px 0 16px;
        }
        .section-title:first-child { margin-top: 0; }

        /* ── FORM GRID ──────────────────────────────────────── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid.full { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.span2 { grid-column: span 2; }

        label {
            font-size: .83em;
            font-weight: 600;
            color: #374151;
        }
        label .req { color: #ef4444; }

        input[type=text],
        input[type=email],
        input[type=url],
        input[type=tel],
        textarea,
        select {
            padding: 10px 13px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: .9em;
            color: #1a202c;
            background: #f9fafb;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
            width: 100%;
        }
        input:focus, textarea:focus, select:focus {
            border-color: #1B3B6F;
            background: white;
            box-shadow: 0 0 0 3px rgba(27,59,111,.1);
        }
        textarea { resize: vertical; min-height: 90px; }

        /* ── ALERT ──────────────────────────────────────────── */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: .9em;
        }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

        /* ── ACTIONS ────────────────────────────────────────── */
        .form-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #f3f4f6;
        }
        .btn {
            padding: 11px 24px;
            border-radius: 10px;
            font-size: .9em;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .15s;
        }
        .btn-primary { background: #1B3B6F; color: white; }
        .btn-primary:hover { background: #0d2247; }
        .btn-secondary { background: #f3f4f6; color: #374151; }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .btn-danger:hover { background: #fee2e2; }
        .btn-delete-wrap { margin-left: auto; }

        /* ── RESPONSIVE ─────────────────────────────────────── */
        @media (max-width: 620px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-group.span2 { grid-column: span 1; }
            .main { margin-top: 20px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-logo">🗺️ Mapita</div>
    <nav class="header-nav">
        <a href="/industrias">← Volver a Industrias</a>
        <a href="/">🗺️ Mapa</a>
    </nav>
</header>

<main class="main">
    <div class="form-card">

        <!-- Card header -->
        <div class="form-card-header">
            <h1><?php echo $editing ? '✏️ Editar Industria' : '🏭 Nueva Industria'; ?></h1>
            <p><?php echo $editing
                ? 'Modificá los datos de tu industria registrada.'
                : 'Completá los datos para registrar tu industria en Mapita.'; ?></p>
        </div>

        <div class="form-card-body">

            <!-- Alert -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $msgType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <!-- ── SECCIÓN 1: Datos básicos ─────────────── -->
                <div class="section-title">📋 Datos básicos</div>
                <div class="form-grid">
                    <div class="form-group span2">
                        <label for="name">Nombre de la industria <span class="req">*</span></label>
                        <input type="text" id="name" name="name" required maxlength="255"
                               value="<?php echo fieldVal('name', $industry); ?>"
                               placeholder="Ej: Planta Procesadora Norte S.A.">
                    </div>
                    <div class="form-group">
                        <label for="industrial_sector_id">Sector industrial</label>
                        <select id="industrial_sector_id" name="industrial_sector_id">
                            <option value="">— Sin sector —</option>
                            <?php
                            $selSector = (int)fieldVal('industrial_sector_id', $industry, 0);
                            $sectorIcons = ['mineria'=>'⛏️','energia'=>'⚡','agro'=>'🌾','infraestructura'=>'🏗️','inmobiliario'=>'🏢','industrial'=>'🏭'];
                            foreach ($sectors as $s):
                                $ico = $sectorIcons[$s['type']] ?? '🏭';
                                $sel = ($selSector === (int)$s['id']) ? 'selected' : '';
                            ?>
                            <option value="<?php echo (int)$s['id']; ?>" <?php echo $sel; ?>>
                                <?php echo $ico . ' ' . htmlspecialchars($s['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Estado <span class="req">*</span></label>
                        <select id="status" name="status" required>
                            <?php
                            $curStatus = fieldVal('status', $industry, 'borrador');
                            $statusOpts = ['borrador'=>'📝 Borrador','activa'=>'✅ Activa','archivada'=>'📦 Archivada'];
                            foreach ($statusOpts as $val => $lbl):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo ($curStatus === $val) ? 'selected' : ''; ?>>
                                <?php echo $lbl; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group span2">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" maxlength="5000"
                                  placeholder="Descripción de la industria, actividades principales, productos o servicios…"><?php echo fieldVal('description', $industry); ?></textarea>
                    </div>
                </div>

                <!-- ── SECCIÓN 2: Ubicación ─────────────────── -->
                <div class="section-title">📍 Ubicación</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="country">País</label>
                        <input type="text" id="country" name="country" maxlength="100"
                               value="<?php echo fieldVal('country', $industry); ?>"
                               placeholder="Ej: Argentina">
                    </div>
                    <div class="form-group">
                        <label for="region">Región / Provincia</label>
                        <input type="text" id="region" name="region" maxlength="100"
                               value="<?php echo fieldVal('region', $industry); ?>"
                               placeholder="Ej: Córdoba">
                    </div>
                    <div class="form-group">
                        <label for="city">Ciudad</label>
                        <input type="text" id="city" name="city" maxlength="100"
                               value="<?php echo fieldVal('city', $industry); ?>"
                               placeholder="Ej: Río Cuarto">
                    </div>
                    <div class="form-group">
                        <label for="website">Sitio web</label>
                        <input type="url" id="website" name="website" maxlength="500"
                               value="<?php echo fieldVal('website', $industry); ?>"
                               placeholder="https://ejemplo.com">
                    </div>
                </div>

                <!-- ── SECCIÓN 3: Contacto ──────────────────── -->
                <div class="section-title">📞 Contacto</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="contact_email">Email de contacto</label>
                        <input type="email" id="contact_email" name="contact_email" maxlength="255"
                               value="<?php echo fieldVal('contact_email', $industry); ?>"
                               placeholder="contacto@empresa.com">
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Teléfono de contacto</label>
                        <input type="tel" id="contact_phone" name="contact_phone" maxlength="50"
                               value="<?php echo fieldVal('contact_phone', $industry); ?>"
                               placeholder="+54 9 351 000-0000">
                    </div>
                </div>

                <!-- ── SECCIÓN 4: Variables específicas de industria ── -->
                <div class="section-title">⚙️ Características industriales</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="employees_range">Rango de empleados</label>
                        <select id="employees_range" name="employees_range">
                            <option value="">— No especificado —</option>
                            <?php
                            $curEmp = fieldVal('employees_range', $industry);
                            $empOpts = ['1-10'=>'👥 1 – 10','11-50'=>'👥 11 – 50','51-200'=>'👥 51 – 200','201-500'=>'👥 201 – 500','500+'=>'👥 Más de 500'];
                            foreach ($empOpts as $val => $lbl):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo ($curEmp === $val) ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="annual_revenue">Escala de la industria</label>
                        <select id="annual_revenue" name="annual_revenue">
                            <option value="">— No especificado —</option>
                            <?php
                            $curRev = fieldVal('annual_revenue', $industry);
                            $revOpts = ['micro'=>'🔵 Micro','pequeña'=>'🟢 Pequeña','mediana'=>'🟡 Mediana','grande'=>'🟠 Grande','corporación'=>'🔴 Corporación'];
                            foreach ($revOpts as $val => $lbl):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo ($curRev === $val) ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="naics_code">Código NAICS <small style="font-weight:400;color:#9ca3af;">(opcional)</small></label>
                        <input type="text" id="naics_code" name="naics_code" maxlength="20"
                               value="<?php echo fieldVal('naics_code', $industry); ?>"
                               placeholder="Ej: 311111">
                    </div>
                    <div class="form-group">
                        <label for="isic_code">Código ISIC <small style="font-weight:400;color:#9ca3af;">(opcional)</small></label>
                        <input type="text" id="isic_code" name="isic_code" maxlength="20"
                               value="<?php echo fieldVal('isic_code', $industry); ?>"
                               placeholder="Ej: C1011">
                    </div>
                    <div class="form-group span2">
                        <label for="certifications">Certificaciones <small style="font-weight:400;color:#9ca3af;">(separadas por coma)</small></label>
                        <input type="text" id="certifications" name="certifications" maxlength="1000"
                               value="<?php echo fieldVal('certifications', $industry); ?>"
                               placeholder="ISO 9001, ISO 14001, FSSC 22000…">
                    </div>
                </div>

                <!-- ── SECCIÓN 5: Vínculos ──────────────────── -->
                <div class="section-title">🔗 Vínculos (opcional)</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="business_id">Negocio relacionado</label>
                        <select id="business_id" name="business_id">
                            <option value="">— Ninguno —</option>
                            <?php
                            $curBiz = (int)fieldVal('business_id', $industry, 0);
                            foreach ($businesses as $b):
                            ?>
                            <option value="<?php echo (int)$b['id']; ?>" <?php echo ($curBiz === (int)$b['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($b['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="brand_id">Marca relacionada</label>
                        <select id="brand_id" name="brand_id">
                            <option value="">— Ninguna —</option>
                            <?php
                            $curBrand = (int)fieldVal('brand_id', $industry, 0);
                            foreach ($brands as $br):
                            ?>
                            <option value="<?php echo (int)$br['id']; ?>" <?php echo ($curBrand === (int)$br['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($br['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- ── ACTIONS ───────────────────────────────── -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editing ? '💾 Guardar cambios' : '🏭 Crear industria'; ?>
                    </button>
                    <a href="/industrias" class="btn btn-secondary">Cancelar</a>

                    <?php if ($editing): ?>
                        <div class="btn-delete-wrap">
                            <button type="submit" name="delete_industry" value="1"
                                    class="btn btn-danger"
                                    onclick="return confirm('¿Eliminar esta industria? Esta acción no se puede deshacer.');">
                                🗑️ Eliminar industria
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            </form>
        </div><!-- /form-card-body -->
    </div><!-- /form-card -->
</main>

</body>
</html>
