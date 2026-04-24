<?php
/**
 * views/sites/_layout.php
 * Layout compartido para las páginas del Módulo Avanzado de Mapita.
 *
 * Uso:
 *   $pageTitle      = 'Título de la página';
 *   $pageIcon       = '⚖️';
 *   $activeSection  = 'juridico';   // slug de la sección activa
 *   require __DIR__ . '/_layout.php';
 *   ... contenido HTML ...
 *   require __DIR__ . '/_layout_footer.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/helpers.php';
setSecurityHeaders();

$pageTitle     = $pageTitle     ?? 'Módulo Avanzado — Mapita';
$pageIcon      = $pageIcon      ?? '🚀';
$activeSection = $activeSection ?? '';

$navItems = [
    ['slug' => 'avanzado',        'href' => '/avanzado',        'label' => 'Avanzado',         'icon' => '🚀'],
    ['slug' => 'juridico',        'href' => '/juridico',        'label' => 'Jurídico',          'icon' => '⚖️'],
    ['slug' => 'fiscal',          'href' => '/fiscal',          'label' => 'Fiscal',            'icon' => '📊'],
    ['slug' => 'inversion',       'href' => '/inversion',       'label' => 'Inversión',         'icon' => '💰'],
    ['slug' => 'compliance',      'href' => '/compliance',      'label' => 'Compliance',        'icon' => '🛡️'],
    ['slug' => 'marca-expansion', 'href' => '/marca-expansion', 'label' => 'Expansión de Marca','icon' => '🏷️'],
    ['slug' => 'contacto',        'href' => '/contacto',        'label' => 'Contacto',          'icon' => '📩'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageIcon . ' ' . $pageTitle) ?> — Mapita</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f7;
            color: #1a202c;
            min-height: 100vh;
        }

        /* ── HEADER ──────────────────────────────────────────────────────── */
        .adv-header {
            background: linear-gradient(135deg, #1B3B6F 0%, #0d2247 100%);
            color: white;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            height: 60px;
            box-shadow: 0 4px 20px rgba(0,0,0,.28);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .adv-header-logo {
            font-size: 1.25em;
            font-weight: 800;
            letter-spacing: -0.5px;
            text-decoration: none;
            color: white;
            white-space: nowrap;
        }
        .adv-header-logo span { opacity: .6; font-weight: 400; font-size: .7em; margin-left: 6px; }
        .adv-header-nav {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }
        .adv-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 13px;
            border-radius: 8px;
            font-size: .82em;
            font-weight: 600;
            text-decoration: none;
            color: rgba(255,255,255,.8);
            border: 1.5px solid transparent;
            transition: all .2s;
            white-space: nowrap;
        }
        .adv-nav-link:hover { background: rgba(255,255,255,.12); color: white; }
        .adv-nav-link.active {
            background: rgba(255,255,255,.18);
            border-color: rgba(255,255,255,.35);
            color: white;
        }
        .adv-nav-back {
            color: rgba(255,255,255,.7);
            font-size: .82em;
            font-weight: 600;
            text-decoration: none;
            padding: 7px 13px;
            border: 1.5px solid rgba(255,255,255,.25);
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all .2s;
        }
        .adv-nav-back:hover { background: rgba(255,255,255,.1); color: white; }

        /* ── MOBILE NAV ──────────────────────────────────────────────────── */
        .adv-mobile-nav {
            display: none;
            background: #0d2247;
            padding: 8px 12px;
            overflow-x: auto;
            gap: 6px;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .adv-mobile-nav a {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: .8em;
            font-weight: 600;
            text-decoration: none;
            color: rgba(255,255,255,.8);
            background: rgba(255,255,255,.08);
            white-space: nowrap;
            border: 1px solid transparent;
            transition: all .2s;
        }
        .adv-mobile-nav a.active { background: rgba(255,255,255,.2); border-color: rgba(255,255,255,.3); color: white; }

        /* ── HERO ────────────────────────────────────────────────────────── */
        .adv-hero {
            background: linear-gradient(135deg, #1B3B6F 0%, #163260 60%, #0d2247 100%);
            color: white;
            padding: 48px 24px 44px;
            text-align: center;
        }
        .adv-hero-icon { font-size: 3rem; margin-bottom: 12px; display: block; }
        .adv-hero h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); font-weight: 800; margin-bottom: 10px; }
        .adv-hero p  { font-size: 1.05em; opacity: .85; max-width: 640px; margin: 0 auto; line-height: 1.6; }

        /* ── MAIN CONTENT ────────────────────────────────────────────────── */
        .adv-main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 36px 20px 72px;
        }

        /* ── CARDS ───────────────────────────────────────────────────────── */
        .adv-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.07);
            padding: 28px 30px;
            margin-bottom: 24px;
            border-top: 4px solid #1B3B6F;
        }
        .adv-card-title {
            font-size: 1.15em;
            font-weight: 700;
            color: #1B3B6F;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .adv-card p { color: #4a5568; line-height: 1.7; margin-bottom: 10px; font-size: .97em; }
        .adv-card ul, .adv-card ol {
            padding-left: 20px;
            margin: 8px 0 12px;
            color: #4a5568;
            font-size: .95em;
            line-height: 1.8;
        }

        /* ── GRID ────────────────────────────────────────────────────────── */
        .adv-grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .adv-grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px; }

        .adv-feature-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
            padding: 22px 20px;
            border-top: 3px solid #e5e7eb;
            transition: box-shadow .2s, transform .15s;
        }
        .adv-feature-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,.12); transform: translateY(-2px); }
        .adv-feature-card-icon { font-size: 1.8rem; margin-bottom: 10px; }
        .adv-feature-card h3 { font-size: 1em; font-weight: 700; color: #1B3B6F; margin-bottom: 6px; }
        .adv-feature-card p  { font-size: .88em; color: #64748b; line-height: 1.6; }

        /* ── TOPIC CARDS (hub) ───────────────────────────────────────────── */
        .adv-topic-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
            padding: 24px;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-left: 5px solid #1B3B6F;
            transition: box-shadow .2s, transform .15s, border-left-color .2s;
        }
        .adv-topic-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.13); transform: translateY(-3px); }
        .adv-topic-card-icon { font-size: 2rem; }
        .adv-topic-card h3 { font-size: 1.1em; font-weight: 700; color: #1B3B6F; }
        .adv-topic-card p  { font-size: .88em; color: #64748b; line-height: 1.55; }
        .adv-topic-card-arrow { font-size: .85em; color: #1B3B6F; font-weight: 700; margin-top: auto; }

        /* ── CTA BANNER ──────────────────────────────────────────────────── */
        .adv-cta-banner {
            background: linear-gradient(135deg, #1B3B6F, #0d2247);
            color: white;
            border-radius: 16px;
            padding: 36px 32px;
            text-align: center;
            margin-top: 32px;
        }
        .adv-cta-banner h2 { font-size: 1.45em; font-weight: 800; margin-bottom: 8px; }
        .adv-cta-banner p  { opacity: .85; font-size: .97em; margin-bottom: 24px; }
        .adv-cta-buttons { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

        .btn-cta-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 26px;
            background: #f59e0b; color: #1a202c;
            border-radius: 10px; font-weight: 700; font-size: .95em;
            text-decoration: none; transition: background .2s;
            border: none; cursor: pointer;
        }
        .btn-cta-primary:hover { background: #d97706; }

        .btn-cta-secondary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 26px;
            background: transparent; color: white;
            border: 2px solid rgba(255,255,255,.5);
            border-radius: 10px; font-weight: 600; font-size: .95em;
            text-decoration: none; transition: all .2s;
        }
        .btn-cta-secondary:hover { background: rgba(255,255,255,.12); border-color: white; }

        /* ── SECTION TITLE ───────────────────────────────────────────────── */
        .adv-section-title {
            font-size: 1.35em;
            font-weight: 800;
            color: #1B3B6F;
            margin: 36px 0 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── HIGHLIGHT BOX ───────────────────────────────────────────────── */
        .adv-highlight {
            background: #eff6ff;
            border-left: 4px solid #1B3B6F;
            border-radius: 0 10px 10px 0;
            padding: 16px 20px;
            margin: 16px 0;
            font-size: .95em;
            color: #1e3a5f;
            line-height: 1.7;
        }
        .adv-highlight-warning {
            background: #fffbeb;
            border-left-color: #f59e0b;
            color: #78350f;
        }
        .adv-highlight-success {
            background: #f0fdf4;
            border-left-color: #22c55e;
            color: #14532d;
        }

        /* ── FOOTER ──────────────────────────────────────────────────────── */
        .adv-footer {
            background: #1B3B6F;
            color: rgba(255,255,255,.7);
            text-align: center;
            padding: 24px 20px;
            font-size: .85em;
        }
        .adv-footer a { color: rgba(255,255,255,.85); text-decoration: none; }
        .adv-footer a:hover { color: white; }

        /* ── RESPONSIVE ──────────────────────────────────────────────────── */
        @media (max-width: 768px) {
            .adv-header-nav { display: none; }
            .adv-mobile-nav { display: flex; }
            .adv-hero { padding: 32px 16px 30px; }
            .adv-main { padding: 24px 14px 56px; }
            .adv-card { padding: 20px 18px; }
            .adv-cta-banner { padding: 26px 18px; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="adv-header">
    <a class="adv-header-logo" href="/">🗺️ Mapita <span>Módulo Avanzado</span></a>
    <nav class="adv-header-nav">
        <a href="/" class="adv-nav-back">← Volver al Mapa</a>
        <?php foreach ($navItems as $item): ?>
        <a href="<?= htmlspecialchars($item['href']) ?>"
           class="adv-nav-link<?= ($activeSection === $item['slug']) ? ' active' : '' ?>">
            <?= $item['icon'] ?> <?= htmlspecialchars($item['label']) ?>
        </a>
        <?php endforeach; ?>
    </nav>
</header>

<!-- MOBILE NAV -->
<nav class="adv-mobile-nav">
    <a href="/" style="background:rgba(255,255,255,.06)">← Mapa</a>
    <?php foreach ($navItems as $item): ?>
    <a href="<?= htmlspecialchars($item['href']) ?>"
       class="<?= ($activeSection === $item['slug']) ? 'active' : '' ?>">
        <?= $item['icon'] ?> <?= htmlspecialchars($item['label']) ?>
    </a>
    <?php endforeach; ?>
</nav>

<!-- HERO -->
<section class="adv-hero">
    <span class="adv-hero-icon"><?= $pageIcon ?></span>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <?php if (!empty($pageSubtitle)): ?>
    <p><?= htmlspecialchars($pageSubtitle) ?></p>
    <?php endif; ?>
</section>

<!-- MAIN CONTENT WRAPPER -->
<main class="adv-main">
