<?php
/**
 * views/brand/form.php
 * Formulario unificado de Marcas — Nueva / Editar
 * Tabla: brands
 */
session_start();
ini_set('display_errors', 0);
error_reporting(0);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

setSecurityHeaders();

$userId   = (int)$_SESSION['user_id'];
$isAdmin  = isAdmin();
$brandId  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$editing  = $brandId !== null;
$brand    = null;
$message  = '';
$msgType  = '';

// ── Cargar datos si es edición ────────────────────────────────────────────────
if ($editing) {
    try {
        $db   = getDbConnection();
        if (!$isAdmin && !canManageBrand($userId, $brandId)) {
            header('Location: /marcas');
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM brands WHERE id = ?");
        $stmt->execute([$brandId]);
        $brand = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$brand) { header('Location: /marcas'); exit; }
    } catch (Exception $e) {
        error_log('Error obteniendo marca: ' . $e->getMessage());
    }
}

// ── Detectar OG cover existente ───────────────────────────────────────────────
$ogCoverPublic = null;
if ($editing) {
    foreach (['jpg','jpeg','png','webp'] as $_ext) {
        $c = __DIR__ . '/../../uploads/brands/' . $brandId . '/og_cover.' . $_ext;
        if (file_exists($c)) { $ogCoverPublic = '/uploads/brands/' . $brandId . '/og_cover.' . $_ext; break; }
    }
}
$hasOg = $ogCoverPublic !== null;

function isMissingMapitaColumnErrorBrand(PDOException $e): bool {
    $sqlState = $e->getCode();
    $driverCode = (int)($e->errorInfo[1] ?? 0);
    if ($sqlState === '42S22' || $driverCode === 1054) return true;
    return stripos($e->getMessage(), 'mapita_id') !== false;
}

// ── Procesar POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $nombre               = trim($_POST['nombre']               ?? '');
    $rubro                = trim($_POST['rubro']                ?? '');
    $website              = trim($_POST['website']              ?? '');
    $ubicacion            = trim($_POST['ubicacion']            ?? '');
    $clase_principal      = trim($_POST['clase_principal']      ?? '');
    $founded_year         = $_POST['founded_year'] ? (int)$_POST['founded_year'] : null;
    $description          = trim($_POST['description']          ?? '');
    $extended_description = trim($_POST['extended_description'] ?? '');
    $annual_revenue       = trim($_POST['annual_revenue']       ?? '');
    $valor_activo         = trim($_POST['valor_activo']         ?? '');
    $nivel_proteccion     = trim($_POST['nivel_proteccion']     ?? '');
    $riesgo_oposicion     = trim($_POST['riesgo_oposicion']     ?? '');
    $lat                  = $_POST['lat']  !== '' ? (float)$_POST['lat']  : null;
    $lng                  = $_POST['lng']  !== '' ? (float)$_POST['lng']  : null;
    $scope                = isset($_POST['scope'])    ? implode(',', $_POST['scope'])    : null;
    $channels             = isset($_POST['channels']) ? implode(',', $_POST['channels']) : null;
    $tiene_zona               = isset($_POST['tiene_zona'])     ? 1 : 0;
    $zona_radius_km           = (int)($_POST['zona_radius_km']  ?? 10);
    $tiene_licencia           = isset($_POST['tiene_licencia']) ? 1 : 0;
    $es_franquicia            = isset($_POST['es_franquicia'])  ? 1 : 0;
    $zona_exclusiva           = isset($_POST['zona_exclusiva']) ? 1 : 0;
    $zona_excl_radius         = (int)($_POST['zona_exclusiva_radius_km'] ?? 2);
    $visible                  = isset($_POST['visible']) ? 1 : 0;
    // INPI
    $inpi_registrada          = isset($_POST['inpi_registrada']) ? 1 : 0;
    $inpi_numero              = trim($_POST['inpi_numero']              ?? '');
    $inpi_fecha_registro      = $_POST['inpi_fecha_registro']  ?: null;
    $inpi_vencimiento         = $_POST['inpi_vencimiento']     ?: null;
    $inpi_clases_registradas  = trim($_POST['inpi_clases_registradas']  ?? '');
    $inpi_tipo                = trim($_POST['inpi_tipo']                ?? '');
    // Historia y clientela
    $historia_marca           = trim($_POST['historia_marca']           ?? '');
    $target_audience          = trim($_POST['target_audience']          ?? '');
    $propuesta_valor          = trim($_POST['propuesta_valor']          ?? '');
    // Redes sociales
    $instagram                = trim($_POST['instagram'] ?? '');
    $facebook                 = trim($_POST['facebook']  ?? '');
    $tiktok                   = trim($_POST['tiktok']    ?? '');
    $twitter                  = trim($_POST['twitter']   ?? '');
    $linkedin                 = trim($_POST['linkedin']  ?? '');
    $youtube                  = trim($_POST['youtube']   ?? '');
    $whatsapp                 = trim($_POST['whatsapp']  ?? '');
    $mapitaId                 = trim($_POST['mapita_id'] ?? '');

    if (!$nombre || !$rubro) {
        $message = 'El nombre y rubro son obligatorios.';
        $msgType = 'error';
    } else {
        try {
            $db = getDbConnection();

            if ($editing) {
                $stmt = $db->prepare("
                    UPDATE brands SET
                        nombre = ?, rubro = ?, website = ?, ubicacion = ?,
                        clase_principal = ?, founded_year = ?,
                        description = ?, extended_description = ?,
                        annual_revenue = ?, valor_activo = ?,
                        nivel_proteccion = ?, riesgo_oposicion = ?,
                        lat = ?, lng = ?,
                        scope = ?, channels = ?,
                        tiene_zona = ?, zona_radius_km = ?,
                        tiene_licencia = ?, es_franquicia = ?,
                        zona_exclusiva = ?, zona_exclusiva_radius_km = ?,
                        visible = ?,
                        inpi_registrada = ?, inpi_numero = ?,
                        inpi_fecha_registro = ?, inpi_vencimiento = ?,
                        inpi_clases_registradas = ?, inpi_tipo = ?,
                        historia_marca = ?, target_audience = ?,
                        propuesta_valor = ?,
                        instagram = ?, facebook = ?, tiktok = ?,
                        twitter = ?, linkedin = ?, youtube = ?, whatsapp = ?,
                        mapita_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                try {
                    $stmt->execute([
                        $nombre, $rubro, $website ?: null, $ubicacion ?: null,
                        $clase_principal ?: null, $founded_year,
                        $description ?: null, $extended_description ?: null,
                        $annual_revenue ?: null, $valor_activo ?: null,
                        $nivel_proteccion ?: null, $riesgo_oposicion ?: null,
                        $lat, $lng, $scope, $channels,
                        $tiene_zona, $zona_radius_km,
                        $tiene_licencia, $es_franquicia,
                        $zona_exclusiva, $zona_excl_radius, $visible,
                        $inpi_registrada, $inpi_numero ?: null,
                        $inpi_fecha_registro, $inpi_vencimiento,
                        $inpi_clases_registradas ?: null, $inpi_tipo ?: null,
                        $historia_marca ?: null, $target_audience ?: null,
                        $propuesta_valor ?: null,
                        $instagram ?: null, $facebook ?: null, $tiktok ?: null,
                        $twitter ?: null, $linkedin ?: null, $youtube ?: null, $whatsapp ?: null,
                        $mapitaId ?: null,
                        $brandId
                    ]);
                } catch (PDOException $e) {
                    if (!isMissingMapitaColumnErrorBrand($e)) throw $e;
                    $stmt = $db->prepare("
                        UPDATE brands SET
                            nombre = ?, rubro = ?, website = ?, ubicacion = ?,
                            clase_principal = ?, founded_year = ?,
                            description = ?, extended_description = ?,
                            annual_revenue = ?, valor_activo = ?,
                            nivel_proteccion = ?, riesgo_oposicion = ?,
                            lat = ?, lng = ?,
                            scope = ?, channels = ?,
                            tiene_zona = ?, zona_radius_km = ?,
                            tiene_licencia = ?, es_franquicia = ?,
                            zona_exclusiva = ?, zona_exclusiva_radius_km = ?,
                            visible = ?,
                            inpi_registrada = ?, inpi_numero = ?,
                            inpi_fecha_registro = ?, inpi_vencimiento = ?,
                            inpi_clases_registradas = ?, inpi_tipo = ?,
                            historia_marca = ?, target_audience = ?,
                            propuesta_valor = ?,
                            instagram = ?, facebook = ?, tiktok = ?,
                            twitter = ?, linkedin = ?, youtube = ?, whatsapp = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $nombre, $rubro, $website ?: null, $ubicacion ?: null,
                        $clase_principal ?: null, $founded_year,
                        $description ?: null, $extended_description ?: null,
                        $annual_revenue ?: null, $valor_activo ?: null,
                        $nivel_proteccion ?: null, $riesgo_oposicion ?: null,
                        $lat, $lng, $scope, $channels,
                        $tiene_zona, $zona_radius_km,
                        $tiene_licencia, $es_franquicia,
                        $zona_exclusiva, $zona_excl_radius, $visible,
                        $inpi_registrada, $inpi_numero ?: null,
                        $inpi_fecha_registro, $inpi_vencimiento,
                        $inpi_clases_registradas ?: null, $inpi_tipo ?: null,
                        $historia_marca ?: null, $target_audience ?: null,
                        $propuesta_valor ?: null,
                        $instagram ?: null, $facebook ?: null, $tiktok ?: null,
                        $twitter ?: null, $linkedin ?: null, $youtube ?: null, $whatsapp ?: null,
                        $brandId
                    ]);
                }
                // Recargar datos
                $stmt2 = $db->prepare('SELECT * FROM brands WHERE id = ?');
                $stmt2->execute([$brandId]);
                $brand = $stmt2->fetch(PDO::FETCH_ASSOC);
                $message = '✔ Marca actualizada correctamente.';
                $msgType = 'success';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO brands (
                        nombre, rubro, website, ubicacion,
                        clase_principal, founded_year,
                        description, extended_description,
                        annual_revenue, valor_activo,
                        nivel_proteccion, riesgo_oposicion,
                        lat, lng, scope, channels,
                        tiene_zona, zona_radius_km,
                        tiene_licencia, es_franquicia,
                        zona_exclusiva, zona_exclusiva_radius_km,
                        visible, user_id,
                        inpi_registrada, inpi_numero,
                        inpi_fecha_registro, inpi_vencimiento,
                        inpi_clases_registradas, inpi_tipo,
                        historia_marca, target_audience, propuesta_valor,
                        instagram, facebook, tiktok, twitter, linkedin, youtube, whatsapp,
                        mapita_id,
                        created_at
                    ) VALUES (
                        ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
                        ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW()
                    )
                ");
                try {
                    $stmt->execute([
                        $nombre, $rubro, $website ?: null, $ubicacion ?: null,
                        $clase_principal ?: null, $founded_year,
                        $description ?: null, $extended_description ?: null,
                        $annual_revenue ?: null, $valor_activo ?: null,
                        $nivel_proteccion ?: null, $riesgo_oposicion ?: null,
                        $lat, $lng, $scope, $channels,
                        $tiene_zona, $zona_radius_km,
                        $tiene_licencia, $es_franquicia,
                        $zona_exclusiva, $zona_excl_radius,
                        $visible, $userId,
                        $inpi_registrada, $inpi_numero ?: null,
                        $inpi_fecha_registro, $inpi_vencimiento,
                        $inpi_clases_registradas ?: null, $inpi_tipo ?: null,
                        $historia_marca ?: null, $target_audience ?: null, $propuesta_valor ?: null,
                        $instagram ?: null, $facebook ?: null, $tiktok ?: null,
                        $twitter ?: null, $linkedin ?: null, $youtube ?: null, $whatsapp ?: null,
                        $mapitaId ?: null
                    ]);
                } catch (PDOException $e) {
                    if (!isMissingMapitaColumnErrorBrand($e)) throw $e;
                    $stmt = $db->prepare("
                        INSERT INTO brands (
                            nombre, rubro, website, ubicacion,
                            clase_principal, founded_year,
                            description, extended_description,
                            annual_revenue, valor_activo,
                            nivel_proteccion, riesgo_oposicion,
                            lat, lng, scope, channels,
                            tiene_zona, zona_radius_km,
                            tiene_licencia, es_franquicia,
                            zona_exclusiva, zona_exclusiva_radius_km,
                            visible, user_id,
                            inpi_registrada, inpi_numero,
                            inpi_fecha_registro, inpi_vencimiento,
                            inpi_clases_registradas, inpi_tipo,
                            historia_marca, target_audience, propuesta_valor,
                            instagram, facebook, tiktok, twitter, linkedin, youtube, whatsapp,
                            created_at
                        ) VALUES (
                            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,
                            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW()
                        )
                    ");
                    $stmt->execute([
                        $nombre, $rubro, $website ?: null, $ubicacion ?: null,
                        $clase_principal ?: null, $founded_year,
                        $description ?: null, $extended_description ?: null,
                        $annual_revenue ?: null, $valor_activo ?: null,
                        $nivel_proteccion ?: null, $riesgo_oposicion ?: null,
                        $lat, $lng, $scope, $channels,
                        $tiene_zona, $zona_radius_km,
                        $tiene_licencia, $es_franquicia,
                        $zona_exclusiva, $zona_excl_radius,
                        $visible, $userId,
                        $inpi_registrada, $inpi_numero ?: null,
                        $inpi_fecha_registro, $inpi_vencimiento,
                        $inpi_clases_registradas ?: null, $inpi_tipo ?: null,
                        $historia_marca ?: null, $target_audience ?: null, $propuesta_valor ?: null,
                        $instagram ?: null, $facebook ?: null, $tiktok ?: null,
                        $twitter ?: null, $linkedin ?: null, $youtube ?: null, $whatsapp ?: null
                    ]);
                }
                $newId = $db->lastInsertId();
                $message = '✔ Marca creada. Redirigiendo...';
                $msgType = 'success';
                header("Refresh: 2; url=/brand_form?id=$newId");
            }
        } catch (Exception $e) {
            error_log('Error guardando marca: ' . $e->getMessage());
            $message = 'Error al guardar: ' . $e->getMessage();
            $msgType = 'error';
        }
    }
}

// ── Helper ────────────────────────────────────────────────────────────────────
function val($brand, $key, $default = '') {
    return htmlspecialchars($brand[$key] ?? $default);
}
function chk($brand, $key) {
    return !empty($brand[$key]) ? 'checked' : '';
}
function scopeChk($brand, $val) {
    return strpos($brand['scope'] ?? '', $val) !== false ? 'checked' : '';
}
function chanChk($brand, $val) {
    return strpos($brand['channels'] ?? '', $val) !== false ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $editing ? 'Editar — ' . htmlspecialchars($brand['nombre']) : 'Nueva Marca' ?> · Mapita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --brand:   #1B3B6F;
            --accent:  #667eea;
            --teal:    #00bfa5;
            --success: #2ecc71;
            --warn:    #f39c12;
            --danger:  #e74c3c;
            --gray1:   #f5f7ff;
            --gray2:   #e8eaf0;
            --gray3:   #b0b8cc;
            --gray4:   #6c7a8d;
            --text:    #1e2535;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray1);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Top bar ── */
        .topbar {
            position: sticky; top: 0; z-index: 1000; /* por encima de Leaflet (máx 700) */
            background: var(--brand);
            color: white;
            display: flex; align-items: center; gap: 16px;
            padding: 0 24px;
            height: 56px;
            box-shadow: 0 2px 12px rgba(0,0,0,.2);
        }
        .topbar h1 { font-size: 16px; font-weight: 700; flex: 1; }
        .topbar a  { color: rgba(255,255,255,.75); font-size: 13px; text-decoration: none; }
        .topbar a:hover { color: white; }
        .topbar .status-badge {
            font-size: 11px; font-weight: 700; padding: 3px 10px;
            border-radius: 20px; border: 1px solid rgba(255,255,255,.3);
        }

        /* ── Layout ── */
        .layout {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 0;
            max-width: 1100px;
            margin: 24px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            overflow: hidden;
        }

        /* ── Nav lateral ── */
        .sidenav {
            background: var(--brand);
            padding: 20px 0;
            position: sticky;
            top: 56px;
            height: calc(100vh - 80px);
            overflow-y: auto;
        }
        .sidenav a {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 20px;
            color: rgba(255,255,255,.65);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all .15s;
            border-left: 3px solid transparent;
        }
        .sidenav a:hover, .sidenav a.active {
            color: white;
            background: rgba(255,255,255,.08);
            border-left-color: var(--accent);
        }
        .sidenav .nav-sep {
            height: 1px; background: rgba(255,255,255,.1);
            margin: 8px 16px;
        }

        /* ── Contenido del formulario ── */
        .form-body { padding: 32px 36px; }

        /* Mensaje feedback */
        .msg {
            padding: 14px 18px; border-radius: 8px; margin-bottom: 24px;
            font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;
        }
        .msg.success { background: #eafaf1; color: #1a7a47; border-left: 4px solid var(--success); }
        .msg.error   { background: #fdecea; color: #9b1c1c; border-left: 4px solid var(--danger); }
        .deleg-list { display:grid; gap:10px; }
        .deleg-item { border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
        .deleg-item strong { color:#111827; font-size:13px; }
        .deleg-item small { color:var(--gray4); font-size:12px; }
        .deleg-empty { color:var(--gray4); font-size:12px; }
        .deleg-btn { border:1px solid #fca5a5; background:#fff; color:#b91c1c; border-radius:8px; padding:7px 10px; font-size:12px; font-weight:700; cursor:pointer; }
        .deleg-btn:hover { background:#fff1f2; }

        /* Secciones */
        .section {
            margin-bottom: 36px;
            scroll-margin-top: 72px;
        }
        .section-head {
            display: flex; align-items: center; gap: 10px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--gray2);
            margin-bottom: 20px;
        }
        .section-head .icon {
            width: 34px; height: 34px; border-radius: 8px;
            background: var(--accent); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .section-head h2 { font-size: 15px; font-weight: 700; color: var(--brand); }
        .section-head p  { font-size: 12px; color: var(--gray4); margin-top: 2px; }

        /* Grid de campos */
        .fgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .fgrid.col3 { grid-template-columns: 1fr 1fr 1fr; }
        .fgrid.col1 { grid-template-columns: 1fr; }
        .full { grid-column: 1 / -1; }

        .field { display: flex; flex-direction: column; gap: 5px; }
        .field label {
            font-size: 11px; font-weight: 700; color: var(--gray4);
            text-transform: uppercase; letter-spacing: .4px;
        }
        .field label .req { color: var(--danger); }
        .field input, .field select, .field textarea {
            padding: 10px 12px;
            border: 1.5px solid var(--gray2);
            border-radius: 7px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color .15s, box-shadow .15s;
            background: white;
        }
        .field input:focus, .field select:focus, .field textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(102,126,234,.12);
        }
        .field textarea { resize: vertical; min-height: 90px; }
        .field .hint { font-size: 11px; color: var(--gray4); font-style: italic; }

        /* Checkboxes pill */
        .pill-group { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
        .pill {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 13px;
            border: 1.5px solid var(--gray2);
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all .15s;
            user-select: none;
        }
        .pill input { display: none; }
        .pill:hover { border-color: var(--accent); color: var(--accent); }
        .pill.checked { background: var(--accent); border-color: var(--accent); color: white; }

        /* Toggle switch */
        .toggle-row {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px;
            border: 1.5px solid var(--gray2);
            border-radius: 8px;
            cursor: pointer;
        }
        .toggle-row:hover { border-color: var(--accent); }
        .toggle-row.on { border-color: var(--accent); background: #f0f4ff; }
        .toggle-row input[type=checkbox] { display: none; }
        .toggle-dot {
            width: 40px; height: 22px; background: var(--gray3); border-radius: 11px;
            position: relative; transition: background .2s; flex-shrink: 0;
        }
        .toggle-dot::after {
            content: ''; position: absolute; left: 3px; top: 3px;
            width: 16px; height: 16px; border-radius: 50%;
            background: white; transition: left .2s;
        }
        .toggle-row.on .toggle-dot { background: var(--accent); }
        .toggle-row.on .toggle-dot::after { left: 21px; }
        .toggle-label { font-size: 13px; font-weight: 600; flex: 1; }
        .toggle-sub   { font-size: 11px; color: var(--gray4); }

        /* Mapa — isolation:isolate evita que Leaflet tape el header sticky */
        #map-picker { height: 280px; border-radius: 8px; border: 1.5px solid var(--gray2); margin-bottom: 12px; isolation: isolate; }

        /* ── Galería ── */
        .gallery-drop {
            border: 2px dashed var(--accent); border-radius: 10px;
            padding: 28px; text-align: center; cursor: pointer;
            background: rgba(102,126,234,.03); transition: all .2s;
        }
        .gallery-drop:hover, .gallery-drop.over { background: rgba(102,126,234,.08); }
        .gallery-drop p { color: var(--accent); font-weight: 600; margin-top: 8px; font-size: 14px; }
        .gallery-drop small { color: var(--gray4); }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 12px; margin-top: 16px;
        }
        .gallery-item {
            position: relative; border-radius: 8px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        .gallery-item img { width: 100%; height: 100px; object-fit: cover; display: block; }
        .gallery-item .gactions {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.65);
            display: flex; align-items: center; justify-content: center; gap: 6px;
            opacity: 0; transition: opacity .2s;
        }
        .gallery-item:hover .gactions { opacity: 1; }
        .gactions button {
            font-size: 11px; padding: 4px 8px; border: none; border-radius: 4px;
            cursor: pointer; font-weight: 700;
        }
        .g-main { background: var(--success); color: white; }
        .g-del  { background: var(--danger);  color: white; }
        .gallery-item.main-img { border: 3px solid var(--success); }
        .gallery-item.main-img::after {
            content: '⭐'; position: absolute; top: 4px; right: 4px;
            font-size: 14px;
        }

        /* ── OG Section ── */
        .og-wrap {
            background: linear-gradient(135deg, rgba(230,126,34,.05), #fffaf5);
            border: 2px dashed #e67e22; border-radius: 10px; padding: 20px;
        }
        .og-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
            margin-bottom: 14px;
        }
        .og-badge.pers { background: var(--success); color: white; }
        .og-badge.auto { background: var(--gray3); color: white; }
        .og-preview { aspect-ratio: 1200/630; border-radius: 8px; overflow: hidden; background: #111; margin-bottom: 12px; }
        .og-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .og-drop {
            border: 2px dashed #e67e22; border-radius: 8px; padding: 20px;
            text-align: center; cursor: pointer; background: rgba(255,255,255,.8);
            transition: all .2s; margin-bottom: 10px;
        }
        .og-drop:hover, .og-drop.over { background: rgba(230,126,34,.07); }
        .og-drop p { color: #e67e22; font-weight: 600; font-size: 13px; margin: 6px 0 0; }
        .og-drop small { color: var(--gray4); }
        .og-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
        .og-actions button {
            padding: 7px 14px; border: none; border-radius: 6px;
            cursor: pointer; font-weight: 700; font-size: 12px;
        }
        .og-up  { background: #e67e22; color: white; }
        .og-del { background: var(--danger); color: white; }
        .wa-sim { background: #e5ddd5; border-radius: 10px; padding: 10px; max-width: 300px; }
        .wa-bub { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.15); }
        .wa-bub img { width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block; }
        .wa-bub .wa-txt { padding: 8px 10px; }
        .wa-bub .wa-t { font-size: .8em; font-weight: 700; color: #111; }
        .wa-bub .wa-d { font-size: .72em; color: #555; }
        .wa-bub .wa-s { font-size: .68em; color: #888; text-transform: uppercase; }

        /* ── Botones de acción ── */
        .form-actions {
            display: flex; gap: 12px; justify-content: flex-end;
            padding: 20px 36px;
            border-top: 1.5px solid var(--gray2);
            background: var(--gray1);
            position: sticky; bottom: 0;
        }
        .btn {
            padding: 11px 26px; border: none; border-radius: 8px;
            font-weight: 700; font-size: 14px; cursor: pointer;
            transition: all .15s; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-save  { background: var(--accent); color: white; }
        .btn-save:hover  { background: #5568d3; box-shadow: 0 4px 14px rgba(102,126,234,.4); }
        .btn-back  { background: white; color: var(--brand); border: 1.5px solid var(--gray2); }
        .btn-back:hover  { border-color: var(--brand); }
        .btn-danger { background: var(--danger); color: white; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; margin: 0; border-radius: 0; }
            .sidenav { display: none; }
            .form-body { padding: 20px; }
            .fgrid { grid-template-columns: 1fr; }
            .fgrid.col3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Top bar -->
<div class="topbar">
    <a href="/marcas">← Mapita</a>
    <h1>🏷️ <?= $editing ? 'Editar Marca' : 'Nueva Marca' ?></h1>
    <?php if ($editing): ?>
        <span class="status-badge">ID #<?= $brandId ?></span>
    <?php endif; ?>
    <a href="/marcas">Ver mapa</a>
</div>

<form method="post" enctype="multipart/form-data">
<?php echo csrfField(); ?>

<div class="layout">

    <!-- Nav lateral -->
    <nav class="sidenav">
        <a href="#sec-identidad" class="active">🏷️ Identidad</a>
        <a href="#sec-descripcion">📝 Descripción</a>
        <a href="#sec-clasificacion">📋 Clasificación</a>
        <a href="#sec-financiero">💰 Financiero</a>
        <a href="#sec-inpi">⚖️ Legal / INPI</a>
        <a href="#sec-historia">📖 Historia</a>
        <a href="#sec-redes">📱 Redes</a>
        <a href="#sec-proteccion">🛡️ Protección</a>
        <a href="#sec-ubicacion">📍 Ubicación</a>
        <?php if ($editing): ?>
        <div class="nav-sep"></div>
        <a href="#sec-logo">🏷️ Logo / Icono</a>
        <a href="#sec-galeria">🖼️ Galería</a>
        <a href="#sec-og">📲 Foto redes</a>
        <a href="#sec-delegacion">👥 Delegación</a>
        <?php endif; ?>
        <div class="nav-sep"></div>
        <a href="/marcas">← Volver</a>
    </nav>

    <!-- Cuerpo -->
    <div class="form-body">

        <?php if ($message): ?>
        <div class="msg <?= $msgType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- ── IDENTIDAD ── -->
        <div class="section" id="sec-identidad">
            <div class="section-head">
                <div class="icon">🏷️</div>
                <div>
                    <h2>Identidad de la Marca</h2>
                    <p>Datos principales que identifican tu marca</p>
                </div>
            </div>
            <div class="fgrid">
                <div class="field">
                    <label>Nombre <span class="req">*</span></label>
                    <input type="text" name="nombre" required placeholder="Ej: Mapita" value="<?= val($brand,'nombre') ?>">
                </div>
                <div class="field">
                    <label>Rubro / Sector <span class="req">*</span></label>
                    <input type="text" name="rubro" required placeholder="Ej: Tecnología, Moda, Alimentos" value="<?= val($brand,'rubro') ?>">
                </div>
                <div class="field">
                    <label>Sitio Web</label>
                    <input type="url" name="website" placeholder="https://..." value="<?= val($brand,'website') ?>">
                </div>
                <div class="field">
                    <label>Año de Fundación</label>
                    <input type="number" name="founded_year" min="1800" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>" value="<?= val($brand,'founded_year') ?>">
                </div>
                <div class="field">
                    <label>Estado / Visibilidad</label>
                    <select name="visible">
                        <option value="1" <?= ($brand['visible'] ?? 1) == 1 ? 'selected' : '' ?>>✅ Visible en el mapa</option>
                        <option value="0" <?= ($brand['visible'] ?? 1) == 0 ? 'selected' : '' ?>>🔒 Oculta</option>
                    </select>
                </div>
                <div class="field">
                    <label>Ubicación (dirección)</label>
                    <input type="text" name="ubicacion" placeholder="Ej: Av. Corrientes 1234, CABA" value="<?= val($brand,'ubicacion') ?>">
                </div>
                <div class="field">
                    <label>Mapita ID</label>
                    <input type="text" name="mapita_id" maxlength="64" placeholder="Ej: BR-001" value="<?= val($brand,'mapita_id') ?>">
                </div>
            </div>
        </div>

        <!-- ── DESCRIPCIÓN ── -->
        <div class="section" id="sec-descripcion">
            <div class="section-head">
                <div class="icon">📝</div>
                <div>
                    <h2>Descripción</h2>
                    <p>Contá quién es tu marca</p>
                </div>
            </div>
            <div class="fgrid col1">
                <div class="field">
                    <label>Descripción breve</label>
                    <textarea name="description" placeholder="Una línea que define tu marca..."><?= val($brand,'description') ?></textarea>
                    <span class="hint">Aparece en el popup del mapa</span>
                </div>
                <div class="field">
                    <label>Historia / Misión / Visión</label>
                    <textarea name="extended_description" rows="5" placeholder="Historia, misión, valores, logros..."><?= val($brand,'extended_description') ?></textarea>
                </div>
            </div>
        </div>

        <!-- ── CLASIFICACIÓN ── -->
        <div class="section" id="sec-clasificacion">
            <div class="section-head">
                <div class="icon">📋</div>
                <div>
                    <h2>Clasificación y Distribución</h2>
                    <p>Niza, alcance geográfico y canales</p>
                </div>
            </div>
            <div class="fgrid">
                <div class="field">
                    <label>Clase NIZA (1–45)</label>
                    <input type="number" name="clase_principal" min="1" max="45" placeholder="Ej: 25" value="<?= val($brand,'clase_principal') ?>">
                    <span class="hint">Define el color y zona de influencia en el mapa</span>
                </div>
                <div class="field">
                    <label>Nivel de Protección</label>
                    <select name="nivel_proteccion">
                        <option value="">Seleccionar...</option>
                        <?php foreach (['Bajo','Medio','Alto','Registrada','Internacional'] as $np): ?>
                        <option value="<?= $np ?>" <?= ($brand['nivel_proteccion'] ?? '') === $np ? 'selected' : '' ?>><?= $np ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field full">
                    <label>Alcance Geográfico</label>
                    <div class="pill-group" id="scope-pills">
                        <?php foreach (['local'=>'🏘️ Local','regional'=>'🗺️ Regional','nacional'=>'🇦🇷 Nacional','internacional'=>'🌍 Internacional'] as $v=>$l): ?>
                        <label class="pill <?= scopeChk($brand ?? [], $v) ? 'checked' : '' ?>">
                            <input type="checkbox" name="scope[]" value="<?= $v ?>" <?= scopeChk($brand ?? [], $v) ?>>
                            <?= $l ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="field full">
                    <label>Canales de Distribución</label>
                    <div class="pill-group" id="channels-pills">
                        <?php foreach (['tienda_fisica'=>'🏪 Tienda Física','ecommerce'=>'🛒 E-commerce','wholesale'=>'📦 Mayorista','marketplace'=>'🏬 Marketplace','redes_sociales'=>'📱 Redes Sociales','distribuidores'=>'🚚 Distribuidores'] as $v=>$l): ?>
                        <label class="pill <?= chanChk($brand ?? [], $v) ? 'checked' : '' ?>">
                            <input type="checkbox" name="channels[]" value="<?= $v ?>" <?= chanChk($brand ?? [], $v) ?>>
                            <?= $l ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── FINANCIERO ── -->
        <div class="section" id="sec-financiero">
            <div class="section-head">
                <div class="icon">💰</div>
                <div>
                    <h2>Datos Financieros</h2>
                    <p>Información económica de la marca</p>
                </div>
            </div>
            <div class="fgrid">
                <div class="field">
                    <label>Ingresos Anuales Estimados</label>
                    <select name="annual_revenue">
                        <option value="">Seleccionar rango</option>
                        <?php foreach (['0-50k'=>'Menor a $50k','50k-500k'=>'$50k – $500k','500k-1m'=>'$500k – $1M','1m-5m'=>'$1M – $5M','5m+'=>'Mayor a $5M'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($brand['annual_revenue'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Valor del Activo Marcario</label>
                    <input type="text" name="valor_activo" placeholder="Ej: 50000" value="<?= val($brand,'valor_activo') ?>">
                    <span class="hint">Se muestra en el panel del mapa de marcas</span>
                </div>
                <div class="field">
                    <label>Riesgo de Oposición</label>
                    <select name="riesgo_oposicion">
                        <option value="">Seleccionar...</option>
                        <?php foreach (['Muy Bajo','Bajo','Medio','Alto','Muy Alto'] as $ro): ?>
                        <option value="<?= $ro ?>" <?= ($brand['riesgo_oposicion'] ?? '') === $ro ? 'selected' : '' ?>><?= $ro ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ── SITUACIÓN LEGAL INPI ── -->
        <div class="section" id="sec-inpi">
            <div class="section-head">
                <div class="icon" style="background:#003f87;">⚖️</div>
                <div>
                    <h2>Situación Legal — INPI</h2>
                    <p>Registro ante el Instituto Nacional de la Propiedad Industrial</p>
                </div>
            </div>

            <!-- Toggle INPI registrada -->
            <label class="toggle-row <?= chk($brand ?? [], 'inpi_registrada') ? 'on' : '' ?>"
                   onclick="toggleRow(this); toggleInpiFields(this)"
                   style="margin-bottom:16px;">
                <input type="checkbox" name="inpi_registrada" <?= chk($brand ?? [], 'inpi_registrada') ?>>
                <div class="toggle-dot"></div>
                <div style="display:flex;align-items:center;gap:12px;flex:1;">
                    <div>
                        <div class="toggle-label">🏛️ Marca registrada ante INPI</div>
                        <div class="toggle-sub">Activa el formulario de datos del registro</div>
                    </div>
                    <?php if (!empty($brand['inpi_registrada'])): ?>
                    <img src="https://www.argentina.gob.ar/sites/default/files/inpi-logo.png"
                         onerror="this.style.display='none'"
                         alt="INPI" style="height:36px;margin-left:auto;opacity:.85;">
                    <?php endif; ?>
                </div>
            </label>

            <div id="inpiFields" style="<?= empty($brand['inpi_registrada']) ? 'display:none' : '' ?>">
                <!-- Badge oficial -->
                <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;
                            background:linear-gradient(135deg,#003f87,#0058c0);
                            border-radius:10px;color:white;margin-bottom:16px;">
                    <div style="font-size:2em;">🏛️</div>
                    <div>
                        <div style="font-weight:700;font-size:14px;">Marca Registrada</div>
                        <div style="font-size:12px;opacity:.8;">Instituto Nacional de la Propiedad Industrial · Argentina</div>
                    </div>
                    <img src="https://www.argentina.gob.ar/sites/default/files/inpi-logo.png"
                         onerror="this.style.display='none'"
                         alt="INPI" style="height:40px;margin-left:auto;filter:brightness(0) invert(1);opacity:.9;">
                </div>

                <div class="fgrid col3">
                    <div class="field">
                        <label>N° de Resolución / Acta</label>
                        <input type="text" name="inpi_numero" placeholder="Ej: 3.456.789"
                               value="<?= val($brand,'inpi_numero') ?>">
                    </div>
                    <div class="field">
                        <label>Fecha de Registro</label>
                        <input type="date" name="inpi_fecha_registro"
                               value="<?= val($brand,'inpi_fecha_registro') ?>">
                    </div>
                    <div class="field">
                        <label>Vencimiento</label>
                        <input type="date" name="inpi_vencimiento"
                               value="<?= val($brand,'inpi_vencimiento') ?>">
                    </div>
                    <div class="field">
                        <label>Tipo de Marca</label>
                        <select name="inpi_tipo">
                            <option value="">Seleccionar...</option>
                            <?php foreach (['Denominativa','Figurativa','Mixta','Tridimensional','Sonora','Olfativa'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($brand['inpi_tipo'] ?? '') === $t ? 'selected':'' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field full">
                        <label>Clases NIZA Registradas</label>
                        <input type="text" name="inpi_clases_registradas"
                               placeholder="Ej: 25, 35, 41 (separadas por coma)"
                               value="<?= val($brand,'inpi_clases_registradas') ?>">
                        <span class="hint">Clases en las que fue aprobada la marca por el INPI</span>
                    </div>
                </div>

                <!-- Estado del registro -->
                <?php
                $venc = $brand['inpi_vencimiento'] ?? null;
                if ($venc) {
                    $dias = (int)((strtotime($venc) - time()) / 86400);
                    $color = $dias > 365 ? '#2ecc71' : ($dias > 90 ? '#f39c12' : '#e74c3c');
                    $label = $dias > 0 ? "Vence en $dias días" : "VENCIDA hace " . abs($dias) . " días";
                    echo "<div style='margin-top:10px;padding:10px 14px;background:{$color}22;
                          border-left:4px solid {$color};border-radius:6px;font-size:13px;font-weight:600;color:{$color}'>
                          ⏱️ Estado del registro: $label</div>";
                }
                ?>
            </div>
        </div>

        <!-- ── HISTORIA Y CLIENTELA ── -->
        <div class="section" id="sec-historia">
            <div class="section-head">
                <div class="icon" style="background:#8e44ad;">📖</div>
                <div>
                    <h2>Historia y Clientela</h2>
                    <p>Relato de la marca y perfil de su público objetivo</p>
                </div>
            </div>
            <div class="fgrid col1">
                <div class="field">
                    <label>Historia de la Marca</label>
                    <textarea name="historia_marca" rows="5"
                              placeholder="Contá el origen de la marca: cuándo surgió, por qué, quiénes la crearon, qué obstáculos superó, hitos importantes..."><?= val($brand,'historia_marca') ?></textarea>
                </div>
                <div class="field">
                    <label>Perfil de la Clientela / Público Objetivo</label>
                    <textarea name="target_audience" rows="4"
                              placeholder="Describí tu cliente ideal: edad, género, nivel socioeconómico, hábitos, necesidades, motivaciones de compra..."><?= val($brand,'target_audience') ?></textarea>
                </div>
                <div class="field">
                    <label>Propuesta de Valor</label>
                    <textarea name="propuesta_valor" rows="3"
                              placeholder="¿Qué diferencia a tu marca de la competencia? ¿Qué problema resuelve? ¿Por qué te eligen?"><?= val($brand,'propuesta_valor') ?></textarea>
                </div>
            </div>
        </div>

        <!-- ── REDES SOCIALES ── -->
        <div class="section" id="sec-redes">
            <div class="section-head">
                <div class="icon" style="background:#e1306c;">📱</div>
                <div>
                    <h2>Redes Sociales y Presencia Digital</h2>
                    <p>Links directos a todos los canales de la marca</p>
                </div>
            </div>
            <div class="fgrid">
                <div class="field">
                    <label>
                        <span style="color:#e1306c;">●</span> Instagram
                    </label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <span style="padding:10px 12px;background:#f5f5f5;border:1.5px solid var(--gray2);
                              border-radius:7px 0 0 7px;color:#888;font-size:13px;white-space:nowrap;">
                            instagram.com/
                        </span>
                        <input type="text" name="instagram" placeholder="usuario"
                               value="<?= val($brand,'instagram') ?>"
                               style="border-radius:0 7px 7px 0;border-left:none;">
                    </div>
                </div>
                <div class="field">
                    <label><span style="color:#1877f2;">●</span> Facebook</label>
                    <div style="display:flex;gap:0;align-items:center;">
                        <span style="padding:10px 12px;background:#f5f5f5;border:1.5px solid var(--gray2);
                              border-radius:7px 0 0 7px;color:#888;font-size:13px;white-space:nowrap;">
                            facebook.com/
                        </span>
                        <input type="text" name="facebook" placeholder="pagina"
                               value="<?= val($brand,'facebook') ?>"
                               style="border-radius:0 7px 7px 0;border-left:none;">
                    </div>
                </div>
                <div class="field">
                    <label><span style="color:#000;">●</span> TikTok</label>
                    <div style="display:flex;gap:0;align-items:center;">
                        <span style="padding:10px 12px;background:#f5f5f5;border:1.5px solid var(--gray2);
                              border-radius:7px 0 0 7px;color:#888;font-size:13px;white-space:nowrap;">
                            tiktok.com/@
                        </span>
                        <input type="text" name="tiktok" placeholder="usuario"
                               value="<?= val($brand,'tiktok') ?>"
                               style="border-radius:0 7px 7px 0;border-left:none;">
                    </div>
                </div>
                <div class="field">
                    <label><span style="color:#1da1f2;">●</span> Twitter / X</label>
                    <div style="display:flex;gap:0;align-items:center;">
                        <span style="padding:10px 12px;background:#f5f5f5;border:1.5px solid var(--gray2);
                              border-radius:7px 0 0 7px;color:#888;font-size:13px;white-space:nowrap;">
                            x.com/
                        </span>
                        <input type="text" name="twitter" placeholder="usuario"
                               value="<?= val($brand,'twitter') ?>"
                               style="border-radius:0 7px 7px 0;border-left:none;">
                    </div>
                </div>
                <div class="field">
                    <label><span style="color:#0077b5;">●</span> LinkedIn</label>
                    <div style="display:flex;gap:0;align-items:center;">
                        <span style="padding:10px 12px;background:#f5f5f5;border:1.5px solid var(--gray2);
                              border-radius:7px 0 0 7px;color:#888;font-size:13px;white-space:nowrap;">
                            linkedin.com/
                        </span>
                        <input type="text" name="linkedin" placeholder="company/nombre"
                               value="<?= val($brand,'linkedin') ?>"
                               style="border-radius:0 7px 7px 0;border-left:none;">
                    </div>
                </div>
                <div class="field">
                    <label><span style="color:#ff0000;">●</span> YouTube</label>
                    <div style="display:flex;gap:0;align-items:center;">
                        <span style="padding:10px 12px;background:#f5f5f5;border:1.5px solid var(--gray2);
                              border-radius:7px 0 0 7px;color:#888;font-size:13px;white-space:nowrap;">
                            youtube.com/
                        </span>
                        <input type="text" name="youtube" placeholder="@canal"
                               value="<?= val($brand,'youtube') ?>"
                               style="border-radius:0 7px 7px 0;border-left:none;">
                    </div>
                </div>
                <div class="field">
                    <label><span style="color:#25d366;">●</span> WhatsApp Business</label>
                    <input type="text" name="whatsapp" placeholder="+54 9 11 1234-5678"
                           value="<?= val($brand,'whatsapp') ?>">
                    <span class="hint">Número con código de país</span>
                </div>
                <div class="field">
                    <label>🌐 Sitio Web Principal</label>
                    <input type="url" name="website" placeholder="https://..."
                           value="<?= val($brand,'website') ?>">
                </div>
            </div>

            <!-- Preview de links activos -->
            <?php
            $redes = array_filter([
                'instagram' => ['url' => 'https://instagram.com/' . ($brand['instagram'] ?? ''), 'color' => '#e1306c', 'label' => 'Instagram'],
                'facebook'  => ['url' => 'https://facebook.com/'  . ($brand['facebook']  ?? ''), 'color' => '#1877f2', 'label' => 'Facebook'],
                'tiktok'    => ['url' => 'https://tiktok.com/@'   . ($brand['tiktok']    ?? ''), 'color' => '#000',    'label' => 'TikTok'],
                'twitter'   => ['url' => 'https://x.com/'         . ($brand['twitter']   ?? ''), 'color' => '#1da1f2', 'label' => 'X / Twitter'],
                'linkedin'  => ['url' => 'https://linkedin.com/'  . ($brand['linkedin']  ?? ''), 'color' => '#0077b5', 'label' => 'LinkedIn'],
                'youtube'   => ['url' => 'https://youtube.com/'   . ($brand['youtube']   ?? ''), 'color' => '#ff0000', 'label' => 'YouTube'],
            ], fn($r, $k) => !empty($brand[$k] ?? ''), ARRAY_FILTER_USE_BOTH);

            if (!empty($redes) || !empty($brand['whatsapp']) || !empty($brand['website'])): ?>
            <div style="margin-top:12px;padding:14px;background:var(--gray1);border-radius:8px;">
                <p style="font-size:11px;font-weight:700;color:var(--gray4);margin-bottom:10px;text-transform:uppercase;letter-spacing:.4px;">
                    🔗 Accesos directos activos
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach ($redes as $k => $r): ?>
                    <a href="<?= htmlspecialchars($r['url']) ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;
                              background:<?= $r['color'] ?>;color:white;border-radius:20px;
                              text-decoration:none;font-size:12px;font-weight:700;">
                        <?= $r['label'] ?>
                    </a>
                    <?php endforeach; ?>
                    <?php if (!empty($brand['whatsapp'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$brand['whatsapp']) ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;
                              background:#25d366;color:white;border-radius:20px;
                              text-decoration:none;font-size:12px;font-weight:700;">
                        💬 WhatsApp
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($brand['website'])): ?>
                    <a href="<?= htmlspecialchars($brand['website']) ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;
                              background:var(--brand);color:white;border-radius:20px;
                              text-decoration:none;font-size:12px;font-weight:700;">
                        🌐 Web
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── PROTECCIÓN ── -->
        <div class="section" id="sec-proteccion">
            <div class="section-head">
                <div class="icon">🛡️</div>
                <div>
                    <h2>Protección y Condiciones</h2>
                    <p>Zona de influencia, licencias y franquicias</p>
                </div>
            </div>
            <div class="fgrid">
                <label class="toggle-row <?= chk($brand ?? [], 'tiene_zona') ? 'on' : '' ?>" onclick="toggleRow(this)">
                    <input type="checkbox" name="tiene_zona" <?= chk($brand ?? [], 'tiene_zona') ?>>
                    <div class="toggle-dot"></div>
                    <div>
                        <div class="toggle-label">🌐 Zona de Influencia</div>
                        <div class="toggle-sub">Dibuja un círculo en el mapa</div>
                    </div>
                </label>
                <div class="field">
                    <label>Radio de Zona (km)</label>
                    <input type="number" name="zona_radius_km" min="1" max="500" value="<?= val($brand,'zona_radius_km','10') ?>">
                </div>

                <label class="toggle-row <?= chk($brand ?? [], 'tiene_licencia') ? 'on' : '' ?>" onclick="toggleRow(this)">
                    <input type="checkbox" name="tiene_licencia" <?= chk($brand ?? [], 'tiene_licencia') ?>>
                    <div class="toggle-dot"></div>
                    <div>
                        <div class="toggle-label">📜 Con Licencia</div>
                        <div class="toggle-sub">La marca opera bajo licencia</div>
                    </div>
                </label>
                <label class="toggle-row <?= chk($brand ?? [], 'es_franquicia') ? 'on' : '' ?>" onclick="toggleRow(this)">
                    <input type="checkbox" name="es_franquicia" <?= chk($brand ?? [], 'es_franquicia') ?>>
                    <div class="toggle-dot"></div>
                    <div>
                        <div class="toggle-label">🏢 Es Franquicia</div>
                        <div class="toggle-sub">Modelo de expansión por franquicia</div>
                    </div>
                </label>

                <label class="toggle-row <?= chk($brand ?? [], 'zona_exclusiva') ? 'on' : '' ?>" onclick="toggleRow(this)">
                    <input type="checkbox" name="zona_exclusiva" <?= chk($brand ?? [], 'zona_exclusiva') ?>>
                    <div class="toggle-dot"></div>
                    <div>
                        <div class="toggle-label">🎯 Zona Exclusiva</div>
                        <div class="toggle-sub">Territorio de exclusividad comercial</div>
                    </div>
                </label>
                <div class="field">
                    <label>Radio Exclusiva (km)</label>
                    <input type="number" name="zona_exclusiva_radius_km" min="1" max="500" value="<?= val($brand,'zona_exclusiva_radius_km','2') ?>">
                </div>
            </div>
        </div>

        <!-- ── UBICACIÓN ── -->
        <div class="section" id="sec-ubicacion">
            <div class="section-head">
                <div class="icon">📍</div>
                <div>
                    <h2>Ubicación en el Mapa</h2>
                    <p>Hacé clic en el mapa para fijar la posición</p>
                </div>
            </div>
            <div id="map-picker"></div>
            <div class="fgrid">
                <div class="field">
                    <label>Latitud</label>
                    <input type="number" step="any" name="lat" id="lat" placeholder="-34.603700" value="<?= val($brand,'lat') ?>">
                </div>
                <div class="field">
                    <label>Longitud</label>
                    <input type="number" step="any" name="lng" id="lng" placeholder="-58.381600" value="<?= val($brand,'lng') ?>">
                </div>
            </div>
        </div>

        <?php if ($editing):
        // Detectar logo existente
        $logoInfo = null;
        foreach (['png','jpg','jpeg','webp'] as $_le) {
            $lf = __DIR__ . '/../../uploads/brands/' . $brandId . '/logo.' . $_le;
            if (file_exists($lf)) {
                $logoInfo = '/uploads/brands/' . $brandId . '/logo.' . $_le . '?t=' . filemtime($lf);
                break;
            }
        }
        ?>

        <!-- ── LOGO / ICONO DEL MAPA ── -->
        <div class="section" id="sec-logo">
            <div class="section-head">
                <div class="icon" style="background:#1B3B6F;">🏷️</div>
                <div>
                    <h2>Logo / Icono en el Mapa</h2>
                    <p>Esta imagen aparece como icono de la marca en el mapa principal y en el popup. Máx. 200 KB · JPG, PNG o WebP. Si tu imagen es grande, comprimila gratis en <a href="https://squoosh.app" target="_blank">squoosh.app</a>.</p>
                </div>
            </div>
            <div style="padding:20px 24px;">

                <!-- Preview actual -->
                <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;margin-bottom:20px;">
                    <div id="logo-preview-wrap" style="width:80px;height:80px;border-radius:50%;
                         border:3px solid #1B3B6F;box-shadow:0 2px 10px rgba(0,0,0,.2);
                         background:<?= $logoInfo ? "url('" . htmlspecialchars($logoInfo) . "') center/cover no-repeat #f5f7ff" : '#f5f7ff' ?>;
                         display:flex;align-items:center;justify-content:center;font-size:2em;flex-shrink:0;">
                        <?= $logoInfo ? '' : '🏷️' ?>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;color:#1a202c;margin-bottom:4px;">
                            <?= $logoInfo ? '✅ Logo cargado' : '⬜ Sin logo aún' ?>
                        </div>
                        <div style="font-size:12px;color:#6b7280;">
                            <?= $logoInfo
                                ? 'Tu marca aparece con esta foto como icono en el mapa.'
                                : 'Sin logo, la marca aparece como un círculo de color en el mapa.' ?>
                        </div>
                        <!-- Simulación del pin en el mapa -->
                        <div style="margin-top:10px;display:flex;align-items:flex-end;gap:12px;">
                            <div style="text-align:center;">
                                <div style="font-size:10px;color:#9ca3af;margin-bottom:4px;">Con logo</div>
                                <div style="width:44px;height:44px;border-radius:50%;
                                     border:3px solid #1B3B6F;box-shadow:0 2px 8px rgba(0,0,0,.25);
                                     background:<?= $logoInfo ? "url('" . htmlspecialchars($logoInfo) . "') center/cover no-repeat #eef2ff" : '#eef2ff' ?>;
                                     display:flex;align-items:center;justify-content:center;font-size:18px;">
                                    <?= $logoInfo ? '' : '🏷️' ?>
                                </div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:10px;color:#9ca3af;margin-bottom:4px;">Sin logo</div>
                                <div style="width:28px;height:28px;border-radius:50%;
                                     background:#1B3B6F;border:3px solid white;
                                     box-shadow:0 2px 6px rgba(0,0,0,.3);">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zona de carga -->
                <div id="logo-dropzone"
                     style="border:2px dashed #c7d2fe;border-radius:10px;padding:22px 16px;text-align:center;
                            cursor:pointer;transition:all .2s;background:white;"
                     onclick="document.getElementById('logo-file-input').click()"
                     ondragover="event.preventDefault();this.style.borderColor='#1B3B6F';this.style.background='#f0f4ff';"
                     ondragleave="this.style.borderColor='#c7d2fe';this.style.background='white';"
                     ondrop="handleLogoDrop(event)">
                    <div style="font-size:2em;margin-bottom:6px;">🖼️</div>
                    <div style="font-weight:700;color:#374151;margin-bottom:4px;">Subir logo de la marca</div>
                    <div style="font-size:12px;color:#9ca3af;">Arrastrá o hacé clic · JPG, PNG, WebP · máx. 200 KB · 1 logo</div>
                    <div style="font-size:11px;color:#b0b8cc;margin-top:6px;">Recomendado: cuadrado o circular, mínimo 200×200 px</div>
                    <input type="file" id="logo-file-input" accept="image/jpeg,image/png,image/webp"
                           style="display:none;" onchange="uploadBrandLogo(this.files[0])">
                </div>

                <?php if ($logoInfo): ?>
                <div style="margin-top:12px;text-align:right;">
                    <button type="button" onclick="deleteBrandLogo()"
                            style="padding:7px 16px;border:1.5px solid #e74c3c;background:white;color:#e74c3c;
                                   border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;">
                        🗑️ Quitar logo
                    </button>
                </div>
                <?php endif; ?>

                <div id="logo-msg" style="display:none;margin-top:12px;padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;"></div>
            </div>
        </div>

        <!-- ── GALERÍA ── -->
        <div class="section" id="sec-galeria">
            <div class="section-head">
                <div class="icon">🖼️</div>
                <div>
                    <h2>Galería de Imágenes</h2>
                    <p>Fotos que aparecen en el popup del mapa · máx. 2 imágenes · 200 KB c/u</p>
                </div>
            </div>
            <div class="gallery-drop" id="galleryDrop">
                <div style="font-size:2em;">📸</div>
                <p>Arrastrá fotos aquí o hacé clic para elegir</p>
                <small>JPG, PNG, WebP · máximo 200 KB cada una · hasta 2 imágenes</small>
                <input type="file" id="galleryInput" multiple accept="image/*" style="display:none;">
            </div>
            <div class="gallery-grid" id="galleryGrid"></div>
        </div>

        <!-- ── FOTO REDES SOCIALES ── -->
        <div class="section" id="sec-og">
            <div class="section-head">
                <div class="icon">📲</div>
                <div>
                    <h2>Foto para Redes Sociales</h2>
                    <p>Imagen que se muestra al compartir en WhatsApp o Facebook</p>
                </div>
            </div>
            <div class="og-wrap">
                <span class="og-badge <?= $hasOg ? 'pers' : 'auto' ?>" id="ogBadge">
                    <?= $hasOg ? '✔ Imagen personalizada' : '⚙ Imagen auto-generada' ?>
                </span>
                <div class="og-preview">
                    <img id="ogPreview" src="/api/og_image.php?type=brand&brand_id=<?= $brandId ?>&t=<?= time() ?>" alt="Preview OG">
                </div>
                <div class="og-drop" id="ogDrop">
                    <div style="font-size:1.8em;">🖼️</div>
                    <p>Arrastrá o hacé clic para subir imagen OG</p>
                    <small>JPG, PNG o WebP · máximo 200 KB · ideal 1200×630 px</small>
                    <input type="file" id="ogInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
                </div>
                <div class="og-actions">
                    <button type="button" class="og-up" onclick="document.getElementById('ogInput').click()">📤 Subir imagen</button>
                    <button type="button" class="og-del" id="ogDelBtn" onclick="deleteOg()" style="<?= $hasOg ? '' : 'display:none' ?>">🗑️ Eliminar</button>
                </div>
                <p style="font-size:11px;color:var(--gray4);margin-bottom:8px;font-weight:700;">📱 Vista previa WhatsApp:</p>
                <div class="wa-sim">
                    <div class="wa-bub">
                        <img id="ogWaImg" src="/api/og_image.php?type=brand&brand_id=<?= $brandId ?>&t=<?= time() ?>" alt="WA preview">
                        <div class="wa-txt">
                            <p class="wa-t"><?= val($brand,'nombre') ?> — Marca en Mapita</p>
                            <p class="wa-d">Conocé la marca<?= $brand['rubro'] ? ' · ' . htmlspecialchars($brand['rubro']) : '' ?></p>
                            <p class="wa-s">mapita.com.ar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section" id="sec-delegacion">
            <div class="section-head">
                <div class="icon">👥</div>
                <div>
                    <h2>Delegación</h2>
                    <p>Administradores delegados (nivel A/admin)</p>
                </div>
            </div>
            <p class="hint" style="margin:0 0 12px;">Ingresá username o email del destinatario y confirmá con tu password para delegar o revocar.</p>
            <div id="brand-deleg-msg" class="msg success" style="display:none;margin-bottom:12px;"></div>
            <div class="fgrid">
                <div class="field">
                    <label for="brand-delegate-query">Username o email del destinatario</label>
                    <input type="text" id="brand-delegate-query" maxlength="120" placeholder="ej: usuario o mail@dominio.com">
                </div>
                <div class="field">
                    <label for="brand-delegate-password">Tu password de confirmación</label>
                    <input type="password" id="brand-delegate-password" maxlength="255" autocomplete="current-password" placeholder="••••••••">
                </div>
            </div>
            <div style="margin-top:12px;">
                <button type="button" class="btn btn-save" onclick="delegateBrandAdmin()">Delegar</button>
            </div>
            <div class="hr"></div>
            <div id="brand-deleg-empty" class="deleg-empty">Cargando delegados…</div>
            <div id="brand-deleg-list" class="deleg-list"></div>
        </div>

        <?php endif; ?>

    </div><!-- /form-body -->
</div><!-- /layout -->

<!-- Botones sticky -->
<div class="form-actions">
    <a href="/marcas"><button type="button" class="btn btn-back">← Volver al mapa</button></a>
    <?php if ($editing): ?>
    <a href="/brand_detail?id=<?= $brandId ?>"><button type="button" class="btn btn-back">👁️ Ver detalle</button></a>
    <?php endif; ?>
    <button type="submit" class="btn btn-save">💾 Guardar Marca</button>
</div>

</form>

<script>
// ── Mapa de ubicación ─────────────────────────────────────────────────────────
const initLat = <?= ($brand['lat'] ?? null) ? (float)$brand['lat'] : -34.6037 ?>;
const initLng = <?= ($brand['lng'] ?? null) ? (float)$brand['lng'] : -58.3816 ?>;
const hasCoords = <?= ($brand['lat'] ?? null) ? 'true' : 'false' ?>;

const mapPicker = L.map('map-picker').setView([initLat, initLng], hasCoords ? 14 : 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(mapPicker);

let mapMarker = hasCoords ? L.marker([initLat, initLng]).addTo(mapPicker) : null;

mapPicker.on('click', e => {
    document.getElementById('lat').value = e.latlng.lat.toFixed(6);
    document.getElementById('lng').value = e.latlng.lng.toFixed(6);
    if (mapMarker) mapPicker.removeLayer(mapMarker);
    mapMarker = L.marker(e.latlng).addTo(mapPicker);
});

// ── Toggle INPI fields ────────────────────────────────────────────────────────
function toggleInpiFields(row) {
    const cb = row.querySelector('input[type=checkbox]');
    document.getElementById('inpiFields').style.display = cb.checked ? '' : 'none';
}

// ── Pills (checkboxes) ────────────────────────────────────────────────────────
document.querySelectorAll('.pill').forEach(pill => {
    const cb = pill.querySelector('input[type=checkbox]');
    pill.addEventListener('click', () => {
        cb.checked = !cb.checked;
        pill.classList.toggle('checked', cb.checked);
    });
});

// ── Toggle switches ───────────────────────────────────────────────────────────
function toggleRow(row) {
    const cb = row.querySelector('input[type=checkbox]');
    cb.checked = !cb.checked;
    row.classList.toggle('on', cb.checked);
}

// ── Nav activo al scroll ──────────────────────────────────────────────────────
const sections = document.querySelectorAll('.section');
const navLinks = document.querySelectorAll('.sidenav a');
window.addEventListener('scroll', () => {
    let cur = '';
    sections.forEach(s => { if (window.scrollY >= s.offsetTop - 80) cur = s.id; });
    navLinks.forEach(a => {
        a.classList.toggle('active', a.getAttribute('href') === '#' + cur);
    });
}, { passive: true });

<?php if ($editing): ?>
const brandId = <?= $brandId ?>;

// ── Logo del mapa ─────────────────────────────────────────────────────────────
function logoMsg(text, ok) {
    const el = document.getElementById('logo-msg');
    if (!el) return;
    el.style.display    = 'block';
    el.style.background = ok ? '#d1fae5' : '#fee2e2';
    el.style.color      = ok ? '#065f46' : '#991b1b';
    el.textContent      = text;
    setTimeout(() => el.style.display = 'none', 4500);
}

function uploadBrandLogo(file) {
    if (!file) return;

    // Validación del lado del cliente: máx 200 KB
    const MAX_BYTES = 200 * 1024;
    if (file.size > MAX_BYTES) {
        const kb = Math.round(file.size / 1024);
        logoMsg(`❌ Tu archivo pesa ${kb} KB. El logo del mapa debe pesar máximo 200 KB. Comprimilo gratis en squoosh.app o tinypng.com`, false);
        return;
    }
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) {
        logoMsg('❌ Formato no permitido. Usá JPG, PNG o WebP.', false);
        return;
    }

    const dz = document.getElementById('logo-dropzone');
    if (dz) { dz.style.opacity = '.5'; dz.style.pointerEvents = 'none'; }

    const fd = new FormData();
    fd.append('brand_id', brandId);
    fd.append('action', 'upload');
    fd.append('logo', file);

    fetch('/api/upload_brand_logo.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (dz) { dz.style.opacity = '1'; dz.style.pointerEvents = 'auto'; }
            if (data.success) {
                logoMsg('✅ Logo subido. El icono del mapa se actualizará en segundos.', true);
                // Actualizar previews
                const ts  = '?t=' + Date.now();
                const url = data.url.split('?')[0] + ts;
                const wrap = document.getElementById('logo-preview-wrap');
                if (wrap) {
                    wrap.style.background = `url('${url}') center/cover no-repeat #f5f7ff`;
                    wrap.textContent      = '';
                }
                // Recargar página en 1.5s para mostrar botón "Quitar"
                setTimeout(() => location.reload(), 1800);
            } else {
                logoMsg('❌ ' + data.message, false);
            }
        })
        .catch(() => {
            if (dz) { dz.style.opacity = '1'; dz.style.pointerEvents = 'auto'; }
            logoMsg('❌ Error de conexión.', false);
        });
}

function handleLogoDrop(e) {
    e.preventDefault();
    const dz = document.getElementById('logo-dropzone');
    if (dz) { dz.style.borderColor = '#c7d2fe'; dz.style.background = 'white'; }
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) uploadBrandLogo(file);
}

function deleteBrandLogo() {
    if (!confirm('¿Eliminar el logo? La marca volverá a aparecer como círculo de color en el mapa.')) return;
    fetch('/api/upload_brand_logo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'brand_id=' + brandId + '&action=delete'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            logoMsg('🗑️ Logo eliminado.', true);
            setTimeout(() => location.reload(), 1200);
        }
    });
}

// ── Galería ───────────────────────────────────────────────────────────────────
const galleryDrop  = document.getElementById('galleryDrop');
const galleryInput = document.getElementById('galleryInput');
const galleryGrid  = document.getElementById('galleryGrid');
const MAX_GALLERY_IMAGES = 2;
const MAX_IMAGE_BYTES = 200 * 1024;
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
let currentGalleryCount = 0;
let galleryHasMainImage = false;

['dragenter','dragover','dragleave','drop'].forEach(ev =>
    galleryDrop.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); })
);
['dragenter','dragover'].forEach(ev => galleryDrop.addEventListener(ev, () => galleryDrop.classList.add('over')));
['dragleave','drop'].forEach(ev => galleryDrop.addEventListener(ev, () => galleryDrop.classList.remove('over')));
galleryDrop.addEventListener('drop', e => uploadGallery(e.dataTransfer.files));
galleryDrop.addEventListener('click', () => galleryInput.click());
galleryInput.addEventListener('change', e => { uploadGallery(e.target.files); e.target.value = ''; });

async function uploadGallery(files) {
    const selectedFiles = Array.from(files || []);
    if (!selectedFiles.length) return;

    const imageFiles = selectedFiles.filter(file => file && file.type.startsWith('image/'));
    if (!imageFiles.length) {
        alert('Solo se aceptan archivos de imagen.');
        return;
    }

    const remainingSlots = MAX_GALLERY_IMAGES - currentGalleryCount;
    if (remainingSlots <= 0) {
        alert('Ya alcanzaste el máximo de 2 imágenes en la galería.');
        return;
    }

    if (imageFiles.length > remainingSlots) {
        alert(`Solo podés subir ${remainingSlots} imagen(es) más. Se cargarán las primeras ${remainingSlots}.`);
    }

    const errors = [];
    const validFiles = [];
    imageFiles.slice(0, remainingSlots).forEach(file => {
        if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
            errors.push(`"${file.name}": formato no permitido (solo JPG, PNG o WebP).`);
            return;
        }
        if (file.size > MAX_IMAGE_BYTES) {
            const kb = Math.round(file.size / 1024);
            errors.push(`"${file.name}" pesa ${kb} KB (máximo 200 KB).`);
            return;
        }
        validFiles.push(file);
    });

    if (errors.length) {
        alert(`No se pudieron cargar algunos archivos:\n\n- ${errors.join('\n- ')}`);
    }

    let localCount = currentGalleryCount;
    let localHasMain = galleryHasMainImage;
    let uploadedAny = false;
    const uploadErrors = [];
    for (const file of validFiles) {
        const fd = new FormData();
        fd.append('brand_id', brandId);
        fd.append('imagen', file);
        fd.append('titulo', file.name);
        fd.append('es_principal', localHasMain ? '0' : '1');
        try {
            const r = await fetch('/api/brand-gallery.php?action=upload', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) {
                localCount += 1;
                localHasMain = true;
                currentGalleryCount = localCount;
                galleryHasMainImage = localHasMain;
                uploadedAny = true;
            } else {
                uploadErrors.push(d.message || `No se pudo subir "${file.name}".`);
            }
        } catch (error) {
            console.error('Error subiendo imagen de galería:', error);
            uploadErrors.push(`Error de red al subir "${file.name}".`);
        }
    }

    if (uploadedAny) {
        loadGallery();
    }
    if (uploadErrors.length) {
        alert(`Algunas imágenes no se pudieron subir:\n\n- ${uploadErrors.join('\n- ')}`);
    }
}

function loadGallery() {
    fetch('/api/brand-gallery.php?brand_id=' + brandId)
        .then(r => r.json())
        .then(d => {
            galleryGrid.innerHTML = '';
            if (!d.success || !d.data?.length) {
                currentGalleryCount = 0;
                galleryHasMainImage = false;
                galleryGrid.innerHTML = '<p style="color:var(--gray4);font-size:13px;">Sin imágenes aún.</p>';
                return;
            }
            currentGalleryCount = d.data.length;
            galleryHasMainImage = d.data.some(img => Number(img.es_principal) === 1 || img.es_principal === true);
            d.data.forEach(img => {
                const div = document.createElement('div');
                div.className = 'gallery-item' + (img.es_principal ? ' main-img' : '');
                div.innerHTML = `
                    <img src="/uploads/brands/${img.filename}" alt="${img.titulo||''}">
                    <div class="gactions">
                        ${!img.es_principal ? `<button class="g-main" onclick="setMain(${img.id})">⭐</button>` : ''}
                        <button class="g-del" onclick="delImg(${img.id})">🗑️</button>
                    </div>`;
                galleryGrid.appendChild(div);
            });
        });
}

window.setMain = id => {
    fetch('/api/brand-gallery.php?action=set-main', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `image_id=${id}&brand_id=${brandId}`
    }).then(r=>r.json()).then(d => { if (d.success) loadGallery(); });
};
window.delImg = id => {
    if (!confirm('¿Eliminar esta imagen?')) return;
    fetch('/api/brand-gallery.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `image_id=${id}&brand_id=${brandId}`
    }).then(r=>r.json()).then(d => { if (d.success) loadGallery(); });
};

loadGallery();

// ── OG Cover ──────────────────────────────────────────────────────────────────
const ogDrop    = document.getElementById('ogDrop');
const ogInput   = document.getElementById('ogInput');
const ogPreview = document.getElementById('ogPreview');
const ogWaImg   = document.getElementById('ogWaImg');
const ogDelBtn  = document.getElementById('ogDelBtn');
const ogBadge   = document.getElementById('ogBadge');

['dragenter','dragover','dragleave','drop'].forEach(ev =>
    ogDrop.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); })
);
['dragenter','dragover'].forEach(ev => ogDrop.addEventListener(ev, () => ogDrop.classList.add('over')));
['dragleave','drop'].forEach(ev => ogDrop.addEventListener(ev, () => ogDrop.classList.remove('over')));
ogDrop.addEventListener('drop', e => { if (e.dataTransfer.files[0]) uploadOg(e.dataTransfer.files[0]); });
ogDrop.addEventListener('click', () => ogInput.click());
ogInput.addEventListener('change', e => { if (e.target.files[0]) uploadOg(e.target.files[0]); e.target.value = ''; });

function refreshOg() {
    const src = `/api/og_image.php?type=brand&brand_id=${brandId}&t=${Date.now()}`;
    ogPreview.src = src;
    ogWaImg.src   = src;
}

function uploadOg(file) {
    if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
        alert('Formato no permitido. Usá JPG, PNG o WebP.');
        return;
    }
    if (file.size > MAX_IMAGE_BYTES) {
        const kb = Math.round(file.size / 1024);
        alert(`Tu imagen pesa ${kb} KB. La foto para redes debe pesar máximo 200 KB.`);
        return;
    }
    const fd = new FormData();
    fd.append('brand_id', brandId);
    fd.append('og_photo', file);
    fd.append('action', 'upload');
    ogDrop.innerHTML = '<p style="color:#e67e22;font-weight:700;">⏳ Subiendo…</p>';
    fetch('/api/upload_og_photo.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            ogDrop.innerHTML = '<div style="font-size:1.8em;">🖼️</div><p>Arrastrá o hacé clic para subir imagen OG</p><small>JPG, PNG o WebP · máximo 200 KB · ideal 1200×630 px</small><input type="file" id="ogInput" accept="image/jpeg,image/png,image/webp" style="display:none;">';
            document.getElementById('ogInput').addEventListener('change', e => { if (e.target.files[0]) uploadOg(e.target.files[0]); e.target.value = ''; });
            ogDrop.onclick = () => document.getElementById('ogInput').click();
            if (d.success) {
                refreshOg();
                ogDelBtn.style.display = '';
                ogBadge.className = 'og-badge pers';
                ogBadge.textContent = '✔ Imagen personalizada';
            } else { alert(d.message || 'Error al subir'); }
        });
}

window.deleteOg = () => {
    if (!confirm('¿Eliminar la foto personalizada?')) return;
    fetch('/api/upload_og_photo.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `brand_id=${brandId}&action=delete`
    }).then(r=>r.json()).then(d => {
        if (d.success) {
            refreshOg();
            ogDelBtn.style.display = 'none';
            ogBadge.className = 'og-badge auto';
            ogBadge.textContent = '⚙ Imagen auto-generada';
        }
    });
};

function delegationCsrfToken() {
    const field = document.querySelector('input[name="csrf_token"]');
    return field ? field.value : '';
}

function brandDelegMsg(text, ok) {
    const el = document.getElementById('brand-deleg-msg');
    if (!el) return;
    el.style.display = 'block';
    el.className = 'msg ' + (ok ? 'success' : 'error');
    el.textContent = (ok ? '✅ ' : '❌ ') + text;
}

function brandDelegPassword() {
    const field = document.getElementById('brand-delegate-password');
    const value = (field?.value || '').trim();
    if (!value) {
        brandDelegMsg('Debés ingresar tu password para confirmar.', false);
        return null;
    }
    return value;
}

async function lookupDelegateUser(query) {
    const r = await fetch('/api/users/lookup.php?query=' + encodeURIComponent(query));
    const data = await r.json();
    if (!r.ok || !data.success || !data.data?.user) {
        throw new Error(data.message || 'No se pudo encontrar el usuario.');
    }
    return data.data.user;
}

function renderBrandDelegations(items) {
    const list = document.getElementById('brand-deleg-list');
    const empty = document.getElementById('brand-deleg-empty');
    if (!list || !empty) return;

    list.innerHTML = '';
    if (!items.length) {
        empty.style.display = '';
        empty.textContent = 'No hay delegados administrativos.';
        return;
    }

    empty.style.display = 'none';
    items.forEach(item => {
        const row = document.createElement('div');
        row.className = 'deleg-item';

        const meta = document.createElement('div');
        const username = document.createElement('strong');
        username.textContent = item.username || ('Usuario #' + item.user_id);
        const email = document.createElement('small');
        email.textContent = item.email || '';
        meta.appendChild(username);
        meta.appendChild(document.createElement('br'));
        meta.appendChild(email);

        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'deleg-btn';
        action.textContent = 'Revocar';
        action.onclick = () => revokeBrandDelegation(item.user_id, item.username || item.email || ('#' + item.user_id));

        row.appendChild(meta);
        row.appendChild(action);
        list.appendChild(row);
    });
}

async function loadBrandDelegations() {
    try {
        const r = await fetch('/api/brand_delegations/list.php?brand_id=' + encodeURIComponent(brandId));
        const data = await r.json();
        if (!r.ok || !data.success) {
            throw new Error(data.message || 'No se pudieron cargar las delegaciones.');
        }
        renderBrandDelegations(data.data?.delegations || []);
    } catch (error) {
        renderBrandDelegations([]);
        brandDelegMsg(error.message || 'No se pudieron cargar las delegaciones.', false);
    }
}

async function delegateBrandAdmin() {
    const query = (document.getElementById('brand-delegate-query')?.value || '').trim();
    if (!query) {
        brandDelegMsg('Ingresá username o email.', false);
        return;
    }
    const password = brandDelegPassword();
    if (!password) return;

    try {
        const user = await lookupDelegateUser(query);
        if (Number(user.id) === Number(<?= json_encode($userId) ?>)) {
            brandDelegMsg('No podés delegarte a vos mismo.', false);
            return;
        }

        const payload = new URLSearchParams();
        payload.append('brand_id', String(brandId));
        payload.append('user_id', String(user.id));
        payload.append('password', password);
        payload.append('csrf_token', delegationCsrfToken());

        const r = await fetch('/api/brand_delegations/create.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: payload.toString()
        });
        const data = await r.json();
        if (!r.ok || !data.success) {
            throw new Error(data.message || 'No se pudo delegar.');
        }

        brandDelegMsg(data.message || 'Delegación creada.', true);
        document.getElementById('brand-delegate-query').value = '';
        document.getElementById('brand-delegate-password').value = '';
        await loadBrandDelegations();
    } catch (error) {
        brandDelegMsg(error.message || 'No se pudo delegar.', false);
    }
}

async function revokeBrandDelegation(userId, label) {
    const password = brandDelegPassword();
    if (!password) return;
    if (!confirm('¿Revocar delegación de ' + label + '?')) return;

    try {
        const payload = new URLSearchParams();
        payload.append('brand_id', String(brandId));
        payload.append('user_id', String(userId));
        payload.append('password', password);
        payload.append('csrf_token', delegationCsrfToken());

        const r = await fetch('/api/brand_delegations/revoke.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: payload.toString()
        });
        const data = await r.json();
        if (!r.ok || !data.success) {
            throw new Error(data.message || 'No se pudo revocar.');
        }

        brandDelegMsg(data.message || 'Delegación revocada.', true);
        document.getElementById('brand-delegate-password').value = '';
        await loadBrandDelegations();
    } catch (error) {
        brandDelegMsg(error.message || 'No se pudo revocar.', false);
    }
}

if (typeof brandId !== 'undefined' && brandId) {
    loadBrandDelegations();
}

<?php endif; ?>
</script>
</body>
</html>
