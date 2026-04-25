<?php
session_start();
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../includes/db_helper.php';

// ── Datos del perfil del usuario logueado (para prellenar formulario de postulación) ──
$_sessionUserId    = (int)($_SESSION['user_id'] ?? 0);
$_sessionUserName  = $_SESSION['user_name'] ?? '';
$_profileEmail     = '';
$_profilePhone     = '';
if ($_sessionUserId > 0) {
    try {
        $_pdb = getDbConnection();
        if ($_pdb) {
            $_pst = $_pdb->prepare("SELECT email, phone FROM users WHERE id = ? LIMIT 1");
            $_pst->execute([$_sessionUserId]);
            $_prow = $_pst->fetch(\PDO::FETCH_ASSOC);
            if ($_prow) {
                $_profileEmail = $_prow['email'] ?? '';
                $_profilePhone = $_prow['phone'] ?? '';
            }
        }
    } catch (Throwable $_e) { /* silencioso */ }
}

// ── Idioma de interfaz ────────────────────────────────────────────────────────
// Persistir si viene por GET (el selector JS recarga con ?lang=xx)
if (isset($_GET['lang'])) {
    setUILanguage($_GET['lang']);
}
$_html_lang = getUILanguage();

// ── Open Graph ────────────────────────────────────────────────────────────────
$og_title       = 'MAPITA - Mapa de Marcas y Negocios';
$og_description = 'Descubrí marcas y negocios cerca tuyo.';
$og_type        = 'website';
$_scheme        = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$og_image       = $_scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'mapita.com.ar') . '/img/og-mapita.png';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_html_lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <title>🗺️ Mapita — Mapa de Negocios y Marcas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../../includes/meta_og.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <link rel="stylesheet" href="/css/components-buttons.css">
    <link rel="stylesheet" href="/css/components-cards.css">
    <link rel="stylesheet" href="/css/components-forms.css">
    <link rel="stylesheet" href="/css/popup-redesign.css">
    <link rel="stylesheet" href="/css/brand-popup-premium.css">
    <link rel="stylesheet" href="/css/map-styles.css">
    <link rel="stylesheet" href="/css/wt-panel.css">
    <link rel="stylesheet" href="/css/disponibles.css">
    <link rel="stylesheet" href="/css/consultas-panel.css">
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.2; }
        }

        /* ── LEYENDA DINÁMICA ───────────────────────────────────────── */
        #map-legend-btn {
            position: absolute;
            bottom: 28px;
            right: 10px;
            z-index: 1001;
            background: rgba(255,255,255,0.97);
            border: none;
            border-radius: 10px;
            padding: 7px 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            gap: 6px;
            color: #1B3B6F;
            transition: box-shadow 0.2s, transform 0.15s;
        }
        #map-legend-btn:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.22); transform: translateY(-1px); }
        #map-legend {
            position: absolute;
            bottom: 70px;
            right: 10px;
            z-index: 1001;
            background: rgba(255,255,255,0.97);
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.18);
            max-width: 240px;
            min-width: 190px;
            max-height: 60vh;
            overflow-y: auto;
            display: none;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            animation: legendFadeIn 0.2s ease;
        }
        @keyframes legendFadeIn {
            from { opacity:0; transform:translateY(6px); }
            to   { opacity:1; transform:translateY(0);   }
        }
        #map-legend h4 { margin: 0 0 8px; font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 700; }
        .legend-item { display:flex; align-items:center; gap:8px; padding:3px 0; font-size:12px; color:#374151; }
        .legend-rel-line { width:22px; height:0; flex-shrink:0; }
        hr.legend-divider { border:none; border-top:1px solid #e5e7eb; margin:8px 0; }

        /* ── TEMPORAL BADGES ────────────────────────────────────────── */
        .mapita-temporal-wrapper { position:relative; display:inline-block; }
        .mapita-temporal-badge {
            position: absolute;
            top: -5px;
            right: -6px;
            background: #e74c3c;
            color: white;
            border-radius: 7px;
            padding: 1px 5px;
            font-size: 8px;
            font-weight: 800;
            line-height: 14px;
            white-space: nowrap;
            border: 1.5px solid white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.3);
            pointer-events: none;
            letter-spacing: 0.3px;
        }
        .mapita-temporal-badge.urgent { background:#e74c3c; animation:badgePulse 1.4s infinite; }
        .mapita-temporal-badge.soon   { background:#f39c12; }
        .mapita-temporal-badge.info   { background:#667eea; }
        @keyframes badgePulse {
            0%, 100% { transform:scale(1); opacity:1; }
            50%      { transform:scale(1.12); opacity:0.85; }
        }

        /* ── MINI HOVER CONTEXT PANEL ───────────────────────────────── */
        #map-ctx-panel {
            position: fixed;
            z-index: 1100;
            background: rgba(15,23,42,0.91);
            color: white;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 12px;
            pointer-events: none;
            max-width: 210px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.32);
            transition: opacity 0.15s;
            opacity: 0;
            line-height: 1.5;
        }
        #map-ctx-panel.visible { opacity: 1; }
        #map-ctx-panel .ctx-name { font-weight:700; font-size:13px; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        #map-ctx-panel .ctx-type { font-size:10px; color:rgba(255,255,255,0.6); margin-bottom:4px; }
        #map-ctx-panel .ctx-badge {
            display:inline-block;
            background:rgba(255,255,255,0.14);
            border-radius:5px;
            padding:1px 6px;
            font-size:10px;
            margin:2px 2px 0 0;
        }

        /* Variables locales del mapa — solo las que variables-luxury.css no define */
        :root {
            --success:     #2ecc71;
            --warning:     #f39c12;
            --danger:      #e74c3c;
            --info:        #3498db;
            --light-gray:  #f5f6fa;
            --medium-gray: #d0d5dd;
            --dark-gray:   #6c757d;
            --charcoal:    #2c3e50;
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif; display: flex; flex-direction: row; height: 100vh; background: #fafbfc; }

        /* ── Sidebar (RESPONSIVE) ────────────────────────────── */
        #sidebar {
            width: var(--sidebar-width);
            padding: var(--space-md);
            background: #f4f5f9; /* always light gray — never dark mode override */
            overflow-y: auto;
            border-right: 1px solid #e2e8f0;
            transition: transform var(--transition-base), left var(--transition-base);
            z-index: var(--z-fixed);
        }
        
        #togglePanel {
            display: none;
            position: fixed;
            top: 10px;
            left: 10px;
            padding: 9px 12px;
            z-index: 1001;           /* siempre encima del ver-selector (z:1000) */
            background: var(--primary, #1B3B6F);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 700;
            box-shadow: 0 3px 12px rgba(0,0,0,0.25);
            transition: background 0.2s, transform 0.15s;
            white-space: nowrap;
        }

        #togglePanel:hover {
            background: var(--primary-dark, #0d2247);
            transform: translateY(-1px);
        }
        
        #map {
            flex: 1;
            height: 100%;
            min-height: 0; /* fix flex child height in some browsers */
        }
        
        /* ── RESPONSIVE: Tablet (≤768px) ────────────────────────── */
        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                top: 0; left: 0;
                width: 260px;
                height: 100%;
                transform: translateX(-100%);
                border-right: none;
                box-shadow: 4px 0 24px rgba(0,0,0,0.18);
                z-index: 900;
            }
            #sidebar.active { transform: translateX(0); }

            #togglePanel {
                display: flex;
                align-items: center;
                gap: 5px;
                top: 10px;
                left: 10px;
                padding: 8px 12px;
                font-size: 13px;
                border-radius: 10px;
            }
            /* Ocultar el toggle de apertura cuando el sidebar ya está abierto */
            #sidebar.active ~ #togglePanel,
            body.sidebar-open #togglePanel { opacity: 0; pointer-events: none; }
        }

        /* ── RESPONSIVE: Mobile (≤480px) ────────────────────────── */
        @media (max-width: 480px) {
            #sidebar { width: 75vw; max-width: 260px; }

            #togglePanel {
                top: 10px; left: 10px; bottom: auto;
                padding: 8px 10px;
                font-size: 13px;
                border-radius: 8px;
            }
        }

        /* ── Backdrop (overlay real click-to-close) ───────────────── */
        #sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 850;
            touch-action: none;
        }
        #sidebar-backdrop.active { display: block; }

        /* ── Botón de cierre dentro del sidebar ───────────────────── */
        #sidebar-close-btn {
            display: none;
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            margin-left: 4px;
            border: 1.5px solid #dde2f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            color: #667eea;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            padding: 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            font-size: 16px;
            font-weight: 700;
            line-height: 1;
        }
        #sidebar-close-btn:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
            box-shadow: 0 2px 8px rgba(231,76,60,0.3);
        }
        @media (max-width: 768px) {
            #sidebar-close-btn { display: flex; }
        }

        /* ── Floating selector (REDISEÑADO) ─────────────────────── */
        #ver-selector {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;

            display: flex;
            align-items: center;
            gap: 1px;

            background: rgba(255, 255, 255, 0.98);
            padding: 6px;
            border-radius: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);

            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            cursor: move;
            user-select: none;
            touch-action: none;
            transition: box-shadow 0.3s ease, transform 0.2s ease;
            animation: slideDown 0.3s ease-out;
        }

        #ver-selector:hover {
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.2);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateX(-50%) translateY(-10px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }

        /* ── RESPONSIVE: Tablet y Mobile (≤768px) ───────────────── */
        @media (max-width: 768px) {
            #ver-selector {
                position: fixed;
                top: 10px;
                left: 50%;
                transform: translateX(-50%);
                animation: none;
                width: fit-content;
                padding: 4px 5px;
                gap: 2px;
                cursor: default;
                touch-action: auto;
            }
            /* Ocultar texto, mostrar solo emoji → botones compactos */
            #ver-selector .toggle-btn .btn-label { display: none; }
            #ver-selector .toggle-btn {
                padding: 8px 14px !important;
                font-size: 16px !important;
                flex: none;
                line-height: 1;
            }
            #ver-selector .drag-handle { display: none; }
        }

        #ver-selector button {
            cursor: pointer;
            padding: 10px 14px;
            border: none;
            border-radius: 22px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            transition: all 0.2s ease;
            white-space: nowrap;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        #ver-selector button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        #ver-selector button:active {
            transform: translateY(0);
        }

        #ver-selector .drag-handle {
            padding: 0 6px; color: #bbb; font-size: 15px; cursor: move; line-height: 1;
            transition: color 0.2s;
        }

        #ver-selector:hover .drag-handle {
            color: #999;
        }

        /* ── Toggle Button Styles ──────────────────── */
        #ver-selector .toggle-btn {
            padding: 10px 14px !important;
            border: none;
            border-radius: 22px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            transition: all 0.2s ease;
            white-space: nowrap;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            background: #f0f0f0;
            color: var(--charcoal);
            cursor: pointer;
        }

        #ver-selector .toggle-btn.active {
            background: var(--primary);
            color: white;
            font-weight: 700;
        }

        #ver-selector .toggle-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        #ver-selector .toggle-btn:active {
            transform: translateY(0);
        }

        /* ── Sidebar cards ─────────────────────────── */
        .sidebar-card {
            background: white; border-radius: 10px; padding: 12px 14px;
            margin-bottom: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .sidebar-card-label {
            font-size: 10px; font-weight: 700; color: #999;
            text-transform: uppercase; letter-spacing: 0.8px;
            margin-bottom: 8px; display: block;
        }
        .quickstart-panel {
            border-left: 4px solid #667eea;
            background: linear-gradient(180deg, #ffffff 0%, #f7f9ff 100%);
        }
        .quickstart-panel__title {
            margin: 0 0 8px;
            font-size: 14px;
            color: #1B3B6F;
            font-weight: 800;
        }
        .quickstart-panel__intro {
            margin: 0 0 8px;
            font-size: 12px;
            color: #334155;
            line-height: 1.45;
        }
        .quickstart-panel__list {
            margin: 0;
            padding-left: 18px;
            font-size: 12px;
            color: #334155;
            line-height: 1.5;
        }
        .section-header {
            font-size: 10px; font-weight: 700; color: #999;
            text-transform: uppercase; letter-spacing: 0.8px;
            padding: 8px 4px 4px; margin: 0;
        }
        #stats { font-size: 11px; font-weight: 700; color: #667eea; }

        /* ── List items ────────────────────────────── */
        #lista .negocio, #lista .marca {
            border-radius: 8px; padding: 10px; margin-bottom: 6px;
            border: none; background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
            cursor: pointer; transition: box-shadow 0.15s;
            line-height: 1.45;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        #lista .negocio:hover, #lista .marca:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.14); }
        #lista .marca { background: #f8f0ff; }

        /* ── Responsive: sidebar item spacing on mobile ──────────── */
        @media (max-width: 768px) {
            #lista .negocio, #lista .marca {
                padding: 9px 10px;
                line-height: 1.5;
            }
            #lista .negocio strong, #lista .marca strong {
                display: block;
                font-size: 13px;
                line-height: 1.3;
                margin-bottom: 2px;
                white-space: normal;
            }
            #lista .negocio span, #lista .marca span {
                display: block;
                font-size: 11.5px;
                line-height: 1.35;
                margin-top: 2px;
            }
            #lista .negocio small, #lista .marca small {
                display: block;
                font-size: 11px;
                line-height: 1.3;
                margin-top: 2px;
            }
            /* Sidebar auth buttons: prevent emoji+text wrap */
            .sidebar-card button {
                line-height: 1.35;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            /* Accordion labels: better wrapping */
            .accordion-content label {
                line-height: 1.4;
                padding: 5px 4px;
            }
        }

        /* ── Generic inputs/buttons reset (solo sidebar) ─────────── */
        #sidebar select, #sidebar input[type="text"], #sidebar input[type="range"] {
            width: 100%; padding: 8px; margin-bottom: 10px; font-size: 1em;
        }

        /* (ver-selector responsive ya definido arriba) */

        /* ── Selector MetaData: hide controls on mobile, show notice ─── */
        .selector-mobile-notice {
            display: none; /* hidden on desktop */
        }
        @media (max-width: 768px) {
            #sb-sec-selection .sb-section-body { display: none !important; }
            .selector-mobile-notice {
                display: block;
                padding: 10px 14px;
                font-size: 12px;
                color: #1565c0;
                background: #e3f2fd;
                border-left: 3px solid #00acc1;
                margin: 4px 0 6px;
                border-radius: 0 6px 6px 0;
                line-height: 1.5;
            }
        }
        @media (min-width: 769px) {
            #sb-sec-selection .sb-section-body {
                display: block !important;
                max-height: none !important;
                opacity: 1 !important;
                overflow: visible !important;
            }
        }

        /* ── Popup action buttons ──────────────────── */
        .popup-action {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 7px 11px; border-radius: 6px; font-size: 12px;
            text-decoration: none; color: white !important; border: none; cursor: pointer;
            font-family: sans-serif; line-height: 1; white-space: nowrap;
            font-weight: 600; letter-spacing: 0.01em;
            box-shadow: 0 2px 6px rgba(0,0,0,0.18);
            transition: filter 0.15s, transform 0.15s, box-shadow 0.15s;
        }
        .popup-action:hover {
            filter: brightness(1.12);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.22);
        }
        .popup-action:active { transform: translateY(0); filter: brightness(0.95); }

        /* ── Open/Closed badge ─────────────────────── */
        .badge-open   { background: #d4edda; color: #155724; border-radius: 20px; padding: 2px 8px; font-size: 11px; font-weight: 600; }
        .badge-closed { background: #f8d7da; color: #721c24; border-radius: 20px; padding: 2px 8px; font-size: 11px; font-weight: 600; }

        /* ── Accordion filters (MEJORADO) ──────────────────────── */
        .accordion-item { border-bottom: 1px solid #e8e9f0; }
        .accordion-item:last-child { border-bottom: none; }
        .accordion-btn {
            width: 100%; text-align: left; padding: 12px 14px;
            background: #f8f9fb; border: none; cursor: pointer;
            font-weight: 600; font-size: 12px; text-transform: uppercase;
            color: #555; letter-spacing: 0.5px;
            transition: all 0.2s; display: flex; justify-content: space-between; align-items: center;
            line-height: 1.4;
        }
        .accordion-btn:hover { background: #f0f1f8; }
        .accordion-btn.active { background: #667eea; color: white; }

        .accordion-content {
            padding: 0; max-height: 0; overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            background: #fafbff;
        }
        .accordion-content.active {
            max-height: 500px;
            padding: 14px 14px;
        }

        /* Checkboxes profesionales */
        .accordion-content input[type="checkbox"],
        .accordion-content input[type="radio"] {
            width: 18px;
            height: 18px;
            min-width: 18px;
            min-height: 18px;
            margin-right: 10px;
            margin-top: 1px;
            cursor: pointer;
            accent-color: #667eea;
            flex-shrink: 0;
        }

        /* Labels con mejor espaciado y alineación */
        .accordion-content label {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 9px;
            font-size: 13px;
            color: #374151;
            cursor: pointer;
            padding: 6px 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
            line-height: 1.5;
            user-select: none;
        }
        .accordion-content label:hover {
            background-color: #f0f1f8;
            color: #667eea;
        }
        .accordion-content label:last-child { margin-bottom: 0; }
        .filter-radius-slider {
            width: 100%;
            margin: 10px 0;
            cursor: pointer;
            accent-color: #667eea;
            height: 6px;
        }
        .filter-location-autocomplete {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d0d5dd;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 13px;
            font-family: inherit;
            color: #374151;
            transition: all 0.2s ease;
        }
        .filter-location-autocomplete:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .filter-location-autocomplete::placeholder {
            color: #9ca3af;
        }

        /* ── MAPITA brand typography (shared by toggle + watermark) ── */
        .mapita-wordmark {
            font-family: Arial Black, Arial, sans-serif;
            font-weight: 900;
            letter-spacing: 1.5px;
            font-size: 11px;
        }

        #mapita-map-watermark {
            pointer-events: auto !important;
            cursor: pointer;
            transition: box-shadow 0.2s ease, transform 0.15s ease, background 0.2s ease;
            user-select: none;
        }
        #mapita-map-watermark:hover {
            background: rgba(255,255,255,0.96) !important;
            box-shadow: 0 4px 16px rgba(0,0,0,0.16) !important;
            transform: translateY(-1px);
        }
        #mapita-map-watermark:focus-visible {
            outline: 2px solid #1B3B6F;
            outline-offset: 2px;
        }

        #mapita-home-panel {
            position: fixed;
            bottom: 76px;
            left: 16px;
            z-index: 1002;
            width: min(340px, calc(100vw - 24px));
            max-height: min(70vh, 520px);
            overflow-y: auto;
            display: none;
            border-radius: 12px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.22);
            background: linear-gradient(180deg, #ffffff 0%, #f7f9ff 100%);
            border-left: 4px solid #667eea;
            padding: 12px 14px 14px;
        }
        #mapita-home-panel.is-open { display: block; }
        .mapita-home-panel__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }
        .mapita-home-panel__close {
            margin: 0;
            border: none;
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: #e9ecfb;
            color: #1B3B6F;
            font-weight: 800;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            flex-shrink: 0;
        }
        .mapita-home-panel__close:hover {
            background: #d9dffa;
        }
        #mapita-home-panel .quickstart-panel__list li {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }
        .quickstart-help-btn {
            border: 1px solid #c8d0f2;
            background: #eef2ff;
            color: #1B3B6F;
            width: 20px;
            height: 20px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            flex: 0 0 20px;
            margin-top: 1px;
        }
        .quickstart-help-btn:hover {
            background: #e2e8ff;
        }
        .quickstart-help-btn:focus-visible {
            outline: 2px solid #1B3B6F;
            outline-offset: 2px;
        }
        .quickstart-help-modal {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.45);
            padding: 16px;
        }
        .quickstart-help-modal.is-open {
            display: flex;
        }
        .quickstart-help-modal__dialog {
            width: min(440px, calc(100vw - 24px));
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.25);
            padding: 16px 16px 14px;
            position: relative;
        }
        .quickstart-help-modal__dialog h4 {
            margin: 0 28px 8px 0;
            font-size: 16px;
            color: #1B3B6F;
        }
        .quickstart-help-modal__dialog p {
            margin: 0;
            color: #334155;
            font-size: 13px;
            line-height: 1.5;
        }
        .quickstart-help-modal__close {
            position: absolute;
            top: 10px;
            right: 10px;
            margin: 0;
            border: none;
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: #e9ecfb;
            color: #1B3B6F;
            font-weight: 800;
            cursor: pointer;
            line-height: 1;
            padding: 0;
        }
        .quickstart-help-modal__close:hover {
            background: #d9dffa;
        }
        .quickstart-help-modal__close:focus-visible {
            outline: 2px solid #1B3B6F;
            outline-offset: 2px;
        }
    </style>
</head>
<body>

<!-- Mobile toggle with MAPITA branding -->
<button id="togglePanel" onclick="toggleSidebar()">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="20" height="20"
         style="flex-shrink:0;border-radius:5px;vertical-align:middle;" role="img" aria-label="Mapita logo">
        <defs>
            <linearGradient id="tpBg" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#E8C547"/>
                <stop offset="100%" stop-color="#D4AF37"/>
            </linearGradient>
        </defs>
        <rect width="40" height="40" rx="9" ry="9" fill="rgba(255,255,255,0.2)"/>
        <ellipse cx="20" cy="16.5" rx="8" ry="8" fill="url(#tpBg)"/>
        <path d="M16.2 22 Q20 31 23.8 22" fill="url(#tpBg)"/>
        <circle cx="20" cy="16.5" r="3.2" fill="white"/>
    </svg>
    <span class="mapita-wordmark">MAPITA</span>
</button>

<!-- Floating draggable view selector (IMPROVED: Independent toggles) -->
<div id="ver-selector">
    <span class="drag-handle" title="Mover">⠿</span>
    <button onclick="toggleVer('negocios')" id="sel-negocios" class="toggle-btn active"
            style="border-radius:20px 0 0 20px;">
        🏪 <span class="btn-label">Negocios</span>
    </button>
    <button onclick="toggleVer('marcas')" id="sel-marcas" class="toggle-btn active"
            style="border-radius:0;">
        🏷️ <span class="btn-label">Marcas</span>
    </button>
    <button onclick="toggleFranquiciasFilter()" id="sel-franquicias" class="toggle-btn"
            style="border-radius:0 20px 20px 0;"
            title="Mostrar solo marcas con franquicias disponibles">
        🤝 <span class="btn-label">Franquicias</span>
    </button>
</div>

<!-- Backdrop: overlay real click-to-close (visible solo en móvil/tablet) -->
<div id="sidebar-backdrop" onclick="closeSidebar()" aria-hidden="true"></div>

<!-- Sidebar -->
<div id="sidebar">
    <!-- ── MAPITA Brand Header ──────────────────────────────── -->
    <div class="mapita-brand-header" style="
        display:flex;align-items:center;gap:10px;
        padding:12px 0 14px;margin-bottom:14px;
        border-bottom:2px solid #eef0f8;
        text-decoration:none;cursor:pointer;"
        onclick="window.location.href='/'">
        <!-- Logo mark: Navy square with golden pin -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"
             width="40" height="40" style="flex-shrink:0;border-radius:9px;box-shadow:0 2px 8px rgba(27,59,111,.25);"
             role="img" aria-label="Mapita logo">
            <defs>
                <linearGradient id="sbBg" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%"   stop-color="#1B3B6F"/>
                    <stop offset="100%" stop-color="#2E5FA3"/>
                </linearGradient>
                <linearGradient id="sbPin" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%"   stop-color="#E8C547"/>
                    <stop offset="100%" stop-color="#D4AF37"/>
                </linearGradient>
            </defs>
            <rect width="40" height="40" rx="9" ry="9" fill="url(#sbBg)"/>
            <!-- Pin body -->
            <ellipse cx="20" cy="16.5" rx="8" ry="8" fill="url(#sbPin)"/>
            <!-- Pin tail -->
            <path d="M16.2 22 Q20 31 23.8 22" fill="url(#sbPin)"/>
            <!-- Pin inner circle -->
            <circle cx="20" cy="16.5" r="3.2" fill="#1B3B6F"/>
            <!-- M lettermark -->
            <text x="20" y="20.2" text-anchor="middle"
                  font-size="5" font-weight="900"
                  font-family="Arial Black,Arial,sans-serif"
                  fill="#D4AF37" letter-spacing="-0.3">M</text>
        </svg>
        <!-- Wordmark -->
        <div style="line-height:1.2;min-width:0;">
            <div style="
                font-size:18px;font-weight:900;letter-spacing:2.5px;
                background:linear-gradient(135deg,#1B3B6F 0%,#2E5FA3 55%,#667eea 100%);
                -webkit-background-clip:text;-webkit-text-fill-color:transparent;
                background-clip:text;color:transparent;
                font-family:Arial Black,Arial,sans-serif;
                text-transform:uppercase;line-height:1.1;">MAPITA</div>
            <div style="
                font-size:9.5px;color:#8292aa;letter-spacing:0.6px;
                font-weight:600;text-transform:uppercase;margin-top:1px;
                white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                Negocios y Marcas
            </div>
        </div>
        <button type="button" id="sb-compact-btn" class="sb-compact-btn"
                onclick="event.stopPropagation();toggleAllSbSections(event)"
                title="Colapsar todas las secciones" aria-label="Colapsar/expandir todas las secciones">
            <svg id="sb-all-icon" width="13" height="13" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <line x1="21" y1="10" x2="3" y2="10"/>
                <line x1="21" y1="6"  x2="3" y2="6"/>
                <line x1="21" y1="14" x2="3" y2="14"/>
                <line x1="21" y1="18" x2="3" y2="18"/>
            </svg>
        </button>
        <button type="button" id="sidebar-close-btn"
                onclick="event.stopPropagation();closeSidebar()"
                title="Cerrar panel" aria-label="Cerrar panel lateral">✕</button>
        <!-- Selector de idioma de interfaz -->
        <div style="position:relative;margin-left:4px;">
            <button type="button" id="lang-globe-btn"
                    onclick="event.stopPropagation();toggleLangPicker()"
                    title="Idioma de la interfaz" aria-label="Cambiar idioma de la interfaz"
                    style="background:none;border:1.5px solid rgba(27,59,111,.3);border-radius:8px;
                           cursor:pointer;padding:5px 8px;font-size:14px;line-height:1;color:#1B3B6F;">
                🌐
            </button>
            <div id="lang-picker" style="display:none;position:absolute;right:0;top:calc(100% + 6px);
                 background:white;border:1.5px solid #e2e8f0;border-radius:10px;
                 box-shadow:0 4px 16px rgba(0,0,0,.12);min-width:170px;z-index:9999;overflow:hidden;">
                <div style="padding:8px 12px;font-size:11px;font-weight:700;color:#6b7280;
                             text-transform:uppercase;letter-spacing:.07em;border-bottom:1px solid #f1f5f9;">
                    Idioma de interfaz
                </div>
                <?php foreach (['es'=>'Español','en'=>'English','pt'=>'Português','fr'=>'Français','de'=>'Deutsch','no'=>'Norsk','zh'=>'中文','ar'=>'العربية','it'=>'Italiano','ru'=>'Русский','el'=>'Ελληνικά','tr'=>'Türkçe','ja'=>'日本語','ko'=>'한국어'] as $lc => $lname): ?>
                <button type="button" id="lang-btn-option" data-lang="<?= $lc ?>"
                        onclick="setMapUILang('<?= $lc ?>')"
                        style="display:block;width:100%;text-align:left;background:none;border:none;
                               padding:9px 14px;font-size:13px;cursor:pointer;color:#374151;
                               transition:background .15s;"
                        onmouseover="this.style.background='#f8fafc'"
                        onmouseout="this.style.background='none'">
                    <?= htmlspecialchars($lname) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <input type="text" id="busqueda" placeholder="🔍 Buscar..." oninput="filtrar()"
           style="width:100%;padding:10px 12px;border:1px solid #d0d5dd;border-radius:8px;margin-bottom:12px;font-size:13px;font-family:inherit;color:#374151;transition:all 0.2s ease;"
           onfocus="this.style.borderColor='#667eea';this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
           onblur="this.style.borderColor='#d0d5dd';this.style.boxShadow='none'">

    <select id="tipo" onchange="filtrar()"
            style="width:100%;padding:10px 12px;border:1px solid #d0d5dd;border-radius:8px;margin-bottom:12px;font-size:13px;font-family:inherit;color:#374151;background-color:white;cursor:pointer;transition:all 0.2s ease;"
            onfocus="this.style.borderColor='#667eea';this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
            onblur="this.style.borderColor='#d0d5dd';this.style.boxShadow='none'">
        <option value="">📂 Todos los tipos</option>
        <optgroup label="Gastronomía">
            <option value="restaurante">🍽️ Restaurante</option>
            <option value="cafeteria">☕ Cafetería</option>
            <option value="bar">🍺 Bar / Pub</option>
            <option value="panaderia">🥐 Panadería</option>
            <option value="heladeria">🍦 Heladería</option>
            <option value="pizzeria">🍕 Pizzería</option>
        </optgroup>
        <optgroup label="Comercio">
            <option value="supermercado">🛒 Supermercado</option>
            <option value="comercio">🛍️ Tienda / Local</option>
            <option value="autos_venta">🚗 Autos a la venta</option>
            <option value="motos_venta">🏍️ Motos a la venta</option>
            <option value="indumentaria">👕 Indumentaria</option>
            <option value="verduleria">🥦 Verdulería / Frutería</option>
            <option value="carniceria">🥩 Carnicería</option>
            <option value="pastas">🍝 Fábrica de Pastas</option>
            <option value="ferreteria">🔧 Ferretería</option>
            <option value="electronica">📱 Tecnología</option>
            <option value="muebleria">🛋️ Mueblería</option>
            <option value="floristeria">💐 Floristería</option>
            <option value="libreria">📖 Librería</option>
            <option value="productora_audiovisual">🎥 Productora audiovisual</option>
            <option value="escuela_musicos">🎼 Escuela de músicos</option>
            <option value="taller_artes">🎨 Taller de artes</option>
            <option value="biodecodificacion">🧬 Biodecodificación</option>
            <option value="libreria_cristiana">📚 Librería cristiana</option>
            <option value="kiosco">🏪 Kiosco</option>
            <option value="optica">👓 Óptica</option>
        </optgroup>
        <optgroup label="Salud">
            <option value="farmacia">💊 Farmacia</option>
            <option value="hospital">🏥 Clínica / Hospital</option>
            <option value="medico_pediatra">🧒 Médico Pediatra</option>
            <option value="medico_traumatologo">🦴 Médico Traumatólogo</option>
            <option value="laboratorio">🧪 Laboratorio</option>
            <option value="odontologia">🦷 Odontología</option>
            <option value="psicologo">🧠 Psicología</option>
            <option value="psicopedagogo">📚 Psicopedagogía</option>
            <option value="fonoaudiologo">🗣️ Fonoaudiología</option>
            <option value="grafologo">✍️ Grafología</option>
            <option value="enfermeria">🩺 Enfermería</option>
            <option value="asistencia_ancianos">🧓 Asistencia a Ancianos</option>
            <option value="veterinaria">🐾 Veterinaria</option>
        </optgroup>
        <optgroup label="Belleza & Bienestar">
            <option value="salon_belleza">💇 Peluquería / Salón</option>
            <option value="barberia">💈 Barbería</option>
            <option value="spa">💆 Spa / Estética</option>
            <option value="gimnasio">💪 Gimnasio</option>
            <option value="danza">💃 Danza / Ballet</option>
        </optgroup>
        <optgroup label="Servicios">
            <option value="banco">🏦 Banco / Financiera</option>
            <option value="inmobiliaria">🏠 Inmobiliaria</option>
            <option value="seguros">🛡️ Seguros</option>
            <option value="abogado">⚖️ Estudio Jurídico</option>
            <option value="contador">📊 Contaduría</option>
            <option value="arquitectura">📐 Arquitectura</option>
            <option value="ingenieria">⚙️ Ingeniería</option>
            <option value="ingenieria_civil">🏗️ Ingeniería Civil</option>
            <option value="electricista">💡 Electricista</option>
            <option value="gasista">🔥 Gasista matriculado</option>
            <option value="gas_en_garrafa">🛢️ Gas en garrafa</option>
            <option value="seguridad">🛡️ Seguridad</option>
            <option value="grafica">🖨️ Gráfica</option>
            <option value="astrologo">🔮 Astrólogo</option>
            <option value="zapatero">👞 Zapatero</option>
            <option value="videojuegos">🎮 Videojuegos</option>
            <option value="maestro_particular">📘 Maestro particular</option>
            <option value="alquiler_mobiliario_fiestas">🪑 Alquiler de mobiliario para fiestas</option>
            <option value="propalacion_musica">🔊 Propalación (música)</option>
            <option value="animacion_fiestas">🎉 Animación de fiestas</option>
            <option value="taller">🔩 Taller Mecánico</option>
            <option value="herreria">🔨 Herrería</option>
            <option value="carpinteria">🪵 Carpintería</option>
            <option value="modista">🧵 Modista / Costura</option>
            <option value="construccion">🏗️ Construcción</option>
            <option value="centro_vecinal">🏘️ Centro Vecinal / ONG</option>
            <option value="remate">🔨 Remates / Subastas</option>
        </optgroup>
        <optgroup label="Educación & Turismo">
            <option value="academia">🎓 Academia / Instituto</option>
            <option value="idiomas">🌐 Instituto de Idiomas</option>
            <option value="escuela">🏫 Escuela / Jardín</option>
            <option value="hotel">🏨 Hotel / Alojamiento</option>
            <option value="turismo">✈️ Turismo / Agencia</option>
            <option value="cine">🎬 Cine / Teatro / Arte</option>
        </optgroup>
        <optgroup label="Otros">
            <option value="otros">📍 Otros</option>
        </optgroup>
    </select>

    <!-- ADVANCED FILTERS ACCORDION -->
    <div class="sb-section" id="sb-sec-filters">
        <div class="sb-section-hdr open" onclick="toggleSbSection(this)"
             aria-expanded="true" title="Filtros avanzados">
            <span class="sb-section-hdr-label">
                <span aria-hidden="true">🔍</span> Filtros Avanzados
            </span>
            <svg class="sb-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
        <div class="sb-section-body open">
    <div id="filters-accordion" style="padding:0;">

        <!-- TIPO DE EMPRESA (MOVIDO AL INICIO - MÁS IMPORTANTE) -->
        <div class="accordion-item">
            <button class="accordion-btn" onclick="toggleAccordion(this)">
                🏢 Tipo de Empresa
                <span style="font-size:10px;">▼</span>
            </button>
            <div class="accordion-content">
                <label><input type="checkbox" name="company-type" value="familiar" onchange="filtrar()"> 👨‍👩‍👧 Familiar</label>
                <label><input type="checkbox" name="company-type" value="pyme" onchange="filtrar()"> 🏪 PYME/Mediana</label>
                <label><input type="checkbox" name="company-type" value="grande" onchange="filtrar()"> 🏬 Gran empresa</label>
                <label><input type="checkbox" name="company-type" value="multinacional" onchange="filtrar()"> 🌍 Multinacional</label>
            </div>
        </div>

        <!-- UBICACIÓN -->
        <div class="accordion-item">
            <button class="accordion-btn" onclick="toggleAccordion(this)">
                📍 Ubicación
                <span style="font-size:10px;">▼</span>
            </button>
            <div class="accordion-content">
                <label>
                    <input type="checkbox" id="filter-location-enable" onchange="filtrar()">
                    Mostrar solo dentro de X km desde mí
                </label>
                <input type="range" id="filter-location-radius" class="filter-radius-slider" min="1" max="50" value="10" onchange="filtrar()">
                <div style="text-align:center;font-size:12px;color:#667eea;margin-bottom:10px;font-weight:600;letter-spacing:0.3px;">
                    <span id="filter-location-radius-value">10</span> km
                </div>
                <input type="text" id="filter-location-city" class="filter-location-autocomplete"
                       placeholder="O filtrar por ciudad..." oninput="filtrar()">
            </div>
        </div>

        <!-- HORARIO -->
        <div class="accordion-item">
            <button class="accordion-btn" onclick="toggleAccordion(this)">
                ⏰ Horario
                <span style="font-size:10px;">▼</span>
            </button>
            <div class="accordion-content">
                <label><input type="checkbox" id="filter-open-now" onchange="filtrar()"> Solo abiertos ahora</label>
                <label><input type="checkbox" name="filter-days" value="lunes" onchange="filtrar()"> Lunes a viernes</label>
                <label><input type="checkbox" name="filter-days" value="sabado" onchange="filtrar()"> Abierto sábados</label>
                <label><input type="checkbox" name="filter-days" value="domingo" onchange="filtrar()"> Abierto domingos</label>
            </div>
        </div>

        <!-- PRECIO -->
        <div class="accordion-item">
            <button class="accordion-btn" onclick="toggleAccordion(this)">
                💰 Precio
                <span style="font-size:10px;">▼</span>
            </button>
            <div class="accordion-content">
                <label><input type="checkbox" name="price-range" value="1" onchange="filtrar()"> 💵 $</label>
                <label><input type="checkbox" name="price-range" value="2" onchange="filtrar()"> 💵💵 $$</label>
                <label><input type="checkbox" name="price-range" value="3" onchange="filtrar()"> 💵💵💵 $$$</label>
                <label><input type="checkbox" name="price-range" value="4" onchange="filtrar()"> 💵💵💵💵 $$$$</label>
                <label><input type="checkbox" name="price-range" value="5" onchange="filtrar()"> 💵💵💵💵💵 $$$$$</label>
            </div>
        </div>

        <!-- PROTECCIÓN DE MARCA (marcas solo) -->
        <div class="accordion-item" id="filter-protection-container" style="display:none;">
            <button class="accordion-btn" onclick="toggleAccordion(this)">
                🛡️ Protección
                <span style="font-size:10px;">▼</span>
            </button>
            <div class="accordion-content">
                <label><input type="checkbox" name="protection-level" value="Alta" onchange="filtrar()"> 🟢 Alta</label>
                <label><input type="checkbox" name="protection-level" value="Media" onchange="filtrar()"> 🟡 Media</label>
                <label><input type="checkbox" name="protection-level" value="Baja" onchange="filtrar()"> 🔴 Baja</label>
            </div>
        </div>

        <!-- SECTOR/RUBRO (marcas solo) -->
        <div class="accordion-item" id="filter-sector-container" style="display:none;">
            <button class="accordion-btn" onclick="toggleAccordion(this)">
                📋 Sector
                <span style="font-size:10px;">▼</span>
            </button>
            <div class="accordion-content">
                <input type="text" id="filter-sector-search" class="filter-location-autocomplete"
                       placeholder="Buscar sector..." oninput="filtrar()" style="margin-bottom:8px;">
                <div id="filter-sector-list" style="max-height:200px;overflow-y:auto;">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>
        </div>

        <!-- PAÍS DE LA ENTIDAD -->
        <div class="accordion-item" id="filter-country-container">
            <button class="accordion-btn" onclick="toggleAccordion(this)">
                🌍 País
                <span style="font-size:10px;">▼</span>
            </button>
            <div class="accordion-content">
                <select id="filter-country-code" onchange="filtrar()"
                        style="width:100%;padding:8px 10px;border:1px solid #d0d5dd;border-radius:8px;
                               font-size:12px;background:white;color:#374151;">
                    <option value="">🌍 Todos los países</option>
                    <?php foreach (getCountryOptions() as $regionLabel => $countries): ?>
                    <optgroup label="<?= htmlspecialchars($regionLabel) ?>">
                        <?php foreach ($countries as $cc => $cname): ?>
                        <option value="<?= htmlspecialchars($cc) ?>"><?= htmlspecialchars($cname) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- IDIOMA DE OPERACIÓN -->
        <div class="accordion-item" id="filter-language-container">
            <button class="accordion-btn" onclick="toggleAccordion(this)">
                🗣️ Idioma
                <span style="font-size:10px;">▼</span>
            </button>
            <div class="accordion-content">
                <select id="filter-language-code" onchange="filtrar()"
                        style="width:100%;padding:8px 10px;border:1px solid #d0d5dd;border-radius:8px;
                               font-size:12px;background:white;color:#374151;">
                    <option value="">🗣️ Todos los idiomas</option>
                    <?php foreach (getLanguageOptions() as $lc => $lname): ?>
                    <option value="<?= htmlspecialchars($lc) ?>"><?= htmlspecialchars($lname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

    </div>

    <!-- Zonas Inmobiliarias: solo visible en modo negocios/ambos -->
    <div id="inmuebles-container" style="padding:10px 14px 12px;border-top:1px solid #eef0f8;">
        <label style="display:flex;align-items:center;cursor:pointer;font-size:12px;">
            <input type="checkbox" id="show-inmuebles" onchange="toggleInmuebles()" style="width:auto;margin-right:8px;">
            🏠 Zonas de influencia (inmobiliarias)
        </label>
    </div>
    <!-- Sectores Industriales -->
    <div id="sectores-industriales-container" style="padding:10px 14px 12px;border-top:1px solid #eef0f8;">
        <label style="display:flex;align-items:center;cursor:pointer;font-size:12px;">
            <input type="checkbox" id="show-sectores-industriales" onchange="toggleSectoresIndustriales()" style="width:auto;margin-right:8px;">
            🏭 Sectores Industriales
        </label>
    </div>
        </div><!-- /sb-section-body -->
    </div><!-- /sb-sec-filters -->

    <!-- Utility buttons -->
    <div class="sb-section" id="sb-sec-tools">
        <div class="sb-section-hdr" onclick="toggleSbSection(this)"
             aria-expanded="false" title="Herramientas del mapa">
            <span class="sb-section-hdr-label">
                <span aria-hidden="true">🛠️</span> Herramientas
            </span>
            <svg class="sb-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
        <div class="sb-section-body">
            <div class="sb-section-body-inner">
                <div style="display:flex;gap:5px;">
                    <button onclick="ubicarme()"
                            style="flex:1;padding:10px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;font-size:12px;width:auto;margin:0;">
                        📍 Ubicarme
                    </button>
                    <button onclick="exportarPDF()"
                            style="flex:1;padding:10px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;font-size:12px;width:auto;margin:0;">
                        🧾 PDF
                    </button>
                </div>
                <div style="display:flex;gap:5px;margin-top:5px;">
                    <button id="btn-follow-me" onclick="toggleFollowMe()"
                            aria-pressed="false"
                            style="flex:1;padding:10px;background:#95a5a6;color:white;border:none;border-radius:6px;cursor:pointer;font-size:12px;width:auto;margin:0;">
                        📡 Seguirme
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="sb-section" id="sb-sec-selection">
        <div class="sb-section-hdr" onclick="toggleSbSection(this)"
             aria-expanded="false" title="Selección múltiple de marcadores" style="border-left:4px solid #00d4ff;">
            <span class="sb-section-hdr-label" style="color:#1565c0;">
                <span aria-hidden="true">🧾</span> Selector MetaData
            </span>
            <svg class="sb-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
        <!-- Mensaje visible solo en mobile/tablet -->
        <div class="selector-mobile-notice">
            📱 Para consultas masivas - General y demás usar tablet, notebook o pc, para selección del mapa.
        </div>
        <div class="sb-section-body">
            <div class="sb-section-body-inner">
                <button type="button" onclick="toggleSelectionMode()" id="sel-multi"
                        style="width:100%;padding:10px;border:none;border-radius:8px;background:#00acc1;color:white;cursor:pointer;font-size:12px;font-weight:700;margin-bottom:6px;">
                    <span id="sel-multi-icon">🧾</span> <span id="sel-multi-label">Activar selección</span>
                </button>
                <div id="selection-mode-status" style="font-size:11px;color:#546e7a;">
                    Modo normal. Para salir del modo selección: botón "Salir del modo selección" o tecla S.
                </div>

                <!-- ── CONSULTAS MASIVAS ─────────────────────────────────────── -->
                <!-- Las secciones son siempre visibles — no colapsables        -->

                <!-- Sección: Consultas -->
                <div class="cq-acc-hdr" id="cq-hdr-consultas">
                    <span>── Consultas ───</span>
                </div>
                <div class="cq-acc-body open" id="cq-grp-consultas">
                    <div class="cq-acc-body-inner">
                        <button type="button" class="cq-btn"
                                onclick="startGeoSelect('masiva')"
                                title="Dibujá un área y enviá una consulta a todos los negocios dentro">
                            📣 Consulta Masiva
                        </button>
                        <button type="button" class="cq-btn cq-btn--general"
                                onclick="openConsultaModal('general')"
                                title="Enviá una consulta a servicios específicos habilitados">
                            🏛️ Consulta General
                        </button>
                        <button type="button" class="cq-btn cq-btn--proveedor"
                                onclick="openConsultaModal('global_proveedor')"
                                title="Consultá negocios designados como Proveedor (P) por rubro">
                            📦 Consulta Proveedores (P)
                        </button>
                        <button type="button" class="cq-btn cq-btn--envio"
                                onclick="startGeoSelect('envio')"
                                title="Consultá transportistas en un área geográfica">
                            🚚 Consulta Envío
                        </button>
                    </div>
                </div>

                <!-- Sección: Inmobiliarias -->
                <div class="cq-acc-hdr" id="cq-hdr-inmobiliarias">
                    <span>── Inmobiliarias ───</span>
                </div>
                <div class="cq-acc-body open" id="cq-grp-inmobiliarias">
                    <div class="cq-acc-body-inner">
                        <button type="button" class="cq-btn" id="btn-cerca"
                                onclick="toggleCerca()"
                                title="Muestra inmuebles publicados por inmobiliarias. Solo inmobiliarias pueden publicar inmuebles.">
                            🏘️ CERCA
                        </button>
                    </div>
                </div>

                <!-- Sección: Arte & Cultura -->
                <div class="cq-acc-hdr" id="cq-hdr-arte">
                    <span>── Arte &amp; Cultura ───</span>
                </div>
                <div class="cq-acc-body open" id="cq-grp-arte">
                    <div class="cq-acc-body-inner">
                        <button type="button" class="cq-btn" id="btn-convocar"
                                onclick="abrirConvocar()"
                                title="Convoca artistas/servicios para tu OBRA DE ARTE. Solo titulares de OBRA DE ARTE pueden usarlo.">
                            🎭 CONVOCAR
                        </button>
                    </div>
                </div>
                <!-- ── FIN CONSULTAS MASIVAS ─────────────────────────────── -->
            </div>
            <div id="selection-panel" class="selection-panel" aria-live="polite" style="display:none;">
                <div class="selection-panel__header">
                    <div class="selection-panel__title">Selección del mapa</div>
                    <button type="button" class="selection-panel__close" onclick="dismissSelectionPanel()" title="Salir del modo selección">✕</button>
                </div>
                <div class="selection-panel__intro">Usá este panel para seleccionar elementos y operar en bloque.</div>
                <div id="selection-summary" class="selection-panel__summary">0 elementos</div>
                <div id="selection-step" class="selection-panel__step">Activá el modo selección y elegí marcadores.</div>
                <div id="selection-hint" class="selection-panel__hint" style="display:none;font-size:11px;color:#8cb4e0;margin-top:4px;">Paso 1: hacé click en marcadores o usá Shift + arrastrar para cuadro de selección.</div>
                <div class="selection-panel__actions">
                    <button type="button" onclick="selectAllVisible()" title="Seleccionar todos los marcadores visibles en pantalla">➕ Seleccionar visibles</button>
                    <button type="button" onclick="clearSelection()">🧹 Vaciar selección</button>
                    <button type="button" id="selection-aggregate-btn" onclick="aggregateSelectedSurveys()">📊 Combinar encuestas</button>
                    <button type="button" onclick="exportSelection()" title="Exportar selección como JSON">📤 Exportar JSON</button>
                    <button type="button" id="selection-details-toggle" onclick="toggleSelectionDetails()">📋 Ver detalles</button>
                </div>
                <div id="selection-details" class="selection-panel__details selection-panel__details--hidden"></div>
                <div id="selection-aggregate-result" class="selection-panel__result"></div>
            </div>
        </div>
    </div>

    <!-- Encuestas Activas -->
    <div id="encuestas-container" class="sidebar-card" style="display:none;border-left:4px solid #f39c12;padding:0;overflow:hidden;">
        <div class="sb-mod-hdr open" onclick="toggleSbModule(this)">
            <h3 style="margin:0;font-size:13px;color:#f39c12;font-weight:700;">📊 Encuestas Activas</h3>
            <svg class="sb-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f39c12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="sb-mod-body open">
            <div class="sb-mod-body-inner">
                <div id="encuestas-list" style="max-height:150px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>

    <!-- Eventos Próximos -->
    <div id="eventos-container" class="sidebar-card" style="display:none;border-left:4px solid #e74c3c;padding:0;overflow:hidden;">
        <div class="sb-mod-hdr open" onclick="toggleSbModule(this)">
            <h3 style="margin:0;font-size:13px;color:#e74c3c;font-weight:700;">🎉 Eventos Próximos</h3>
            <svg class="sb-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="sb-mod-body open">
            <div class="sb-mod-body-inner">
                <div id="eventos-list" style="max-height:150px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>

    <!-- Trivias Disponibles -->
    <div id="trivias-container" class="sidebar-card" style="display:none;border-left:4px solid #9b59b6;padding:0;overflow:hidden;">
        <div class="sb-mod-hdr open" onclick="toggleSbModule(this)">
            <h3 style="margin:0;font-size:13px;color:#9b59b6;font-weight:700;">🎯 Trivias Disponibles</h3>
            <svg class="sb-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9b59b6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="sb-mod-body open">
            <div class="sb-mod-body-inner">
                <div id="trivias-list" style="max-height:150px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>

    <div id="noticias-container" class="sidebar-card" style="display:none;border-left:4px solid #667eea;padding:0;overflow:hidden;">
        <div class="sb-mod-hdr open" onclick="toggleSbModule(this)">
            <h3 style="margin:0;font-size:13px;color:#667eea;font-weight:700;">📰 Últimas Noticias</h3>
            <svg class="sb-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="sb-mod-body open">
            <div class="sb-mod-body-inner">
                <div id="noticias-list" style="max-height:150px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>

    <!-- Ofertas Activas -->
    <div id="ofertas-container" class="sidebar-card" style="display:none;border-left:4px solid #e74c3c;padding:0;overflow:hidden;">
        <div class="sb-mod-hdr open" onclick="toggleSbModule(this)">
            <h3 style="margin:0;font-size:13px;color:#e74c3c;font-weight:700;">🏷️ Ofertas Activas</h3>
            <svg class="sb-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="sb-mod-body open">
            <div class="sb-mod-body-inner">
                <div id="ofertas-list" style="max-height:150px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>

    <!-- Transmisiones en Vivo -->
    <div id="transmisiones-container" class="sidebar-card" style="display:none;border-left:4px solid #c0392b;padding:0;overflow:hidden;">
        <div class="sb-mod-hdr open" onclick="toggleSbModule(this)">
            <h3 style="margin:0;font-size:13px;color:#c0392b;font-weight:700;display:flex;align-items:center;gap:5px;">
                📡 <span style="display:inline-block;width:7px;height:7px;background:#c0392b;border-radius:50%;animation:blink 1s infinite;"></span> En Vivo
            </h3>
            <svg class="sb-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="sb-mod-body open">
            <div class="sb-mod-body-inner">
                <div id="transmisiones-list" style="max-height:150px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>

    <?php if (isAdmin()): ?>
    <!-- Admin: Toggle capas del mapa -->
    <div class="sb-section" id="sb-sec-admin">
        <div class="sb-section-hdr" onclick="toggleSbSection(this)"
             aria-expanded="false" title="Administración de visibilidad de capas" style="border-left:4px solid #6f42c1;">
            <span class="sb-section-hdr-label" style="color:#6f42c1;">
                <span aria-hidden="true">🛡️</span> Visibilidad de capas
            </span>
            <svg class="sb-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="sb-section-body">
            <div class="sb-section-body-inner">
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;">
                        <input type="checkbox" id="toggle-eventos" checked onchange="toggleCapa('eventos',this.checked)" style="width:auto;margin:0;">
                        🎉 Eventos
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;">
                        <input type="checkbox" id="toggle-noticias" checked onchange="toggleCapa('noticias',this.checked)" style="width:auto;margin:0;">
                        📰 Noticias
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;">
                        <input type="checkbox" id="toggle-trivias" checked onchange="toggleCapa('trivias',this.checked)" style="width:auto;margin:0;">
                        🎯 Trivias
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;">
                        <input type="checkbox" id="toggle-encuestas" checked onchange="toggleCapa('encuestas',this.checked)" style="width:auto;margin:0;">
                        📊 Encuestas
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;">
                        <input type="checkbox" id="toggle-ofertas" checked onchange="toggleCapa('ofertas',this.checked)" style="width:auto;margin:0;">
                        🏷️ Ofertas
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;">
                        <input type="checkbox" id="toggle-transmisiones" checked onchange="toggleCapa('transmisiones',this.checked)" style="width:auto;margin:0;">
                        📡 Transmisiones
                    </label>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats + Results list -->
    <div class="sb-section" id="sb-sec-results">
        <div class="sb-section-hdr open" onclick="toggleSbSection(this)" aria-expanded="true">
            <span class="sb-section-hdr-label">
                Resultados
            </span>
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:1;min-width:0;overflow:hidden;">
                <span id="stats" style="font-size:11px;font-weight:700;color:#667eea;display:inline-flex;align-items:center;gap:3px;flex-wrap:wrap;min-width:0;"></span>
                <svg class="sb-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>
        <div class="sb-section-body open">
            <div style="display:flex;align-items:center;gap:6px;padding:6px 8px 4px;border-bottom:1px solid #eef0f5;">
                <label id="sb-radius-lbl" style="font-size:11px;color:#555;white-space:nowrap;" for="sb-radius-select">📏 Radio:</label>
                <select id="sb-radius-select" onchange="filtrar()" aria-labelledby="sb-radius-lbl" style="font-size:11px;padding:2px 4px;border:1px solid #d0d5dd;border-radius:4px;flex:1;">
                    <option value="1">1 km</option>
                    <option value="2">2 km</option>
                    <option value="5" selected>5 km</option>
                    <option value="10">10 km</option>
                </select>
            </div>
            <div id="lista" style="max-height:320px;overflow-y:auto;padding:4px 0 8px;"></div>
        </div>
    </div>


    <!-- Auth -->
    <div class="sidebar-card" style="margin-top:8px;">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <button onclick="window.location.href='/login'"
                    style="width:100%;padding:10px;background:#007bff;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:5px;">
                👤 Iniciar Sesión
            </button>
            <button onclick="window.location.href='/register'"
                    style="width:100%;padding:10px;background:#28a745;color:white;border:none;border-radius:6px;cursor:pointer;">
                📝 Registrarse
            </button>
        <?php else: ?>
            <p style="font-size:12px;color:#666;margin:0 0 10px 0;">
                👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </p>
            <button onclick="window.location.href='/mis-negocios'"
                    style="width:100%;padding:10px;background:#007bff;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:5px;">
                🏢 Mis Negocios
            </button>
            <button onclick="window.location.href='/add'"
                    style="width:100%;padding:10px;background:#28a745;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:5px;">
                ➕ Agregar Negocio
            </button>
            <button onclick="window.location.href='/views/wt_preferences.php'"
                    style="width:100%;padding:10px;background:#3d56c9;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:5px;"
                    title="Configurar preferencias del canal WT">
                📻 Preferencias WT
            </button>
            <?php if (isAdmin()): ?>
            <button onclick="window.location.href='/admin'"
                    style="width:100%;padding:10px;background:#6f42c1;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:5px;">
                🛡️ Admin
            </button>
            <?php endif; ?>
            <button onclick="window.location.href='/logout'"
                    style="width:100%;padding:10px;background:#dc3545;color:white;border:none;border-radius:6px;cursor:pointer;">
                🚪 Cerrar Sesión
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- ── Modal CONVOCAR — rediseñado con estética arte/teatro ─────────────── -->
<!-- Mejora UX/UI: paleta teatro, íconos SVG, contraste accesible, form claro -->
<!--
     TODO (backend): Verificar que los siguientes tipos de negocio estén registrados
     en la tabla de tipos de negocios para que la convocatoria pueda notificarlos:
       - musico, cantante, bailarin, actor, actriz, director_artistico, guionista,
         escenografo, fotografo_artistico, productor_artistico, maquillador, pintor,
         poeta, musicalizador, editor_grafico, asistente_artistico
     Si alguno falta, agregar en la lista de tipos (BUSINESS_TYPE_LABELS en map.php
     y en el select de tipo en add_business.php / categorías de admin).
-->
<div id="modal-convocar" class="conv-modal-overlay" style="display:none;">
    <div class="conv-modal" role="dialog" aria-modal="true" aria-labelledby="conv-modal-title">

        <!-- Encabezado temático teatro/arte -->
        <div class="conv-modal-header">
            <!-- SVG: máscaras de teatro (comedia y tragedia) -->
            <div class="conv-modal-header-icon" aria-hidden="true">
                <svg width="38" height="22" viewBox="0 0 76 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Máscara comedia (izquierda) -->
                    <ellipse cx="20" cy="20" rx="16" ry="18" fill="rgba(255,255,255,.22)" stroke="#f0d060" stroke-width="1.5"/>
                    <circle cx="14" cy="17" r="2.5" fill="#f0d060"/>
                    <circle cx="26" cy="17" r="2.5" fill="#f0d060"/>
                    <path d="M13 25 Q20 31 27 25" stroke="#f0d060" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                    <!-- Máscara tragedia (derecha) -->
                    <ellipse cx="56" cy="24" rx="16" ry="18" fill="rgba(255,255,255,.15)" stroke="rgba(240,208,96,.6)" stroke-width="1.5"/>
                    <circle cx="50" cy="21" r="2.5" fill="rgba(240,208,96,.7)"/>
                    <circle cx="62" cy="21" r="2.5" fill="rgba(240,208,96,.7)"/>
                    <path d="M49 32 Q56 26 63 32" stroke="rgba(240,208,96,.7)" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                </svg>
            </div>
            <div class="conv-modal-header-text">
                <h3 id="conv-modal-title">🎭 Lanzar Convocatoria</h3>
                <p class="conv-modal-subtitle">Convoca artistas y servicios para tu proyecto. Se enviará notificación a todos los servicios que coincidan con los roles definidos en tu Obra de Arte.</p>
            </div>
            <button onclick="cerrarConvocar()" class="conv-modal-close" aria-label="Cerrar">✕</button>
        </div>
        <!-- Barra dorada decorativa -->
        <div class="conv-modal-accent-bar"></div>

        <!-- Cuerpo del formulario -->
        <div class="conv-modal-body">
            <!-- Obra de Arte -->
            <div class="conv-field">
                <label for="conv-obra-select">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 20h20M4 20V10l8-7 8 7v10"/></svg>
                    Obra de Arte <span style="color:#c0392b;">*</span>
                </label>
                <select id="conv-obra-select">
                    <option value="">Seleccioná una obra…</option>
                </select>
            </div>

            <!-- Fechas en fila -->
            <div class="conv-dates-row">
                <div class="conv-field" style="margin-bottom:0;">
                    <label for="conv-fecha-inicio">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Fecha inicio <span style="color:#c0392b;">*</span>
                    </label>
                    <input type="date" id="conv-fecha-inicio">
                </div>
                <div class="conv-field" style="margin-bottom:0;">
                    <label for="conv-fecha-fin">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Fecha fin <span style="color:#c0392b;">*</span>
                    </label>
                    <input type="date" id="conv-fecha-fin">
                </div>
            </div>

            <!-- Aviso si no hay obra de arte publicada -->
            <div id="conv-no-obra-notice" class="conv-notice" style="display:none;">
                <strong>⚠️ Sin obras publicadas.</strong> No tenés negocios del tipo <em>OBRA DE ARTE</em> publicados.<br>
                Publicá una obra para poder lanzar convocatorias.
            </div>

            <!-- Feedback de operación -->
            <div id="conv-msg"></div>
        </div>

        <!-- Acciones -->
        <div class="conv-modal-footer">
            <button onclick="cerrarConvocar()" class="conv-btn-cancel">Cancelar</button>
            <button id="conv-btn-enviar" onclick="enviarConvocatoria()" class="conv-btn-send">
                <!-- SVG: máscara de teatro pequeña -->
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96-.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 1.98-3A2.5 2.5 0 0 1 9.5 2Z"/>
                    <path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96-.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-1.98-3A2.5 2.5 0 0 0 14.5 2Z"/>
                </svg>
                Enviar convocatoria
            </button>
        </div>
    </div>
</div>

<div id="map"></div>

<!-- ── Leyenda dinámica del mapa ───────────────────────────────── -->
<button id="map-legend-btn" aria-expanded="false" aria-controls="map-legend" onclick="toggleMapLegend()" title="Mostrar leyenda del mapa">
    🗺️ Leyenda
</button>
<div id="map-legend" role="complementary" aria-label="Leyenda del mapa">
    <h4>Capas activas</h4>
    <div id="legend-layers"></div>
    <hr class="legend-divider">
    <h4>Tipo de relación</h4>
    <div id="legend-relations"></div>
</div>

<!-- ── Panel de contexto flotante (aparece al hacer hover sobre un marcador) ── -->
<div id="map-ctx-panel" aria-hidden="true" aria-live="polite"></div>

<script>
// ─── State ──────────────────────────────────────────────────────────────────────
const IS_ADMIN       = <?= isAdmin() ? 'true' : 'false' ?>;
const SESSION_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
const SESSION_USER_NAME = <?= json_encode($_sessionUserName) ?>;
const SESSION_USER_EMAIL = <?= json_encode($_profileEmail) ?>;
const SESSION_USER_PHONE = <?= json_encode($_profilePhone) ?>;
const MAPITA_LOCALE = document.documentElement.lang || 'es-AR';

// ─── UI_STRINGS: etiquetas multilenguaje del popup y la interfaz ──────────────
const UI_STRINGS = {
    es: {
        lbl_hours: 'Horario', lbl_phone: 'Teléfono', lbl_address: 'Dirección',
        lbl_type: 'Tipo', lbl_specialty: 'Especialidad', lbl_products: 'Productos/Servicios',
        lbl_email: 'Email', lbl_website: 'Sitio web', lbl_open_now: 'Abierto ahora',
        lbl_closed: 'Cerrado', lbl_niza_class: 'Clase Niza', lbl_protection: 'Protección',
        lbl_country: 'País', lbl_language: 'Idioma', lbl_currency: 'Moneda',
        lbl_registry: 'Registro', btn_website: 'Ver sitio web', btn_directions: 'Cómo llegar',
        filter_all_countries: 'Todos los países', filter_all_languages: 'Todos los idiomas',
    },
    en: {
        lbl_hours: 'Hours', lbl_phone: 'Phone', lbl_address: 'Address',
        lbl_type: 'Type', lbl_specialty: 'Specialty', lbl_products: 'Products/Services',
        lbl_email: 'Email', lbl_website: 'Website', lbl_open_now: 'Open now',
        lbl_closed: 'Closed', lbl_niza_class: 'Nice Class', lbl_protection: 'Protection',
        lbl_country: 'Country', lbl_language: 'Language', lbl_currency: 'Currency',
        lbl_registry: 'Registry', btn_website: 'Visit website', btn_directions: 'Get directions',
        filter_all_countries: 'All countries', filter_all_languages: 'All languages',
    },
    pt: {
        lbl_hours: 'Horário', lbl_phone: 'Telefone', lbl_address: 'Endereço',
        lbl_type: 'Tipo', lbl_specialty: 'Especialidade', lbl_products: 'Produtos/Serviços',
        lbl_email: 'E-mail', lbl_website: 'Site', lbl_open_now: 'Aberto agora',
        lbl_closed: 'Fechado', lbl_niza_class: 'Classe de Nice', lbl_protection: 'Proteção',
        lbl_country: 'País', lbl_language: 'Idioma', lbl_currency: 'Moeda',
        lbl_registry: 'Registro', btn_website: 'Visitar site', btn_directions: 'Como chegar',
        filter_all_countries: 'Todos os países', filter_all_languages: 'Todos os idiomas',
    },
    fr: {
        lbl_hours: 'Horaires', lbl_phone: 'Téléphone', lbl_address: 'Adresse',
        lbl_type: 'Type', lbl_specialty: 'Spécialité', lbl_products: 'Produits/Services',
        lbl_email: 'E-mail', lbl_website: 'Site web', lbl_open_now: 'Ouvert maintenant',
        lbl_closed: 'Fermé', lbl_niza_class: 'Classe de Nice', lbl_protection: 'Protection',
        lbl_country: 'Pays', lbl_language: 'Langue', lbl_currency: 'Devise',
        lbl_registry: 'Enregistrement', btn_website: 'Voir le site', btn_directions: 'Itinéraire',
        filter_all_countries: 'Tous les pays', filter_all_languages: 'Toutes les langues',
    },
    de: {
        lbl_hours: 'Öffnungszeiten', lbl_phone: 'Telefon', lbl_address: 'Adresse',
        lbl_type: 'Typ', lbl_specialty: 'Spezialität', lbl_products: 'Produkte/Dienste',
        lbl_email: 'E-Mail', lbl_website: 'Website', lbl_open_now: 'Jetzt geöffnet',
        lbl_closed: 'Geschlossen', lbl_niza_class: 'Nizza-Klasse', lbl_protection: 'Schutz',
        lbl_country: 'Land', lbl_language: 'Sprache', lbl_currency: 'Währung',
        lbl_registry: 'Register', btn_website: 'Website besuchen', btn_directions: 'Route',
        filter_all_countries: 'Alle Länder', filter_all_languages: 'Alle Sprachen',
    },
    no: {
        lbl_hours: 'Åpningstider', lbl_phone: 'Telefon', lbl_address: 'Adresse',
        lbl_type: 'Type', lbl_specialty: 'Spesialitet', lbl_products: 'Produkter/Tjenester',
        lbl_email: 'E-post', lbl_website: 'Nettsted', lbl_open_now: 'Åpent nå',
        lbl_closed: 'Stengt', lbl_niza_class: 'Nice-klassifikasjon', lbl_protection: 'Beskyttelse',
        lbl_country: 'Land', lbl_language: 'Språk', lbl_currency: 'Valuta',
        lbl_registry: 'Register', btn_website: 'Besøk nettsted', btn_directions: 'Veibeskrivelse',
        filter_all_countries: 'Alle land', filter_all_languages: 'Alle språk',
    },
    zh: {
        lbl_hours: '营业时间', lbl_phone: '电话', lbl_address: '地址',
        lbl_type: '类型', lbl_specialty: '专业', lbl_products: '产品/服务',
        lbl_email: '电子邮件', lbl_website: '网站', lbl_open_now: '现在营业',
        lbl_closed: '已关闭', lbl_niza_class: '尼斯分类', lbl_protection: '保护',
        lbl_country: '国家', lbl_language: '语言', lbl_currency: '货币',
        lbl_registry: '注册', btn_website: '访问网站', btn_directions: '获取路线',
        filter_all_countries: '所有国家', filter_all_languages: '所有语言',
    },
    ar: {
        lbl_hours: 'ساعات العمل', lbl_phone: 'الهاتف', lbl_address: 'العنوان',
        lbl_type: 'النوع', lbl_specialty: 'التخصص', lbl_products: 'المنتجات/الخدمات',
        lbl_email: 'البريد الإلكتروني', lbl_website: 'الموقع', lbl_open_now: 'مفتوح الآن',
        lbl_closed: 'مغلق', lbl_niza_class: 'فئة نيس', lbl_protection: 'حماية',
        lbl_country: 'الدولة', lbl_language: 'اللغة', lbl_currency: 'العملة',
        lbl_registry: 'تسجيل', btn_website: 'زيارة الموقع', btn_directions: 'الاتجاهات',
        filter_all_countries: 'جميع الدول', filter_all_languages: 'جميع اللغات',
    },
    it: {
        lbl_hours: 'Orario', lbl_phone: 'Telefono', lbl_address: 'Indirizzo',
        lbl_type: 'Tipo', lbl_specialty: 'Specialità', lbl_products: 'Prodotti/Servizi',
        lbl_email: 'Email', lbl_website: 'Sito web', lbl_open_now: 'Aperto ora',
        lbl_closed: 'Chiuso', lbl_niza_class: 'Classe Nizza', lbl_protection: 'Protezione',
        lbl_country: 'Paese', lbl_language: 'Lingua', lbl_currency: 'Valuta',
        lbl_registry: 'Registro', btn_website: 'Visita il sito', btn_directions: 'Come arrivare',
        filter_all_countries: 'Tutti i paesi', filter_all_languages: 'Tutte le lingue',
    },
    ru: {
        lbl_hours: 'Часы работы', lbl_phone: 'Телефон', lbl_address: 'Адрес',
        lbl_type: 'Тип', lbl_specialty: 'Специализация', lbl_products: 'Продукты/Услуги',
        lbl_email: 'Email', lbl_website: 'Веб-сайт', lbl_open_now: 'Открыто сейчас',
        lbl_closed: 'Закрыто', lbl_niza_class: 'Класс МКТУ', lbl_protection: 'Защита',
        lbl_country: 'Страна', lbl_language: 'Язык', lbl_currency: 'Валюта',
        lbl_registry: 'Реестр', btn_website: 'Посетить сайт', btn_directions: 'Как добраться',
        filter_all_countries: 'Все страны', filter_all_languages: 'Все языки',
    },
    el: {
        lbl_hours: 'Ώρες', lbl_phone: 'Τηλέφωνο', lbl_address: 'Διεύθυνση',
        lbl_type: 'Τύπος', lbl_specialty: 'Ειδικότητα', lbl_products: 'Προϊόντα/Υπηρεσίες',
        lbl_email: 'Email', lbl_website: 'Ιστοσελίδα', lbl_open_now: 'Ανοιχτό τώρα',
        lbl_closed: 'Κλειστό', lbl_niza_class: 'Κλάση Νίτσα', lbl_protection: 'Προστασία',
        lbl_country: 'Χώρα', lbl_language: 'Γλώσσα', lbl_currency: 'Νόμισμα',
        lbl_registry: 'Μητρώο', btn_website: 'Επίσκεψη', btn_directions: 'Οδηγίες',
        filter_all_countries: 'Όλες οι χώρες', filter_all_languages: 'Όλες οι γλώσσες',
    },
    tr: {
        lbl_hours: 'Çalışma saatleri', lbl_phone: 'Telefon', lbl_address: 'Adres',
        lbl_type: 'Tür', lbl_specialty: 'Uzmanlık', lbl_products: 'Ürünler/Hizmetler',
        lbl_email: 'E-posta', lbl_website: 'Web sitesi', lbl_open_now: 'Şimdi açık',
        lbl_closed: 'Kapalı', lbl_niza_class: 'Nice sınıfı', lbl_protection: 'Koruma',
        lbl_country: 'Ülke', lbl_language: 'Dil', lbl_currency: 'Para birimi',
        lbl_registry: 'Sicil', btn_website: 'Web sitesini ziyaret et', btn_directions: 'Yol tarifi',
        filter_all_countries: 'Tüm ülkeler', filter_all_languages: 'Tüm diller',
    },
    ja: {
        lbl_hours: '営業時間', lbl_phone: '電話', lbl_address: '住所',
        lbl_type: '種類', lbl_specialty: '専門', lbl_products: '製品/サービス',
        lbl_email: 'メール', lbl_website: 'ウェブサイト', lbl_open_now: '営業中',
        lbl_closed: '閉店', lbl_niza_class: 'ニース分類', lbl_protection: '保護',
        lbl_country: '国', lbl_language: '言語', lbl_currency: '通貨',
        lbl_registry: '登録', btn_website: 'ウェブサイトを見る', btn_directions: '道順',
        filter_all_countries: 'すべての国', filter_all_languages: 'すべての言語',
    },
    ko: {
        lbl_hours: '영업시간', lbl_phone: '전화', lbl_address: '주소',
        lbl_type: '유형', lbl_specialty: '전문 분야', lbl_products: '제품/서비스',
        lbl_email: '이메일', lbl_website: '웹사이트', lbl_open_now: '지금 영업 중',
        lbl_closed: '영업 종료', lbl_niza_class: '니스 분류', lbl_protection: '보호',
        lbl_country: '국가', lbl_language: '언어', lbl_currency: '통화',
        lbl_registry: '등록', btn_website: '웹사이트 방문', btn_directions: '길 찾기',
        filter_all_countries: '모든 국가', filter_all_languages: '모든 언어',
    },
};

/** Idioma activo de la interfaz del visitante (persiste en localStorage). */
let MAPITA_UI_LANG = (function() {
    const stored = localStorage.getItem('mapita_ui_lang');
    const supported = Object.keys(UI_STRINGS);
    if (stored && supported.includes(stored)) return stored;
    const browser = (navigator.language || 'es').split('-')[0].toLowerCase();
    return supported.includes(browser) ? browser : 'es';
})();

/** Devuelve el string traducido para la clave dada. */
function uiStr(key) {
    return (UI_STRINGS[MAPITA_UI_LANG] || UI_STRINGS.es)[key] || key;
}

/** Cambia el idioma de interfaz, persiste en sesión PHP y recarga la página. */
function setMapUILang(lang) {
    if (!UI_STRINGS[lang]) return;
    MAPITA_UI_LANG = lang;
    localStorage.setItem('mapita_ui_lang', lang);
    // Recarga la página pasando el idioma como param; map.php lo persiste en sesión
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
}

let negocios  = [];
let marcas    = [];
let mapa, marcadores = [], miUbicacion = null;
let currentVer = 'negocios';
let followMe = false;
let watchID = null;
const SIDEBAR_LIST_LIMIT = 200;
let clusterGroup = null;
let selectionMode = false;
let selectionHighlightsLayer = null;
const selectedItems = new Map();
const selectionHighlights = new Map();
const wtPollers = new Map();
let eventoMarkers = [];
let encuestaMarkers = [];
const WT_MAX_MESSAGE_LEN = 140;
const WT_POLL_INTERVAL_MS = 6000;
const WT_HEARTBEAT_INTERVAL_MS = 20000;
const WT_MESSAGE_CACHE_LIMIT = 30;

// Capas independientes para contenido del dashboard (no se borran con clearLayers)
let eventosLayer, noticiasLayer, triviasLayer, encuestasLayer, ofertasLayer, transmisionesLayer;
let capasVisibles = { eventos:true, noticias:true, trivias:true, encuestas:true, ofertas:true, transmisiones:true };

// Admin: mostrar u ocultar una capa de contenido del dashboard
function toggleCapa(tipo, visible) {
    capasVisibles[tipo] = visible;
    const layerMap = {
        eventos:       () => eventosLayer,
        noticias:      () => noticiasLayer,
        trivias:       () => triviasLayer,
        encuestas:     () => encuestasLayer,
        ofertas:       () => ofertasLayer,
        transmisiones: () => transmisionesLayer,
    };
    const getLayer = layerMap[tipo];
    if (!getLayer) return;
    const layer = getLayer();
    if (!layer) return;
    if (visible) {
        mapa.addLayer(layer);
    } else {
        mapa.removeLayer(layer);
    }
}

let myBusinessMarkers   = [];
let propertyZoneMarkers = [];
let circles = [], licenseMarkers = [], franchiseMarkers = [], exclusiveMarkers = [];
let zonaMarcaMarkers = [], licenciaMarcaMarkers = [], franquiciaMarcaMarkers = [], exclusivaMarcaMarkers = [];
let activeSingleOverlays = [];
let activeRelationLines = [];

// Location filter state
let userRadiusCircle = null;
let userLocationMarker = null;

// ─── A1: SVG Marker system (iconos cargados dinámicamente desde BD) ────────────────
let iconosDB = {};  // Se llena con datos del API /api/api_iconos.php

// ─── Etiquetas legibles y emojis de respaldo por tipo de negocio ──────────────────
const BUSINESS_TYPE_LABELS = {
    restaurante:'Restaurante', cafeteria:'Cafetería', bar:'Bar / Pub',
    panaderia:'Panadería', heladeria:'Heladería', pizzeria:'Pizzería',
    supermercado:'Supermercado', comercio:'Tienda / Local',
    autos_venta:'Autos en Venta', motos_venta:'Motos en Venta',
    indumentaria:'Indumentaria', verduleria:'Verdulería', carniceria:'Carnicería',
    pastas:'Fábrica de Pastas', ferreteria:'Ferretería', electronica:'Tecnología',
    muebleria:'Mueblería', floristeria:'Floristería', libreria:'Librería',
    kiosco:'Kiosco', optica:'Óptica', farmacia:'Farmacia', clinica:'Clínica/Salud',
    veterinaria:'Veterinaria', peluqueria:'Peluquería/Belleza',
    gym:'Gimnasio', inmobiliaria:'Inmobiliaria', hotel:'Hotel/Alojamiento',
    bar_restoran:'Bar-Restorán', banco:'Banco/Financiero',
    educacion:'Educación', teatro:'Arte y Cultura',
    taller_mecanico:'Taller Mecánico', lavadero:'Lavadero', cerrajeria:'Cerrajería',
    remate:'Remate/Subasta', productora_audiovisual:'Productora Audiovisual',
    escuela_musicos:'Escuela de Músicos', taller_artes:'Taller de Artes',
    psicologo:'Psicología', fonoaudiologo:'Fonoaudiología', grafologo:'Grafología',
    biodecodificacion:'Biodecodificación', libreria_cristiana:'Librería Cristiana',
    medico_pediatra:'Médico Pediatra', medico_traumatologo:'Médico Traumatólogo',
    laboratorio:'Laboratorio', enfermeria:'Enfermería', asistencia_ancianos:'Asistencia a Ancianos',
    ingenieria_civil:'Ingeniería Civil', electricista:'Electricista', gasista:'Gasista matriculado',
    gas_en_garrafa:'Gas en garrafa', seguridad:'Seguridad', grafica:'Gráfica',
    astrologo:'Astrólogo', zapatero:'Zapatero', videojuegos:'Videojuegos',
    maestro_particular:'Maestro particular', alquiler_mobiliario_fiestas:'Alquiler de mobiliario para fiestas',
    propalacion_musica:'Propalación (música)', animacion_fiestas:'Animación de fiestas',
};
const BUSINESS_FALLBACK_EMOJI = {
    restaurante:'🍽️', cafeteria:'☕', bar:'🍺', panaderia:'🥐',
    heladeria:'🍦', pizzeria:'🍕', supermercado:'🛒', comercio:'🛍️',
    autos_venta:'🚗', motos_venta:'🏍️', indumentaria:'👕', verduleria:'🥦',
    carniceria:'🥩', pastas:'🍝', ferreteria:'🔧', electronica:'📱',
    muebleria:'🛋️', floristeria:'💐', libreria:'📖', kiosco:'🏪',
    optica:'👓', farmacia:'💊', clinica:'🏥', veterinaria:'🐾',
    peluqueria:'✂️', gym:'🏋️', inmobiliaria:'🏠', hotel:'🏨',
    banco:'🏦', educacion:'📚', teatro:'🎭',
    taller_mecanico:'🔩', remate:'🔨',
    lavadero:'🧺', cerrajeria:'🔑',
    productora_audiovisual:'🎥', escuela_musicos:'🎼', taller_artes:'🎨',
    psicologo:'🧠', fonoaudiologo:'🗣️', grafologo:'✍️',
    biodecodificacion:'🧬', libreria_cristiana:'📚',
    medico_pediatra:'🧒', medico_traumatologo:'🦴', laboratorio:'🧪',
    enfermeria:'🩺', asistencia_ancianos:'🧓',
    ingenieria_civil:'🏗️', electricista:'💡', gasista:'🔥',
    gas_en_garrafa:'🛢️', seguridad:'🛡️', grafica:'🖨️',
    astrologo:'🔮', zapatero:'👞', videojuegos:'🎮',
    maestro_particular:'📘', alquiler_mobiliario_fiestas:'🪑',
    propalacion_musica:'🔊', animacion_fiestas:'🎉',
};

// ─── Color helpers ───────────────────────────────────────────────────────────────
function _hexToRgb(hex) {
    hex = hex.replace(/^#/, '');
    if (hex.length === 3) hex = hex.split('').map(c => c+c).join('');
    const n = parseInt(hex, 16);
    return [n >> 16 & 255, n >> 8 & 255, n & 255];
}
function _rgbToHex(r, g, b) {
    return '#' + [r,g,b].map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2,'0')).join('');
}
function lightenColor(hex, pct) {
    const [r,g,b] = _hexToRgb(hex);
    return _rgbToHex(r + (255-r)*pct/100, g + (255-g)*pct/100, b + (255-b)*pct/100);
}
function darkenColor(hex, pct) {
    const [r,g,b] = _hexToRgb(hex);
    return _rgbToHex(r*(1-pct/100), g*(1-pct/100), b*(1-pct/100));
}
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function uploadBrandLogoPopup(input, brandId) {
    const file = input.files[0];
    if (!file) return;
    const msgEl = document.getElementById('brand-logo-msg-' + brandId);
    const show = (txt, ok) => {
        if (!msgEl) return;
        msgEl.style.display = 'block';
        msgEl.style.background = ok ? '#d1fae5' : '#fee2e2';
        msgEl.style.color = ok ? '#065f46' : '#991b1b';
        msgEl.textContent = txt;
    };
    if (file.size > 200 * 1024) { show('❌ Máximo 200 KB. Comprimí en squoosh.app', false); return; }
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) { show('❌ Solo JPG, PNG o WebP', false); return; }
    const fd = new FormData();
    fd.append('brand_id', brandId);
    fd.append('action', 'upload');
    fd.append('logo', file);
    show('⏳ Subiendo...', true);
    try {
        const d = await fetch('/api/upload_brand_logo.php', { method: 'POST', body: fd }).then(r => r.json());
        if (d.success) {
            show('✅ Icono actualizado. Se verá en el mapa al recargar.', true);
        } else {
            show('❌ ' + (d.message || 'Error'), false);
        }
    } catch (e) {
        show('❌ Error de conexión', false);
    }
    input.value = '';
}

async function deleteReview(reviewId, businessId) {
    if (!confirm('¿Eliminar esta reseña?')) return;
    try {
        const r = await fetch('/api/reviews.php?review_id=' + reviewId, { method: 'DELETE' });
        const d = await r.json();
        if (d.success) {
            const item = document.getElementById('review-item-' + reviewId);
            if (item) item.remove();
            // Refresh average
            const el = document.querySelector('[data-rating-id="' + businessId + '"]');
            if (el) {
                const res = await fetch('/api/reviews.php?business_id=' + businessId).then(x => x.json());
                const avg = parseFloat(res.data?.average?.avg || 0);
                const tot = parseInt(res.data?.average?.total || 0);
                const avgSpan = el.querySelector('span[style*="color:#888"]');
                if (avgSpan) {
                    if (tot === 0) {
                        avgSpan.textContent = 'Sin reseñas aún';
                    } else {
                        avgSpan.textContent = avg.toFixed(1) + ' (' + tot + ' reseñas)';
                    }
                }
            }
        } else {
            alert(d.message || 'No se pudo eliminar la reseña.');
        }
    } catch (e) {
        alert('Error al eliminar la reseña.');
    }
}

// ─── Contador para IDs únicos de gradientes SVG ──────────────────────────────────
let _svgIconCount = 0;

// ─── createSvgIcon con apariencia 3D ─────────────────────────────────────────────
function createSvgIcon(emoji, color, isOpen, options = {}) {
    const w  = options.size   || 32;
    const h  = options.height || 44;
    const cx = w / 2;
    const id = 'si' + (++_svgIconCount);

    const light  = lightenColor(color, 45);
    const dark   = darkenColor(color, 28);
    const extraClass = [
        options.pulse ? 'icon-pulse' : '',
        options.glow  ? 'icon-glow'  : '',
    ].filter(Boolean).join(' ');

    // Path escalado al tamaño del pin
    const px = (v) => (v / 30 * w).toFixed(2);
    const py = (v) => (v / 40 * h).toFixed(2);

    const dot = isOpen === true
        ? `<circle cx="${w-6}" cy="6" r="5" fill="#2ecc71" stroke="white" stroke-width="1.5"/>`
        : isOpen === false
        ? `<circle cx="${w-6}" cy="6" r="5" fill="#e74c3c" stroke="white" stroke-width="1.5"/>`
        : '';

    const svg = `<svg xmlns="http://www.w3.org/2000/svg"
        width="${w}" height="${h}" viewBox="0 0 ${w} ${h}"
        class="mapita-icon ${extraClass}">
      <defs>
        <radialGradient id="rg${id}" cx="33%" cy="28%" r="68%">
          <stop offset="0%"   stop-color="${light}"/>
          <stop offset="55%"  stop-color="${color}"/>
          <stop offset="100%" stop-color="${dark}"/>
        </radialGradient>
        <filter id="fs${id}" x="-25%" y="-10%" width="150%" height="145%">
          <feDropShadow dx="1" dy="3" stdDeviation="2.5" flood-color="rgba(0,0,0,0.32)"/>
        </filter>
        <radialGradient id="gl${id}" cx="32%" cy="22%" r="42%">
          <stop offset="0%"   stop-color="white" stop-opacity="0.50"/>
          <stop offset="100%" stop-color="white" stop-opacity="0"/>
        </radialGradient>
      </defs>
      <!-- Sombra en el suelo -->
      <ellipse cx="${cx}" cy="${h - 2}" rx="${(w*0.36).toFixed(1)}" ry="2.5"
               fill="rgba(0,0,0,0.16)"/>
      <!-- Cuerpo del pin (gradiente 3D) -->
      <path d="M${cx} 1.5 C${px(7)} 1.5 1.5 ${py(7)} 1.5 ${py(15)}
               C1.5 ${py(26)} ${cx} ${h-1.5} ${cx} ${h-1.5}
               C${cx} ${h-1.5} ${(w-1.5).toFixed(1)} ${py(26)} ${(w-1.5).toFixed(1)} ${py(15)}
               C${(w-1.5).toFixed(1)} ${py(7)} ${px(23)} 1.5 ${cx} 1.5Z"
            fill="url(#rg${id})" filter="url(#fs${id})"/>
      <!-- Capa de brillo (gloss) -->
      <path d="M${cx} 1.5 C${px(7)} 1.5 1.5 ${py(7)} 1.5 ${py(15)}
               C1.5 ${py(26)} ${cx} ${h-1.5} ${cx} ${h-1.5}
               C${cx} ${h-1.5} ${(w-1.5).toFixed(1)} ${py(26)} ${(w-1.5).toFixed(1)} ${py(15)}
               C${(w-1.5).toFixed(1)} ${py(7)} ${px(23)} 1.5 ${cx} 1.5Z"
            fill="url(#gl${id})"/>
      <!-- Emoji -->
      <text x="${cx}" y="${(h*0.43).toFixed(1)}"
            text-anchor="middle" dominant-baseline="middle"
            font-size="${(w*0.44).toFixed(0)}">${emoji}</text>
      ${dot}
    </svg>`;

    return L.divIcon({
        html: svg,
        className: '',
        iconSize:    [w, h],
        iconAnchor:  [cx, h],
        popupAnchor: [0, -(h + 2)]
    });
}

function createSvgIconWithOffer(emoji, color, isOpen) {
    const base = createSvgIcon(emoji, color, isOpen, { size: 32, height: 44 });
    const wrapped = `<div style="position:relative;display:inline-block;">
        ${base.options.html}
        <span style="position:absolute;top:-3px;left:-3px;background:#e74c3c;color:white;border:2px solid #fff;border-radius:10px;padding:0 4px;font-size:9px;font-weight:700;line-height:14px;box-shadow:0 1px 4px rgba(0,0,0,.3)">%</span>
    </div>`;
    return L.divIcon({
        html: wrapped,
        className: '',
        iconSize: [32, 44],
        iconAnchor: [16, 44],
        popupAnchor: [0, -46]
    });
}

const nizaColors = { '1':'#e74c3c','2':'#e67e22','3':'#f1c40f','4':'#2ecc71','5':'#1abc9c','6':'#3498db','7':'#9b59b6' };
function getNizaColor(clase) {
    if (!clase) return '#3498db';
    return nizaColors[clase.split(',')[0].trim()] || '#3498db';
}

// ─── Helper: genera SVG 3D para marcadores de contenido ─────────────────────────
function make3dPin(emoji, color, w, h, extraClass) {
    const cx    = w / 2;
    const id    = 'ci' + (++_svgIconCount);
    const light = lightenColor(color, 45);
    const dark  = darkenColor(color, 28);
    const px    = (v) => (v / 30 * w).toFixed(2);
    const py    = (v) => (v / 40 * h).toFixed(2);
    return `<svg xmlns="http://www.w3.org/2000/svg"
        width="${w}" height="${h}" viewBox="0 0 ${w} ${h}"
        class="mapita-icon${extraClass ? ' '+extraClass : ''}">
      <defs>
        <radialGradient id="rg${id}" cx="33%" cy="28%" r="68%">
          <stop offset="0%"   stop-color="${light}"/>
          <stop offset="55%"  stop-color="${color}"/>
          <stop offset="100%" stop-color="${dark}"/>
        </radialGradient>
        <filter id="fs${id}" x="-25%" y="-10%" width="150%" height="145%">
          <feDropShadow dx="1" dy="3" stdDeviation="2.5" flood-color="rgba(0,0,0,0.30)"/>
        </filter>
        <radialGradient id="gl${id}" cx="32%" cy="22%" r="42%">
          <stop offset="0%"   stop-color="white" stop-opacity="0.48"/>
          <stop offset="100%" stop-color="white" stop-opacity="0"/>
        </radialGradient>
      </defs>
      <ellipse cx="${cx}" cy="${h-2}" rx="${(w*0.36).toFixed(1)}" ry="2.5"
               fill="rgba(0,0,0,0.15)"/>
      <path d="M${cx} 1.5 C${px(7)} 1.5 1.5 ${py(7)} 1.5 ${py(15)}
               C1.5 ${py(26)} ${cx} ${h-1.5} ${cx} ${h-1.5}
               C${cx} ${h-1.5} ${(w-1.5).toFixed(1)} ${py(26)} ${(w-1.5).toFixed(1)} ${py(15)}
               C${(w-1.5).toFixed(1)} ${py(7)} ${px(23)} 1.5 ${cx} 1.5Z"
            fill="url(#rg${id})" filter="url(#fs${id})"/>
      <path d="M${cx} 1.5 C${px(7)} 1.5 1.5 ${py(7)} 1.5 ${py(15)}
               C1.5 ${py(26)} ${cx} ${h-1.5} ${cx} ${h-1.5}
               C${cx} ${h-1.5} ${(w-1.5).toFixed(1)} ${py(26)} ${(w-1.5).toFixed(1)} ${py(15)}
               C${(w-1.5).toFixed(1)} ${py(7)} ${px(23)} 1.5 ${cx} 1.5Z"
            fill="url(#gl${id})"/>
      <text x="${cx}" y="${(h*0.43).toFixed(1)}"
            text-anchor="middle" dominant-baseline="middle"
            font-size="${(w*0.46).toFixed(0)}">${emoji}</text>
    </svg>`;
}

// ─── TEMPORAL STATES ─────────────────────────────────────────────────────────
/**
 * Returns { label, urgency } for a marker that is time-sensitive, or null.
 * urgency: 'urgent' | 'soon'
 */
function getEntityTemporalState(entity, entityType) {
    const now = Date.now();
    if (entityType === 'evento') {
        const dateStr = entity.fecha || entity.fecha_inicio;
        const timeStr = entity.hora || '';
        if (!dateStr) return null;
        try {
            const isoStr = dateStr.includes('T') ? dateStr : dateStr + 'T' + (timeStr ? timeStr.substring(0,8).padEnd(8,'0') : '00:00:00');
            const dt   = new Date(isoStr);
            const diff = dt - now;
            if (diff < 0)           return null;
            if (diff < 86400000)    return { label: '🔴 HOY',   urgency: 'urgent' };
            if (diff < 604800000)   return { label: '⚡ PRONTO', urgency: 'soon'   };
        } catch (_) { /* ignore */ }
        return null;
    }
    if (entityType === 'encuesta' || entityType === 'oferta') {
        const expStr = entity.fecha_expiracion;
        if (!expStr) return null;
        try {
            const isoExp = expStr.includes('T') ? expStr : expStr + 'T00:00:00';
            const diffDays = (new Date(isoExp) - now) / 86400000;
            if (diffDays < 0)  return null;
            if (diffDays < 1)  return { label: '⏰ HOY',    urgency: 'urgent' };
            if (diffDays < 3)  return { label: '⏳ PRONTO', urgency: 'soon'   };
        } catch (_) { /* ignore */ }
        return null;
    }
    return null;
}

/** Wraps a raw SVG/HTML pin string with a temporal badge overlay. */
function wrapPinWithTemporalBadge(svgHtml, temporalState) {
    if (!temporalState) return svgHtml;
    return `<div class="mapita-temporal-wrapper">${svgHtml}<span class="mapita-temporal-badge ${temporalState.urgency}">${temporalState.label}</span></div>`;
}

// ─── MINI HOVER CONTEXT PANEL ────────────────────────────────────────────────
const _CTX_PANEL_TYPE_LABELS = {
    business: '🏪 Negocio', brand: '🏷️ Marca', evento: '🎉 Evento',
    encuesta: '📋 Encuesta', noticia: '📰 Noticia', trivia: '🎯 Trivia',
    oferta: '💰 Oferta', transmision: '📡 Transmisión'
};

const CTX_PANEL_WIDTH  = 210;
const CTX_PANEL_OFFSET = 14;

function getEntityDisplayName(entity) {
    return entity.nombre || entity.name || entity.titulo || 'Sin nombre';
}

/** Returns icon size array [w, h] adjusted when a temporal badge overlay is present. */
function calcBadgeIconSize(baseW, baseH, hasBadge) {
    return hasBadge ? [baseW + 10, baseH + 10] : [baseW, baseH];
}

let _ctxHideTimer = null;

function getCtxPanel() {
    return document.getElementById('map-ctx-panel');
}

function showCtxPanel(marker, entity, entityType) {
    if (_ctxHideTimer) { clearTimeout(_ctxHideTimer); _ctxHideTimer = null; }
    const panel = getCtxPanel();
    if (!panel || !mapa) return;
    const latlng = marker.getLatLng ? marker.getLatLng() : null;
    if (!latlng) return;

    const name      = escapeHtml(getEntityDisplayName(entity));
    const typeLabel = _CTX_PANEL_TYPE_LABELS[entityType] || entityType;

    const badges = [];
    if (entity.ciudad)        badges.push('📍 ' + escapeHtml(entity.ciudad));
    else if (entity.ubicacion) badges.push('📍 ' + escapeHtml(String(entity.ubicacion).substring(0,30)));
    if (entityType === 'evento'  && entity.fecha)         badges.push('📅 ' + entity.fecha.substring(0,10));
    if (entityType === 'oferta'  && entity.precio_oferta) badges.push('💲 $' + parseFloat(entity.precio_oferta).toLocaleString());
    if (entityType === 'transmision' && entity.en_vivo)   badges.push('🔴 EN VIVO');
    if (entity.tipo_comercio)  badges.push('🔹 ' + escapeHtml(entity.tipo_comercio));
    if (entityType === 'trivia' && entity.dificultad)     badges.push('🎯 ' + entity.dificultad);

    const badgesHtml = badges.length > 0
        ? '<div style="margin-top:4px;">' + badges.map(b => `<span class="ctx-badge">${b}</span>`).join('') + '</div>'
        : '';

    panel.innerHTML = `<div class="ctx-name">${name}</div><div class="ctx-type">${typeLabel}</div>${badgesHtml}`;

    // Position relative to map container
    const mapDiv = document.getElementById('map');
    if (!mapDiv) return;
    const rect   = mapDiv.getBoundingClientRect();
    const pt     = mapa.latLngToContainerPoint(latlng);
    let x = rect.left + pt.x + CTX_PANEL_OFFSET;
    let y = rect.top  + pt.y - 72;
    if (x + CTX_PANEL_WIDTH > window.innerWidth)  x = rect.left + pt.x - CTX_PANEL_WIDTH - CTX_PANEL_OFFSET;
    if (y < 8)                                     y = rect.top  + pt.y + 22;
    panel.style.left = x + 'px';
    panel.style.top  = y + 'px';
    panel.classList.add('visible');
    panel.setAttribute('aria-hidden', 'false');
}

function hideCtxPanel() {
    _ctxHideTimer = setTimeout(() => {
        const panel = getCtxPanel();
        if (panel) {
            panel.classList.remove('visible');
            panel.setAttribute('aria-hidden', 'true');
        }
    }, 120);
}

// ─── LEYENDA DINÁMICA ────────────────────────────────────────────────────────
const LEGEND_LAYER_CONFIG = [
    { emoji: '📍', label: 'Negocios'         },
    { emoji: '🏷️', label: 'Marcas'           },
    { emoji: '🎉', label: 'Eventos'          },
    { emoji: '📋', label: 'Encuestas'        },
    { emoji: '📰', label: 'Noticias'         },
    { emoji: '🎯', label: 'Trivias'          },
    { emoji: '💰', label: 'Ofertas'          },
    { emoji: '📡', label: 'Transmisiones'    },
];
const LEGEND_RELATION_CONFIG = [
    { color: '#00b894', dash: '6 4',  label: 'Vinculado / Alianza'  },
    { color: '#e67e22', dash: '2 6',  label: 'Promociona / Destaca' },
    { color: '#8e44ad', dash: '10 4', label: 'Provee / Depende'     },
    { color: '#1B3B6F', dash: '6 6',  label: 'General'              },
];

function buildMapLegend() {
    const layersEl    = document.getElementById('legend-layers');
    const relationsEl = document.getElementById('legend-relations');
    if (!layersEl || !relationsEl) return;

    layersEl.innerHTML = LEGEND_LAYER_CONFIG.map(l =>
        `<div class="legend-item"><span style="font-size:16px;line-height:1;">${l.emoji}</span><span>${l.label}</span></div>`
    ).join('');

    relationsEl.innerHTML = LEGEND_RELATION_CONFIG.map(r =>
        `<div class="legend-item">
            <svg width="22" height="8" style="flex-shrink:0;" aria-hidden="true">
                <line x1="0" y1="4" x2="22" y2="4"
                      stroke="${r.color}" stroke-width="2.5"
                      stroke-dasharray="${r.dash}" stroke-linecap="round"/>
            </svg>
            <span>${r.label}</span>
        </div>`
    ).join('');
}

function toggleMapLegend() {
    const panel = document.getElementById('map-legend');
    const btn   = document.getElementById('map-legend-btn');
    if (!panel) return;
    const open = panel.style.display === 'block';
    panel.style.display = open ? 'none' : 'block';
    if (btn) btn.setAttribute('aria-expanded', String(!open));
    if (!open) buildMapLegend();
}

// ─── FASE 1: Cargar iconos dinámicamente desde API ──────────────────────────────
async function cargarIconosDesdeAPI() {
    try {
        const response = await fetch('/api/api_iconos.php');
        const resultado = await response.json();

        if (resultado.success && resultado.data) {
            // API devuelve un objeto keyed por business_type, no un array
            if (Array.isArray(resultado.data)) {
                // Si por algún motivo viene como array, convertir a objeto
                iconosDB = {};
                resultado.data.forEach(icon => {
                    iconosDB[icon.business_type] = {
                        emoji: icon.emoji,
                        color: icon.color
                    };
                });
            } else {
                // API devuelve objeto keyed correctamente, usar directamente
                iconosDB = resultado.data;
            }
            console.log(`✅ Iconos cargados: ${Object.keys(iconosDB).length} tipos de negocio`);
            console.debug('Tipos disponibles:', Object.keys(iconosDB).slice(0, 5));
        } else {
            console.warn('⚠️ Error al cargar iconos desde API:', resultado.message || 'Respuesta inválida');
            // Usar fallback con icono genérico
            iconosDB = {
                '*': { emoji: '📍', color: '#667eea' }
            };
        }
    } catch (error) {
        console.error('❌ Error cargando iconos:', error);
        // Usar fallback genérico
        iconosDB = {
            '*': { emoji: '📍', color: '#667eea' }
        };
    }
}

// ─── A2: Open/Closed logic ───────────────────────────────────────────────────────
/**
 * Quita tildes del español y convierte a minúsculas para normalizar nombres de días.
 * Ej: 'Miércoles' → 'miercoles', 'sábado' → 'sabado'
 */
function normalizeSpanishDay(str) {
    return str.toLowerCase()
        .replace(/á/g,'a').replace(/é/g,'e').replace(/í/g,'i')
        .replace(/ó/g,'o').replace(/ú/g,'u');
}

/**
 * Obtiene la fecha/hora actual en la timezone del negocio usando Intl.DateTimeFormat.
 * Retorna { day: 'lunes', mins: 540 } (minutos desde medianoche en esa TZ).
 */
function getNowInTimezone(tz) {
    const now = new Date();
    try {
        const fmt = new Intl.DateTimeFormat('es-AR', {
            timeZone: tz,
            weekday: 'long',
            hour:    'numeric',
            minute:  'numeric',
            hour12:  false,
        });
        const parts = fmt.formatToParts(now);
        const get   = type => (parts.find(p => p.type === type) || {}).value || '';
        const weekday = normalizeSpanishDay(get('weekday'));
        const hour   = parseInt(get('hour'),   10) || 0;
        const minute = parseInt(get('minute'), 10) || 0;
        return { day: weekday, mins: hour * 60 + minute };
    } catch (e) {
        // Fallback: hora local del visitante si el timezone es inválido
        const d = new Date();
        const days = ['domingo','lunes','martes','miercoles','jueves','viernes','sabado'];
        return { day: days[d.getDay()], mins: d.getHours() * 60 + d.getMinutes() };
    }
}

function estaAbierto(n) {
    if (!n.horario_apertura || !n.horario_cierre) return null;
    const tz = n.timezone || 'America/Argentina/Buenos_Aires';
    const { day, mins } = getNowInTimezone(tz);
    if (n.dias_cierre && n.dias_cierre.toLowerCase().replace(/\s/g,'').split(',').includes(day)) return false;
    const [oh, om] = n.horario_apertura.split(':').map(Number);
    const [ch, cm] = n.horario_cierre.split(':').map(Number);
    return mins >= oh * 60 + om && mins <= ch * 60 + cm;
}

function isVehicleSaleType(n) {
    return n && (n.business_type === 'autos_venta' || n.business_type === 'motos_venta');
}
function isRemateType(n) {
    return n && n.business_type === 'remate';
}
function parseDateSafe(dateValue) {
    if (!dateValue) return null;
    const d = new Date(dateValue);
    return isNaN(d.getTime()) ? null : d;
}
function getRemateWindow(n) {
    const inicio = parseDateSafe(n.remate_fecha_inicio || n.fecha_inicio);
    const fin = parseDateSafe(n.remate_fecha_fin || n.remate_fecha_cierre || n.fecha_fin || n.fecha_cierre);
    return { inicio, fin };
}
function getRemateStatus(n) {
    const now = new Date();
    const { inicio, fin } = getRemateWindow(n);
    if (!inicio) return { key: 'sin_fecha', label: '🔨 Sin fecha definida' };
    if (now < inicio) return { key: 'programado', label: '🟡 Programado' };
    if (fin && now > fin) return { key: 'finalizado', label: '⚫ Finalizado' };
    return { key: 'in_curso', label: '🟢 En curso' };
}
function formatDateTime(dateValue) {
    return dateValue ? dateValue.toLocaleString(MAPITA_LOCALE) : '';
}
function pasaFiltroAbiertosAhora(n) {
    if (isVehicleSaleType(n)) return false;
    if (isRemateType(n)) return getRemateStatus(n).key === 'in_curso';
    return estaAbierto(n) === true;
}

// ─── A5: Price indicator ─────────────────────────────────────────────────────────
function precioStr(pr) {
    if (!pr || pr < 1 || pr > 5) return '';
    const filled = '<span style="color:#27ae60;font-weight:700;">$</span>'.repeat(pr);
    const empty  = '<span style="color:#ddd;">$</span>'.repeat(5 - pr);
    return '<span style="letter-spacing:1px;font-size:13px;">' + filled + empty + '</span>';
}

// ─── WT (Walkie Talkie) MVP ───────────────────────────────────────────────────────
const WT_META_SEPARATOR = ' · ';

function escapeHtml(s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function wtPanelKey(entityType, entityId) {
    return entityType + ':' + entityId;
}

function renderWtPanel(entityType, entityId, title) {
    if (!entityType || !entityId) return '';
    const safeEntityType = escapeHtml(entityType);
    const safeEntityId = escapeHtml(entityId);
    const key = wtPanelKey(safeEntityType, safeEntityId);
    const safeTitle = escapeHtml(title || '');
    const panelId = 'wt-panel-' + key.replace(/[^a-z0-9]/gi, '-');
    return `
        <div class="wt-toggle-wrapper" style="margin:6px 0 2px;">
            <button type="button"
                class="popup-action popup-action-wt-toggle"
                aria-controls="${panelId}"
                aria-expanded="false"
                onclick="(function(btn){
                    var p=document.getElementById('${panelId}');
                    var open=p.style.display!=='none'&&p.style.display!=='';
                    p.style.display=open?'none':'block';
                    btn.setAttribute('aria-expanded',!open);
                    if(!open&&!p.dataset.wtReady){initWtPanels(p.parentElement);}
                })(this)"
                style="background:#1f2937;display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;border:none;cursor:pointer;color:white;font-size:12px;font-weight:600;transition:all 0.2s;">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" style="flex-shrink:0;">
                    <rect x="8" y="6" width="8" height="14" rx="2" stroke="currentColor" fill="none" stroke-width="1.8"/>
                    <line x1="12" y1="2" x2="12" y2="6" stroke="currentColor" stroke-width="1.8"/>
                    <circle cx="12" cy="12" r="2" fill="currentColor"/>
                </svg>
                <span style="font-style:italic;color:#60a5fa;">Mensajes WT</span>
            </button>
        </div>
        <div id="${panelId}" class="wt-panel" data-wt-panel="1" data-wt-key="${key}" data-wt-entity-type="${safeEntityType}" data-wt-entity-id="${safeEntityId}" data-wt-since-id="0" style="display:none;">
            <div class="wt-header">
                <strong>📻 WT</strong>
                <span class="wt-title">${safeTitle}</span>
                <span class="wt-listeners" data-wt-listeners>👂 0</span>
            </div>
            <div class="wt-messages" data-wt-messages><div class="wt-empty">Sin mensajes por ahora.</div></div>
            <div class="wt-compose">
                <input type="text" maxlength="500" placeholder="Escribí un mensaje..." data-wt-input>
                <button type="button" data-wt-send>Enviar</button>
            </div>
        </div>
    `;
}

function stopWtPollingByKey(key) {
    const poller = wtPollers.get(key);
    if (poller) {
        clearInterval(poller);
        wtPollers.delete(key);
    }
}

function stopWtPollingInRoot(root) {
    if (!root) return;
    root.querySelectorAll?.('[data-wt-panel="1"]').forEach(panel => {
        stopWtPollingByKey(panel.dataset.wtKey || '');
    });
}

function stopAllWtPolling() {
    wtPollers.forEach(timer => clearInterval(timer));
    wtPollers.clear();
}

async function wtApi(action, payload, method = 'GET') {
    const isGet = method === 'GET';
    const params = new URLSearchParams();
    params.set('action', action);
    if (payload) {
        Object.entries(payload).forEach(([k, v]) => {
            if (v !== undefined && v !== null && v !== '') params.set(k, String(v));
        });
    }
    const url = '/api/wt.php' + (isGet ? ('?' + params.toString()) : '');
    const res = await fetch(url, isGet ? { credentials: 'same-origin' } : {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(Object.assign({ action }, payload || {}))
    });
    return res.json();
}

async function wtPoll(panel) {
    if (!panel || !document.body.contains(panel)) return;
    const entityType = panel.dataset.wtEntityType;
    const entityId = panel.dataset.wtEntityId;
    let sinceId = parseInt(panel.dataset.wtSinceId || '0', 10) || 0;
    const messagesEl = panel.querySelector('[data-wt-messages]');
    const listenersEl = panel.querySelector('[data-wt-listeners]');
    if (!entityType || !entityId || !messagesEl) return;

    try {
        const [msgRes, listenersRes] = await Promise.all([
            wtApi('messages', { entity_type: entityType, entity_id: entityId, since_id: sinceId }, 'GET'),
            wtApi('listeners', { entity_type: entityType, entity_id: entityId }, 'GET')
        ]);

        if (msgRes?.success && Array.isArray(msgRes.data)) {
            if (sinceId === 0) messagesEl.innerHTML = '';
            msgRes.data.forEach(m => {
                const row = document.createElement('div');
                row.className = 'wt-message';
                const meta = document.createElement('div');
                meta.className = 'wt-meta';
                meta.textContent = (m.sender_name || 'Invitado') + WT_META_SEPARATOR + (m.created_at || '');
                const text = document.createElement('div');
                text.className = 'wt-text';
                text.textContent = m.message || '';
                row.appendChild(meta);
                row.appendChild(text);
                messagesEl.appendChild(row);
                const mid = parseInt(m.id, 10);
                if (mid > sinceId) {
                    sinceId = mid;
                    panel.dataset.wtSinceId = String(mid);
                }
            });
            if (!messagesEl.children.length) {
                messagesEl.innerHTML = '<div class="wt-empty">Sin mensajes por ahora.</div>';
            } else {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }
        }

        if (listenersEl && listenersRes?.success) {
            listenersEl.textContent = '👂 ' + (listenersRes.data?.count || 0);
        }
    } catch (e) {
        // Silencioso para no romper popups existentes
    }
}

async function wtSendMessage(panel) {
    if (!panel) return;
    const input = panel.querySelector('[data-wt-input]');
    if (!input) return;
    const message = (input.value || '').trim();
    if (!message) return;
    input.disabled = true;
    try {
        const res = await wtApi('send', {
            entity_type: panel.dataset.wtEntityType,
            entity_id: panel.dataset.wtEntityId,
            message: message
        }, 'POST');
        if (res?.success) {
            input.value = '';
            await wtPoll(panel);
        }
    } catch (e) {
        // Ignorar error para mantener UX del mapa estable
    } finally {
        input.disabled = false;
        input.focus();
    }
}

function startWtPolling(panel) {
    if (!panel) return;
    const key = panel.dataset.wtKey;
    if (!key) return;
    stopWtPollingByKey(key);
    wtApi('presence', {
        entity_type: panel.dataset.wtEntityType,
        entity_id: panel.dataset.wtEntityId
    }, 'POST').catch(() => {});
    wtPoll(panel);
    const timer = setInterval(() => {
        if (!document.body.contains(panel)) {
            stopWtPollingByKey(key);
            return;
        }
        wtApi('presence', {
            entity_type: panel.dataset.wtEntityType,
            entity_id: panel.dataset.wtEntityId
        }, 'POST').catch(() => {});
        wtPoll(panel);
    }, WT_POLL_INTERVAL_MS);
    wtPollers.set(key, timer);
}

function initWtPanels(root) {
    const container = root || document;
    container.querySelectorAll?.('[data-wt-panel="1"]').forEach(panel => {
        if (panel.dataset.wtReady === '1') return;
        panel.dataset.wtReady = '1';
        const sendBtn = panel.querySelector('[data-wt-send]');
        const input = panel.querySelector('[data-wt-input]');
        sendBtn?.addEventListener('click', () => wtSendMessage(panel));
        input?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                wtSendMessage(panel);
            }
        });
        startWtPolling(panel);
    });
}

// ─── Map Init ────────────────────────────────────────────────────────────────────
function inicializarMapa() {
    if (mapa) { mapa.remove(); }
    mapa = L.map('map').setView([-34.6037, -58.3816], 12);

    const capas = {
        'Mapa':     L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                        { attribution: '© OpenStreetMap' }),
        'Satélite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                        { attribution: '© Esri' }),
        'Oscuro':   L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
                        { attribution: '© OpenStreetMap © CartoDB' })
    };
    capas['Mapa'].addTo(mapa);
    L.control.layers(capas, {}, { position: 'topright', collapsed: true }).addTo(mapa);

    clusterGroup = L.markerClusterGroup({ disableClusteringAtZoom: 16 });
    mapa.addLayer(clusterGroup);
    selectionHighlightsLayer = L.layerGroup().addTo(mapa);
    clusterGroup.on('clusterclick', function(e) {
        if (!selectionMode) return;
        stopLeafletEvent(e);
        const cluster = e?.layer;
        const childMarkers = cluster?.getAllChildMarkers ? cluster.getAllChildMarkers() : [];
        let added = 0;
        childMarkers.forEach(marker => {
            if (addMarkerToSelection(marker)) added++;
        });
        const hint = document.getElementById('selection-hint');
        if (hint) hint.style.display = 'none';
        updateSelectionPanel();
        const result = document.getElementById('selection-aggregate-result');
        if (result) result.textContent = added > 0
            ? `Se agregaron ${added} elementos del grupo seleccionado.`
            : 'Ese grupo ya estaba completamente seleccionado.';
    });

    // Capas separadas para contenido del dashboard
    eventosLayer      = L.layerGroup().addTo(mapa);
    noticiasLayer     = L.layerGroup().addTo(mapa);
    triviasLayer      = L.layerGroup().addTo(mapa);
    encuestasLayer    = L.layerGroup().addTo(mapa);
    ofertasLayer      = L.layerGroup().addTo(mapa);
    transmisionesLayer = L.layerGroup().addTo(mapa);

    mapa.on('popupclose', () => {
        clearSingleOverlays();
        clearRelationLines();
    });

    // ─── FASE 1: Cargar iconos dinámicamente desde la BD ──────────────
    cargarIconosDesdeAPI();

    // ─── B1: Lazy rating load on popup open ────────────────────────────────────
    mapa.on('popupopen', async (e) => {
        if (selectionMode) {
            mapa.closePopup(e.popup);
            return;
        }
        const el = e.popup.getElement()?.querySelector('[data-rating-id]');
        if (el) {
            const bid = el.getAttribute('data-rating-id');
            try {
                const res = await fetch('/api/reviews.php?business_id=' + bid).then(r => r.json());
                const avg = parseFloat(res.data?.average?.avg || res.data?.average?.avg_rating || 0);
                const tot = parseInt(res.data?.average?.total || 0);
                const reviews = Array.isArray(res.data?.reviews) ? res.data.reviews : [];

                let html = '';
                if (tot === 0) {
                    html = '<span style="color:#bbb;font-size:11px;">Sin reseñas aún</span>';
                } else {
                    const full  = Math.min(5, Math.round(avg));
                    const stars = '⭐'.repeat(full) + '☆'.repeat(5 - full);
                    html = stars + ' <span style="color:#888;font-size:11px;">' + avg.toFixed(1) + ' (' + tot + ' reseñas)</span>';
                }

                // Show individual reviews for admin or when there are any
                if (reviews.length > 0) {
                    html += '<div id="review-list-' + bid + '" style="margin-top:6px;max-height:160px;overflow-y:auto;">';
                    reviews.forEach(function(rv) {
                        const canDel = IS_ADMIN || (SESSION_USER_ID > 0 && parseInt(rv.user_id) === SESSION_USER_ID);
                        const rStars = '⭐'.repeat(Math.min(5, parseInt(rv.rating) || 0));
                        const date   = rv.created_at ? rv.created_at.substring(0, 10) : '';
                        const cmt    = rv.comment ? escapeHtml(String(rv.comment)) : '<em style="color:#888;">Sin comentario</em>';
                        html += '<div id="review-item-' + rv.id + '" style="border-top:1px solid #333;padding:4px 0;font-size:11px;">'
                             + '<div style="display:flex;justify-content:space-between;align-items:center;">'
                             + '<span style="color:#aaa;">' + rStars + ' <strong>' + escapeHtml(rv.username || '') + '</strong> <span style="color:#666;">' + date + '</span></span>'
                             + (canDel ? '<button onclick="deleteReview(' + rv.id + ',' + bid + ')" style="background:#c0392b;color:#fff;border:none;border-radius:3px;padding:1px 6px;font-size:10px;cursor:pointer;">✕</button>' : '')
                             + '</div>'
                             + '<div style="color:#ccc;margin-top:2px;">' + cmt + '</div>'
                             + '</div>';
                    });
                    html += '</div>';
                }

                el.innerHTML = html;
            } catch (err) {
                el.innerHTML = '';
            }
        }
        initWTPanelsInPopup(e.popup.getElement());
    });
    mapa.on('popupopen', (e) => {
        drawRelationLinesForPopup(e.popup?._source || null);
    });

    filtrar();
}

function getSelectionKey(kind, id) {
    return kind + ':' + id;
}

function buildSelectionMetadata(rawMeta = {}) {
    const meta = {};
    Object.entries(rawMeta).forEach(([label, value]) => {
        if (value === null || value === undefined) return;
        const normalized = String(value).trim();
        if (!normalized) return;
        meta[label] = normalized;
    });
    return meta;
}

function truncateHourLabel(value) {
    const raw = value === null || value === undefined ? '' : String(value).trim();
    if (!raw) return null;
    return raw.length >= 5 ? raw.substring(0, 5) : raw;
}

function getSelectionEmoji(kind) {
    if (kind === 'negocio') return '🏪';
    if (kind === 'marca') return '🏷️';
    if (kind === 'evento') return '🎉';
    if (kind === 'encuesta') return '📋';
    if (kind === 'noticia') return '📰';
    if (kind === 'trivia') return '🎯';
    if (kind === 'oferta') return '💰';
    if (kind === 'transmision') return '📡';
    return '📍';
}

function renderSelectionMetaItem(item) {
    const metaLines = Object.entries(item.metadata || {})
        .map(([label, value]) => `<div><strong>${escapeHtml(label)}:</strong> ${escapeHtml(value)}</div>`)
        .join('');
    return `<article class="selection-meta-item">
        <div class="selection-meta-item__title">${getSelectionEmoji(item.kind)} ${escapeHtml(item.name || 'Elemento')}</div>
        ${metaLines || '<div>Sin metadatos disponibles.</div>'}
    </article>`;
}

function buildNegocioMetadata(n) {
    const openHour = truncateHourLabel(n.horario_apertura);
    const closeHour = truncateHourLabel(n.horario_cierre);
    const meta = {
        [uiStr('lbl_type')]:       n.business_type,
        [uiStr('lbl_specialty')]:  n.tipo_comercio,
        [uiStr('lbl_products')]:   n.categorias_productos,
        [uiStr('lbl_hours')]:      (openHour && closeHour) ? `${openHour} a ${closeHour}` : null,
        [uiStr('lbl_address')]:    n.address || n.ubicacion,
        'Ciudad':                  n.ciudad,
        [uiStr('lbl_phone')]:      n.phone,
        [uiStr('lbl_email')]:      n.email,
        'Web':                     n.website,
        'Mapita ID':               n.mapita_id,
    };
    if (n.country_code) meta[uiStr('lbl_country')] = n.country_code;
    return buildSelectionMetadata(meta);
}

function buildMarcaMetadata(n) {
    return buildSelectionMetadata({
        'Rubro': n.rubro,
        'Clase Niza': n.clase_principal,
        'Ubicación': n.ubicacion || n.address,
        'Estado': n.estado,
        'Mapita ID': n.mapita_id
    });
}

function buildEventoMetadata(evt) {
    return buildSelectionMetadata({
        'Fecha': evt.fecha,
        'Hora': evt.hora,
        'Categoría': evt.categoria,
        'Ubicación': evt.ubicacion,
        'Organizador': evt.organizador,
        'Mapita ID': evt.mapita_id
    });
}

function buildEncuestaMetadata(enc) {
    return buildSelectionMetadata({
        'Descripción': enc.descripcion,
        'Estado activa': Number(enc.activo) === 1 ? 'Sí' : 'No',
        'Creación': enc.fecha_creacion,
        'Vencimiento': enc.fecha_expiracion,
        'Ubicación': enc.ubicacion,
        'Link': enc.link,
        'Mapita ID': enc.mapita_id
    });
}

function buildNoticiaMetadata(n) {
    return buildSelectionMetadata({
        'Categoría': n.categoria,
        'Vistas': n.vistas,
        'Fecha': n.fecha_publicacion ? String(n.fecha_publicacion).substring(0, 10) : null,
        'Ubicación': n.ubicacion,
        'Mapita ID': n.mapita_id
    });
}

function buildTriviaMetadata(tri) {
    return buildSelectionMetadata({
        'Dificultad': tri.dificultad,
        'Tiempo límite': tri.tiempo_limite ? tri.tiempo_limite + ' seg' : null,
        'Ubicación': tri.ubicacion,
        'Mapita ID': tri.mapita_id
    });
}

function buildOfertaMetadata(o) {
    return buildSelectionMetadata({
        'Precio normal': o.precio_normal ? '$' + parseFloat(o.precio_normal).toLocaleString() : null,
        'Precio oferta': o.precio_oferta ? '$' + parseFloat(o.precio_oferta).toLocaleString() : null,
        'Vencimiento': o.fecha_expiracion,
        'Mapita ID': o.mapita_id
    });
}

function buildTransmisionMetadata(tx) {
    return buildSelectionMetadata({
        'Tipo': tx.tipo,
        'En vivo': tx.en_vivo ? 'Sí' : 'No',
        'Mapita ID': tx.mapita_id
    });
}

function applySelectionVisual(marker, key) {
    if (!mapa || !marker?.getLatLng || !selectedItems.has(key)) return;
    const latlng = marker.getLatLng();
    const ring = L.circleMarker(latlng, {
        radius: 20,
        color: '#00d4ff',
        weight: 3,
        fillColor: '#00d4ff',
        fillOpacity: 0.08,
        interactive: false
    });
    selectionHighlightsLayer?.addLayer(ring);
    selectionHighlights.set(key, ring);
    const el = marker.getElement ? marker.getElement() : null;
    if (el) el.classList.add('mapita-selected-marker');
    if (marker instanceof L.CircleMarker) {
        if (!marker._selectionOriginalStyle) {
            marker._selectionOriginalStyle = {
                color: marker.options.color,
                weight: marker.options.weight,
                fillColor: marker.options.fillColor,
                fillOpacity: marker.options.fillOpacity
            };
        }
        marker.setStyle({ weight: 5, color: '#00d4ff' });
    }
}

function removeSelectionVisual(marker, key) {
    const ring = selectionHighlights.get(key);
    if (ring && selectionHighlightsLayer) selectionHighlightsLayer.removeLayer(ring);
    selectionHighlights.delete(key);
    const el = marker?.getElement ? marker.getElement() : null;
    if (el) el.classList.remove('mapita-selected-marker');
    if (marker instanceof L.CircleMarker && marker._selectionOriginalStyle) {
        marker.setStyle(marker._selectionOriginalStyle);
    }
}

function updateSelectionPanel() {
    const panel = document.getElementById('selection-panel');
    const summary = document.getElementById('selection-summary');
    const aggregateBtn = document.getElementById('selection-aggregate-btn');
    const step = document.getElementById('selection-step');
    const detailsToggle = document.getElementById('selection-details-toggle');
    const details = document.getElementById('selection-details');
    const hint = document.getElementById('selection-hint');
    if (!panel || !summary || !hint) return;
    const counts = { negocio: 0, marca: 0, evento: 0, encuesta: 0, noticia: 0, trivia: 0, oferta: 0, transmision: 0 };
    selectedItems.forEach(item => {
        if (counts[item.kind] !== undefined) counts[item.kind] += 1;
    });
    const total = selectedItems.size;
    const parts = [];
    if (counts.negocio)    parts.push('🏪 ' + counts.negocio);
    if (counts.marca)      parts.push('🏷️ ' + counts.marca);
    if (counts.evento)     parts.push('🎉 ' + counts.evento);
    if (counts.encuesta)   parts.push('📋 ' + counts.encuesta);
    if (counts.noticia)    parts.push('📰 ' + counts.noticia);
    if (counts.trivia)     parts.push('🎯 ' + counts.trivia);
    if (counts.oferta)     parts.push('💰 ' + counts.oferta);
    if (counts.transmision) parts.push('📡 ' + counts.transmision);
    summary.textContent = total + ' seleccionado' + (total !== 1 ? 's' : '') + (parts.length ? ' · ' + parts.join(' · ') : '');
    if (aggregateBtn) {
        aggregateBtn.disabled = counts.encuesta === 0;
    }
    if (details) {
        const items = Array.from(selectedItems.values());
        details.innerHTML = items.length > 0
            ? items.map(renderSelectionMetaItem).join('')
            : '';
    }
    if (detailsToggle) {
        const hidden = details?.classList.contains('selection-panel__details--hidden');
        detailsToggle.disabled = total === 0;
        detailsToggle.textContent = hidden ? '📋 Ver detalles' : '📕 Ocultar detalles';
    }
    if (step) {
        if (total === 0 && selectionMode) {
            step.textContent = 'Paso 1: seleccioná marcadores en el mapa.';
        } else if (total > 0 && selectionMode) {
            step.textContent = 'Paso 2: aplicá una acción sobre la selección.';
        } else if (total > 0) {
            step.textContent = 'La selección quedó guardada; podés exportar o limpiarla.';
        } else {
            step.textContent = 'Activá el modo selección y elegí marcadores.';
        }
    }
    hint.style.display = selectionMode && total === 0 ? 'block' : 'none';
    panel.style.display = (selectionMode || total > 0) ? 'block' : 'none';
    updateSelectionToggleState();
}

function updateSelectionToggleState() {
    const btn = document.getElementById('sel-multi');
    const icon = document.getElementById('sel-multi-icon');
    const label = document.getElementById('sel-multi-label');
    const status = document.getElementById('selection-mode-status');
    if (btn) {
        btn.style.background = selectionMode ? '#00d4ff' : '#00acc1';
    }
    if (icon) icon.textContent = selectionMode ? '✅' : '🧾';
    if (label) label.textContent = selectionMode ? 'Salir del modo selección' : 'Activar selección';
    if (status) {
        status.textContent = selectionMode
            ? 'Modo selección activo. Click en íconos para agregar/quitar; Shift + arrastrar dibuja cuadro. Para salir: botón o tecla S.'
            : 'Modo normal. Para activar el modo selección: botón “Activar selección” o tecla S.';
    }
}

function toggleSelectionDetails() {
    const details = document.getElementById('selection-details');
    if (!details) return;
    details.classList.toggle('selection-panel__details--hidden');
    updateSelectionPanel();
}

function dismissSelectionPanel() {
    selectionMode = false;
    applySelectionModeVisualState();
    clearSelection();
    updateSelectionPanel();
}

function applySelectionModeVisualState() {
    const mapContainer = document.getElementById('map');
    if (mapContainer) mapContainer.classList.toggle('selection-mode-active', selectionMode);
    if (!mapa) return;

    if (selectionMode) {
        mapa.dragging?.disable();
        mapa.scrollWheelZoom?.disable();
        mapa.doubleClickZoom?.disable();
        mapa.touchZoom?.disable();
        mapa.keyboard?.disable();
        mapa.boxZoom?.enable();
    } else {
        mapa.dragging?.enable();
        mapa.scrollWheelZoom?.enable();
        mapa.doubleClickZoom?.enable();
        mapa.touchZoom?.enable();
        mapa.keyboard?.enable();
        mapa.boxZoom?.disable();
    }
}

function toggleSelectionMode() {
    selectionMode = !selectionMode;
    applySelectionModeVisualState();
    if (selectionMode && mapa) mapa.closePopup();
    if (selectionMode) {
        const toast = document.createElement('div');
        toast.className = 'selection-mode-toast';
        toast.textContent = '✅ Modo selección activo · Hacé click en marcadores para seleccionar';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3200);
    }
    updateSelectionPanel();
}

function toggleSelection(item, marker) {
    const key = getSelectionKey(item.kind, item.id);
    if (selectedItems.has(key)) {
        selectedItems.delete(key);
        removeSelectionVisual(marker, key);
    } else {
        selectedItems.set(key, item);
        applySelectionVisual(marker, key);
    }
    const hint = document.getElementById('selection-hint');
    if (hint) hint.style.display = 'none';
    updateSelectionPanel();
}

function addMarkerToSelection(marker) {
    if (!marker?._selectionItem || !marker?._selectionKey) return false;
    if (selectedItems.has(marker._selectionKey)) return false;
    selectedItems.set(marker._selectionKey, marker._selectionItem);
    applySelectionVisual(marker, marker._selectionKey);
    return true;
}

function clearSelection() {
    selectedItems.clear();
    selectionHighlights.forEach((layer) => selectionHighlightsLayer?.removeLayer(layer));
    selectionHighlights.clear();
    [...marcadores, ...eventoMarkers, ...encuestaMarkers, ...noticiaMarkers, ...triviaMarkers, ...ofertaMarkers, ...transmisionMarkers].forEach((marker) => {
        const el = marker?.getElement ? marker.getElement() : null;
        if (el) el.classList.remove('mapita-selected-marker');
    });
    updateSelectionPanel();
    const result = document.getElementById('selection-aggregate-result');
    if (result) result.textContent = 'Selección vacía.';
}

function refreshSelectionVisuals() {
    if (!selectionHighlightsLayer) return;
    selectionHighlights.forEach((layer) => selectionHighlightsLayer.removeLayer(layer));
    selectionHighlights.clear();
    [...marcadores, ...eventoMarkers, ...encuestaMarkers, ...noticiaMarkers, ...triviaMarkers, ...ofertaMarkers, ...transmisionMarkers].forEach((marker) => {
        const key = marker?._selectionKey;
        if (!key) return;
        if (selectedItems.has(key)) applySelectionVisual(marker, key);
        else removeSelectionVisual(marker, key);
    });
}

function selectAllVisible() {
    if (!mapa) return;
    const bounds = mapa.getBounds();
    const allMarkers = [...marcadores, ...eventoMarkers, ...encuestaMarkers, ...noticiaMarkers, ...triviaMarkers, ...ofertaMarkers, ...transmisionMarkers];
    let added = 0;
    allMarkers.forEach(marker => {
        const latlng = marker.getLatLng ? marker.getLatLng() : null;
        if (!latlng || !bounds.contains(latlng)) return;
        if (addMarkerToSelection(marker)) added++;
    });
    updateSelectionPanel();
    const hint = document.getElementById('selection-hint');
    if (hint) hint.style.display = 'none';
    const result = document.getElementById('selection-aggregate-result');
    if (result) result.textContent = added > 0
        ? 'Se agregaron ' + added + ' elementos visibles.'
        : 'No había nuevos elementos visibles para agregar.';
}

function exportSelection() {
    if (selectedItems.size === 0) return;
    const data = Array.from(selectedItems.values()).map(item => ({
        tipo: item.kind,
        nombre: item.name,
        lat: item.lat,
        lng: item.lng,
        ...item.metadata
    }));
    const json = JSON.stringify(data, null, 2);
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const date = new Date().toISOString().split('T')[0];
    const a = document.createElement('a');
    a.href = url;
    a.download = 'mapita_seleccion_' + date + '.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

async function aggregateSelectedSurveys() {
    const result = document.getElementById('selection-aggregate-result');
    const ids = Array.from(selectedItems.values())
        .filter(item => item.kind === 'encuesta')
        .map(item => item.id);
    if (ids.length === 0) {
        if (result) result.textContent = 'Seleccioná al menos una encuesta.';
        return;
    }
    try {
        const res = await fetch('/api/encuestas.php?action=aggregate&ids=' + ids.join(','));
        const data = await res.json();
        if (!data.success) throw new Error(data.error || data.message || 'Error');
        const totalRespuestas = data.data?.summary?.total_respuestas ?? 0;
        const participantes = data.data?.summary?.total_participantes ?? 0;
        if (result) result.textContent = 'Encuestas: ' + ids.length + ' · Respuestas: ' + totalRespuestas + ' · Participantes: ' + participantes;
    } catch (err) {
        if (result) result.textContent = 'No se pudo agregar encuestas.';
    }
}

function getWTKey(entityType, entityId) {
    return entityType + ':' + entityId;
}

function stopLeafletEvent(e) {
    if (e?.originalEvent) L.DomEvent.stop(e.originalEvent);
}

function cleanupWTPopup(popupEl) {
    if (!popupEl || typeof popupEl.querySelectorAll !== 'function') return;
    popupEl.querySelectorAll('.wt-panel[data-entity-type][data-entity-id]').forEach(panel => {
        const key = getWTKey(panel.dataset.entityType, panel.dataset.entityId);
        const timers = wtPollers.get(key);
        if (timers) {
            clearInterval(timers.poll);
            clearInterval(timers.heartbeat);
            wtPollers.delete(key);
        }
    });
}

function renderWTMessages(panel, container, messages) {
    if (!panel || !container) return;
    if (!messages || messages.length === 0) {
        container.innerHTML = '<div class="wt-empty">Sin mensajes aún</div>';
        return;
    }
    container.innerHTML = messages.map(msg => {
        const messageId = parseInt(msg.id, 10) || 0;
        const user = escapeHtml(msg.user_name || 'Usuario');
        const text = escapeHtml(msg.message || '');
        const when = escapeHtml(msg.created_at || '');
        const isPreset = !!msg.is_preset;
        const canDismiss = !!msg.can_dismiss && messageId > 0;
        const closeBtn = canDismiss
            ? '<button type="button" class="wt-dismiss" data-wt-dismiss="' + messageId + '" title="Cerrar mensaje temporal">✕</button>'
            : '';
        return '<div class="wt-msg' + (isPreset ? '' : ' wt-msg-temp') + '" data-wt-msg-id="' + messageId + '">'
            + '<div class="wt-msg-head"><strong>' + user + '</strong>' + closeBtn + '</div>'
            + '<span class="wt-msg-text">' + text + '</span>'
            + '<small>' + when + '</small>'
            + '</div>';
    }).join('');
    container.scrollTop = container.scrollHeight;
}

async function loadWTMessages(panel) {
    const entityType = panel.dataset.entityType;
    const entityId = panel.dataset.entityId;
    const listEl = panel.querySelector('[data-wt-messages]');
    const key = getWTKey(entityType, entityId);
    const state = wtPollers.get(key) || {};
    const hadLastId = !!state.lastId;
    const params = new URLSearchParams({
        action: 'list',
        entity_type: entityType,
        entity_id: entityId
    });
    if (hadLastId) params.set('since_id', String(state.lastId));
    const response = await fetch('/api/wt.php?' + params.toString());
    const data = await response.json();
    if (!data.success) return;
    const messages = data.data?.messages || [];
    if (messages.length > 0) {
        state.lastId = messages[messages.length - 1].id;
        wtPollers.set(key, state);
    }
    if (!hadLastId || messages.length > 0) {
        const mergedMessages = hadLastId
            ? ((state.cached || []).concat(messages)).slice(-WT_MESSAGE_CACHE_LIMIT)
            : messages.slice(-WT_MESSAGE_CACHE_LIMIT);
        renderWTMessages(panel, listEl, mergedMessages);
        state.cached = mergedMessages;
    }
    const count = data.data?.presence_count;
    const header = panel.querySelector('.wt-header');
    if (header && typeof count !== 'undefined') {
        header.textContent = '📻 WT · ' + (panel.dataset.entityTitle || '') + ' · 👥 ' + count;
    }
}

async function sendWTHeartbeat(panel) {
    const body = new URLSearchParams({
        action: 'heartbeat',
        entity_type: panel.dataset.entityType,
        entity_id: panel.dataset.entityId
    });
    await fetch('/api/wt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    });
}

async function sendWTMessage(panel) {
    const input = panel.querySelector('[data-wt-input]');
    if (!input) return;
    const message = input.value.trim().slice(0, WT_MAX_MESSAGE_LEN);
    if (!message) return;
    const body = new URLSearchParams({
        action: 'send',
        entity_type: panel.dataset.entityType,
        entity_id: panel.dataset.entityId,
        message: message
    });
    const response = await fetch('/api/wt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    });
    const data = await response.json();
    if (!data.success) {
        alert(data.error || data.message || 'No se pudo enviar el mensaje');
        return;
    }
    input.value = '';
    await loadWTMessages(panel);
}

async function dismissWTMessage(panel, messageId) {
    const parsedMessageId = parseInt(messageId, 10) || 0;
    if (!parsedMessageId) return;
    const body = new URLSearchParams({
        action: 'dismiss',
        message_id: String(parsedMessageId),
        entity_type: panel.dataset.entityType,
        entity_id: panel.dataset.entityId
    });
    const response = await fetch('/api/wt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    });
    const data = await response.json();
    if (!data.success) {
        alert(data.error || data.message || 'No se pudo cerrar el mensaje');
        return;
    }
    const key = getWTKey(panel.dataset.entityType, panel.dataset.entityId);
    const state = wtPollers.get(key) || {};
    state.lastId = null;
    state.cached = [];
    wtPollers.set(key, state);
    await loadWTMessages(panel);
}

function initWTPanel(panel) {
    if (!panel || panel.dataset.wtInit === '1') return;
    panel.dataset.wtInit = '1';
    const entityType = panel.dataset.entityType;
    const entityId = panel.dataset.entityId;
    const key = getWTKey(entityType, entityId);
    panel.querySelectorAll('[data-wt-preset]').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = panel.querySelector('[data-wt-input]');
            if (input) input.value = (btn.getAttribute('data-wt-preset') || '').slice(0, WT_MAX_MESSAGE_LEN);
        });
    });
    const sendBtn = panel.querySelector('[data-wt-send]');
    if (sendBtn) sendBtn.addEventListener('click', () => sendWTMessage(panel));
    const input = panel.querySelector('[data-wt-input]');
    if (input) {
        input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                sendWTMessage(panel);
            }
        });
    }
    const listEl = panel.querySelector('[data-wt-messages]');
    if (listEl) {
        listEl.addEventListener('click', (ev) => {
            const target = ev.target?.closest?.('[data-wt-dismiss]');
            if (!target) return;
            const messageId = target.getAttribute('data-wt-dismiss');
            dismissWTMessage(panel, messageId).catch(() => {});
        });
    }
    wtPollers.set(key, { poll: null, heartbeat: null, lastId: null, cached: [] });
    // Cargar estado del canal y luego mensajes
    loadWTChannelStatus(panel).catch(() => {}).finally(() => {
        loadWTMessages(panel).catch(() => {});
    });
    sendWTHeartbeat(panel).catch(() => {});
    const poll = setInterval(() => loadWTMessages(panel).catch(() => {}), WT_POLL_INTERVAL_MS);
    const heartbeat = setInterval(() => sendWTHeartbeat(panel).catch(() => {}), WT_HEARTBEAT_INTERVAL_MS);
    const state = wtPollers.get(key) || {};
    state.poll = poll;
    state.heartbeat = heartbeat;
    wtPollers.set(key, state);
}

function initWTPanelsInPopup(popupEl) {
    if (!popupEl) return;
    popupEl.querySelectorAll('.wt-panel[data-entity-type][data-entity-id]').forEach(initWTPanel);
}

// ─── Filtering (ENHANCED with accordion filters) ────────────────────────────────
function filtrar() {
    [...circles, ...licenseMarkers, ...franchiseMarkers, ...exclusiveMarkers,
     ...myBusinessMarkers, ...propertyZoneMarkers].forEach(l => mapa.removeLayer(l));
    circles=[]; licenseMarkers=[]; franchiseMarkers=[]; exclusiveMarkers=[];
    myBusinessMarkers=[]; propertyZoneMarkers=[];
    clearSingleOverlays();
    clearRelationLines();

    if (currentVer === 'ninguno') {
    document.getElementById('stats').innerHTML =
        '<span class="stats-total">🏪&nbsp;0</span><span class="stats-sep">|</span><span class="stats-badge">🏷️&nbsp;0</span>';
        mostrarLista([], { geoRequired: false });
        mostrarMarcadores([]);
        return;
    }

    // ─── Get filter values ───────────────────────────────────────────────────────
    const tipo  = document.getElementById('tipo').value.toLowerCase();
    const texto = document.getElementById('busqueda').value.toLowerCase();
    const locationFilter = getLocationFilter();
    const locationCity = getLocationCityFilter();
    const priceFilter = getPriceFilter();
    const timeFilter = getTimeFilter();
    const daysFilter = getDaysFilter();
    const companyTypeFilter = getCompanyTypeFilter();
    const protectionFilter = getProtectionFilter();
    const sectorFilter = getSectorFilter();
    const countryFilter = (document.getElementById('filter-country-code')?.value || '').trim();
    const languageFilter = (document.getElementById('filter-language-code')?.value || '').trim().toLowerCase();

    // Update UI visibility for brand/business-only filters
    const showBrandFilters = (currentVer === 'marcas' || currentVer === 'ambos') || franquiciasFilter;
    document.getElementById('filter-protection-container').style.display = showBrandFilters ? '' : 'none';
    document.getElementById('filter-sector-container').style.display = showBrandFilters ? '' : 'none';

    // ─── Filter negocios ────────────────────────────────────────────────────────
    let negocios_filtered = [];
    if (currentVer === 'negocios' || currentVer === 'ambos') {
        negocios_filtered = negocios.filter(n => {
            // Basic filters
            if (tipo && n.business_type !== tipo) return false;
            if (texto && !(n.name||'').toLowerCase().includes(texto) && !(n.address||'').toLowerCase().includes(texto)) return false;

            // Location filter (radius)
            if (locationFilter && miUbicacion && n.lat && n.lng) {
                const dist = calcularDistancia(miUbicacion.lat, miUbicacion.lng, n.lat, n.lng);
                if (dist > locationFilter.radius) return false;
            }

            // Location city filter
            if (locationCity && !((n.address||'').toLowerCase().includes(locationCity))) return false;

            // Price filter
            if (priceFilter && !priceFilter.includes(n.price_range)) return false;

            // Time filter: only open now
            if (timeFilter) {
                if (!pasaFiltroAbiertosAhora(n)) return false;
            }

            // Days filter
            if (daysFilter && daysFilter.length > 0) {
                const { day: currentDay } = getNowInTimezone(n.timezone || 'America/Argentina/Buenos_Aires');
                if (!daysFilter.includes(currentDay)) return false;
                // Also check if closed on this day
                if (n.dias_cierre && n.dias_cierre.toLowerCase().replace(/\s/g,'').split(',').includes(currentDay)) {
                    return false;
                }
            }

            // Company type filter
            if (companyTypeFilter && !companyTypeFilter.includes(inferCompanyType(n))) return false;

            // Country filter
            if (countryFilter && n.country_code !== countryFilter) return false;

            // Language filter
            if (languageFilter) {
                const nLang = (n.language_code || '').toLowerCase();
                if (!nLang || !nLang.startsWith(languageFilter.split('-')[0])) return false;
            }

            return true;
        }).map(n => ({...n, tipo: 'negocio'}));
    }

    // ─── Filter marcas ──────────────────────────────────────────────────────────
    let marcas_filtered = [];
    if (currentVer === 'marcas' || currentVer === 'ambos') {
        marcas_filtered = marcas.filter(m => {
            // Basic filter
            if (texto && !(m.nombre||'').toLowerCase().includes(texto)) return false;

            // FRANQUICIAS filter: solo marcas con crear_franquicia=1
            if (franquiciasFilter && !(m.crear_franquicia == 1 || m.crear_franquicia === true || m.crear_franquicia === '1')) return false;

            // Protection filter
            if (protectionFilter && !protectionFilter.includes(m.nivel_proteccion)) return false;

            // Sector filter
            if (sectorFilter) {
                if (sectorFilter.text && !(m.rubro||'').toLowerCase().includes(sectorFilter.text)) return false;
                if (sectorFilter.selected.length > 0 && !sectorFilter.selected.includes(m.rubro)) return false;
            }

            // Country filter (marcas)
            if (countryFilter && m.country_code !== countryFilter) return false;

            // Language filter (marcas)
            if (languageFilter) {
                const mLang = (m.language_code || '').toLowerCase();
                if (!mLang || !mLang.startsWith(languageFilter.split('-')[0])) return false;
            }

            return true;
        }).map(m => ({...m, tipo: 'marca'}));
    }

    let items = [...negocios_filtered, ...marcas_filtered];

    const cntN = items.filter(i => i.tipo === 'negocio').length;
    const cntM = items.filter(i => i.tipo === 'marca').length;
    const sbRadius = parseInt(document.getElementById('sb-radius-select')?.value) || 5;

    // Sidebar: requires geolocation; filter by radius and sort by distance ascending
    let sidebarItems = [];
    if (miUbicacion) {
        sidebarItems = items.filter(i => {
            if (!i.lat || !i.lng) return false;
            return calcularDistancia(miUbicacion.lat, miUbicacion.lng, i.lat, i.lng) <= sbRadius;
        });
        sidebarItems.sort((a, b) =>
            calcularDistancia(miUbicacion.lat, miUbicacion.lng, a.lat, a.lng) -
            calcularDistancia(miUbicacion.lat, miUbicacion.lng, b.lat, b.lng)
        );
    }

    /* Mejora UX: usar innerHTML con badges para mejor separación visual de datos */
    document.getElementById('stats').innerHTML =
        `<span class="stats-total">Total:&nbsp;${cntN + cntM}</span>`
        + `<span class="stats-sep">|</span>`
        + `<span class="stats-badge">📍&nbsp;${sidebarItems.length}&nbsp;cerca&nbsp;(${sbRadius}&nbsp;km)</span>`;

    mostrarLista(sidebarItems, { geoRequired: true, sbRadius });
    mostrarMarcadores(items);
}

// ─── List rendering ──────────────────────────────────────────────────────────────
function mostrarLista(lista, opts) {
    const contenedor = document.getElementById('lista');
    contenedor.innerHTML = '';

    const geoRequired = !opts || opts.geoRequired !== false;
    const sbRadius = (opts && opts.sbRadius) || 5;

    if (geoRequired && !miUbicacion) {
        contenedor.innerHTML = '<div style="padding:16px 12px;text-align:center;color:#667eea;font-size:13px;line-height:1.5;">📍 Activá tu ubicación para ver negocios cercanos.</div>';
        return;
    }

    const total = lista.length;
    const limited = lista.slice(0, SIDEBAR_LIST_LIMIT);

    const negs = limited.filter(i => i.tipo === 'negocio');
    const mrks = limited.filter(i => i.tipo === 'marca');

    if (currentVer === 'ambos' && negs.length && mrks.length) {
        addListHeader(contenedor, '🏪 Negocios (' + negs.length + ')');
        negs.forEach(n => addListItem(contenedor, n));
        addListHeader(contenedor, '🏷️ Marcas (' + mrks.length + ')');
        mrks.forEach(m => addListItem(contenedor, m));
    } else {
        limited.forEach(n => addListItem(contenedor, n));
    }

    if (total > SIDEBAR_LIST_LIMIT) {
        const hint = document.createElement('div');
        hint.style.cssText = 'padding:8px 12px;text-align:center;color:#888;font-size:11px;border-top:1px solid #eef0f5;margin-top:4px;';
        hint.textContent = `Mostrando ${SIDEBAR_LIST_LIMIT} de ${total} dentro de ${sbRadius} km. Refiná con filtros para ver más.`;
        contenedor.appendChild(hint);
    }
}

function addListHeader(container, text) {
    const h = document.createElement('div');
    h.className = 'section-header';
    h.textContent = text;
    container.appendChild(h);
}

function addListItem(container, n) {
    const isMarca = n.tipo === 'marca';
    const div = document.createElement('div');
    div.classList.add(isMarca ? 'marca' : 'negocio');

    let dText = '';
    if (miUbicacion && n.lat && n.lng) {
        dText = ' · ' + calcularDistancia(miUbicacion.lat, miUbicacion.lng, n.lat, n.lng).toFixed(1) + ' km';
    }

    const name    = n.nombre || n.name    || 'Sin nombre';
    const address = n.ubicacion || n.address || 'Sin dirección';
    const type    = isMarca ? '🏷️ Marca' : (n.business_type || 'Negocio');

    // Open/closed mini indicator for list items
    const openStatus = (!isMarca && !isVehicleSaleType(n) && !isRemateType(n)) ? estaAbierto(n) : null;
    const dot = openStatus === true  ? '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#2ecc71;margin-left:4px;vertical-align:middle;"></span>'
              : openStatus === false ? '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#e74c3c;margin-left:4px;vertical-align:middle;"></span>'
              : '';

    div.innerHTML =
        '<strong>' + name + '</strong>' + dot +
        '<br><span style="color:#666;font-size:12px;">' + address + '</span>' +
        '<br><small style="color:#aaa;">' + type + dText + '</small>';

    // A7: open popup automatically when clicking list item
    if (n.lat && n.lng) div.onclick = () => focusMarker(n.lat, n.lng);
    container.appendChild(div);
}

// ─── A7: Focus marker and open its popup ─────────────────────────────────────────
function focusMarker(lat, lng) {
    mapa.setView([lat, lng], 15);
    const m = marcadores.find(mk => {
        const ll = mk.getLatLng ? mk.getLatLng() : null;
        return ll && Math.abs(ll.lat - lat) < 0.00005 && Math.abs(ll.lng - lng) < 0.00005;
    });
    if (!m) return;
    if (clusterGroup && clusterGroup.zoomToShowLayer) {
        clusterGroup.zoomToShowLayer(m, () => setTimeout(() => m.openPopup(), 100));
    } else {
        m.openPopup();
    }
}

// ─── Marker rendering ────────────────────────────────────────────────────────────
function mostrarMarcadores(lista) {
    clusterGroup.clearLayers();
    marcadores = [];

    lista.forEach(n => {
        if (n.lat == null || n.lng == null) return;

        const isMarca = n.tipo === 'marca';
        const itemKind = isMarca ? 'marca' : 'negocio';
        const itemId = parseInt(n.id, 10);
        let marker;

        if (isMarca) {
            if (n.logo_url) {
                // ── Icono de foto circular ────────────────────────────────────
                const html = `<div style="
                    width:44px;height:44px;border-radius:50%;
                    border:3px solid #1B3B6F;
                    box-shadow:0 2px 8px rgba(0,0,0,.35);
                    background:url('${n.logo_url}') center/cover no-repeat #fff;
                    position:relative;">
                    <div style="position:absolute;bottom:-2px;right:-2px;
                        background:#1B3B6F;color:white;border-radius:50%;
                        width:16px;height:16px;font-size:9px;
                        display:flex;align-items:center;justify-content:center;
                        border:2px solid white;">🏷️</div>
                </div>`;
                marker = L.marker([n.lat, n.lng], {
                    icon: L.divIcon({
                        html: html, className: '',
                        iconSize: [44, 44], iconAnchor: [22, 44], popupAnchor: [0, -46]
                    })
                });
            } else {
                // ── Círculo de color (fallback sin logo) ──────────────────────
                const color = getNizaColor(n.clase_principal);
                marker = L.circleMarker([n.lat, n.lng], {
                    radius: 14, fillColor: color, color: '#fff', weight: 3, fillOpacity: 0.9
                });
            }
        } else {
            // A1: SVG icon with open/closed dot (cargado dinámicamente desde API)
            const iconData = iconosDB[n.business_type] || { emoji: '📍', color: '#667eea' };
            const color = iconData.color;
            const emoji = iconData.emoji;
            const open  = (isVehicleSaleType(n) || isRemateType(n)) ? null : estaAbierto(n);
            const hasOfertaDestacada = !!(n.oferta_destacada_id || n.oferta_activa_id);
            const icon = hasOfertaDestacada ? createSvgIconWithOffer(emoji, color, open) : createSvgIcon(emoji, color, open);
            marker = L.marker([n.lat, n.lng], { icon });
        }
        marker._mapitaMeta = {
            entity_type: isMarca ? 'brand' : 'business',
            entity_id: n.id || null,
            mapita_id: n.mapita_id || null
        };

        const name = n.nombre || n.name || 'Sin nombre';

        marker.bindPopup(buildPopup(n, isMarca), { maxWidth: 290 });

        marker._selectionKey = getSelectionKey(itemKind, itemId);
        marker._selectionItem = {
            kind: itemKind,
            id: itemId,
            name: name,
            lat: parseFloat(n.lat),
            lng: parseFloat(n.lng),
            metadata: isMarca ? buildMarcaMetadata(n) : buildNegocioMetadata(n)
        };

        marker.on('mouseover', function() { showCtxPanel(marker, n, isMarca ? 'brand' : 'business'); });
        marker.on('mouseout',  function() { hideCtxPanel(); });
        marker.on('click', function(e) {
            if (selectionMode) {
                stopLeafletEvent(e);
                toggleSelection(marker._selectionItem, marker);
                if (marker.closePopup) marker.closePopup();
                return;
            }
            if (isMarca) {
                marker.openPopup();
            }
        });
        marker.on('preclick', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
        });

        clusterGroup.addLayer(marker);
        marcadores.push(marker);
    });
    refreshSelectionVisuals();
}

// ─── Popup builder ───────────────────────────────────────────────────────────────
function buildWTPopupSection(entityType, entityId, title) {
    const safeTitle     = escapeHtml(title || '');
    const safeType      = escapeHtml(entityType);
    const safeEntityId  = escapeHtml(String(parseInt(entityId, 10) || 0));
    const panelId       = 'wt-panel-' + safeType + '-' + safeEntityId;
    const presets       = ['Hola 👋', 'Estoy cerca 📍', '¿Hay novedades?', 'Gracias 🙌'];
    const presetButtons = presets
        .map(t => '<button type="button" class="wt-preset" data-wt-preset="' + escapeHtml(t) + '">' + escapeHtml(t) + '</button>')
        .join('');

    const toggleBtn =
        '<div style="margin:6px 0 2px;">' +
            '<button type="button"' +
            ' onclick="(function(b){' +
                'var p=document.getElementById(\'' + panelId + '\');' +
                'var open=p.style.display===\'block\';' +
                'p.style.display=open?\'none\':\'block\';' +
                'b.setAttribute(\'aria-expanded\',!open);' +
                'if(!open&&!p.dataset.wtInitDone){' +
                    'p.dataset.wtInitDone=\'1\';' +
                    'initWTPanelsInPopup(p.parentElement);' +
                '}' +
            '})(this)"' +
            ' aria-expanded="false"' +
            ' style="background:#1f2937;display:inline-flex;align-items:center;gap:6px;' +
                    'padding:6px 12px;border-radius:8px;border:none;cursor:pointer;color:white;' +
                    'font-size:12px;font-weight:600;transition:background 0.2s;">' +
                '<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" style="flex-shrink:0;">' +
                    '<rect x="8" y="6" width="8" height="14" rx="2" stroke="currentColor" fill="none" stroke-width="1.8"/>' +
                    '<line x1="12" y1="2" x2="12" y2="6" stroke="currentColor" stroke-width="1.8"/>' +
                    '<circle cx="12" cy="12" r="2" fill="currentColor"/>' +
                '</svg>' +
                '<span style="font-style:italic;color:#60a5fa;">Mensajes WT</span>' +
            '</button>' +
        '</div>';

    const panel =
        '<div id="' + panelId + '" class="wt-panel"' +
            ' data-entity-type="' + safeType + '"' +
            ' data-entity-id="' + safeEntityId + '"' +
            ' data-entity-title="' + safeTitle + '"' +
            ' style="display:none;">' +
            '<div class="wt-header">📻 WT · ' + safeTitle +
                '<span class="wt-status-badge" data-wt-status-badge aria-label="Estado del canal WT"></span>' +
            '</div>' +
            '<div class="wt-channel-notice" data-wt-channel-notice style="display:none;"></div>' +
            '<div class="wt-presets">' + presetButtons + '</div>' +
            '<div class="wt-messages" data-wt-messages><div class="wt-empty">Sin mensajes aún</div></div>' +
            '<div class="wt-footer">' +
                '<input maxlength="' + WT_MAX_MESSAGE_LEN + '" class="wt-input" data-wt-input placeholder="Mensaje corto (máx. ' + WT_MAX_MESSAGE_LEN + ')">' +
                '<button type="button" class="wt-send" data-wt-send>Enviar</button>' +
            '</div>' +
        '</div>';

    return toggleBtn + panel;
}

// ─── WT Channel status ────────────────────────────────────────────────────────
async function loadWTChannelStatus(panel) {
    const entityType = panel.dataset.entityType;
    const entityId   = panel.dataset.entityId;
    const badge      = panel.querySelector('[data-wt-status-badge]');
    const notice     = panel.querySelector('[data-wt-channel-notice]');
    const footer     = panel.querySelector('.wt-footer');
    if (!badge) return;
    try {
        const params = new URLSearchParams({ action: 'status', entity_type: entityType, entity_id: entityId });
        const res  = await fetch('/api/wt.php?' + params.toString());
        const data = await res.json();
        if (!data.success) return;
        const { status, reason } = data.data || {};
        badge.className = 'wt-status-badge wt-status-' + (status || 'open');
        const statusLabels = { open: '🟢', closed: '🔴', blocked: '🚫', restricted: '🟡', self_closed: '🔴' };
        badge.textContent = statusLabels[status] || '⚪';
        badge.title       = reason || '';
        if (status && status !== 'open') {
            if (notice) {
                notice.style.display = 'block';
                notice.className = 'wt-channel-notice wt-notice-' + status;
                const link = SESSION_USER_ID > 0
                    ? ' <a href="/views/wt_preferences.php" target="_blank">Configurar WT</a>'
                    : '';
                notice.innerHTML = (reason || 'Canal restringido') + '.' + link;
            }
            if (footer) {
                const input  = footer.querySelector('[data-wt-input]');
                const sendBtn = footer.querySelector('[data-wt-send]');
                if (input)   { input.disabled = true; input.placeholder = 'Canal no disponible'; }
                if (sendBtn) { sendBtn.disabled = true; }
            }
        } else {
            if (notice) notice.style.display = 'none';
            if (footer) {
                const input  = footer.querySelector('[data-wt-input]');
                const sendBtn = footer.querySelector('[data-wt-send]');
                if (input)   { input.disabled = false; input.placeholder = 'Mensaje corto (máx. ' + WT_MAX_MESSAGE_LEN + ')'; }
                if (sendBtn) { sendBtn.disabled = false; }
            }
        }
    } catch { /* ignorar errores de red */ }
}

function toggleBrandLegalInfo(btn) {
    var tooltip = btn.parentNode.nextElementSibling;
    var open = tooltip.classList.toggle('brand-legal-tooltip--open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}

function buildPopup(n, isMarca) {
    const name    = n.nombre || n.name    || 'Sin nombre';
    const address = n.ubicacion || n.address || '';
    const relType = isMarca ? 'brand' : 'business';
    const relId = n.id || '';
    const relMapitaId = n.mapita_id || '';

    let p = '<div style="font-family: inherit;" data-rel-entity-type="' + relType + '" data-rel-entity-id="' + relId + '" data-rel-mapita-id="' + relMapitaId + '">';

    // PROFESSIONAL HEADER with icon + type badge
    p += '<div class="popup-header' + (isMarca ? ' popup-header--brand' : '') + '">';
    p += '<div class="popup-header-inner">';

    // ── Icon column ──
    if (isMarca) {
        const safeLogo = (n.logo_url && /^https?:\/\//i.test(n.logo_url)) ? n.logo_url : null;
        if (safeLogo) {
            p += '<div class="popup-header-icon"><img src="' + escapeHtml(safeLogo) + '" alt="Logo ' + escapeHtml(name) + '"></div>';
        } else {
            p += '<div class="popup-header-icon">🏷️</div>';
        }
    } else {
        const iconData = iconosDB[n.business_type] || {};
        const popupEmoji = iconData.emoji || BUSINESS_FALLBACK_EMOJI[n.business_type] || '📍';
        p += '<div class="popup-header-icon">' + popupEmoji + '</div>';
    }

    // ── Text column ──
    p += '<div class="popup-header-text">';
    p += '<h3>' + escapeHtml(name) + '</h3>';

    if (!isMarca && n.business_type) {
        const typeLabel = BUSINESS_TYPE_LABELS[n.business_type] || n.business_type;
        p += '<span class="popup-type-badge">' + typeLabel + '</span>';
    } else if (isMarca && n.rubro) {
        p += '<span class="popup-type-badge">' + n.rubro + '</span>';
    }

    if (!isMarca) {
        if (isRemateType(n)) {
            const remateStatus = getRemateStatus(n);
            p += '<br><span class="status-badge">' + remateStatus.label + '</span>';
        } else if (!isVehicleSaleType(n)) {
            // For regular businesses: add open/closed status badge
            const openStatus = estaAbierto(n);
            if (openStatus === true)  p += '<br><span class="status-badge">🟢 Abierto ahora</span>';
            if (openStatus === false) p += '<br><span class="status-badge">🔴 Cerrado</span>';
        }
    }
    p += '</div>'; // Close popup-header-text
    p += '</div>'; // Close popup-header-inner
    p += '</div>'; // Close popup-header

    p += '<div class="popup-body">';

    if (isMarca) {
        // ── Brand popup ──────────────────────────────────────────────────────

        // Status badges row
        const inpiReg    = n.inpi_registrada == 1 || n.inpi_registrada === true || n.inpi_registrada === '1';
        const esFranq    = n.es_franquicia == 1 || n.es_franquicia === true || n.es_franquicia === '1';
        const tieneL     = n.tiene_licencia == 1 || n.tiene_licencia === true || n.tiene_licencia === '1';
        const crearFranq = n.crear_franquicia == 1 || n.crear_franquicia === true || n.crear_franquicia === '1';
        p += '<div class="brand-status-row">';
        if (inpiReg) {
            p += '<span class="brand-status-badge brand-status-badge--inpi">✅ INPI Registrada</span>';
        } else {
            p += '<span class="brand-status-badge brand-status-badge--hecho">⚪ Marca de Hecho</span>';
        }
        if (crearFranq) p += '<span class="brand-status-badge brand-status-badge--franquicia" style="background:#7c3aed;">🤝 Franquicia disponible</span>';
        else if (esFranq) p += '<span class="brand-status-badge brand-status-badge--franquicia">🏢 Franquicia</span>';
        if (tieneL)  p += '<span class="brand-status-badge brand-status-badge--licencia">📜 Con Licencia</span>';
        p += '<button type="button" class="brand-legal-hint-btn" aria-label="Información legal Ley 22.362"'
           + ' onclick="toggleBrandLegalInfo(this)"'
           + ' aria-expanded="false">?</button>';
        p += '</div>';
        // Legal info panel (toggles on ? click)
        p += '<div class="brand-legal-tooltip" role="note">'
           + '<p><strong>Ley 22.362 — Marcas y designaciones (Arg)</strong></p>'
           + '<p>Estos activos intangibles pueden ser capitalizados adecuadamente si reúnen los requisitos esenciales.</p>'
           + '<p>Para más información '
           + '<a href="https://www.fariasortiz.com.ar/marcas.html" target="_blank" rel="noopener noreferrer" class="brand-legal-link">Estudio Farías Ortiz ↗</a>'
           + '</p>'
           + '</div>';

        // Clase Niza + Rubro pills
        if (n.clase_principal || n.rubro) {
            p += '<div style="margin-bottom:10px;display:flex;flex-wrap:wrap;gap:5px;align-items:center;">';
            if (n.clase_principal) {
                p += '<span style="background:' + getNizaColor(n.clase_principal) + ';color:white;padding:3px 9px;border-radius:10px;font-size:11px;font-weight:700;">📋 CLASE ' + escapeHtml(String(n.clase_principal)) + '</span>';
            }
            if (n.rubro) {
                p += '<span style="background:#f0ecff;color:#6a2fa2;padding:3px 9px;border-radius:10px;font-size:11px;font-weight:600;">🏷️ ' + escapeHtml(n.rubro) + '</span>';
            }
            p += '</div>';
        }

        // Description excerpt
        const descText = n.description || n.extended_description || '';
        if (descText && descText.trim()) {
            const exc = descText.length > 110 ? descText.substring(0, 110) + '…' : descText;
            p += '<p style="margin:0 0 10px;color:#555;font-size:12px;line-height:1.5;">' + escapeHtml(exc) + '</p>';
        }

        // INPI registration details
        if (inpiReg && (n.inpi_numero || n.inpi_fecha_registro || n.inpi_vencimiento || n.inpi_tipo)) {
            p += '<div class="brand-inpi-grid">';
            if (n.inpi_tipo)           p += '<div class="brand-inpi-item"><span class="label">Tipo</span><span class="value">' + escapeHtml(n.inpi_tipo) + '</span></div>';
            if (n.inpi_numero)         p += '<div class="brand-inpi-item"><span class="label">N° Registro</span><span class="value">' + escapeHtml(n.inpi_numero) + '</span></div>';
            if (n.inpi_fecha_registro) p += '<div class="brand-inpi-item"><span class="label">Registrada</span><span class="value">' + escapeHtml(n.inpi_fecha_registro) + '</span></div>';
            if (n.inpi_vencimiento)    p += '<div class="brand-inpi-item"><span class="label">Vencimiento</span><span class="value">' + escapeHtml(n.inpi_vencimiento) + '</span></div>';
            p += '</div>';
        }

        // Protection level + risk
        if (n.nivel_proteccion || n.riesgo_oposicion) {
            p += '<div class="brand-protection-row">';
            if (n.nivel_proteccion) {
                const pCol = n.nivel_proteccion==='Alta'?'#155724':n.nivel_proteccion==='Media'?'#856404':'#721c24';
                const pBg  = n.nivel_proteccion==='Alta'?'#d4edda':n.nivel_proteccion==='Media'?'#fff3cd':'#f8d7da';
                p += '<span style="background:' + pBg + ';color:' + pCol + ';padding:3px 9px;border-radius:10px;font-size:11px;font-weight:700;">🛡️ Protección ' + escapeHtml(n.nivel_proteccion) + '</span>';
            }
            if (n.riesgo_oposicion) {
                p += '<span style="background:#ffeeba;color:#856404;padding:3px 9px;border-radius:10px;font-size:11px;font-weight:600;">⚠️ Riesgo: ' + escapeHtml(n.riesgo_oposicion) + '</span>';
            }
            p += '</div>';
        }

        // Franchise details (legacy field + new crear_franquicia panel)
        // crearFranq is already defined above from n.crear_franquicia
        if (esFranq && n.franchise_details && !crearFranq) {
            p += '<div class="popup-section" style="margin-bottom:8px;">';
            p += '<div class="popup-label">🏢 Detalles de franquicia</div>';
            p += '<div class="popup-value" style="font-size:12px;">' + escapeHtml(n.franchise_details) + '</div>';
            p += '</div>';
        }
        if (crearFranq) {
            p += '<div class="popup-section" style="background:#f5f3ff;border:1px solid #c4b5fd;border-radius:8px;padding:10px 12px;margin-bottom:8px;">';
            p += '<div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">';
            p += '<span style="font-size:16px;">🤝</span>';
            p += '<span style="font-weight:700;color:#7c3aed;font-size:13px;">FRANQUICIA DISPONIBLE</span>';
            p += '<a href="https://www.alfoweb.com.ar" target="_blank" rel="noopener noreferrer" '
               + 'title="Conocé ALFO — sitios web gratuitos" '
               + 'style="margin-left:auto;font-size:14px;text-decoration:none;" aria-label="ALFO web gratuito">💡</a>';
            p += '</div>';
            if (n.franquicia_descripcion) {
                p += '<p style="margin:0 0 6px;font-size:12px;color:#374151;line-height:1.45;">' + escapeHtml(n.franquicia_descripcion) + '</p>';
            }
            const fItems = [];
            if (n.franquicia_condiciones)  fItems.push(['📋 Condiciones', n.franquicia_condiciones]);
            if (n.franquicia_territorio)   fItems.push(['🗺️ Territorio', n.franquicia_territorio]);
            if (n.franquicia_productos)    fItems.push(['📦 Productos', n.franquicia_productos]);
            if (n.franquicia_garantias)    fItems.push(['🛡️ Garantías', n.franquicia_garantias]);
            const exclVal = n.franquicia_exclusividad == 1 || n.franquicia_exclusividad === true || n.franquicia_exclusividad === '1';
            if (exclVal) fItems.push(['🎯 Exclusividad', 'Con exclusividad territorial']);
            if (fItems.length) {
                p += '<div style="display:grid;gap:4px;margin-top:4px;">';
                fItems.forEach(([lbl, val]) => {
                    p += '<div style="font-size:11px;">'
                       + '<span style="font-weight:700;color:#7c3aed;">' + escapeHtml(lbl) + ':</span> '
                       + '<span style="color:#374151;">' + escapeHtml(String(val)) + '</span>'
                       + '</div>';
                });
                p += '</div>';
            }
            if (n.franquicia_url) {
                let safeFrqUrl = null;
                try {
                    const uf = new URL(String(n.franquicia_url));
                    if (uf.protocol === 'https:' || uf.protocol === 'http:') safeFrqUrl = escapeHtml(uf.href);
                } catch (_) { /* URL inválida */ }
                if (safeFrqUrl) {
                    p += '<a href="' + safeFrqUrl + '" target="_blank" rel="noopener" '
                       + 'style="display:inline-flex;align-items:center;gap:5px;margin-top:8px;padding:6px 12px;'
                       + 'background:#7c3aed;color:white;border-radius:20px;text-decoration:none;'
                       + 'font-size:11px;font-weight:700;">🔗 Más info sobre la franquicia</a>';
                }
            }
            p += '</div>';
        }

        // Valor activo
        if (n.valor_activo) {
            p += '<p style="margin:6px 0 8px;color:#27ae60;font-weight:700;font-size:13px;">💰 Valor activo: $' + escapeHtml(String(n.valor_activo)) + '</p>';
        }

        // Founded year
        if (n.founded_year) {
            p += '<p style="margin:4px 0 8px;font-size:12px;color:#777;">📅 Fundada en ' + escapeHtml(String(n.founded_year)) + '</p>';
        }

        // Location
        if (address) {
            p += '<div class="popup-section">';
            p += '<div class="popup-label">📍 Ubicación</div>';
            p += '<div class="popup-value">' + escapeHtml(address) + '</div>';
            p += '</div>';
        }

        // Map visualization buttons
        const hasConds = n.tiene_zona || n.tiene_licencia || esFranq || n.zona_exclusiva;
        if (hasConds) {
            p += '<div style="margin:8px 0;border-top:1px solid #eee;padding-top:7px;">';
            p += '<p style="margin:0 0 5px;font-size:10px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Ver en mapa</p>';
            p += '<div style="display:flex;flex-wrap:wrap;gap:4px;">';
            const bs = 'padding:5px 10px;border:none;border-radius:12px;cursor:pointer;font-size:11px;color:white;font-family:sans-serif;';
            if (n.tiene_zona)     p += '<button onclick="toggleZonaSingle(' + n.lat + ',' + n.lng + ',' + (n.zona_radius_km||10) + ')" style="' + bs + 'background:#3498db;">🌐 Zona</button>';
            if (n.tiene_licencia) p += '<button onclick="toggleLicenciaSingle(' + n.lat + ',' + n.lng + ')" style="' + bs + 'background:#27ae60;">📜 Licencia</button>';
            if (esFranq)          p += '<button onclick="toggleFranquiciaSingle(' + n.lat + ',' + n.lng + ')" style="' + bs + 'background:#9c27b0;">🏢 Franquicia</button>';
            if (n.zona_exclusiva) p += '<button onclick="toggleExclusivaSingle(' + n.lat + ',' + n.lng + ',' + (n.zona_exclusiva_radius_km||2) + ')" style="' + bs + 'background:#e74c3c;">🎯 Exclusiva</button>';
            p += '</div></div>';
        }

        // WT channel
        p += buildWTPopupSection('marca', n.id, name);

        p += '</div>'; // Close popup-body

        // Footer actions
        p += '<div class="popup-footer">';
        var detalleUrl = (n.fuente === 'marcas')
            ? '/brand_form?id=' + n.id
            : '/brand_detail?id=' + n.id;
        p += '<a href="' + detalleUrl + '" class="popup-action" style="background:#6a2fa2;">📋 Detalle</a>';
        if (n.website) {
            let safeWebsite = null;
            try {
                const u = new URL(String(n.website));
                if (u.protocol === 'https:' || u.protocol === 'http:') safeWebsite = escapeHtml(u.href);
            } catch (_) { /* URL inválida, ignorar */ }
            if (safeWebsite) p += '<a href="' + safeWebsite + '" target="_blank" rel="noopener" class="popup-action" style="background:#e67e22;">🌐 Web</a>';
        }
        if (n.whatsapp) {
            const waNum = String(n.whatsapp).replace(/\D/g, '');
            p += '<a href="https://wa.me/' + escapeHtml(waNum) + '" target="_blank" rel="noopener" class="popup-action" style="background:#25d366;">💬 WA</a>';
        }
        p += '</div>';

    } else {
        // ── Business popup ──────────────────────────────────────────────────

        if (n.oferta_destacada_id || n.oferta_activa_id) {
            const precioNormal = n.oferta_destacada_precio_normal ? '$' + Number(n.oferta_destacada_precio_normal).toLocaleString() : '';
            const precioOferta = n.oferta_destacada_precio_oferta ? '$' + Number(n.oferta_destacada_precio_oferta).toLocaleString() : '';
            p += '<div class="popup-section" style="border:1px solid #ffd6d6;background:#fff5f5;">';
            p += '<div class="popup-label">🏷️ Oferta destacada</div>';
            p += '<div class="popup-value" style="font-weight:700;color:#c0392b;">' + (n.oferta_destacada_nombre || 'Promoción especial') + '</div>';
            if (n.oferta_destacada_descripcion) {
                p += '<div class="popup-value" style="font-size:12px;color:#555;">' + n.oferta_destacada_descripcion + '</div>';
            }
            if (precioOferta || precioNormal) {
                p += '<div class="popup-value" style="margin-top:4px;">'
                    + (precioNormal ? '<span style="text-decoration:line-through;color:#999;margin-right:6px;">' + precioNormal + '</span>' : '')
                    + (precioOferta ? '<strong style="color:#e74c3c;">' + precioOferta + '</strong>' : '')
                    + '</div>';
            }
            if (n.oferta_destacada_fecha_expiracion) {
                p += '<div class="popup-value" style="font-size:11px;color:#777;">⏰ Hasta ' + n.oferta_destacada_fecha_expiracion + '</div>';
            }
            p += '</div>';
        }

        // Address section
        if (address) {
            p += '<div class="popup-section">';
            p += '<div class="popup-label">📍 Dirección</div>';
            p += '<div class="popup-value">' + address + '</div>';
            p += '</div>';
        }

        // A6: Description excerpt
        if (n.description && n.description.trim()) {
            const exc = n.description.length > 120 ? n.description.substring(0, 120) + '…' : n.description;
            p += '<p style="margin:6px 0;color:#555;font-size:12px;line-height:1.5;">' + exc + '</p>';
        }

        // Details
        if (n.phone) p += '<p style="margin:4px 0;font-size:12px;">📞 ' + n.phone + '</p>';
        if (n.business_type === 'comercio' && n.tipo_comercio) p += '<p style="margin:4px 0;font-size:12px;">🏬 ' + n.tipo_comercio + '</p>';
        if (isVehicleSaleType(n)) {
            const vTipo = n.business_type === 'autos_venta' ? '🚗 Auto en venta' : '🏍️ Moto en venta';
            p += '<p style="margin:4px 0;font-size:12px;">' + vTipo + '</p>';
            if (n.vehiculo_marca || n.vehiculo_modelo) {
                p += '<p style="margin:4px 0;font-size:12px;">🔧 ' + [n.vehiculo_marca, n.vehiculo_modelo].filter(Boolean).join(' ') + '</p>';
            } else if (n.tipo_comercio) {
                p += '<p style="margin:4px 0;font-size:12px;">🔧 ' + n.tipo_comercio + '</p>';
            }
            if (n.vehiculo_anio) p += '<p style="margin:4px 0;font-size:12px;">📅 Año: ' + n.vehiculo_anio + '</p>';
            if (n.vehiculo_km) p += '<p style="margin:4px 0;font-size:12px;">🛣️ Km: ' + Number(n.vehiculo_km).toLocaleString() + '</p>';
            if (n.vehiculo_precio) p += '<p style="margin:4px 0;font-size:12px;color:#e67e22;font-weight:700;">💰 $' + Number(n.vehiculo_precio).toLocaleString() + '</p>';
            if (n.vehiculo_contacto) p += '<p style="margin:4px 0;font-size:12px;">📲 ' + n.vehiculo_contacto + '</p>';
        } else if (isRemateType(n)) {
            const rs = getRemateStatus(n);
            p += '<p style="margin:4px 0;font-size:12px;">' + rs.label + '</p>';
            const rw = getRemateWindow(n);
            if (rw.inicio) p += '<p style="margin:4px 0;font-size:12px;">🕐 Inicio: ' + formatDateTime(rw.inicio) + '</p>';
            if (rw.fin) p += '<p style="margin:4px 0;font-size:12px;">⏳ Cierre: ' + formatDateTime(rw.fin) + '</p>';
            if (n.remate_titulo || n.tipo_comercio) p += '<p style="margin:4px 0;font-size:12px;">🔨 ' + (n.remate_titulo || n.tipo_comercio) + '</p>';
        } else if (n.horario_apertura || n.horario_cierre) {
            const openHour = truncateHourLabel(n.horario_apertura);
            const closeHour = truncateHourLabel(n.horario_cierre);
            const scheduleLabel = (openHour && closeHour) ? (openHour + ' – ' + closeHour) : (openHour || closeHour);
            if (scheduleLabel) p += '<p style="margin:4px 0;font-size:12px;">🕐 ' + scheduleLabel + '</p>';
        }
        if (n.categorias_productos) p += '<p style="margin:4px 0;color:#888;font-size:11px;">🏷️ ' + n.categorias_productos + '</p>';

        // B1: Rating placeholder (lazy loaded on popupopen)
        p += '<div class="popup-section">';
        p += '<div data-rating-id="' + n.id + '" style="min-height:18px;">'
           + '<span style="color:#ccc;font-size:11px;">⭐ cargando reseñas...</span></div>';
        p += '</div>';
        p += buildWTPopupSection('negocio', n.id, name);

        p += '</div>'; // Close popup-body

        // A4: Action bar (professional footer)
        const waText = encodeURIComponent((n.name || '') + ' — ' + (n.address || '') + (n.website ? ' — ' + n.website : ''));
        const gmaps  = 'https://www.google.com/maps/dir/?api=1&destination=' + n.lat + ',' + n.lng;
        const wa     = 'https://wa.me/?text=' + waText;

        p += '<div class="popup-footer">';
        if (n.phone)   p += '<a href="tel:' + n.phone + '" class="popup-action" style="background:#27ae60;">📞 Llamar</a>';
        if (n.email)   p += '<a href="mailto:' + n.email + '" class="popup-action" style="background:#8e44ad;">📧 Email</a>';
        p += '<a href="' + gmaps + '" target="_blank" class="popup-action" style="background:#4285F4;">🗺️ Mapa</a>';
        p += '<a href="' + wa   + '" target="_blank" class="popup-action" style="background:#25D366;">💬 WA</a>';
        if (n.website) p += '<a href="' + n.website + '" target="_blank" class="popup-action" style="background:#e67e22;">🌐 Web</a>';
        p += '<a href="/business/view_business.php?id=' + n.id + '" target="_blank" class="popup-action" style="background:#1B3B6F;">📋 Detalle</a>';
        // Botón módulo disponibles (solo si el titular activó el módulo)
        if (n.disponibles_activo) {
            p += '<button type="button" class="popup-action" style="background:#d97706;font-weight:800;letter-spacing:.5px;" '
               + 'onclick="abrirDisponibles(' + parseInt(n.id) + ',\'' + escapeHtml(n.name || n.nombre || '').replace(/'/g, '&#39;') + '\')">$$$</button>';
        }
        // Botón módulo Busco Empleados/as (solo si hay oferta activa)
        if (n.job_offer_active) {
            p += '<button type="button" class="popup-action" style="background:#1d4ed8;font-weight:700;" '
               + 'onclick="abrirOfertaTrabajo(' + parseInt(n.id) + ',\'' + escapeHtml(n.name || n.nombre || '').replace(/'/g, '&#39;') + '\')">💼 Empleos</button>';
        }
        p += '</div>';
    }

    p += '</div>';
    return p;
}

// ─── Single-brand overlay functions ──────────────────────────────────────────────
function clearSingleOverlays() {
    activeSingleOverlays.forEach(l => mapa.removeLayer(l));
    activeSingleOverlays = [];
}

function clearRelationLines() {
    activeRelationLines.forEach(l => mapa.removeLayer(l));
    activeRelationLines = [];
}

function getVisibleMarkersWithMeta() {
    const out = [];
    const relationLayers = [clusterGroup, eventosLayer, encuestasLayer, noticiasLayer, triviasLayer, ofertasLayer, transmisionesLayer];
    relationLayers.forEach(layerGroup => {
        if (!layerGroup || !layerGroup.eachLayer) return;
        layerGroup.eachLayer(layer => {
            if (layer && layer._mapitaMeta) out.push(layer);
        });
    });
    return out;
}

function getRelationVisual(relationType = '') {
    const type = String(relationType || '').toLowerCase();
    const relationVisuals = {
        vinculado:   { color: '#00b894', dashArray: '6,4',  weight: 3,   opacity: 0.85 },
        asociado:    { color: '#00b894', dashArray: '6,4',  weight: 3,   opacity: 0.85 },
        asociacion:  { color: '#00b894', dashArray: '6,4',  weight: 3,   opacity: 0.85 },
        alianza:     { color: '#00b894', dashArray: '6,4',  weight: 3,   opacity: 0.85 },
        promociona:  { color: '#e67e22', dashArray: '2,6',  weight: 2,   opacity: 0.75 },
        difunde:     { color: '#e67e22', dashArray: '2,6',  weight: 2,   opacity: 0.75 },
        destaca:     { color: '#e67e22', dashArray: '2,6',  weight: 2,   opacity: 0.75 },
        depende:     { color: '#8e44ad', dashArray: '10,4', weight: 2,   opacity: 0.65 },
        provee:      { color: '#8e44ad', dashArray: '10,4', weight: 2,   opacity: 0.65 },
        abastece:    { color: '#8e44ad', dashArray: '10,4', weight: 2,   opacity: 0.65 }
    };
    if (relationVisuals[type]) return relationVisuals[type];
    return { color: '#1B3B6F', dashArray: '6,6', weight: 1.5, opacity: 0.60 };
}

function findMarkerByEntity(entityType, entityId, mapitaId) {
    const markers = getVisibleMarkersWithMeta();
    return markers.find(m => {
        const meta = m._mapitaMeta || {};
        if (mapitaId && meta.mapita_id && meta.mapita_id === mapitaId) return true;
        return meta.entity_type === entityType && String(meta.entity_id || '') === String(entityId || '');
    }) || null;
}

async function drawRelationLinesForPopup(sourceMarker) {
    if (!sourceMarker || !sourceMarker._mapitaMeta) return;
    const meta = sourceMarker._mapitaMeta;
    if (!meta.entity_id && !meta.mapita_id) return;
    try {
        const qs = meta.entity_id
            ? `entity_type=${encodeURIComponent(meta.entity_type)}&entity_id=${encodeURIComponent(meta.entity_id)}`
            : `mapita_id=${encodeURIComponent(meta.mapita_id)}`;
        const res = await fetch('/api/relaciones.php?' + qs).then(r => r.json());
        if (!res.success || !Array.isArray(res.data)) return;

        clearRelationLines();
        const sourceLL = sourceMarker.getLatLng ? sourceMarker.getLatLng() : null;
        if (!sourceLL) return;

        res.data.forEach(rel => {
            const isSource = rel.source_entity_type === meta.entity_type && String(rel.source_entity_id) === String(meta.entity_id);
            const otherType = isSource ? rel.target_entity_type : rel.source_entity_type;
            const otherId = isSource ? rel.target_entity_id : rel.source_entity_id;
            const otherMapita = isSource ? rel.target_mapita_id : rel.source_mapita_id;
            const targetMarker = findMarkerByEntity(otherType, otherId, otherMapita);
            if (!targetMarker || !targetMarker.getLatLng) return;
            const targetLL = targetMarker.getLatLng();
            if (!targetLL) return;
            const relVisual = getRelationVisual(rel.relation_type);
            const line = L.polyline([sourceLL, targetLL], {
                color: relVisual.color,
                weight: relVisual.weight,
                opacity: relVisual.opacity,
                dashArray: relVisual.dashArray
            }).addTo(mapa);
            const relLabel = [rel.relation_type, rel.descripcion].filter(Boolean).join(' · ');
            if (relLabel) line.bindTooltip(relLabel, { sticky: true, opacity: 0.92 });
            const targetDot = L.circleMarker(targetLL, {
                radius: Math.max(3, Math.round(relVisual.weight) + 1),
                color: '#fff',
                weight: 1.5,
                fillColor: relVisual.color,
                fillOpacity: relVisual.opacity
            }).addTo(mapa);
            activeRelationLines.push(line);
            activeRelationLines.push(targetDot);
        });
    } catch (err) {
        console.warn('Relaciones no disponibles:', err);
    }
}

function toggleZonaSingle(lat, lng, radiusKm) {
    clearSingleOverlays();
    activeSingleOverlays.push(
        L.circle([lat, lng], { radius: radiusKm*1000, fillColor:'#3498db', color:'#3498db', fillOpacity:0.2, weight:2, dashArray:'6,4' }).addTo(mapa)
    );
}
function toggleLicenciaSingle(lat, lng) {
    clearSingleOverlays();
    activeSingleOverlays.push(
        L.circleMarker([lat, lng], { radius:16, fillColor:'#27ae60', color:'#fff', weight:3, fillOpacity:0.85 }).addTo(mapa)
    );
}
function toggleFranquiciaSingle(lat, lng) {
    clearSingleOverlays();
    activeSingleOverlays.push(
        L.circleMarker([lat, lng], { radius:16, fillColor:'#9c27b0', color:'#fff', weight:3, fillOpacity:0.85 }).addTo(mapa)
    );
}
function toggleExclusivaSingle(lat, lng, radiusKm) {
    clearSingleOverlays();
    activeSingleOverlays.push(
        L.circle([lat, lng], { radius: radiusKm*1000, fillColor:'#e74c3c', color:'#e74c3c', fillOpacity:0.2, weight:2 }).addTo(mapa)
    );
}

// ─── Layer toggles ───────────────────────────────────────────────────────────────
function toggleMyBusiness() {
    myBusinessMarkers.forEach(m => mapa.removeLayer(m));
    myBusinessMarkers = [];
    if (!document.getElementById('show-mis-negocios')?.checked) return;
    if (currentVer === 'marcas') return;
    negocios.slice(0, 5).forEach(n => {
        if (!n.lat || !n.lng) return;
        const m = L.circleMarker([n.lat, n.lng], { radius:12, fillColor:'#007bff', color:'#fff', weight:3 });
        m.bindPopup('<b>🏢 ' + (n.name||'') + '</b><br>✅ Mi negocio');
        m.addTo(mapa); myBusinessMarkers.push(m);
    });
}

function toggleInmuebles() {
    propertyZoneMarkers.forEach(m => mapa.removeLayer(m));
    propertyZoneMarkers = [];
    if (!document.getElementById('show-inmuebles').checked) return;
    if (currentVer === 'marcas' || currentVer === 'ninguno') return;
    negocios
        .filter(n => n.business_type === 'inmobiliaria' && n.lat && n.lng)
        .forEach(n => {
            const c = L.circle([n.lat, n.lng], {
                radius: 10000, fillColor:'#e67e22', color:'#e67e22', fillOpacity:0.15, weight:2, dashArray:'10,10'
            });
            c.bindPopup('<b>🏠 ' + (n.name||'') + '</b><br>Zona inmobiliaria');
            c.addTo(mapa); propertyZoneMarkers.push(c);
        });
}

// ─── Sectores Industriales ────────────────────────────────────────────────────
let sectorIndustrialLayers = [];
let sectoresIndustrialesData = [];

const SECTOR_TYPE_COLORS = {
    mineria:         '#8B4513',
    energia:         '#FFD700',
    agro:            '#228B22',
    infraestructura: '#4682B4',
    inmobiliario:    '#E67E22',
    industrial:      '#6A5ACD',
};
const SECTOR_STATUS_OPACITY = {
    activo:    0.4,
    proyecto:  0.2,
    potencial: 0.15,
};

function getSectorStyle(sector) {
    const color   = SECTOR_TYPE_COLORS[sector.type]   || '#667eea';
    const opacity = SECTOR_STATUS_OPACITY[sector.status] || 0.2;
    const style   = { color, fillColor: color, fillOpacity: opacity, weight: 2 };
    if (sector.status === 'proyecto') style.dashArray = '8,6';
    return style;
}

function toggleSectoresIndustriales() {
    sectorIndustrialLayers.forEach(l => mapa.removeLayer(l));
    sectorIndustrialLayers = [];
    if (!document.getElementById('show-sectores-industriales').checked) return;
    if (!sectoresIndustrialesData.length) {
        fetch('/api/industrial_sectors.php')
            .then(r => r.json())
            .then(result => {
                if (result.success) { sectoresIndustrialesData = result.data || []; renderSectorLayers(); }
            })
            .catch(e => console.error('Error cargando sectores industriales', e));
    } else {
        renderSectorLayers();
    }
}

function renderSectorLayers() {
    sectoresIndustrialesData.forEach(sector => {
        if (!sector.geometry) return;
        let geo;
        try {
            geo = typeof sector.geometry === 'string' ? JSON.parse(sector.geometry) : sector.geometry;
        } catch(parseErr) {
            console.warn('Sector industrial con JSON de geometría inválido (id=' + sector.id + '):', parseErr);
            return;
        }
        try {
            const style  = getSectorStyle(sector);
            const layer  = L.geoJSON(geo, {
                style: () => style,
                pointToLayer: (feature, latlng) => L.circleMarker(latlng, Object.assign({ radius: 10 }, style)),
            });
            const typeLabel   = { mineria:'⛏ Minería', energia:'⚡ Energía', agro:'🌾 Agro',
                                  infraestructura:'🏗 Infraestructura', inmobiliario:'🏢 Inmobiliario', industrial:'🏭 Industrial' }[sector.type] || sector.type;
            const statusLabel = { activo:'✅ Activo', proyecto:'📐 Proyecto', potencial:'💡 Potencial' }[sector.status] || sector.status;
            const invLabel    = { bajo:'🟢 Bajo', medio:'🟡 Medio', alto:'🔴 Alto' }[sector.investment_level] || '';
            const riskLabel   = { bajo:'🟢 Bajo', medio:'🟡 Medio', alto:'🔴 Alto' }[sector.risk_level] || '';
            layer.bindPopup(
                '<b>🏭 ' + (sector.name||'') + '</b>' +
                '<br>' + typeLabel + (sector.subtype ? ' — ' + sector.subtype : '') +
                '<br>Estado: ' + statusLabel +
                '<br>Inversión: ' + invLabel + ' &nbsp; Riesgo: ' + riskLabel +
                (sector.jurisdiction ? '<br>📍 ' + sector.jurisdiction : '') +
                (sector.description  ? '<br><span style="font-size:11px;color:#555;">' + sector.description.substring(0,120) + '</span>' : '')
            );
            layer.addTo(mapa);
            sectorIndustrialLayers.push(layer);
        } catch(e) {
            console.warn('Sector industrial con geometría inválida:', sector.id, e);
        }
    });
}

function toggleZonasMarca() {
    zonaMarcaMarkers.forEach(m => mapa.removeLayer(m)); zonaMarcaMarkers = [];
    if (!(currentVer==='marcas'||currentVer==='ambos')) return;
    marcas.filter(m => m.lat && m.lng && m.tiene_zona).forEach(m => {
        const r = m.zona_radius_km || 10;
        const c = L.circle([m.lat,m.lng],{radius:r*1000,fillColor:'#3498db',color:'#3498db',fillOpacity:0.15,weight:2,dashArray:'5,5'});
        c.bindPopup(`<b>${m.nombre}</b><br>🌐 Zona: ${r}km`); c.addTo(mapa); zonaMarcaMarkers.push(c);
    });
}
function toggleLicenciasMarca() {
    licenciaMarcaMarkers.forEach(m => mapa.removeLayer(m)); licenciaMarcaMarkers = [];
    if (!(currentVer==='marcas'||currentVer==='ambos')) return;
    marcas.filter(m => m.lat && m.lng && m.tiene_licencia).forEach(m => {
        const mk = L.circleMarker([m.lat,m.lng],{radius:10,fillColor:'#27ae60',color:'#fff',weight:2});
        mk.bindPopup(`<b>${m.nombre}</b><br>📜 Licencia${m.licencia_detalle?': '+m.licencia_detalle:''}`);
        mk.addTo(mapa); licenciaMarcaMarkers.push(mk);
    });
}
function toggleFranquiciasMarca() {
    franquiciaMarcaMarkers.forEach(m => mapa.removeLayer(m)); franquiciaMarcaMarkers = [];
    if (!(currentVer==='marcas'||currentVer==='ambos')) return;
    marcas.filter(m => m.lat && m.lng && m.es_franquicia).forEach(m => {
        const mk = L.circleMarker([m.lat,m.lng],{radius:10,fillColor:'#9c27b0',color:'#fff',weight:2,fillOpacity:0.8});
        mk.bindPopup(`<b>${m.nombre}</b><br>🏢 Franquicia${m.franchise_details?': '+m.franchise_details:''}`);
        mk.addTo(mapa); franquiciaMarcaMarkers.push(mk);
    });
}
function toggleExclusivasMarca() {
    exclusivaMarcaMarkers.forEach(m => mapa.removeLayer(m)); exclusivaMarcaMarkers = [];
    if (!(currentVer==='marcas'||currentVer==='ambos')) return;
    marcas.filter(m => m.lat && m.lng && m.zona_exclusiva).forEach(m => {
        const r = m.zona_exclusiva_radius_km || 2;
        const c = L.circle([m.lat,m.lng],{radius:r*1000,fillColor:'#e74c3c',color:'#e74c3c',fillOpacity:0.2,weight:2});
        c.bindPopup(`<b>${m.nombre}</b><br>🎯 Zona exclusiva: ${r}km`); c.addTo(mapa); exclusivaMarcaMarkers.push(c);
    });
}

// ─── VER Selector ────────────────────────────────────────────────────────────────
// Estado de visibilidad (nuevo sistema de toggles independientes)
let mostrarNegocios = true;
let mostrarMarcas = true;
let franquiciasFilter = false;

function toggleFranquiciasFilter() {
    franquiciasFilter = !franquiciasFilter;
    const btn = document.getElementById('sel-franquicias');
    if (btn) btn.classList.toggle('active', franquiciasFilter);
    // Al activar FRANQUICIAS, asegurarse de que marcas esté visible
    if (franquiciasFilter && !mostrarMarcas) {
        mostrarMarcas = true;
        const btnMarcas = document.getElementById('sel-marcas');
        if (btnMarcas) btnMarcas.classList.add('active');
        if (mostrarNegocios) { currentVer = 'ambos'; } else { currentVer = 'marcas'; }
    }
    filtrar();
}

function toggleVer(tipo) {
    if (tipo === 'negocios') {
        mostrarNegocios = !mostrarNegocios;
    } else if (tipo === 'marcas') {
        mostrarMarcas = !mostrarMarcas;
    }

    // Actualizar estilos de botones
    const btnNegocios = document.getElementById('sel-negocios');
    const btnMarcas = document.getElementById('sel-marcas');

    if (btnNegocios) {
        if (mostrarNegocios) {
            btnNegocios.classList.add('active');
        } else {
            btnNegocios.classList.remove('active');
        }
    }

    if (btnMarcas) {
        if (mostrarMarcas) {
            btnMarcas.classList.add('active');
        } else {
            btnMarcas.classList.remove('active');
        }
    }

    // Actualizar variable currentVer para compatibilidad
    if (mostrarNegocios && mostrarMarcas) {
        currentVer = 'ambos';
    } else if (mostrarNegocios) {
        currentVer = 'negocios';
    } else if (mostrarMarcas) {
        currentVer = 'marcas';
    } else {
        currentVer = 'ninguno';
    }

    // Aplicar filtro
    filtrar();
}

// Función legacy (mantener por compatibilidad)
function setVer(val) {
    if (val === 'negocios') {
        mostrarNegocios = true;
        mostrarMarcas = false;
    } else if (val === 'marcas') {
        mostrarNegocios = false;
        mostrarMarcas = true;
    } else if (val === 'ambos') {
        mostrarNegocios = true;
        mostrarMarcas = true;
    } else if (val === 'ninguno') {
        mostrarNegocios = false;
        mostrarMarcas = false;
    }

    currentVer = val;

    // Actualizar botones
    const btnNegocios = document.getElementById('sel-negocios');
    const btnMarcas = document.getElementById('sel-marcas');

    if (btnNegocios) {
        if (mostrarNegocios) {
            btnNegocios.classList.add('active');
        } else {
            btnNegocios.classList.remove('active');
        }
    }

    if (btnMarcas) {
        if (mostrarMarcas) {
            btnMarcas.classList.add('active');
        } else {
            btnMarcas.classList.remove('active');
        }
    }

    filtrar();
}

function openSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggle   = document.getElementById('togglePanel');
    sidebar.classList.add('active');
    if (backdrop) backdrop.classList.add('active');
    if (toggle)   toggle.style.opacity = '0', toggle.style.pointerEvents = 'none';
}

function closeSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggle   = document.getElementById('togglePanel');
    sidebar.classList.remove('active');
    if (backdrop) backdrop.classList.remove('active');
    if (toggle)   toggle.style.opacity = '', toggle.style.pointerEvents = '';
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.classList.contains('active')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

// ─── Touch swipe for sidebar ─────────────────────────────────────────────────────
let _verSelectorDragging = false;
(function () {
    let txStart = 0, tyStart = 0, tActive = false;
    const SWIPE = 60, EDGE = 30;
    document.addEventListener('touchstart', e => { txStart = e.touches[0].clientX; tyStart = e.touches[0].clientY; tActive = true; }, { passive: true });
    document.addEventListener('touchmove', e => {
        if (!tActive) return;
        if (Math.abs(e.touches[0].clientY - tyStart) > Math.abs(e.touches[0].clientX - txStart)) tActive = false;
    }, { passive: true });
    document.addEventListener('touchend', e => {
        if (!tActive || _verSelectorDragging) return;
        tActive = false;
        const dx = e.changedTouches[0].clientX - txStart;
        const sidebar = document.getElementById('sidebar');
        if (dx >  SWIPE && txStart < EDGE) openSidebar();
        if (dx < -SWIPE && sidebar.classList.contains('active')) closeSidebar();
    }, { passive: true });
})();

// ─── Draggable ver-selector ───────────────────────────────────────────────────────
(function () {
    const el = document.getElementById('ver-selector');
    let dragging = false, ox = 0, oy = 0, ex = 0, ey = 0;

    function startDrag(cx, cy) {
        dragging = true; _verSelectorDragging = true;
        ox = cx; oy = cy;
        const r = el.getBoundingClientRect(); ex = r.left; ey = r.top;
    }
    function doDrag(cx, cy) {
        if (!dragging) return;
        el.style.left = Math.max(0, ex + cx - ox) + 'px';
        el.style.top  = Math.max(0, ey + cy - oy) + 'px';
    }
    function endDrag() { dragging = false; setTimeout(() => { _verSelectorDragging = false; }, 50); }

    el.addEventListener('mousedown', e => { if (e.target.tagName === 'BUTTON') return; startDrag(e.clientX, e.clientY); e.preventDefault(); });
    document.addEventListener('mousemove', e => doDrag(e.clientX, e.clientY));
    document.addEventListener('mouseup', endDrag);

    el.addEventListener('touchstart', e => { if (e.target.tagName === 'BUTTON') return; startDrag(e.touches[0].clientX, e.touches[0].clientY); }, { passive: true });
    el.addEventListener('touchmove', e => { if (!dragging) return; doDrag(e.touches[0].clientX, e.touches[0].clientY); e.preventDefault(); }, { passive: false });
    el.addEventListener('touchend', endDrag, { passive: true });
})();

// ─── Utilities ───────────────────────────────────────────────────────────────────
function calcularDistancia(lat1, lng1, lat2, lng2) {
    const R = 6371, dLat = (lat2-lat1)*Math.PI/180, dLng = (lng2-lng1)*Math.PI/180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function exportarPDF() {
    const el = document.getElementById('lista');
    if (!el.hasChildNodes()) { alert('Lista vacía'); return; }
    html2canvas(el, { scale: 2 }).then(canvas => {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();
        pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 10, 10, 190, 0);
        pdf.save('listado.pdf');
    });
}

function _applyPosition(pos, centerMap) {
    miUbicacion = { lat: pos.coords.latitude, lng: pos.coords.longitude };

    // Update marker
    if (userLocationMarker) mapa.removeLayer(userLocationMarker);
    userLocationMarker = L.marker([miUbicacion.lat, miUbicacion.lng], {
        icon: L.divIcon({
            html: '<div style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;background:white;border:3px solid var(--primary);border-radius:50%;box-shadow:0 2px 8px rgba(102,126,234,0.3);z-index:1000;"><span style="font-size:14px;">📍</span></div>',
            className: '',
            iconSize: [28, 28],
            iconAnchor: [14, 14],
            popupAnchor: [0, -16]
        })
    })
    .bindPopup('📍 Tu ubicación')
    .addTo(mapa);

    updateRadiusCircle();
    if (centerMap) mapa.setView([miUbicacion.lat, miUbicacion.lng], 14);
    filtrar();
}

function ubicarme() {
    if (!navigator.geolocation) { alert('Sin geolocalización'); return; }

    // Start continuous watching if not already active
    if (watchID === null) {
        watchID = navigator.geolocation.watchPosition(
            pos => _applyPosition(pos, followMe),
            err => console.warn('watchPosition error', err.code, err.message),
            { enableHighAccuracy: false, maximumAge: 15000, timeout: 30000 }
        );
    }

    // Also immediately get current position and center map
    navigator.geolocation.getCurrentPosition(
        pos => _applyPosition(pos, true),
        () => alert('No se pudo obtener la ubicación')
    );
}

function toggleFollowMe() {
    followMe = !followMe;
    const btn = document.getElementById('btn-follow-me');
    if (btn) {
        btn.style.background = followMe ? '#27ae60' : '#95a5a6';
        btn.textContent = followMe ? '📡 Seguirme ✓' : '📡 Seguirme';
        btn.setAttribute('aria-pressed', String(followMe));
    }
    if (followMe && miUbicacion) {
        mapa.setView([miUbicacion.lat, miUbicacion.lng], 14);
    }
    // When disabling follow-me, stop the watch to save battery
    if (!followMe && watchID !== null) {
        navigator.geolocation.clearWatch(watchID);
        watchID = null;
    }
}

// ─── Accordion toggle ────────────────────────────────────────────────────────────
function toggleAccordion(btn) {
    const content = btn.nextElementSibling;
    btn.classList.toggle('active');
    content.classList.toggle('active');
}

// ─── Collapsible sidebar sections (sb-section system) ────────────────────────────
function toggleSbSection(hdr) {
    const section = hdr.closest('.sb-section');
    if (!section) return;
    const body = section.querySelector(':scope > .sb-section-body');
    if (!body) return;
    const willOpen = !hdr.classList.contains('open');
    hdr.classList.toggle('open', willOpen);
    body.classList.toggle('open', willOpen);
    hdr.setAttribute('aria-expanded', String(willOpen));
}

function toggleSbModule(hdr) {
    const body = hdr.nextElementSibling;
    if (!body) return;
    const willOpen = !hdr.classList.contains('open');
    hdr.classList.toggle('open', willOpen);
    body.classList.toggle('open', willOpen);
}

let _sbAllOpen = true;
function toggleAllSbSections(e) {
    if (e) e.stopPropagation();
    _sbAllOpen = !_sbAllOpen;
    document.querySelectorAll('#sidebar .sb-section-hdr').forEach(hdr => {
        hdr.classList.toggle('open', _sbAllOpen);
        hdr.setAttribute('aria-expanded', String(_sbAllOpen));
        const section = hdr.closest('.sb-section');
        const body = section ? section.querySelector(':scope > .sb-section-body') : hdr.nextElementSibling;
        if (body) body.classList.toggle('open', _sbAllOpen);
    });
    const icon = document.getElementById('sb-all-icon');
    if (icon) {
        // Switch between compress (collapse-all) and expand icons
        icon.innerHTML = _sbAllOpen
            ? '<line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="3" y2="18"/>'
            : '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>';
    }
    const btn = document.getElementById('sb-compact-btn');
    if (btn) btn.title = _sbAllOpen ? 'Colapsar todas las secciones' : 'Expandir todas las secciones';
}

// ─── Filter helper functions ────────────────────────────────────────────────────
function getLocationFilter() {
    const enable = document.getElementById('filter-location-enable')?.checked;
    if (!enable || !miUbicacion) return null;
    const radius = parseInt(document.getElementById('filter-location-radius').value) || 10;
    return { enabled: true, radius: radius };
}

function getLocationCityFilter() {
    const city = document.getElementById('filter-location-city')?.value.toLowerCase().trim();
    return city || null;
}

function getPriceFilter() {
    const checks = Array.from(document.querySelectorAll('input[name="price-range"]:checked'));
    if (checks.length === 0) return null;
    return checks.map(c => parseInt(c.value));
}

function getTimeFilter() {
    return document.getElementById('filter-open-now')?.checked || false;
}

function getDaysFilter() {
    const checks = Array.from(document.querySelectorAll('input[name="filter-days"]:checked'));
    return checks.length > 0 ? checks.map(c => c.value) : null;
}

function getCompanyTypeFilter() {
    const checks = Array.from(document.querySelectorAll('input[name="company-type"]:checked'));
    if (checks.length === 0) return null;
    return checks.map(c => c.value);
}

function getProtectionFilter() {
    const checks = Array.from(document.querySelectorAll('input[name="protection-level"]:checked'));
    if (checks.length === 0) return null;
    return checks.map(c => c.value);
}

function getSectorFilter() {
    const text = document.getElementById('filter-sector-search')?.value.toLowerCase().trim();
    const checks = Array.from(document.querySelectorAll('input[name="filter-sector"]:checked'));
    if (checks.length === 0 && !text) return null;
    return { text: text, selected: checks.map(c => c.value) };
}

// ─── Update radius circle on map ────────────────────────────────────────────────
function updateRadiusCircle() {
    if (!miUbicacion) return;

    // Limpiar círculo anterior
    if (userRadiusCircle) mapa.removeLayer(userRadiusCircle);

    // Verificar si el filtro está activo
    const locationFilter = getLocationFilter();
    if (!locationFilter) return;

    // Dibujar nuevo círculo con el radio actual
    const radiusMeters = locationFilter.radius * 1000;
    userRadiusCircle = L.circle([miUbicacion.lat, miUbicacion.lng], {
        radius: radiusMeters,
        fillColor: '#667eea',
        color: '#667eea',
        weight: 2,
        opacity: 0.3,
        fillOpacity: 0.08,
        dashArray: '8,4'
    }).addTo(mapa);

    // Agregar tooltip
    userRadiusCircle.bindTooltip(
        `📍 Radio de búsqueda: ${locationFilter.radius} km`,
        { permanent: false, direction: 'center', opacity: 0.85 }
    );
}

// ─── Heuristics for company type ────────────────────────────────────────────────
function inferCompanyType(negocio) {
    if (!negocio) return 'pyme';
    const name = (negocio.name || '').toLowerCase();
    const desc = (negocio.description || '').toLowerCase();

    // Detect multinational: company name contains international keywords or multiple locations
    if (desc.includes('internacional') || desc.includes('global') ||
        name.includes('global') || name.includes('international')) {
        return 'multinacional';
    }

    // Detect large chain
    const chainKeywords = ['cadena', 'sucursal', 'franquicia', 'grupo', 'empresa'];
    if (chainKeywords.some(kw => desc.includes(kw))) {
        return 'grande';
    }

    // Detect family business
    if (desc.includes('familiar') || desc.includes('familia')) {
        return 'familiar';
    }

    // Default: PYME
    return 'pyme';
}

// ─── Boot ─────────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    // Setup radius slider: update display and circle in real-time
    const radiusInput = document.getElementById('filter-location-radius');
    const radiusValue = document.getElementById('filter-location-radius-value');
    const filterEnable = document.getElementById('filter-location-enable');

    if (radiusInput && radiusValue) {
        radiusInput.addEventListener('input', () => {
            radiusValue.textContent = radiusInput.value;
            updateRadiusCircle();  // Update circle on map in real-time
        });
    }

    // Setup location filter toggle
    if (filterEnable) {
        filterEnable.addEventListener('change', () => {
            updateRadiusCircle();  // Show/hide circle when toggling filter
        });
    }

    // Inicializar mapa primero para que clusterGroup esté listo
    inicializarMapa();

    // Load data
    try {
        const rn = await fetch('/api/api_comercios.php').then(r => r.json());
        if (rn.success) negocios = rn.data;
    } catch (e) { console.error('Error cargando negocios', e); }

    try {
        const rm = await fetch('/api/brands.php').then(r => r.json());
        if (rm.success) marcas = rm.data;
        populateSectorFilter();
    } catch (e) { console.error('Error cargando marcas', e); }

    // Dibujar negocios y marcas en el mapa al cargar la página
    filtrar();

    // Cargar encuestas activas
    try {
        const re = await fetch('/api/encuestas.php').then(r => r.json());
        if (re.success && re.data.length > 0) {
            mostrarEncuestasWidget(re.data);
            mostrarMarcadoresEncuestas(re.data);
        }
    } catch (e) { console.error('Error cargando encuestas', e); }

    // Cargar eventos proximos
    try {
        const rev = await fetch('/api/eventos.php?action=upcoming').then(r => r.json());
        if (rev.success && rev.data.length > 0) {
            mostrarEventosWidget(rev.data);
            mostrarMarcadoresEventos(rev.data);
        }
    } catch (e) { console.error('Error cargando eventos', e); }

    // Cargar trivias activas
    try {
        const rt = await fetch('/api/trivias.php').then(r => r.json());
        if (rt.success && rt.data.length > 0) {
            mostrarTriviasWidget(rt.data);
            mostrarMarcadoresTrivias(rt.data);
        }
    } catch (e) { console.error('Error cargando trivias', e); }

    // Cargar noticias recientes
    try {
        const rn = await fetch('/api/noticias.php?action=recent&limit=20').then(r => r.json());
        if (rn.success && rn.data.length > 0) {
            mostrarNoticiasWidget(rn.data);
            mostrarMarcadoresNoticias(rn.data);
        }
    } catch (e) { console.error('Error cargando noticias', e); }

    // Cargar ofertas activas
    try {
        const ro = await fetch('/api/ofertas.php').then(r => r.json());
        if (ro.success && ro.data.length > 0) {
            mostrarOfertasWidget(ro.data);
            mostrarMarcadoresOfertas(ro.data);
        }
    } catch (e) { console.error('Error cargando ofertas', e); }

    // Cargar transmisiones
    try {
        const rt2 = await fetch('/api/transmisiones.php').then(r => r.json());
        if (rt2.success && rt2.data.length > 0) {
            mostrarTransmisionesWidget(rt2.data);
            mostrarMarcadoresTransmisiones(rt2.data);
        }
    } catch (e) { console.error('Error cargando transmisiones', e); }

    // Indicador de contenido cargado en el mapa
    const totalExtra = eventoMarkers.length + noticiaMarkers.length + triviaMarkers.length + encuestaMarkers.length;
    if (totalExtra > 0) {
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:rgba(30,37,53,.88);color:white;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:600;z-index:4000;pointer-events:none;';
        toast.textContent = `✅ ${totalExtra} elemento${totalExtra !== 1 ? 's' : ''} cargado${totalExtra !== 1 ? 's' : ''} en el mapa`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }
    updateSelectionPanel();
    // Initialize legend content (pre-populated so it's ready before user clicks)
    buildMapLegend();

    // ── Mobile: close filters section by default (less screen space) ──────────
    if (window.innerWidth <= 768) {
        const filtersHdr = document.querySelector('#sb-sec-filters .sb-section-hdr');
        if (filtersHdr) {
            filtersHdr.classList.remove('open');
            filtersHdr.setAttribute('aria-expanded', 'false');
            const filtersSection = filtersHdr.closest('.sb-section');
            const body = filtersSection ? filtersSection.querySelector(':scope > .sb-section-body') : filtersHdr.nextElementSibling;
            if (body) body.classList.remove('open');
        }
    }

});

// ─── Populate sector/rubro filter from marcas ────────────────────────────────────
function populateSectorFilter() {
    const sectors = new Set();
    marcas.forEach(m => {
        if (m.rubro) sectors.add(m.rubro);
    });

    const sectorList = document.getElementById('filter-sector-list');
    if (!sectorList) return;

    Array.from(sectors).sort().forEach(sector => {
        const label = document.createElement('label');
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.name = 'filter-sector';
        input.value = sector;
        input.addEventListener('change', filtrar);

        const text = document.createElement('span');
        text.textContent = sector;

        label.appendChild(input);
        label.appendChild(text);
        label.style.display = 'flex';
        label.style.alignItems = 'center';
        label.style.marginBottom = '6px';
        label.style.fontSize = '13px';
        label.style.cursor = 'pointer';

        sectorList.appendChild(label);
    });
}

// ─── Photo Gallery Modal ──────────────────────────────────────────────────────────
let photoGalleryData = { businessId: null, photos: [], currentIndex: 0 };

function openPhotoGallery(businessId) {
    const negocio = negocios.find(n => n.id === businessId);
    if (!negocio || !negocio.photos || negocio.photos.length === 0) return;

    photoGalleryData = {
        businessId: businessId,
        photos: negocio.photos,
        currentIndex: 0
    };

    const modal = document.getElementById('photo-gallery-modal');
    const modalImg = document.getElementById('photo-gallery-img');
    const modalCaption = document.getElementById('photo-gallery-caption');

    modalImg.src = photoGalleryData.photos[0];
    modalCaption.innerHTML = '<strong>' + (negocio.name || 'Galería') + '</strong><br>Foto 1 de ' + negocio.photos.length;
    modal.style.display = 'flex';

    updateGalleryNav();
}

function closePhotoGallery() {
    const modal = document.getElementById('photo-gallery-modal');
    modal.style.display = 'none';
}

function nextPhoto() {
    photoGalleryData.currentIndex = (photoGalleryData.currentIndex + 1) % photoGalleryData.photos.length;
    updateGalleryPhoto();
}

function prevPhoto() {
    photoGalleryData.currentIndex = (photoGalleryData.currentIndex - 1 + photoGalleryData.photos.length) % photoGalleryData.photos.length;
    updateGalleryPhoto();
}

function updateGalleryPhoto() {
    const modalImg = document.getElementById('photo-gallery-img');
    const modalCaption = document.getElementById('photo-gallery-caption');
    const negocio = negocios.find(n => n.id === photoGalleryData.businessId);

    modalImg.src = photoGalleryData.photos[photoGalleryData.currentIndex];
    if (negocio) {
        modalCaption.innerHTML = '<strong>' + (negocio.name || 'Galería') + '</strong><br>Foto ' + (photoGalleryData.currentIndex + 1) + ' de ' + photoGalleryData.photos.length;
    }
    updateGalleryNav();
}

function updateGalleryNav() {
    const prevBtn = document.getElementById('gallery-prev-btn');
    const nextBtn = document.getElementById('gallery-next-btn');

    prevBtn.style.opacity = photoGalleryData.currentIndex === 0 ? '0.5' : '1';
    nextBtn.style.opacity = photoGalleryData.currentIndex === photoGalleryData.photos.length - 1 ? '0.5' : '1';
    prevBtn.style.cursor = photoGalleryData.currentIndex === 0 ? 'default' : 'pointer';
    nextBtn.style.cursor = photoGalleryData.currentIndex === photoGalleryData.photos.length - 1 ? 'default' : 'pointer';
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('photo-gallery-modal');
    if (modal.style.display !== 'flex') {
        if (e.key === 'Escape' && selectionMode) {
            e.preventDefault();
            dismissSelectionPanel();
            return;
        }
        // Selection mode shortcut: S key (when no input/textarea focused)
        if (e.key === 's' || e.key === 'S') {
            const active = document.activeElement;
            if (!active || (active.tagName !== 'INPUT' && active.tagName !== 'TEXTAREA' && active.tagName !== 'SELECT')) {
                e.preventDefault();
                toggleSelectionMode();
            }
        }
        return;
    }

    if (e.key === 'ArrowLeft') prevPhoto();
    if (e.key === 'ArrowRight') nextPhoto();
    if (e.key === 'Escape') closePhotoGallery();
});

// Click outside modal to close
window.addEventListener('click', (e) => {
    const modal = document.getElementById('photo-gallery-modal');
    if (e.target === modal) closePhotoGallery();
});

// ─── Encuestas Widget ────────────────────────────────────────────────────────────
function mostrarEncuestasWidget(encuestas) {
    const container = document.getElementById('encuestas-container');
    const lista = document.getElementById('encuestas-list');

    if (!container || !lista || !encuestas || encuestas.length === 0) {
        return;
    }

    container.style.display = 'block';
    lista.innerHTML = '';

    encuestas.forEach(enc => {
        const item = document.createElement('div');
        item.style.cssText = `
            padding: 12px;
            background: linear-gradient(135deg, #fff9e6 0%, #fff5cc 100%);
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #ffc107;
        `;

        item.onmouseover = () => {
            item.style.background = 'linear-gradient(135deg, #ffecb3 0%, #ffe082 100%)';
            item.style.transform = 'translateY(-2px)';
            item.style.boxShadow = '0 4px 12px rgba(243, 156, 18, 0.2)';
        };

        item.onmouseout = () => {
            item.style.background = 'linear-gradient(135deg, #fff9e6 0%, #fff5cc 100%)';
            item.style.transform = 'translateY(0)';
            item.style.boxShadow = 'none';
        };

        item.onclick = () => {
            abrirEncuesta(enc.id, enc.titulo);
        };

        const titulo = document.createElement('h4');
        titulo.textContent = enc.titulo;
        titulo.style.cssText = 'margin: 0 0 6px 0; font-size: 13px; color: #f39c12; font-weight: 600;';

        const desc = document.createElement('p');
        desc.textContent = (enc.descripcion || 'Sin descripción').substring(0, 60) + (enc.descripcion && enc.descripcion.length > 60 ? '...' : '');
        desc.style.cssText = 'margin: 0; font-size: 12px; color: #856404; line-height: 1.3;';

        item.appendChild(titulo);
        item.appendChild(desc);
        lista.appendChild(item);
    });
}

function abrirEncuesta(encuestaId, titulo) {
    // Crear modal para responder encuesta
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        z-index: 5000;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    `;

    content.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #f39c12;">📊 ${titulo}</h2>
            <button onclick="this.closest('div').parentElement.parentElement.remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">✕</button>
        </div>
        <p style="color: #666; margin-bottom: 20px;">Cargando encuesta...</p>
    `;

    modal.appendChild(content);
    document.body.appendChild(modal);

    // Cargar datos de la encuesta
    fetch('/api/encuestas.php?id=' + encuestaId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const enc = data.data;
                let html = `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0; color: #f39c12;">${enc.titulo}</h2>
                        <button onclick="this.closest('div').parentElement.parentElement.remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">✕</button>
                    </div>
                `;

                if (enc.descripcion) {
                    html += `<p style="color: #666; margin-bottom: 15px; line-height: 1.5;">${enc.descripcion}</p>`;
                }

                if (enc.preguntas && enc.preguntas.length > 0) {
                    html += '<form id="encuesta-form">';
                    enc.preguntas.forEach((preg, idx) => {
                        html += `
                            <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                                <p style="margin: 0 0 10px 0; font-weight: 600; color: #2c3e50;">${idx + 1}. ${preg.pregunta}</p>
                        `;

                        if (preg.tipo === 'si_no') {
                            html += `
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="radio" name="pregunta_${preg.id}" value="Sí" style="margin-right: 8px;"> Sí
                                </label>
                                <label style="display: block;">
                                    <input type="radio" name="pregunta_${preg.id}" value="No" style="margin-right: 8px;"> No
                                </label>
                            `;
                        } else if (preg.tipo === 'escala') {
                            html += '<div style="display: flex; gap: 6px;">';
                            for (let i = 1; i <= 5; i++) {
                                html += `
                                    <label style="flex: 1; text-align: center; cursor: pointer;">
                                        <input type="radio" name="pregunta_${preg.id}" value="${i}" style="width: 100%;"> ${i}
                                    </label>
                                `;
                            }
                            html += '</div>';
                        } else {
                            html += `
                                <textarea name="pregunta_${preg.id}" placeholder="Escribe tu respuesta..." style="width: 100%; padding: 10px; border: 1px solid #d0d5dd; border-radius: 4px; font-family: inherit; font-size: 13px;"></textarea>
                            `;
                        }

                        html += '</div>';
                    });

                    html += `
                        <button type="button" onclick="enviarEncuesta(${enc.id})" style="width: 100%; padding: 12px; background: #f39c12; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                            ✓ Enviar Respuestas
                        </button>
                    </form>
                    `;
                } else {
                    html += '<p style="color: #999; text-align: center;">Esta encuesta aún no tiene preguntas.</p>';
                }

                content.innerHTML = html;
            }
        })
        .catch(e => {
            console.error('Error cargando encuesta', e);
            content.innerHTML = '<p style="color: #e74c3c;">Error al cargar la encuesta.</p>';
        });

    // Cerrar al hacer click afuera
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
}

function enviarEncuesta(encuestaId) {
    if (!<?= isset($_SESSION['user_id']) && $_SESSION['user_id'] ? 'true' : 'false' ?>) {
        alert('Debes iniciar sesión para responder encuestas');
        window.location.href = '/login';
        return;
    }

    const form = document.getElementById('encuesta-form');
    const respuestas = {};

    form.querySelectorAll('[name^="pregunta_"]').forEach(input => {
        if (input.type === 'textarea' || input.type === 'radio' || input.type === 'text') {
            const pregId = input.name.replace('pregunta_', '');
            if (input.type === 'radio') {
                const checked = form.querySelector(`[name="${input.name}"]:checked`);
                if (checked) respuestas[pregId] = checked.value;
            } else if (input.type === 'textarea') {
                if (input.value) respuestas[pregId] = input.value;
            }
        }
    });

    if (Object.keys(respuestas).length === 0) {
        alert('Debes responder al menos una pregunta');
        return;
    }

    fetch('/api/encuestas.php?action=respond', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            encuesta_id: encuestaId,
            respuestas: respuestas
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('¡Gracias por responder!');
            document.querySelector('[onclick*="parentElement"]').closest('div').parentElement.remove();
        } else {
            alert('Error: ' + (data.message || 'No se pudieron guardar las respuestas'));
        }
    })
    .catch(e => {
        console.error('Error enviando respuestas', e);
        alert('Error al enviar respuestas');
    });
}

// ─── Eventos Widget ────────────────────────────────────────────────────────────
function mostrarEventosWidget(eventos) {
    const container = document.getElementById('eventos-container');
    const lista = document.getElementById('eventos-list');

    if (!container || !lista || !eventos || eventos.length === 0) {
        return;
    }

    container.style.display = 'block';
    lista.innerHTML = '';

    eventos.forEach(evt => {
        const item = document.createElement('div');
        item.style.cssText = `
            padding: 12px;
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e74c3c;
        `;

        item.onmouseover = () => {
            item.style.background = 'linear-gradient(135deg, #ffcdd2 0%, #ef9a9a 100%)';
            item.style.transform = 'translateY(-2px)';
            item.style.boxShadow = '0 4px 12px rgba(231, 76, 60, 0.2)';
        };

        item.onmouseout = () => {
            item.style.background = 'linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%)';
            item.style.transform = 'translateY(0)';
            item.style.boxShadow = 'none';
        };

        item.onclick = () => {
            abrirEvento(evt.id, evt.titulo);
        };

        const titulo = document.createElement('h4');
        titulo.textContent = evt.titulo;
        titulo.style.cssText = 'margin: 0 0 6px 0; font-size: 13px; color: #e74c3c; font-weight: 600;';

        const info = document.createElement('p');
        let infoText = '';
        if (evt.fecha_inicio) {
            try {
                const dt = new Date(evt.fecha_inicio);
                infoText = dt.toLocaleDateString('es-AR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                infoText = evt.fecha_inicio.substring(0, 10);
            }
        }
        info.textContent = infoText + (evt.ubicacion ? ' · ' + evt.ubicacion : '');
        info.style.cssText = 'margin: 0; font-size: 11px; color: #bf360c; line-height: 1.3;';

        item.appendChild(titulo);
        item.appendChild(info);
        lista.appendChild(item);
    });
}

function abrirEvento(eventoId, titulo) {
    // Crear modal para detalle de evento
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        z-index: 5000;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    `;

    content.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #e74c3c;">🎉 ${titulo}</h2>
            <button onclick="this.closest('div').parentElement.parentElement.remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">✕</button>
        </div>
        <p style="color: #666; margin-bottom: 20px;">Cargando evento...</p>
    `;

    modal.appendChild(content);
    document.body.appendChild(modal);

    // Cargar datos del evento
    fetch('/api/eventos.php?id=' + eventoId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const evt = data.data;
                let html = `
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div>
                            <h2 style="margin: 0 0 5px 0; color: #e74c3c;">${evt.titulo}</h2>
                            <span style="display: inline-block; background: #ffebee; color: #e74c3c; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">${evt.categoria || 'Evento'}</span>
                        </div>
                        <button onclick="this.closest('div').parentElement.remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">✕</button>
                    </div>
                `;

                if (evt.descripcion) {
                    html += `<p style="color: #666; margin-bottom: 15px; line-height: 1.5;">${evt.descripcion}</p>`;
                }

                html += '<div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 15px;">';

                if (evt.fecha_inicio) {
                    try {
                        const dt = new Date(evt.fecha_inicio);
                        const formatted = dt.toLocaleDateString('es-AR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                        html += `<p style="margin: 0 0 10px 0;"><strong>📅</strong> ${formatted}</p>`;
                    } catch (e) {
                        html += `<p style="margin: 0 0 10px 0;"><strong>📅</strong> ${evt.fecha_inicio}</p>`;
                    }
                }

                if (evt.ubicacion) {
                    html += `<p style="margin: 0 0 10px 0;"><strong>📍</strong> ${evt.ubicacion}</p>`;
                }

                if (evt.lat && evt.lng) {
                    html += '<p style="margin: 0;"><strong>🗺\uFE0F</strong> ' + parseFloat(evt.lat).toFixed(4) + ', ' + parseFloat(evt.lng).toFixed(4) + '</p>';
                }
                if (evt.organizador) {
                    html += '<p style="margin:8px 0 0"><strong>👤 Org.:</strong> ' + evt.organizador + '</p>';
                }

                html += '</div>';

                // YouTube embed
                if (evt.youtube_link) {
                    var ytId = null;
                    var m1 = evt.youtube_link.match(/youtu\.be\/([^?]+)/);
                    var m2 = evt.youtube_link.match(/[?&]v=([^&]+)/);
                    if (m1) ytId = m1[1];
                    else if (m2) ytId = m2[1];

                    if (ytId) {
                        html += '<div style="border-radius:8px;overflow:hidden;margin-bottom:15px;background:#000">';
                        html += '<div style="position:relative;padding-top:56.25%">';
                        html += '<iframe src="https://www.youtube.com/embed/' + ytId + '?rel=0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen></iframe>';
                        html += '</div></div>';
                    } else {
                        html += '<a href="' + evt.youtube_link + '" target="_blank" style="display:block;padding:12px;background:#ff0000;color:white;border-radius:6px;text-align:center;font-weight:600;text-decoration:none;margin-bottom:15px;">&#9654; Ver en YouTube</a>';
                    }
                }

                if (evt.lat && evt.lng) {
                    html += '<button onclick="document.querySelectorAll(\'.modal-overlay\').forEach(m=>m.remove());mapa.setView([' + evt.lat + ',' + evt.lng + '],16);" style="display:block;width:100%;padding:12px;background:#667eea;color:white;border:none;border-radius:6px;text-align:center;font-weight:600;cursor:pointer;transition:0.2s;">📍 Ver en Mapa</button>';
                }

                html += buildWTPopupSection('evento', evt.id, evt.titulo || 'Evento');
                content.innerHTML = html;
                initWTPanelsInPopup(content);
            }
        })
        .catch(e => {
            console.error('Error cargando evento', e);
            content.innerHTML = '<p style="color: #e74c3c;">Error al cargar el evento.</p>';
        });

    // Cerrar al hacer click afuera
    modal.onclick = (e) => {
        if (e.target === modal) {
            stopWtPollingInRoot(modal);
            modal.remove();
        }
    };
}

// ─── Marcadores de Eventos en el mapa ─────────────────────────────────────────
function mostrarMarcadoresEventos(eventos) {
    eventosLayer.clearLayers();
    eventoMarkers = [];

    eventos.forEach(function(evt) {
        if (!evt.lat || !evt.lng) return;
        var hasYT  = !!evt.youtube_link;
        var evColor = hasYT ? '#e74c3c' : '#c0392b';
        var evEmoji = hasYT ? '▶' : '🎉';
        // ¿Evento en las próximas 24h?
        var isPulse = false;
        if (evt.fecha) {
            var evDate = new Date(evt.fecha + (evt.hora ? ' ' + evt.hora : ''));
            var diff   = evDate - Date.now();
            isPulse    = diff >= 0 && diff < 86400000;
        }
        var svgRaw   = make3dPin(evEmoji, evColor, 32, 44, isPulse ? 'icon-pulse' : '');
        var temporal = getEntityTemporalState(evt, 'evento');
        var iconHtml = wrapPinWithTemporalBadge(svgRaw, temporal);
        var iconSize = calcBadgeIconSize(32, 44, !!temporal);
        var icon = L.divIcon({ html: iconHtml, className: '', iconSize: iconSize, iconAnchor: [iconSize[0]/2, iconSize[1]], popupAnchor: [0, -(iconSize[1]+2)] });
        var m = L.marker([parseFloat(evt.lat), parseFloat(evt.lng)], { icon: icon });
        m._mapitaMeta = { entity_type: 'evento', entity_id: evt.id || null, mapita_id: evt.mapita_id || null };
        var popupHtml = '<div style="font-family:inherit;min-width:200px">'
            + '<div style="background:linear-gradient(135deg,#e74c3c,#c0392b);color:white;padding:12px;margin:-1px -1px 12px;border-radius:4px 4px 0 0">'
            + '<strong style="font-size:14px;">' + evt.titulo + '</strong></div>'
            + '<div style="padding:0 4px 8px">';
        if (evt.fecha) popupHtml += '<p style="margin:4px 0;font-size:12px">📅 ' + evt.fecha + (evt.hora ? ' ' + evt.hora.substring(0,5) : '') + '</p>';
        if (evt.ubicacion) popupHtml += '<p style="margin:4px 0;font-size:12px">📍 ' + evt.ubicacion + '</p>';
        if (evt.organizador) popupHtml += '<p style="margin:4px 0;font-size:12px">👤 ' + evt.organizador + '</p>';
        if (evt.descripcion) popupHtml += '<p style="margin:8px 0;font-size:12px;color:#555;">' + evt.descripcion.substring(0,100) + (evt.descripcion.length>100?'…':'') + '</p>';
        popupHtml += buildWTPopupSection('evento', evt.id, evt.titulo);
        popupHtml += '<div style="margin-top:10px;display:flex;gap:6px;flex-wrap:wrap">';
        if (hasYT) popupHtml += '<button onclick="abrirEvento(' + evt.id + ',this.closest(\'.leaflet-popup-content\').querySelector(\'strong\').textContent)" style="padding:6px 12px;background:#ff0000;color:white;border:none;border-radius:12px;cursor:pointer;font-size:12px;">▶ YouTube</button>';
        popupHtml += '<button onclick="abrirEvento(' + evt.id + ',\'' + evt.titulo.replace(/'/g, '') + '\')" style="padding:6px 12px;background:#667eea;color:white;border:none;border-radius:12px;cursor:pointer;font-size:12px;">📋 Detalle</button>';
        popupHtml += '</div></div></div>';

        m.bindPopup(popupHtml, { maxWidth: 280 });
        m._selectionKey = getSelectionKey('evento', parseInt(evt.id, 10));
        m._selectionItem = {
            kind: 'evento',
            id: parseInt(evt.id, 10),
            name: evt.titulo || 'Evento',
            lat: parseFloat(evt.lat),
            lng: parseFloat(evt.lng),
            metadata: buildEventoMetadata(evt)
        };
        m.on('mouseover', function() { showCtxPanel(m, evt, 'evento'); });
        m.on('mouseout',  function() { hideCtxPanel(); });
        m.on('click', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
            toggleSelection(m._selectionItem, m);
            m.closePopup();
        });
        m.on('preclick', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
        });
        eventosLayer.addLayer(m);
        eventoMarkers.push(m);
    });
    refreshSelectionVisuals();
}

// ─── Marcadores de Encuestas en el mapa ───────────────────────────────────────
function mostrarMarcadoresEncuestas(encuestas) {
    encuestasLayer.clearLayers();
    encuestaMarkers = [];

    encuestas.forEach(function(enc) {
        if (!enc.lat || !enc.lng || enc.lat == 0) return;
        var temporal = getEntityTemporalState(enc, 'encuesta');
        var svgRaw   = make3dPin('📋', '#f39c12', 30, 42, '');
        var iconHtml = wrapPinWithTemporalBadge(svgRaw, temporal);
        var iconSize = calcBadgeIconSize(30, 42, !!temporal);
        var icon = L.divIcon({ html: iconHtml, className: '', iconSize: iconSize, iconAnchor: [iconSize[0]/2, iconSize[1]], popupAnchor: [0,-44] });
        var m = L.marker([parseFloat(enc.lat), parseFloat(enc.lng)], { icon: icon });
        m._mapitaMeta = { entity_type: 'encuesta', entity_id: enc.id || null, mapita_id: enc.mapita_id || null };
        var popHtml = '<div style="font-family:inherit;min-width:200px">'
            + '<div style="background:linear-gradient(135deg,#f39c12,#e67e22);color:white;padding:12px;margin:-1px -1px 12px;border-radius:4px 4px 0 0">'
            + '<strong style="font-size:14px;">📋 ' + enc.titulo + '</strong></div>'
            + '<div style="padding:0 4px 8px">';
        if (enc.descripcion) popHtml += '<p style="margin:4px 0;font-size:12px;color:#555">' + enc.descripcion.substring(0,80) + '...</p>';
        if (enc.fecha_expiracion) popHtml += '<p style="margin:6px 0;font-size:11px;color:#888">Vence: ' + enc.fecha_expiracion + '</p>';
        if (enc.link) {
            popHtml += '<a href="' + enc.link + '" target="_blank" style="display:block;padding:8px;background:#f39c12;color:white;border-radius:8px;text-align:center;text-decoration:none;font-size:12px;font-weight:600;margin-top:8px;">🔗 Responder</a>';
        } else {
            popHtml += '<button onclick="abrirEncuesta(' + enc.id + ',\'' + enc.titulo.replace(/'/g, '') + '\')" style="width:100%;padding:8px;background:#f39c12;color:white;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;margin-top:8px;">📋 Responder</button>';
        }
        popHtml += buildWTPopupSection('encuesta', enc.id, enc.titulo);
        popHtml += '</div></div>';

        m.bindPopup(popHtml, { maxWidth: 260 });
        m._selectionKey = getSelectionKey('encuesta', parseInt(enc.id, 10));
        m._selectionItem = {
            kind: 'encuesta',
            id: parseInt(enc.id, 10),
            name: enc.titulo || 'Encuesta',
            lat: parseFloat(enc.lat),
            lng: parseFloat(enc.lng),
            metadata: buildEncuestaMetadata(enc)
        };
        m.on('mouseover', function() { showCtxPanel(m, enc, 'encuesta'); });
        m.on('mouseout',  function() { hideCtxPanel(); });
        m.on('click', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
            toggleSelection(m._selectionItem, m);
            m.closePopup();
        });
        m.on('preclick', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
        });
        encuestasLayer.addLayer(m);
        encuestaMarkers.push(m);
    });
    refreshSelectionVisuals();
}

// ─── Trivias Widget ────────────────────────────────────────────────────────────
function mostrarTriviasWidget(trivias) {
    const container = document.getElementById('trivias-container');
    const lista = document.getElementById('trivias-list');

    if (!container || !lista || !trivias || trivias.length === 0) {
        return;
    }

    container.style.display = 'block';
    lista.innerHTML = '';

    trivias.forEach(tri => {
        const item = document.createElement('div');
        item.style.cssText = `
            padding: 12px;
            background: linear-gradient(135deg, #f3e5f5 0%, #e8d4f8 100%);
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #9b59b6;
        `;

        item.onmouseover = () => {
            item.style.background = 'linear-gradient(135deg, #e8d4f8 0%, #dfc6f0 100%)';
            item.style.transform = 'translateY(-2px)';
            item.style.boxShadow = '0 4px 12px rgba(155, 89, 182, 0.2)';
        };

        item.onmouseout = () => {
            item.style.background = 'linear-gradient(135deg, #f3e5f5 0%, #e8d4f8 100%)';
            item.style.transform = 'translateY(0)';
            item.style.boxShadow = 'none';
        };

        item.onclick = () => {
            if (!<?= isset($_SESSION['user_id']) && $_SESSION['user_id'] ? 'true' : 'false' ?>) {
                alert('Debes iniciar sesión para jugar trivias');
                window.location.href = '/login';
                return;
            }
            abrirTrivia(tri.id, tri.titulo);
        };

        const titulo = document.createElement('h4');
        titulo.textContent = tri.titulo;
        titulo.style.cssText = 'margin: 0 0 6px 0; font-size: 13px; color: #9b59b6; font-weight: 600;';

        const dif = document.createElement('p');
        let dificultad = '';
        if (tri.dificultad === 'facil') dificultad = '⭐ Fácil';
        else if (tri.dificultad === 'dificil') dificultad = '⭐⭐⭐ Difícil';
        else dificultad = '⭐⭐ Medio';
        dif.textContent = dificultad;
        dif.style.cssText = 'margin: 0; font-size: 11px; color: #7b3fa0; font-weight: 600;';

        item.appendChild(titulo);
        item.appendChild(dif);
        lista.appendChild(item);
    });
}

function abrirTrivia(triviaId, titulo) {
    // Crear modal para jugar trivia
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        z-index: 5000;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    `;

    content.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #9b59b6;">🎯 ${titulo}</h2>
            <button onclick="this.closest('div').parentElement.parentElement.remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">✕</button>
        </div>
        <p style="color: #666; margin-bottom: 20px;">Cargando trivia...</p>
    `;

    modal.appendChild(content);
    document.body.appendChild(modal);

    fetch('/api/trivias.php?id=' + triviaId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                const tri = data.data;
                let html = `
                    <h2 style="margin: 0 0 10px 0; color: #9b59b6;">${tri.titulo}</h2>
                `;

                if (tri.descripcion) {
                    html += `<p style="color: #666; margin-bottom: 20px;">${tri.descripcion}</p>`;
                }

                if (tri.preguntas && tri.preguntas.length > 0) {
                    html += '<form id="trivia-form">';
                    tri.preguntas.forEach((preg, idx) => {
                        html += `
                            <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                                <p style="margin: 0 0 12px 0; font-weight: 600; color: #2c3e50;">${idx + 1}. ${preg.pregunta}</p>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label style="cursor: pointer;">
                                        <input type="radio" name="pregunta_${preg.id}" value="A" style="margin-right: 8px;"> A) ${preg.opcion_a}
                                    </label>
                                    <label style="cursor: pointer;">
                                        <input type="radio" name="pregunta_${preg.id}" value="B" style="margin-right: 8px;"> B) ${preg.opcion_b}
                                    </label>
                                    <label style="cursor: pointer;">
                                        <input type="radio" name="pregunta_${preg.id}" value="C" style="margin-right: 8px;"> C) ${preg.opcion_c}
                                    </label>
                                    <label style="cursor: pointer;">
                                        <input type="radio" name="pregunta_${preg.id}" value="D" style="margin-right: 8px;"> D) ${preg.opcion_d}
                                    </label>
                                </div>
                            </div>
                        `;
                    });

                    html += `
                        <button type="button" onclick="verificarTrivia(${tri.id}, ${tri.preguntas.length})" style="width: 100%; padding: 12px; background: #9b59b6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                            ✓ Verificar Respuestas
                        </button>
                    </form>
                    `;
                } else {
                    html += '<p style="color: #999; text-align: center;">Esta trivia aún no tiene preguntas.</p>';
                }

                content.innerHTML = html;
            }
        })
        .catch(e => {
            console.error('Error cargando trivia', e);
            content.innerHTML = '<p style="color: #9b59b6;">Error al cargar la trivia.</p>';
        });

    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
}

function verificarTrivia(triviaId, totalPreguntas) {
    const form = document.getElementById('trivia-form');
    let correctas = 0;
    const respuestas = {};

    form.querySelectorAll('[name^="pregunta_"]').forEach(input => {
        if (input.type === 'radio') {
            const checked = form.querySelector(`[name="${input.name}"]:checked`);
            if (checked) {
                respuestas[input.name] = checked.value;
            }
        }
    });

    if (Object.keys(respuestas).length === 0) {
        alert('Debes responder al menos una pregunta');
        return;
    }

    // Calcular puntos (simplificado: 10 puntos por respuesta correcta)
    correctas = Object.keys(respuestas).length;
    const puntos = correctas * 10;

    fetch('/api/trivias.php?action=score', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            trivia_id: triviaId,
            puntos: puntos,
            respuestas_correctas: correctas
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`¡Excelente! Has acertado ${correctas} respuesta(s) y ganado ${puntos} puntos 🎉`);
            document.querySelector('[onclick*="parentElement"]').closest('div').parentElement.remove();
        } else {
            alert('Error al registrar tu puntuación');
        }
    })
    .catch(e => {
        console.error('Error', e);
        alert('Error al procesar tu respuesta');
    });
}

// ─── NOTICIAS WIDGET ────────────────────────────────────────────────────────────
function mostrarNoticiasWidget(noticias) {
    const container = document.getElementById('noticias-container');
    const lista = document.getElementById('noticias-list');

    if (!container || !lista || !noticias || noticias.length === 0) {
        return;
    }

    container.style.display = 'block';
    lista.innerHTML = '';

    noticias.forEach(noticia => {
        const item = document.createElement('div');
        item.style.cssText = `
            padding: 12px;
            background: linear-gradient(135deg, #eef4ff 0%, #e3e8ff 100%);
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #667eea;
        `;

        item.onmouseover = () => {
            item.style.background = 'linear-gradient(135deg, #e3e8ff 0%, #d9deff 100%)';
            item.style.transform = 'translateY(-2px)';
            item.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.2)';
        };

        item.onmouseout = () => {
            item.style.background = 'linear-gradient(135deg, #eef4ff 0%, #e3e8ff 100%)';
            item.style.transform = 'translateY(0)';
            item.style.boxShadow = 'none';
        };

        item.onclick = () => {
            abrirNoticiaModal(noticia.id, noticia.titulo, noticia);
        };

        const titulo = document.createElement('h4');
        titulo.textContent = noticia.titulo.substring(0, 35) + (noticia.titulo.length > 35 ? '...' : '');
        titulo.style.cssText = 'margin: 0 0 6px 0; font-size: 13px; color: #667eea; font-weight: 600;';

        const categoria = document.createElement('p');
        categoria.textContent = `📁 ${noticia.categoria}`;
        categoria.style.cssText = 'margin: 0; font-size: 11px; color: #764ba2; font-weight: 500;';

        const vistas = document.createElement('p');
        vistas.textContent = `👁️ ${noticia.vistas} vistas`;
        vistas.style.cssText = 'margin: 4px 0 0 0; font-size: 10px; color: #999;';

        item.appendChild(titulo);
        item.appendChild(categoria);
        item.appendChild(vistas);
        lista.appendChild(item);
    });
}

function abrirNoticiaModal(noticiaId, titulo, noticiaData) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        z-index: 5000;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    `;

    const header = document.createElement('div');
    header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;';

    const h2 = document.createElement('h2');
    h2.textContent = '📰 ' + titulo;
    h2.style.cssText = 'margin: 0; color: #667eea;';

    const closeBtn = document.createElement('button');
    closeBtn.textContent = '✕';
    closeBtn.style.cssText = 'background: none; border: none; font-size: 24px; cursor: pointer; color: #999;';
    closeBtn.onclick = () => modal.remove();

    header.appendChild(h2);
    header.appendChild(closeBtn);
    content.appendChild(header);

    // Categoría y vistas
    const meta = document.createElement('div');
    meta.style.cssText = 'display: flex; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;';
    meta.innerHTML = `
        <span style="font-size: 12px; color: #667eea; font-weight: 600;"><strong>📁 Categoría:</strong> ${noticiaData.categoria || 'General'}</span>
        <span style="font-size: 12px; color: #999;"><strong>👁️</strong> ${noticiaData.vistas || 0} vistas</span>
        <span style="font-size: 12px; color: #999;"><strong>📅</strong> ${new Date(noticiaData.fecha_publicacion).toLocaleDateString('es-ES')}</span>
    `;
    content.appendChild(meta);

    // Imagen si existe
    if (noticiaData.imagen) {
        const img = document.createElement('img');
        img.src = '/uploads/noticias/' + noticiaData.imagen;
        img.style.cssText = 'width: 100%; height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 15px;';
        img.onerror = () => img.style.display = 'none';
        content.appendChild(img);
    }

    // Contenido
    const body = document.createElement('div');
    body.style.cssText = 'font-size: 14px; line-height: 1.6; color: #333; margin-bottom: 20px;';
    body.innerHTML = noticiaData.contenido || '<p>Contenido no disponible</p>';
    content.appendChild(body);

    // Botón de acción
    const footer = document.createElement('div');
    footer.style.cssText = 'display: flex; gap: 10px; padding-top: 20px; border-top: 1px solid #eee;';
    footer.innerHTML = `
        <a href="/noticia?id=${noticiaId}" target="_blank" style="flex: 1; padding: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; text-align: center; border-radius: 6px; font-weight: 600;">Leer Completo</a>
        <button onclick="this.closest('div').parentElement.parentElement.remove()" style="padding: 10px 20px; background: #f0f0f0; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Cerrar</button>
    `;
    content.appendChild(footer);

    modal.appendChild(content);
    document.body.appendChild(modal);

    // Registrar vista
    fetch('/api/noticias.php?action=view', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + noticiaId
    }).catch(e => console.log('Vista registrada'));
}

// ─── Marcadores de Trivias en el mapa ─────────────────────────────────────────
let triviaMarkers = [];
function mostrarMarcadoresTrivias(trivias) {
    triviasLayer.clearLayers();
    triviaMarkers = [];

    trivias.forEach(tri => {
        if (!tri.lat || !tri.lng) return;

        const difColors = { facil: '#27ae60', medio: '#f39c12', dificil: '#e74c3c' };
        const difColor  = difColors[tri.dificultad] || '#9b59b6';

        const svg  = make3dPin('🎯', difColor, 32, 44, '');
        const icon = L.divIcon({ html: svg, className: '', iconSize: [32, 44], iconAnchor: [16, 44], popupAnchor: [0, -46] });
        const m    = L.marker([parseFloat(tri.lat), parseFloat(tri.lng)], { icon });
        m._mapitaMeta = { entity_type: 'trivia', entity_id: tri.id || null, mapita_id: tri.mapita_id || null };

        const dificultadLabel = { facil: '🟢 Fácil', medio: '🟡 Medio', dificil: '🔴 Difícil' }[tri.dificultad] || tri.dificultad;
        let popup = '<div style="font-family:inherit;min-width:200px">'
            + '<div style="background:linear-gradient(135deg,' + difColor + ',#7d3c98);color:white;padding:12px;margin:-1px -1px 12px;border-radius:4px 4px 0 0">'
            + '<strong style="font-size:14px;">🎯 ' + tri.titulo + '</strong></div>'
            + '<div style="padding:0 4px 8px">';
        if (tri.descripcion) popup += '<p style="margin:4px 0;font-size:12px;color:#555">' + tri.descripcion.substring(0, 100) + (tri.descripcion.length > 100 ? '…' : '') + '</p>';
        popup += '<p style="margin:6px 0;font-size:12px">📊 Dificultad: ' + dificultadLabel + '</p>';
        if (tri.tiempo_limite) popup += '<p style="margin:4px 0;font-size:12px">⏱️ Tiempo límite: ' + tri.tiempo_limite + ' seg</p>';
        if (tri.ubicacion) popup += '<p style="margin:4px 0;font-size:12px">📍 ' + tri.ubicacion + '</p>';
        popup += '<button onclick="abrirTrivia(' + tri.id + ',\'' + tri.titulo.replace(/'/g, '') + '\')" style="width:100%;padding:8px;background:' + difColor + ';color:white;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;margin-top:8px;">🎯 Jugar trivia</button>';
        popup += '</div></div>';

        m.bindPopup(popup, { maxWidth: 280 });
        m._selectionKey = getSelectionKey('trivia', parseInt(tri.id, 10));
        m._selectionItem = {
            kind: 'trivia',
            id: parseInt(tri.id, 10),
            name: tri.titulo || 'Trivia',
            lat: parseFloat(tri.lat),
            lng: parseFloat(tri.lng),
            metadata: buildTriviaMetadata(tri)
        };
        m.on('mouseover', function() { showCtxPanel(m, tri, 'trivia'); });
        m.on('mouseout',  function() { hideCtxPanel(); });
        m.on('click', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
            toggleSelection(m._selectionItem, m);
            m.closePopup();
        });
        m.on('preclick', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
        });
        triviasLayer.addLayer(m);
        triviaMarkers.push(m);
    });
}

// ─── Marcadores de Noticias en el mapa ────────────────────────────────────────
let noticiaMarkers = [];
const _noticiaCache = {};  // cache para abrirNoticiaPorId

function abrirNoticiaPorId(id) {
    const n = _noticiaCache[id];
    if (n) abrirNoticiaModal(n.id, n.titulo, n);
}

function mostrarMarcadoresNoticias(noticias) {
    noticiasLayer.clearLayers();
    noticiaMarkers = [];

    noticias.forEach(noticia => {
        _noticiaCache[noticia.id] = noticia;   // guardar para popup
        if (!noticia.lat || !noticia.lng) return;

        const svg  = make3dPin('📰', '#667eea', 30, 42, '');
        const icon = L.divIcon({ html: svg, className: '', iconSize: [30, 42], iconAnchor: [15, 42], popupAnchor: [0, -44] });
        const m    = L.marker([parseFloat(noticia.lat), parseFloat(noticia.lng)], { icon });
        m._mapitaMeta = { entity_type: 'noticia', entity_id: noticia.id || null, mapita_id: noticia.mapita_id || null };

        const textoPrev = (noticia.contenido || '').replace(/<[^>]*>/g, '').substring(0, 120);
        let popup = '<div style="font-family:inherit;min-width:200px">'
            + '<div style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:12px;margin:-1px -1px 12px;border-radius:4px 4px 0 0">'
            + '<strong style="font-size:14px;">📰 ' + noticia.titulo + '</strong></div>'
            + '<div style="padding:0 4px 8px">';
        if (noticia.categoria) popup += '<p style="margin:4px 0;font-size:11px;background:#f0f2ff;display:inline-block;padding:2px 8px;border-radius:10px;color:#667eea;font-weight:600">' + noticia.categoria + '</p>';
        if (textoPrev) popup += '<p style="margin:8px 0;font-size:12px;color:#555">' + textoPrev + '…</p>';
        if (noticia.fecha_publicacion) popup += '<p style="margin:4px 0;font-size:11px;color:#999">📅 ' + noticia.fecha_publicacion.substring(0, 10) + '</p>';
        if (noticia.ubicacion) popup += '<p style="margin:4px 0;font-size:12px">📍 ' + noticia.ubicacion + '</p>';
        popup += '<button onclick="abrirNoticiaPorId(' + noticia.id + ')" style="width:100%;padding:8px;background:#667eea;color:white;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:700;margin-top:8px;">📰 Leer más</button>';
        popup += '</div></div>';

        m.bindPopup(popup, { maxWidth: 280 });
        m._selectionKey = getSelectionKey('noticia', parseInt(noticia.id, 10));
        m._selectionItem = {
            kind: 'noticia',
            id: parseInt(noticia.id, 10),
            name: noticia.titulo || 'Noticia',
            lat: parseFloat(noticia.lat),
            lng: parseFloat(noticia.lng),
            metadata: buildNoticiaMetadata(noticia)
        };
        m.on('mouseover', function() { showCtxPanel(m, noticia, 'noticia'); });
        m.on('mouseout',  function() { hideCtxPanel(); });
        m.on('click', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
            toggleSelection(m._selectionItem, m);
            m.closePopup();
        });
        m.on('preclick', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
        });
        noticiasLayer.addLayer(m);
        noticiaMarkers.push(m);
    });
}

// ─── OFERTAS WIDGET ────────────────────────────────────────────────────────────
function mostrarOfertasWidget(ofertas) {
    const container = document.getElementById('ofertas-container');
    const lista     = document.getElementById('ofertas-list');
    if (!container || !lista || !ofertas || ofertas.length === 0) return;

    container.style.display = 'block';
    lista.innerHTML = '';

    ofertas.forEach(oferta => {
        const item = document.createElement('div');
        item.style.cssText = `
            padding: 10px 12px;
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e74c3c;
        `;
        item.onmouseover = () => { item.style.transform = 'translateY(-2px)'; item.style.boxShadow = '0 4px 12px rgba(231,76,60,0.2)'; };
        item.onmouseout  = () => { item.style.transform = 'translateY(0)'; item.style.boxShadow = 'none'; };
        item.onclick     = () => abrirOfertaModal(oferta);

        const titulo = document.createElement('h4');
        titulo.textContent = oferta.nombre.substring(0, 38) + (oferta.nombre.length > 38 ? '…' : '');
        titulo.style.cssText = 'margin: 0 0 4px 0; font-size: 13px; color: #c0392b; font-weight: 600;';

        const precio = document.createElement('p');
        if (oferta.precio_oferta) {
            const old = oferta.precio_normal ? `<span style="text-decoration:line-through;color:#aaa;font-size:11px;margin-right:4px;">$${parseFloat(oferta.precio_normal).toLocaleString()}</span>` : '';
            precio.innerHTML = old + `<strong style="color:#e74c3c;">$${parseFloat(oferta.precio_oferta).toLocaleString()}</strong>`;
        } else {
            precio.textContent = oferta.descripcion ? oferta.descripcion.substring(0, 45) + '…' : '';
            precio.style.color = '#888';
            precio.style.fontSize = '12px';
        }
        precio.style.margin = '0';

        item.appendChild(titulo);
        item.appendChild(precio);
        lista.appendChild(item);
    });
}

function abrirOfertaModal(oferta) {
    const modal   = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;z-index:5000;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;';
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };

    const pct = oferta.precio_normal && oferta.precio_oferta
        ? Math.round((1 - oferta.precio_oferta / oferta.precio_normal) * 100)
        : null;

    modal.innerHTML = `
        <div style="background:white;border-radius:12px;padding:28px;max-width:440px;width:90%;max-height:80vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,0.25);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
                <h2 style="margin:0;color:#c0392b;font-size:1.2em;">🏷️ ${oferta.nombre}</h2>
                ${pct ? `<span style="background:#e74c3c;color:white;padding:4px 10px;border-radius:20px;font-weight:700;font-size:14px;flex-shrink:0;margin-left:8px;">-${pct}%</span>` : ''}
                <button onclick="this.closest('div').parentElement.parentElement.remove()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#999;margin-left:8px;">✕</button>
            </div>
            ${oferta.descripcion ? `<p style="color:#555;line-height:1.55;margin:0 0 16px;">${oferta.descripcion}</p>` : ''}
            ${(oferta.precio_normal || oferta.precio_oferta) ? `
            <div style="display:flex;align-items:center;gap:12px;background:#fff5f5;padding:12px;border-radius:8px;margin-bottom:16px;">
                ${oferta.precio_normal ? `<span style="text-decoration:line-through;color:#aaa;font-size:18px;">$${parseFloat(oferta.precio_normal).toLocaleString()}</span>` : ''}
                ${oferta.precio_oferta ? `<span style="color:#e74c3c;font-size:28px;font-weight:800;">$${parseFloat(oferta.precio_oferta).toLocaleString()}</span>` : ''}
            </div>` : ''}
            ${oferta.fecha_expiracion ? `<p style="font-size:12px;color:#888;margin:0 0 16px;">⏰ Válido hasta: <strong>${oferta.fecha_expiracion}</strong></p>` : ''}
            <button onclick="this.closest('div').parentElement.parentElement.remove()" style="width:100%;padding:10px;background:linear-gradient(135deg,#e74c3c,#c0392b);color:white;border:none;border-radius:8px;cursor:pointer;font-weight:700;">Cerrar</button>
        </div>`;

    document.body.appendChild(modal);
}

// ─── MARCADORES DE OFERTAS ─────────────────────────────────────────────────────
let ofertaMarkers = [];
function mostrarMarcadoresOfertas(ofertas) {
    ofertasLayer.clearLayers();
    ofertaMarkers = [];

    ofertas.forEach(function(o) {
        if (!o.lat || !o.lng || parseFloat(o.lat) === 0) return;
        var temporal = getEntityTemporalState(o, 'oferta');
        var svgRaw   = make3dPin('🏷️', '#e74c3c', 30, 42, '');
        var iconHtml = wrapPinWithTemporalBadge(svgRaw, temporal);
        var iconSize = calcBadgeIconSize(30, 42, !!temporal);
        var icon = L.divIcon({ html: iconHtml, className: '', iconSize: iconSize, iconAnchor: [iconSize[0]/2, iconSize[1]], popupAnchor: [0,-44] });
        var m    = L.marker([parseFloat(o.lat), parseFloat(o.lng)], { icon: icon });
        m._mapitaMeta = { entity_type: 'oferta', entity_id: o.id || null, mapita_id: o.mapita_id || null };
        var pct = o.precio_normal && o.precio_oferta
            ? ' <strong style="color:#e74c3c;">-' + Math.round((1 - o.precio_oferta / o.precio_normal) * 100) + '%</strong>' : '';
        var popHtml = '<div style="font-family:inherit;min-width:200px">'
            + '<div style="background:linear-gradient(135deg,#e74c3c,#c0392b);color:white;padding:12px;margin:-1px -1px 12px;border-radius:4px 4px 0 0">'
            + '<strong style="font-size:14px;">🏷️ ' + o.nombre + '</strong></div>'
            + '<div style="padding:0 4px 8px">';
        if (o.descripcion) popHtml += '<p style="margin:4px 0;font-size:12px;color:#555">' + o.descripcion.substring(0, 80) + '…</p>';
        if (o.precio_oferta) popHtml += '<p style="margin:6px 0;font-size:16px;font-weight:700;color:#e74c3c">$' + parseFloat(o.precio_oferta).toLocaleString() + pct + '</p>';
        if (o.fecha_expiracion) popHtml += '<p style="margin:4px 0;font-size:11px;color:#888">⏰ Válido hasta: ' + o.fecha_expiracion + '</p>';
        popHtml += '</div></div>';

        m.bindPopup(popHtml, { maxWidth: 260 });
        m._selectionKey = getSelectionKey('oferta', parseInt(o.id, 10));
        m._selectionItem = {
            kind: 'oferta',
            id: parseInt(o.id, 10),
            name: o.nombre || 'Oferta',
            lat: parseFloat(o.lat),
            lng: parseFloat(o.lng),
            metadata: buildOfertaMetadata(o)
        };
        m.on('mouseover', function() { showCtxPanel(m, o, 'oferta'); });
        m.on('mouseout',  function() { hideCtxPanel(); });
        m.on('click', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
            toggleSelection(m._selectionItem, m);
            m.closePopup();
        });
        m.on('preclick', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
        });
        ofertasLayer.addLayer(m);
        ofertaMarkers.push(m);
    });
}

// ─── TRANSMISIONES WIDGET ──────────────────────────────────────────────────────
function mostrarTransmisionesWidget(transmisiones) {
    const container = document.getElementById('transmisiones-container');
    const lista     = document.getElementById('transmisiones-list');
    if (!container || !lista || !transmisiones || transmisiones.length === 0) return;

    container.style.display = 'block';
    lista.innerHTML = '';

    const iconTipo = { youtube_live: '▶', radio_stream: '📻', audio_stream: '🎵', video_stream: '🎬' };

    transmisiones.forEach(tx => {
        const item = document.createElement('div');
        item.style.cssText = `
            padding: 10px 12px;
            background: linear-gradient(135deg, #fff2f2 0%, #ffe5e5 100%);
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #c0392b;
        `;
        item.onmouseover = () => { item.style.transform = 'translateY(-2px)'; item.style.boxShadow = '0 4px 12px rgba(192,57,43,0.2)'; };
        item.onmouseout  = () => { item.style.transform = 'translateY(0)'; item.style.boxShadow = 'none'; };
        item.onclick     = () => abrirTransmisionModal(tx);

        const titulo = document.createElement('div');
        const liveDot = tx.en_vivo
            ? '<span style="display:inline-block;width:7px;height:7px;background:#e74c3c;border-radius:50%;animation:blink 1s infinite;vertical-align:middle;margin-right:4px;"></span>'
            : '';
        titulo.innerHTML = liveDot + `<strong style="font-size:13px;color:#c0392b;">${(iconTipo[tx.tipo] || '📡')} ${tx.titulo.substring(0, 32) + (tx.titulo.length > 32 ? '…' : '')}</strong>`;
        titulo.style.margin = '0 0 3px 0';

        const sub = document.createElement('p');
        sub.textContent = tx.en_vivo ? 'EN VIVO ahora' : (tx.descripcion ? tx.descripcion.substring(0, 40) + '…' : tx.tipo);
        sub.style.cssText = `margin:0;font-size:11px;color:${tx.en_vivo ? '#e74c3c' : '#888'};font-weight:${tx.en_vivo ? '700' : '400'};`;

        item.appendChild(titulo);
        item.appendChild(sub);
        lista.appendChild(item);
    });
}

function abrirTransmisionModal(tx) {
    const modal   = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;z-index:5000;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;';
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };

    const tipos   = { youtube_live: 'YouTube Live', radio_stream: 'Radio Online', audio_stream: 'Audio Stream', video_stream: 'Video Stream' };
    const isYT    = tx.tipo === 'youtube_live' && tx.stream_url;
    const ytEmbed = isYT ? tx.stream_url.replace('youtu.be/', 'www.youtube.com/embed/').replace('watch?v=', 'embed/') : '';

    modal.innerHTML = `
        <div style="background:white;border-radius:12px;padding:0;max-width:540px;width:95%;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.3);">
            <div style="background:linear-gradient(135deg,#c0392b,#922b21);padding:20px 24px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <h2 style="margin:0;color:white;font-size:1.15em;">📡 ${tx.titulo}</h2>
                    <p style="margin:4px 0 0;color:rgba(255,255,255,.7);font-size:.8em;">${tipos[tx.tipo] || tx.tipo}${tx.en_vivo ? ' · <strong style="color:#ff6b6b;">🔴 EN VIVO</strong>' : ''}</p>
                </div>
                <button onclick="this.closest('div').parentElement.parentElement.remove()" style="background:rgba(255,255,255,.15);border:none;color:white;font-size:20px;cursor:pointer;border-radius:50%;width:32px;height:32px;line-height:1;">✕</button>
            </div>
            <div style="padding:20px 24px;">
                ${tx.descripcion ? `<p style="color:#555;margin:0 0 16px;line-height:1.5;">${tx.descripcion}</p>` : ''}
                ${isYT ? `<div style="aspect-ratio:16/9;border-radius:8px;overflow:hidden;margin-bottom:16px;">
                    <iframe src="${ytEmbed}?autoplay=1" width="100%" height="100%" frameborder="0" allow="autoplay;encrypted-media" allowfullscreen style="display:block;"></iframe>
                </div>` : ''}
                ${!isYT && tx.stream_url ? `<a href="${tx.stream_url}" target="_blank" style="display:block;padding:14px;background:linear-gradient(135deg,#c0392b,#922b21);color:white;text-decoration:none;text-align:center;border-radius:8px;font-weight:700;margin-bottom:12px;">🎧 Abrir transmisión</a>` : ''}
                <button onclick="this.closest('div').parentElement.parentElement.remove()" style="width:100%;padding:10px;background:#f0f0f0;border:none;border-radius:8px;cursor:pointer;font-weight:600;">Cerrar</button>
            </div>
        </div>`;

    document.body.appendChild(modal);
}

// ─── MARCADORES DE TRANSMISIONES ───────────────────────────────────────────────
// Cache para acceder al objeto completo desde el onclick del popup sin serializar en HTML
const _txCache = new Map();

let transmisionMarkers = [];
function mostrarMarcadoresTransmisiones(transmisiones) {
    transmisionesLayer.clearLayers();
    transmisionMarkers = [];

    transmisiones.forEach(function(tx) {
        if (!tx.lat || !tx.lng || parseFloat(tx.lat) === 0) return;
        _txCache.set(tx.id, tx);   // guardar referencia por ID
        var color = tx.en_vivo ? '#e74c3c' : '#c0392b';
        var label = tx.en_vivo ? '🔴' : '📡';
        var svg   = make3dPin(label, color, 30, 42, tx.en_vivo ? 'icon-glow' : '');
        var icon  = L.divIcon({ html: svg, className: '', iconSize: [30,42], iconAnchor: [15,42], popupAnchor: [0,-44] });
        var m    = L.marker([parseFloat(tx.lat), parseFloat(tx.lng)], { icon: icon });
        m._mapitaMeta = { entity_type: 'transmision', entity_id: tx.id || null, mapita_id: tx.mapita_id || null };
        var popHtml = '<div style="font-family:inherit;min-width:200px">'
            + '<div style="background:linear-gradient(135deg,' + color + ',#922b21);color:white;padding:12px;margin:-1px -1px 12px;border-radius:4px 4px 0 0">'
            + (tx.en_vivo ? '<span style="background:rgba(255,255,255,.25);padding:2px 6px;border-radius:8px;font-size:11px;margin-right:6px;">🔴 EN VIVO</span>' : '')
            + '<strong style="font-size:14px;">📡 ' + tx.titulo + '</strong></div>'
            + '<div style="padding:0 4px 8px">';
        if (tx.descripcion) popHtml += '<p style="margin:4px 0;font-size:12px;color:#555">' + tx.descripcion.substring(0, 80) + '…</p>';
        if (tx.stream_url) {
            popHtml += '<button onclick="abrirTransmisionModal(_txCache.get(' + tx.id + '))" style="width:100%;padding:8px;background:#c0392b;color:white;border:none;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;margin-top:8px;">▶ Ver Transmisión</button>';
        }
        popHtml += '</div></div>';

        m.bindPopup(popHtml, { maxWidth: 260 });
        m._selectionKey = getSelectionKey('transmision', parseInt(tx.id, 10));
        m._selectionItem = {
            kind: 'transmision',
            id: parseInt(tx.id, 10),
            name: tx.titulo || 'Transmisión',
            lat: parseFloat(tx.lat),
            lng: parseFloat(tx.lng),
            metadata: buildTransmisionMetadata(tx)
        };
        m.on('mouseover', function() { showCtxPanel(m, tx, 'transmision'); });
        m.on('mouseout',  function() { hideCtxPanel(); });
        m.on('click', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
            toggleSelection(m._selectionItem, m);
            m.closePopup();
        });
        m.on('preclick', function(e) {
            if (!selectionMode) return;
            stopLeafletEvent(e);
        });
        transmisionesLayer.addLayer(m);
        transmisionMarkers.push(m);
    });
}

// ─── BRAND POPUP PREMIUM ────────────────────────────────────────────────────────
function abrirBrandPopupPremium(brandId, brandName, brandRubro, brandData = {}) {
    const overlay = document.createElement('div');
    overlay.className = 'brand-popup-overlay';

    const container = document.createElement('div');
    container.className = 'brand-popup-container';

    // Cargar datos de la marca
    fetch(`/api/brand-gallery.php?brand_id=${brandId}`)
        .then(r => r.json())
        .then(data => {
            let images = data.success ? data.data : [];

            let html = `
                <!-- Sección Galería -->
                <div class="brand-popup-gallery">
                    <div id="brand-gallery-main" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                        ${images.length > 0
                            ? `<img src="/uploads/brands/${images[0].filename}" alt="${brandName}" class="brand-gallery-image">`
                            : `<div class="brand-gallery-placeholder">
                                 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                     <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                     <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                     <path d="M21 15l-5-5L5 21"></path>
                                 </svg>
                                 <p>Galería de Marca</p>
                             </div>`
                        }
                    </div>
                    ${images.length > 0 ? `
                        <button class="brand-gallery-btn" onclick="cambiarGaleria(-1)" style="margin-left: auto;">←</button>
                        <button class="brand-gallery-btn" onclick="cambiarGaleria(1)">→</button>
                        <div class="brand-gallery-thumbnails">
                            ${images.map((img, idx) => `<div class="brand-gallery-thumb ${idx === 0 ? 'active' : ''}" onclick="irAImagen(${idx})" data-index="${idx}"></div>`).join('')}
                        </div>
                    ` : ''}
                </div>

                <!-- Sección Información -->
                <div class="brand-popup-info">
                    <div>
                        <!-- Header -->
                        <div class="brand-info-header">
                            <div class="brand-logo-wrapper">🏢</div>
                            <div class="brand-header-text">
                                <h2>${brandName}</h2>
                                <span class="rubro">${brandRubro || 'Marca'}</span>
                            </div>
                        </div>

                        <!-- Info Cards -->
                        <div class="brand-info-cards">
                            ${brandData.ubicacion ? `
                                <div class="brand-info-card">
                                    <span class="label">📍 Ubicación</span>
                                    <span class="value">${brandData.ubicacion}</span>
                                </div>
                            ` : ''}
                            ${brandData.estado ? `
                                <div class="brand-info-card">
                                    <span class="label">🏛️ Estado</span>
                                    <span class="value">${brandData.estado}</span>
                                </div>
                            ` : ''}
                        </div>

                        <!-- Description -->
                        ${brandData.historia ? `
                            <div class="brand-description">
                                <span class="label">📖 Historia</span>
                                <p class="text">${brandData.historia}</p>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Action Buttons -->
                    <div class="brand-actions">
                        <a href="/brand_detail?id=${brandId}" class="brand-action-btn brand-action-primary">
                            Ver Detalles Completos →
                        </a>
                        ${(IS_ADMIN || (SESSION_USER_ID && SESSION_USER_ID == brandData.user_id)) ? `
                        <a href="/brand_edit?id=${brandId}" class="brand-action-btn" style="background:#0ea5e9;color:white;text-decoration:none;text-align:center;">
                            ✏️ Editar Marca
                        </a>
                        <label style="display:block;margin:4px 0 0;cursor:pointer;">
                            <input type="file" accept="image/jpeg,image/png,image/webp" style="display:none;"
                                onchange="uploadBrandLogoPopup(this,${brandId})">
                            <span class="brand-action-btn" style="background:#7c3aed;color:white;display:block;text-align:center;">
                                🖼️ Subir icono del mapa
                            </span>
                        </label>
                        <div id="brand-logo-msg-${brandId}" style="display:none;font-size:11px;padding:4px 6px;border-radius:4px;margin-top:3px;"></div>
                        ` : ''}
                        <button class="brand-action-btn brand-action-secondary" onclick="compartirMarca('${brandName}', '${brandId}')">
                            📤 Compartir Marca
                        </button>
                    </div>
                    ${buildWTPopupSection('marca', brandId, brandName)}
                </div>

                <button class="brand-popup-close" onclick="cerrarBrandPopup()">✕</button>
            `;

            container.innerHTML = html;
            initWTPanelsInPopup(container);

            // Guardar referencia global para controles
            window.brandGalleryData = {
                images: images,
                currentIndex: 0,
                brandId: brandId
            };
        });

    overlay.appendChild(container);
    document.body.appendChild(overlay);

    // Cerrar al hacer click en overlay
    overlay.onclick = (e) => {
        if (e.target === overlay) cerrarBrandPopup();
    };

    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') cerrarBrandPopup();
    }, { once: true });
}

function cerrarBrandPopup() {
    const overlay = document.querySelector('.brand-popup-overlay');
    if (overlay) {
        stopWtPollingInRoot(overlay);
        overlay.style.animation = 'fadeOutOverlay 0.3s ease-out forwards';
        setTimeout(() => overlay.remove(), 300);
    }
}

function cambiarGaleria(direcci) {
    if (!window.brandGalleryData) return;

    const data = window.brandGalleryData;
    data.currentIndex = (data.currentIndex + direcci + data.images.length) % data.images.length;
    irAImagen(data.currentIndex);
}

function irAImagen(index) {
    if (!window.brandGalleryData) return;

    const data = window.brandGalleryData;
    const images = data.images;

    if (index < 0 || index >= images.length) return;

    data.currentIndex = index;

    const mainGallery = document.getElementById('brand-gallery-main');
    if (mainGallery) {
        mainGallery.innerHTML = `<img src="/uploads/brands/${images[index].filename}" alt="Brand image" class="brand-gallery-image">`;
    }

    // Actualizar thumbnails
    document.querySelectorAll('.brand-gallery-thumb').forEach((thumb, idx) => {
        thumb.classList.toggle('active', idx === index);
    });
}

function compartirMarca(brandName, brandId) {
    const url = `${window.location.origin}/brand_detail?id=${brandId}`;
    const texto = `¡Mira esta marca: ${brandName} en Mapita! ${url}`;

    if (navigator.share) {
        navigator.share({
            title: brandName,
            text: texto,
            url: url
        }).catch(err => console.log('Error compartiendo:', err));
    } else {
        // Fallback: copiar al portapapeles
        navigator.clipboard.writeText(texto).then(() => {
            alert('¡Enlace copiado al portapapeles!');
        });
    }
}

// ── Lang picker toggle ───────────────────────────────────────────────────────
function toggleLangPicker() {
    const picker = document.getElementById('lang-picker');
    if (!picker) return;
    const isOpen = picker.style.display === 'block';
    picker.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) {
        // Resaltar el idioma activo
        document.querySelectorAll('#lang-btn-option').forEach(el => {
            el.style.fontWeight = (el.dataset.lang === MAPITA_UI_LANG) ? '700' : '400';
            el.style.background = (el.dataset.lang === MAPITA_UI_LANG) ? '#f0f4ff' : 'none';
        });
        setTimeout(() => {
            document.addEventListener('click', function _close(e) {
                const btn = document.getElementById('lang-globe-btn');
                if (!picker.contains(e.target) && e.target !== btn) {
                    picker.style.display = 'none';
                }
                document.removeEventListener('click', _close);
            });
        }, 50);
    }
}

// ─── CERCA: Inmuebles de inmobiliarias ───────────────────────────────────────
let _cercaActivo = false;
let _cercaLayer  = null;

function toggleCerca() {
    if (_cercaActivo) {
        desactivarCerca();
    } else {
        activarCerca();
    }
}

async function activarCerca() {
    const btn = document.getElementById('btn-cerca');
    if (btn) { btn.style.background = '#16a34a'; btn.style.color = 'white'; btn.textContent = '✅ CERCA (activo)'; }
    _cercaActivo = true;
    // Hide normal markers
    if (typeof markerCluster !== 'undefined' && markerCluster) {
        mapa.removeLayer(markerCluster);
    }
    // Load inmuebles
    try {
        const r = await fetch('/api/inmuebles.php?all=1');
        const d = await r.json();
        if (_cercaLayer) { mapa.removeLayer(_cercaLayer); _cercaLayer = null; }
        if (!d.success || !d.data || !d.data.length) {
            alert('No hay inmuebles publicados aún por inmobiliarias.');
            desactivarCerca();
            return;
        }
        _cercaLayer = L.layerGroup();
        d.data.forEach(function(inm) {
            const lat = parseFloat(inm.lat) || parseFloat(inm.inm_lat_fallback);
            const lng = parseFloat(inm.lng) || parseFloat(inm.inm_lng_fallback);
            if (lat == null || lng == null || isNaN(lat) || isNaN(lng)) return;

            const iconHtml = inm.inmobiliaria_icon
                ? '<img src="' + escapeHtml(inm.inmobiliaria_icon) + '" style="width:36px;height:36px;border-radius:50%;border:2px solid #16a34a;object-fit:cover;" />'
                : '<div style="background:#16a34a;color:white;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:18px;">🏘️</div>';
            const mk = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: '',
                    html: iconHtml,
                    iconSize: [36, 36],
                    iconAnchor: [18, 18],
                })
            });

            const op = inm.operacion === 'alquiler' ? '🔑 Alquiler' : '🏠 Venta';
            const precio = inm.precio ? ' — $' + Number(inm.precio).toLocaleString() : '';
            let popup = '<div style="min-width:200px;">';
            popup += '<div style="font-weight:700;font-size:.95em;margin-bottom:4px;">' + escapeHtml(inm.titulo || 'Inmueble') + '</div>';
            popup += '<div style="font-size:.82em;color:#16a34a;margin-bottom:4px;">' + op + precio + '</div>';
            popup += '<div style="font-size:.8em;color:#555;margin-bottom:3px;">🏢 ' + escapeHtml(inm.inmobiliaria_nombre || 'Inmobiliaria') + '</div>';
            if (inm.descripcion) popup += '<div style="font-size:.8em;color:#374151;margin-bottom:4px;">' + escapeHtml(inm.descripcion) + '</div>';
            if (inm.direccion)   popup += '<div style="font-size:.79em;color:#6b7280;">📍 ' + escapeHtml(inm.direccion) + '</div>';
            if (inm.contacto)    popup += '<div style="font-size:.79em;color:#1d4ed8;margin-top:4px;">📞 ' + escapeHtml(inm.contacto) + '</div>';
            popup += '</div>';
            mk.bindPopup(popup);
            _cercaLayer.addLayer(mk);
        });
        _cercaLayer.addTo(mapa);
    } catch (e) {
        alert('Error al cargar inmuebles.');
        desactivarCerca();
    }
}

function desactivarCerca() {
    _cercaActivo = false;
    const btn = document.getElementById('btn-cerca');
    if (btn) { btn.style.background = ''; btn.style.color = ''; btn.textContent = '🏘️ CERCA'; }
    if (_cercaLayer) { mapa.removeLayer(_cercaLayer); _cercaLayer = null; }
    // Restore normal markers
    if (typeof markerCluster !== 'undefined' && markerCluster && !mapa.hasLayer(markerCluster)) {
        mapa.addLayer(markerCluster);
    }
}

// ─── CONVOCAR: Obra de Arte ─────────────────────────────────────────────────
function abrirConvocar() {
    <?php if (empty($_SESSION['user_id'])): ?>
    alert('Debés iniciar sesión para usar esta función.');
    return;
    <?php endif; ?>
    const modal = document.getElementById('modal-convocar');
    if (!modal) return;
    /* Usar flex para centrar el diálogo dentro del overlay */
    modal.style.display = 'flex';
    cargarMisObras();
}

function cerrarConvocar() {
    const modal = document.getElementById('modal-convocar');
    if (modal) modal.style.display = 'none';
    /* Limpiar mensaje y select al cerrar */
    const msg = document.getElementById('conv-msg');
    if (msg) { msg.className = ''; msg.style.display = 'none'; msg.textContent = ''; }
}

async function cargarMisObras() {
    const sel    = document.getElementById('conv-obra-select');
    const notice = document.getElementById('conv-no-obra-notice');
    const msg    = document.getElementById('conv-msg');
    if (!sel) return;
    sel.innerHTML = '<option value="">Cargando...</option>';
    try {
        const r = await fetch('/api/convocatorias.php?action=mis_obras');
        const d = await r.json();
        if (!d.success || !d.data || !d.data.length) {
            sel.innerHTML = '<option value="">Sin obras de arte publicadas</option>';
            if (notice) notice.style.display = 'block';
            if (msg) { msg.className = 'conv-msg--err'; msg.textContent = 'No tenés negocios del tipo OBRA DE ARTE publicados.'; msg.style.display = 'block'; }
            return;
        }
        if (notice) notice.style.display = 'none';
        sel.innerHTML = '<option value="">Seleccioná una obra…</option>' +
            d.data.map(o => '<option value="' + o.id + '">' + escapeHtml(o.name) + '</option>').join('');
        if (msg) msg.style.display = 'none';
    } catch (e) {
        sel.innerHTML = '<option value="">Error al cargar</option>';
    }
}

async function enviarConvocatoria() {
    const bizId      = document.getElementById('conv-obra-select')?.value;
    const fechaInicio = document.getElementById('conv-fecha-inicio')?.value;
    const fechaFin    = document.getElementById('conv-fecha-fin')?.value;
    const msg        = document.getElementById('conv-msg');
    const btn        = document.getElementById('conv-btn-enviar');

    function showMsg(text, isErr) {
        if (!msg) return;
        msg.textContent = text;
        msg.className   = isErr ? 'conv-msg--err' : 'conv-msg--ok';
        msg.style.display = 'block';
    }

    if (!bizId)      { showMsg('Seleccioná una obra.', true); return; }
    if (!fechaInicio){ showMsg('Ingresá fecha de inicio.', true); return; }
    if (!fechaFin)   { showMsg('Ingresá fecha de fin.', true); return; }

    if (btn) { btn.disabled = true; btn.querySelector('svg') && (btn.querySelector('svg').style.display = 'none'); btn.childNodes.forEach(n => { if (n.nodeType === 3) n.textContent = '⏳ Enviando…'; }); }
    try {
        const r = await fetch('/api/convocatorias.php?action=convocar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ business_id: parseInt(bizId, 10), fecha_inicio: fechaInicio, fecha_fin: fechaFin })
        });
        const d = await r.json();
        showMsg(d.success ? ('✅ ' + (d.message || 'Convocatoria enviada')) : ('❌ ' + (d.message || 'Error')), !d.success);
        if (d.success) { setTimeout(cerrarConvocar, 3000); }
    } catch (e) {
        showMsg('Error de conexión.', true);
    } finally {
        if (btn) { btn.disabled = false; if (btn.querySelector('svg')) btn.querySelector('svg').style.display = ''; btn.childNodes.forEach(n => { if (n.nodeType === 3) n.textContent = ' Enviar convocatoria'; }); }
    }
}
</script>

<!-- Photo Gallery Modal -->
<div id="photo-gallery-modal" style="display:none;position:fixed;z-index:10000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.9);align-items:center;justify-content:center;flex-direction:column;">
    <div style="position:relative;max-width:800px;width:90%;max-height:85vh;">
        <img id="photo-gallery-img" src="" style="width:100%;height:auto;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.5);" alt="Galería de fotos">

        <button id="gallery-prev-btn" onclick="prevPhoto()" style="position:absolute;left:-50px;top:50%;transform:translateY(-50%);background:#667eea;color:white;border:none;width:40px;height:40px;border-radius:50%;font-size:20px;cursor:pointer;transition:all 0.2s;">❮</button>
        <button id="gallery-next-btn" onclick="nextPhoto()" style="position:absolute;right:-50px;top:50%;transform:translateY(-50%);background:#667eea;color:white;border:none;width:40px;height:40px;border-radius:50%;font-size:20px;cursor:pointer;transition:all 0.2s;">❯</button>

        <button onclick="closePhotoGallery()" style="position:absolute;top:-40px;right:0;background:white;color:#333;border:none;width:35px;height:35px;border-radius:50%;font-size:18px;cursor:pointer;transition:all 0.2s;">✕</button>

        <div id="photo-gallery-caption" style="color:white;text-align:center;margin-top:12px;font-size:14px;"></div>
    </div>

    <div style="color:#999;margin-top:15px;font-size:12px;text-align:center;">
        Usa ← → para navegar o Esc para cerrar
    </div>
</div>

<!-- ══ MÓDULO BUSCO EMPLEADOS/AS — Modal de postulación ═════════════════════ -->
<div id="job-modal-overlay" style="display:none;position:fixed;z-index:10100;left:0;top:0;width:100%;height:100%;
     background:rgba(0,0,0,0.6);align-items:center;justify-content:center;"
     onclick="if(event.target===this)cerrarOfertaTrabajo()">
    <div style="background:white;border-radius:16px;width:100%;max-width:520px;max-height:92vh;
                overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.35);margin:10px;"
         onclick="event.stopPropagation()">
        <!-- Header -->
        <div style="background:linear-gradient(135deg,#1B3B6F,#0d2247);padding:18px 22px;
                    display:flex;align-items:center;gap:12px;border-radius:16px 16px 0 0;">
            <span style="font-size:1.6em;">💼</span>
            <div style="flex:1;">
                <div id="job-modal-title" style="color:white;font-weight:800;font-size:1em;">Busco Empleados/as</div>
            </div>
            <button type="button" onclick="cerrarOfertaTrabajo()"
                    style="background:rgba(255,255,255,.15);border:none;color:white;border-radius:50%;
                           width:32px;height:32px;font-size:1.1em;cursor:pointer;line-height:1;">✕</button>
        </div>
        <!-- Body -->
        <div style="padding:22px;">
            <div id="job-offer-details" style="margin-bottom:18px;"></div>

            <div id="job-modal-msg" style="display:none;margin-bottom:14px;"></div>

            <!-- Login gate -->
            <div id="job-login-gate" style="display:none;text-align:center;padding:20px 10px;">
                <div style="font-size:2em;margin-bottom:8px;">🔒</div>
                <p style="font-weight:700;color:#1B3B6F;margin-bottom:8px;">Iniciá sesión para postularte</p>
                <p style="font-size:.86em;color:#6b7280;margin-bottom:16px;">Las postulaciones solo están disponibles para usuarios registrados.</p>
                <a href="/login" style="display:inline-block;padding:10px 22px;background:#1B3B6F;color:white;
                   border-radius:8px;font-weight:700;text-decoration:none;font-size:.9em;">Iniciar sesión</a>
                <a href="/register" style="display:inline-block;margin-left:10px;padding:10px 22px;
                   background:#f3f4f6;color:#374151;border-radius:8px;font-weight:700;text-decoration:none;font-size:.9em;">Registrarse</a>
            </div>

            <!-- Formulario interno -->
            <div id="job-app-form-wrap" style="display:none;">
                <div style="border-top:1px solid #e5e7eb;padding-top:16px;margin-bottom:14px;">
                    <p style="font-size:.84em;font-weight:700;color:#374151;margin-bottom:12px;">📝 Completá tu postulación</p>
                    <div style="display:flex;flex-direction:column;gap:11px;">
                        <div>
                            <label style="font-size:.8em;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Nombre completo *</label>
                            <input id="job-app-name" type="text" maxlength="255"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #d1d5db;border-radius:8px;font-size:.875em;"
                                   placeholder="Tu nombre">
                        </div>
                        <div>
                            <label style="font-size:.8em;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Email *</label>
                            <input id="job-app-email" type="email" maxlength="255"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #d1d5db;border-radius:8px;font-size:.875em;"
                                   placeholder="tucorreo@ejemplo.com">
                        </div>
                        <div>
                            <label style="font-size:.8em;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Teléfono <span style="color:#9ca3af;font-weight:400;">(opcional)</span></label>
                            <input id="job-app-phone" type="tel" maxlength="50"
                                   style="width:100%;padding:9px 12px;border:1.5px solid #d1d5db;border-radius:8px;font-size:.875em;"
                                   placeholder="+54 9 ...">
                        </div>
                        <div>
                            <label style="font-size:.8em;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Mensaje <span style="color:#9ca3af;font-weight:400;">(opcional)</span></label>
                            <textarea id="job-app-message" rows="3" maxlength="2000"
                                      style="width:100%;padding:9px 12px;border:1.5px solid #d1d5db;border-radius:8px;font-size:.875em;resize:vertical;"
                                      placeholder="Contale algo al empleador…"></textarea>
                        </div>
                        <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;">
                            <input id="job-app-consent" type="checkbox" style="margin-top:3px;flex-shrink:0;">
                            <span style="font-size:.78em;color:#6b7280;line-height:1.5;">
                                Autorizo el uso de mis datos (nombre, email y teléfono) para ser contactado/a por el negocio respecto a esta oferta. <strong>*</strong>
                            </span>
                        </label>
                    </div>
                </div>
                <button id="job-app-btn" type="button" onclick="enviarPostulacion()"
                        style="width:100%;padding:12px;background:#1B3B6F;color:white;border:none;
                               border-radius:10px;font-size:.95em;font-weight:800;cursor:pointer;">
                    📤 Postularme
                </button>
            </div>

            <!-- Éxito -->
            <div id="job-app-success" style="display:none;text-align:center;padding:20px 10px;">
                <div style="font-size:2.5em;margin-bottom:8px;">🎉</div>
                <p style="font-weight:800;color:#065f46;margin-bottom:8px;">¡Postulación enviada!</p>
                <p style="font-size:.86em;color:#6b7280;">El negocio se comunicará con vos a la brevedad. ¡Buena suerte!</p>
                <button onclick="cerrarOfertaTrabajo()"
                        style="margin-top:16px;padding:10px 22px;background:#1B3B6F;color:white;
                               border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9em;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ══ FIN MÓDULO BUSCO EMPLEADOS/AS ══════════════════════════════════════════ -->

<!-- ══ MÓDULO DISPONIBLES — Modal del usuario solicitante ══════════════════ --><div id="disp-overlay" class="disp-overlay" style="display:none;" onclick="if(event.target===this)cerrarDisponibles()">
    <div class="disp-panel" onclick="event.stopPropagation()">
        <div class="disp-header">
            <span style="font-size:1.3em;">📦</span>
            <h2 id="disp-panel-title">Disponibles</h2>
            <span class="disp-badge" id="disp-panel-badge">cargando…</span>
            <button class="disp-close" onclick="cerrarDisponibles()">✕</button>
        </div>
        <div class="disp-body">
            <!-- Email del solicitante -->
            <div class="disp-email-row">
                <label for="disp-email">📧 Tu email (obligatorio):</label>
                <input type="email" id="disp-email" placeholder="tucorreo@ejemplo.com" maxlength="255">
            </div>

            <!-- Leyenda -->
            <div class="disp-legend">
                <strong>Leyenda:</strong>
                <span><span class="disp-dot-sel"></span> Columna amarilla = selección del usuario (solo completable por vos)</span>
                <span>✅ Seleccionado = <strong>SÍ</strong></span>
                <span>Dejado vacío = <strong>NO</strong></span>
            </div>

            <!-- Mensaje de estado -->
            <div id="disp-msg" class="disp-msg"></div>

            <!-- Tabla de ítems -->
            <div class="disp-table-wrap">
                <table class="disp-table">
                    <thead>
                        <tr>
                            <th>Precio</th>
                            <th>Cant.</th>
                            <th>Tipo de bien</th>
                            <th>Desde</th>
                            <th>Hasta</th>
                            <th>Horario</th>
                            <th>Servicio</th>
                            <th class="col-sel">✅ Seleccionar</th>
                        </tr>
                    </thead>
                    <tbody id="disp-tbody">
                        <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:20px;">Cargando ítems…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="disp-footer">
            <button type="button" class="disp-btn disp-btn-secondary" onclick="cerrarDisponibles()">✖ Desistir</button>
            <button type="button" class="disp-btn disp-btn-amber" id="disp-btn-orden" onclick="enviarOrden()">📤 Orden de solicitud</button>
        </div>
    </div>
</div>

<script>
// ── Módulo Disponibles ──────────────────────────────────────────────────────
let _dispBizId   = 0;
let _dispItems   = [];
let _dispSolId   = 0;

function abrirDisponibles(bizId, bizName) {
    _dispBizId  = bizId;
    _dispItems  = [];
    _dispSolId  = 0;

    document.getElementById('disp-panel-title').textContent = bizName + ' — Disponibles';
    document.getElementById('disp-panel-badge').textContent = 'cargando…';
    document.getElementById('disp-tbody').innerHTML =
        '<tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:20px;">Cargando…</td></tr>';
    dispMsg('', '');
    document.getElementById('disp-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    fetch('/api/disponibles.php?business_id=' + bizId)
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            dispMsg(d.message || 'El módulo de disponibles no está activo en este negocio.', 'err');
            document.getElementById('disp-tbody').innerHTML =
                '<tr><td colspan="8" style="text-align:center;color:#ef4444;padding:20px;">' + escapeHtml(d.message || 'No disponible') + '</td></tr>';
            document.getElementById('disp-btn-orden').disabled = true;
            document.getElementById('disp-panel-badge').textContent = 'inactivo';
            return;
        }
        _dispItems = d.data.items || [];
        document.getElementById('disp-panel-badge').textContent =
            _dispItems.length + ' ítem' + (_dispItems.length !== 1 ? 's' : '');
        document.getElementById('disp-btn-orden').disabled = false;

        if (!_dispItems.length) {
            document.getElementById('disp-tbody').innerHTML =
                '<tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:20px;">No hay ítems publicados aún.</td></tr>';
            return;
        }

        const tbody = document.getElementById('disp-tbody');
        tbody.innerHTML = '';
        _dispItems.forEach(item => {
            const tr = document.createElement('tr');

            const precioStr = item.precio_a_definir ? '<em style="color:#9ca3af">a definir</em>'
                : (item.precio !== null ? '$' + parseFloat(item.precio).toLocaleString('es-AR', {minimumFractionDigits:2}) : '—');

            const cantStr = item.cantidad !== null ? item.cantidad : '—';

            const fechaStr = (item.disponible_desde || item.disponible_hasta)
                ? (item.disponible_desde || '') + (item.disponible_hasta ? ' al ' + item.disponible_hasta : '')
                : '—';

            const horStr = (item.horario_inicio || item.horario_fin)
                ? (item.horario_inicio ? item.horario_inicio.substring(0,5) : '') + (item.horario_fin ? '–' + item.horario_fin.substring(0,5) : '')
                : '—';

            tr.innerHTML =
                '<td>' + precioStr + '</td>' +
                '<td>' + escapeHtml(String(cantStr)) + '</td>' +
                '<td>' + escapeHtml(item.tipo_bien || '—') + '</td>' +
                '<td>' + escapeHtml(item.disponible_desde || '—') + '</td>' +
                '<td>' + escapeHtml(item.disponible_hasta || '—') + '</td>' +
                '<td>' + escapeHtml(horStr) + '</td>' +
                '<td>' + escapeHtml(item.servicio || '—') + '</td>' +
                '<td class="col-sel"><input type="checkbox" class="disp-checkbox-sel" data-item-id="' + item.id + '"></td>';
            tbody.appendChild(tr);
        });
    })
    .catch(() => {
        dispMsg('Error de conexión. Intentá de nuevo.', 'err');
    });
}

function cerrarDisponibles() {
    document.getElementById('disp-overlay').style.display = 'none';
    document.body.style.overflow = '';
    dispMsg('', '');
}

function enviarOrden() {
    const email = document.getElementById('disp-email').value.trim();
    if (!email) { dispMsg('Ingresá tu email para continuar.', 'err'); return; }
    // RFC-compatible email check via native constraint
    const emailInput = document.createElement('input');
    emailInput.type = 'email';
    emailInput.value = email;
    if (!emailInput.checkValidity()) { dispMsg('El email no es válido.', 'err'); return; }

    const checkboxes = document.querySelectorAll('#disp-tbody .disp-checkbox-sel:checked');
    if (checkboxes.length === 0) { dispMsg('Seleccioná al menos un ítem.', 'err'); return; }

    const selIds = Array.from(checkboxes).map(cb => parseInt(cb.dataset.itemId));

    const btn = document.getElementById('disp-btn-orden');
    btn.disabled = true;
    btn.textContent = '⏳ Enviando…';

    fetch('/api/disponibles_solicitudes.php?action=crear', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            business_id: _dispBizId,
            email: email,
            items_seleccionados: selIds
        })
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        btn.textContent = '📤 Orden de solicitud';
        if (d.success) {
            _dispSolId = d.data && d.data.solicitud_id ? d.data.solicitud_id : 0;
            dispMsg('✅ ' + d.message, 'ok');
            // Deshabilitar edición después de enviar
            document.querySelectorAll('#disp-tbody .disp-checkbox-sel').forEach(cb => cb.disabled = true);
            document.getElementById('disp-email').disabled = true;
            btn.disabled = true;
            // Cerrar automáticamente tras 3s
            setTimeout(cerrarDisponibles, 3000);
        } else {
            dispMsg('❌ ' + (d.message || 'Error al enviar'), 'err');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = '📤 Orden de solicitud';
        dispMsg('Error de conexión. Intentá de nuevo.', 'err');
    });
}

function dispMsg(text, type) {
    const el = document.getElementById('disp-msg');
    if (!el) return;
    if (!text) { el.className = 'disp-msg'; el.textContent = ''; return; }
    el.className = 'disp-msg ' + (type === 'ok' ? 'ok' : (type === 'err' ? 'err' : ''));
    el.textContent = text;
}

// ── Módulo Busco Empleados/as ─────────────────────────────────────────────────
let _jobBizId   = 0;
let _jobBizName = '';

function abrirOfertaTrabajo(bizId, bizName) {
    _jobBizId   = bizId;
    _jobBizName = bizName;

    document.getElementById('job-modal-title').textContent = bizName + ' — Busco Empleados/as';
    document.getElementById('job-offer-details').innerHTML = '<em style="color:#9ca3af;">Cargando oferta…</em>';
    document.getElementById('job-app-form-wrap').style.display = 'none';
    document.getElementById('job-login-gate').style.display = 'none';
    document.getElementById('job-app-success').style.display = 'none';
    jobModalMsg('', '');

    document.getElementById('job-modal-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    fetch('/api/job_offers.php?business_id=' + bizId)
    .then(r => r.json())
    .then(d => {
        if (!d.success || !d.data) {
            document.getElementById('job-offer-details').innerHTML =
                '<p style="color:#ef4444;">No hay oferta activa en este negocio.</p>';
            return;
        }
        const data = d.data;
        let html = '';
        if (data.job_offer_position) {
            html += '<div style="font-size:1.05em;font-weight:800;color:#1B3B6F;margin-bottom:8px;">🔍 ' + escapeHtml(data.job_offer_position) + '</div>';
        }
        if (data.job_offer_description) {
            html += '<div style="font-size:.88em;color:#374151;line-height:1.6;margin-bottom:12px;white-space:pre-line;">' + escapeHtml(data.job_offer_description) + '</div>';
        }
        if (data.job_offer_url) {
            let safeUrl = null;
            try { const u = new URL(data.job_offer_url); if (u.protocol === 'https:' || u.protocol === 'http:') safeUrl = u.href; } catch(_) {}
            if (safeUrl) {
                html += '<div style="margin-bottom:12px;">'
                     + '<a href="' + escapeHtml(safeUrl) + '" target="_blank" rel="noopener" '
                     + 'style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#1B3B6F;color:white;border-radius:8px;font-size:.86em;font-weight:700;text-decoration:none;">'
                     + '🔗 Postularse por link externo</a>'
                     + '</div>';
            }
        }
        document.getElementById('job-offer-details').innerHTML = html || '<em style="color:#9ca3af;">Sin descripción.</em>';

        // Login gate o formulario
        if (SESSION_USER_ID > 0) {
            // Prellenar formulario con datos del perfil
            const nameEl  = document.getElementById('job-app-name');
            const emailEl = document.getElementById('job-app-email');
            const phoneEl = document.getElementById('job-app-phone');
            if (nameEl  && !nameEl.value)  nameEl.value  = SESSION_USER_NAME  || '';
            if (emailEl && !emailEl.value) emailEl.value = SESSION_USER_EMAIL || '';
            if (phoneEl && !phoneEl.value) phoneEl.value = SESSION_USER_PHONE || '';
            document.getElementById('job-app-form-wrap').style.display = '';
        } else {
            document.getElementById('job-login-gate').style.display = '';
        }
    })
    .catch(() => {
        document.getElementById('job-offer-details').innerHTML =
            '<p style="color:#ef4444;">Error de conexión. Intentá de nuevo.</p>';
    });
}

function cerrarOfertaTrabajo() {
    document.getElementById('job-modal-overlay').style.display = 'none';
    document.body.style.overflow = '';
    jobModalMsg('', '');
}

function jobModalMsg(text, type) {
    const el = document.getElementById('job-modal-msg');
    if (!el) return;
    if (!text) { el.style.display = 'none'; return; }
    el.style.display    = 'block';
    el.style.padding    = '8px 12px';
    el.style.borderRadius = '8px';
    el.style.fontWeight = '600';
    el.style.fontSize   = '.875em';
    el.style.color      = type === 'ok' ? '#065f46' : '#991b1b';
    el.style.background = type === 'ok' ? '#d1fae5' : '#fee2e2';
    el.textContent = text;
}

function enviarPostulacion() {
    const name    = (document.getElementById('job-app-name')?.value    || '').trim();
    const email   = (document.getElementById('job-app-email')?.value   || '').trim();
    const phone   = (document.getElementById('job-app-phone')?.value   || '').trim();
    const message = (document.getElementById('job-app-message')?.value || '').trim();
    const consent = document.getElementById('job-app-consent')?.checked;
    const btn     = document.getElementById('job-app-btn');

    if (!name)    { jobModalMsg('El nombre es obligatorio.', 'err'); return; }
    if (!email)   { jobModalMsg('El email es obligatorio.', 'err'); return; }
    const emailCheck = document.createElement('input');
    emailCheck.type  = 'email';
    emailCheck.value = email;
    if (!emailCheck.checkValidity()) { jobModalMsg('El email no es válido.', 'err'); return; }
    if (!consent) { jobModalMsg('Debés aceptar el consentimiento para postularte.', 'err'); return; }

    if (btn) { btn.disabled = true; btn.textContent = '⏳ Enviando…'; }

    fetch('/api/job_applications.php?action=create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            business_id:     _jobBizId,
            applicant_name:  name,
            applicant_email: email,
            applicant_phone: phone,
            message:         message,
            consent:         true,
        })
    })
    .then(r => r.json())
    .then(d => {
        if (btn) { btn.disabled = false; btn.textContent = '📤 Postularme'; }
        if (d.success) {
            document.getElementById('job-app-form-wrap').style.display = 'none';
            document.getElementById('job-app-success').style.display   = '';
            jobModalMsg('', '');
        } else if (d.message && d.message.includes('ya te postulaste')) {
            jobModalMsg('ℹ️ ' + d.message, 'err');
        } else {
            jobModalMsg('❌ ' + (d.message || 'Error al enviar'), 'err');
        }
    })
    .catch(() => {
        if (btn) { btn.disabled = false; btn.textContent = '📤 Postularme'; }
        jobModalMsg('Error de conexión. Intentá de nuevo.', 'err');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const watermark = document.getElementById('mapita-map-watermark');
    const panel = document.getElementById('mapita-home-panel');
    const closeBtn = document.getElementById('mapita-home-panel-close');
    if (!watermark || !panel) return;
    const helpModal = document.getElementById('quickstart-help-modal');
    const helpModalTitle = document.getElementById('quickstart-help-modal-title');
    const helpModalText = document.getElementById('quickstart-help-modal-text');
    const helpModalCloseBtn = document.getElementById('quickstart-help-modal-close');
    const helpButtons = panel ? panel.querySelectorAll('.quickstart-help-btn') : [];
    const hasQuickstartHelpModal = helpModal && helpModalTitle && helpModalText;
    let quickstartHelpTriggerBtn = null;
    const quickstartHelpContent = {
        explorar: {
            title: 'Explorar el mapa',
            text: 'Podés acercar/alejar, mover el mapa y tocar cada marcador para ver datos clave de negocios y marcas en tu zona.'
        },
        contacto: {
            title: 'Contactar titulares',
            text: 'Al abrir una ficha vas a encontrar canales de contacto para consultar disponibilidad, hacer pedidos o iniciar una conversación.'
        },
        novedades: {
            title: 'Seguir novedades',
            text: 'Revisá publicaciones y actualizaciones para enterarte de ofertas, lanzamientos y contenido reciente de cada perfil.'
        },
        registrar: {
            title: 'Registrar tu perfil',
            text: 'Si todavía no figurás en el mapa, completá el alta para ubicar tu negocio o marca y mejorar tu visibilidad.'
        },
        wt: {
            title: 'Canales WT selectivos',
            text: 'Usá WT para crear comunicación segmentada y mantener conversaciones más ordenadas según intereses y necesidades.'
        },
        franquicias: {
            title: 'Franquicias y oportunidades',
            text: 'Podés mostrar franquicias y propuestas de expansión para conectar con personas interesadas en nuevas oportunidades.'
        },
        empleados: {
            title: '💼 Busco Empleados/as',
            text: 'Los titulares de negocio pueden publicar una oferta laboral activa. Los usuarios registrados en Mapita pueden postularse con un formulario interno o por link externo. Para activar la funcionalidad, accedé a "Editar negocio" → sección "Busco Empleados/as". Completá el puesto buscado, la descripción y (opcionalmente) un link externo, guardá y activá la oferta. Para ver y gestionar las postulaciones, usá el botón "Panel de Trabajo" desde "Mis Negocios". Importante: para postularse es obligatorio tener una cuenta registrada en Mapita.'
        }
    };

    function closeHelpModal() {
        if (!hasQuickstartHelpModal) return;
        helpModal.classList.remove('is-open');
        helpModal.setAttribute('aria-hidden', 'true');
        if (quickstartHelpTriggerBtn) {
            quickstartHelpTriggerBtn.focus();
        }
        quickstartHelpTriggerBtn = null;
    }

    function openHelpModal(key, triggerBtn) {
        if (!hasQuickstartHelpModal) return;
        if (!Object.prototype.hasOwnProperty.call(quickstartHelpContent, key)) return;
        const content = quickstartHelpContent[key];
        quickstartHelpTriggerBtn = triggerBtn || null;
        helpModalTitle.textContent = content.title;
        helpModalText.textContent = content.text;
        helpModal.classList.add('is-open');
        helpModal.setAttribute('aria-hidden', 'false');
        if (helpModalCloseBtn) {
            helpModalCloseBtn.focus();
        }
    }

    function setOpen(open) {
        panel.classList.toggle('is-open', !!open);
        watermark.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (!open) closeHelpModal();
    }

    watermark.addEventListener('click', function(e) {
        e.stopPropagation();
        setOpen(!panel.classList.contains('is-open'));
    });
    watermark.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            setOpen(!panel.classList.contains('is-open'));
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            setOpen(false);
        });
    }

    document.addEventListener('click', function(e) {
        if (!panel.classList.contains('is-open')) return;
        if (helpModal && helpModal.classList.contains('is-open')) return;
        if (panel.contains(e.target) || watermark.contains(e.target)) return;
        setOpen(false);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab' && helpModal && helpModal.classList.contains('is-open')) {
            const focusables = helpModal.querySelectorAll('button:not(:disabled), [href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex="0"]');
            if (!focusables.length) {
                e.preventDefault();
                return;
            }
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
                return;
            }
            if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
                return;
            }
        }
        if (e.key === 'Escape' && helpModal && helpModal.classList.contains('is-open')) {
            closeHelpModal();
            return;
        }
        if (e.key === 'Escape' && panel.classList.contains('is-open')) {
            setOpen(false);
        }
    });

    if (hasQuickstartHelpModal) {
        helpButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                const key = btn.getAttribute('data-help-key');
                if (!key) return;
                e.stopPropagation();
                openHelpModal(key, btn);
            });
        });
        if (helpModalCloseBtn) {
            helpModalCloseBtn.addEventListener('click', function() {
                closeHelpModal();
            });
        }
        if (helpModal) {
            helpModal.addEventListener('click', function(e) {
                e.stopPropagation();
                if (e.target === helpModal) closeHelpModal();
            });
        }
    } else {
        helpButtons.forEach(function(btn) {
            btn.hidden = true;
        });
    }
});
</script>
<!-- ══ FIN MÓDULO DISPONIBLES ═══════════════════════════════════════════════ -->
<!-- ── MAPITA map watermark ── -->
<div id="mapita-map-watermark" style="
    position:fixed;bottom:28px;left:16px;
    z-index:999;
    display:flex;align-items:center;gap:5px;
    background:rgba(255,255,255,0.88);
    backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
    border-radius:20px;padding:5px 11px 5px 8px;
    box-shadow:0 2px 10px rgba(0,0,0,0.12);
    color:#1B3B6F;"
    role="button"
    tabindex="0"
    aria-controls="mapita-home-panel"
    aria-expanded="false">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" width="18" height="18"
         style="border-radius:4px;flex-shrink:0;" role="img" aria-label="Mapita">
        <defs>
            <linearGradient id="wmBg" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#1B3B6F"/>
                <stop offset="100%" stop-color="#2E5FA3"/>
            </linearGradient>
            <linearGradient id="wmPin" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#E8C547"/>
                <stop offset="100%" stop-color="#D4AF37"/>
            </linearGradient>
        </defs>
        <rect width="40" height="40" rx="9" ry="9" fill="url(#wmBg)"/>
        <ellipse cx="20" cy="16.5" rx="8" ry="8" fill="url(#wmPin)"/>
        <path d="M16.2 22 Q20 31 23.8 22" fill="url(#wmPin)"/>
        <circle cx="20" cy="16.5" r="3.2" fill="#1B3B6F"/>
    </svg>
    <span class="mapita-wordmark">INICIO</span>
</div>

<div id="mapita-home-panel" role="dialog" aria-modal="false" aria-label="Panel de Inicio">
    <div class="mapita-home-panel__header">
        <div>
            <span class="sidebar-card-label">Guía inicial</span>
            <h3 class="quickstart-panel__title" style="margin-bottom:0;">🧭 Panel de Inicio</h3>
        </div>
        <button id="mapita-home-panel-close" class="mapita-home-panel__close" type="button" aria-label="Cerrar panel de inicio">✕</button>
    </div>
    <p class="quickstart-panel__intro">Este panel te orienta rápidamente sobre lo básico del sistema y cómo empezar.</p>
    <ul class="quickstart-panel__list">
        <li><span>Ver y elegir negocios y marcas en el mapa.</span><button type="button" class="quickstart-help-btn" data-help-key="explorar" aria-label="Ayuda sobre explorar el mapa">?</button></li>
        <li><span>Contactarte con sus titulares y hacer pedidos.</span><button type="button" class="quickstart-help-btn" data-help-key="contacto" aria-label="Ayuda sobre contacto con titulares">?</button></li>
        <li><span>Ver novedades, ofertas y contenidos recientes.</span><button type="button" class="quickstart-help-btn" data-help-key="novedades" aria-label="Ayuda sobre novedades y ofertas">?</button></li>
        <li><span>Registrarte para ubicar tu negocio y marca en el mapa.</span><button type="button" class="quickstart-help-btn" data-help-key="registrar" aria-label="Ayuda sobre registro en el mapa">?</button></li>
        <li><span>Crear canales de comunicación selectivos (WT).</span><button type="button" class="quickstart-help-btn" data-help-key="wt" aria-label="Ayuda sobre canales WT selectivos">?</button></li>
        <li><span>Mostrar franquicias y generar oportunidades para todos.</span><button type="button" class="quickstart-help-btn" data-help-key="franquicias" aria-label="Ayuda sobre franquicias y oportunidades">?</button></li>
        <li><span>Publicar y gestionar ofertas laborales (Busco Empleados/as).</span><button type="button" class="quickstart-help-btn" data-help-key="empleados" aria-label="Ayuda sobre Busco Empleados/as">?</button></li>
    </ul>
</div>
<div id="quickstart-help-modal" class="quickstart-help-modal" role="dialog" aria-modal="true" aria-labelledby="quickstart-help-modal-title" aria-hidden="true">
    <div class="quickstart-help-modal__dialog" role="document">
        <button id="quickstart-help-modal-close" class="quickstart-help-modal__close" type="button" aria-label="Cerrar ayuda">✕</button>
        <h4 id="quickstart-help-modal-title">Ayuda rápida</h4>
        <p id="quickstart-help-modal-text"></p>
    </div>
</div>

<!-- ══ MÓDULO CONSULTAS MASIVAS ════════════════════════════════════════════ -->
<script>
/* SESSION_USER_ID is already declared as const above; expose for consultas-panel.js */
window.SESSION_USER_ID = window.SESSION_USER_ID !== undefined ? window.SESSION_USER_ID : <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
/* BUSINESS_TYPE_LABELS is already declared as const above; expose for consultas-panel.js */
window.BUSINESS_TYPE_LABELS = window.BUSINESS_TYPE_LABELS || {
    'restaurante':'Restaurante','cafeteria':'Cafetería','bar':'Bar / Pub','panaderia':'Panadería',
    'heladeria':'Heladería','pizzeria':'Pizzería','supermercado':'Supermercado','comercio':'Tienda / Local',
    'autos_venta':'Autos a la venta','motos_venta':'Motos a la venta','indumentaria':'Indumentaria',
    'verduleria':'Verdulería / Frutería','carniceria':'Carnicería','pastas':'Fábrica de Pastas',
    'ferreteria':'Ferretería','electronica':'Tecnología','muebleria':'Mueblería','floristeria':'Floristería',
    'libreria':'Librería','productora_audiovisual':'Productora audiovisual','escuela_musicos':'Escuela de músicos',
    'taller_artes':'Taller de artes','biodecodificacion':'Biodecodificación','libreria_cristiana':'Librería cristiana',
    'farmacia':'Farmacia','hospital':'Clínica / Hospital','medico_pediatra':'Médico Pediatra',
    'medico_traumatologo':'Médico Traumatólogo','laboratorio':'Laboratorio','odontologia':'Odontología',
    'psicologo':'Psicología','psicopedagogo':'Psicopedagogía','fonoaudiologo':'Fonoaudiología',
    'grafologo':'Grafología','enfermeria':'Enfermería','asistencia_ancianos':'Asistencia a Ancianos',
    'veterinaria':'Veterinaria','optica':'Óptica','salon_belleza':'Peluquería / Salón','barberia':'Barbería',
    'spa':'Spa / Estética','gimnasio':'Gimnasio','danza':'Danza / Ballet','banco':'Banco / Financiera',
    'inmobiliaria':'Inmobiliaria','seguros':'Seguros','abogado':'Estudio Jurídico','contador':'Contaduría',
    'arquitectura':'Arquitectura','ingenieria':'Ingeniería','ingenieria_civil':'Ingeniería Civil',
    'electricista':'Electricista','gasista':'Gasista matriculado','gas_en_garrafa':'Gas en garrafa',
    'seguridad':'Seguridad','grafica':'Gráfica','astrologo':'Astrólogo','zapatero':'Zapatero',
    'videojuegos':'Videojuegos','maestro_particular':'Maestro particular',
    'alquiler_mobiliario_fiestas':'Alquiler de mobiliario para fiestas','propalacion_musica':'Propalación (música)',
    'animacion_fiestas':'Animación de fiestas','taller':'Taller Mecánico','herreria':'Herrería',
    'carpinteria':'Carpintería','modista':'Modista / Costura','construccion':'Construcción',
    'centro_vecinal':'Centro Vecinal / ONG','remate':'Remates / Subastas','academia':'Academia / Instituto',
    'idiomas':'Instituto de Idiomas','escuela':'Escuela / Jardín','hotel':'Hotel / Alojamiento',
    'turismo':'Turismo / Agencia','cine':'Cine / Teatro / Arte','transporte':'Transporte',
    'transportista':'Transportista','logistica':'Logística','flota':'Flota',
    'otros':'Otro tipo'
};
</script>
<script src="/consultas-panel.js"></script>
<!-- ══ FIN MÓDULO CONSULTAS MASIVAS ════════════════════════════════════════ -->

</body>
</html>
