<?php
/**
 * views/sector/_layout.php
 * Layout compartido para módulos de Sector Comercial e Industrial.
 */

function sectorHeader(string $title, string $sectorType, int $sectorId, string $activeTab = ''): void {
    $backLabel = $sectorType === 'commercial' ? 'Sector Comercial' : 'Sector Industrial';
    $baseUrl   = $sectorType === 'commercial'
        ? "/sector-comercial?id=$sectorId"
        : "/sector-industrial?id=$sectorId";
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title) ?> — Mapita</title>
        <link rel="stylesheet" href="/css/map-styles.css">
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; background: #f3f4f6; color: #111827; }
            .sector-wrap { max-width: 1100px; margin: 0 auto; padding: 22px 16px 60px; }
            .sector-topbar { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:16px; }
            .back-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; background:#111827; color:#fff; border-radius:10px; text-decoration:none; font-weight:800; font-size:14px; }
            .back-btn:hover { background:#374151; }
            .sector-title { font-size:22px; font-weight:800; margin:0; }
            .sector-subtitle { font-size:13px; color:#6b7280; margin:0; }
            /* Tabs */
            .sector-tabs { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:22px; border-bottom:2px solid #e5e7eb; padding-bottom:0; }
            .sector-tab { padding:10px 16px; border:none; background:none; cursor:pointer; font-weight:700; font-size:14px; border-bottom:3px solid transparent; color:#6b7280; margin-bottom:-2px; text-decoration:none; display:inline-block; }
            .sector-tab.active { color:#4f46e5; border-bottom-color:#4f46e5; }
            .sector-tab:hover:not(.active) { background:#f9fafb; color:#374151; }
            /* Cards */
            .s-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:20px; margin-bottom:16px; }
            .s-card h2 { margin:0 0 10px; font-size:18px; }
            .s-card h3 { margin:0 0 8px; font-size:15px; }
            .s-card p  { margin:0 0 10px; line-height:1.6; color:#374151; font-size:14px; }
            .grid-2 { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px; }
            .grid-3 { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
            /* Badges */
            .badge { display:inline-block; padding:2px 10px; border-radius:9999px; font-size:11px; font-weight:700; }
            .badge-green  { background:#d1fae5; color:#065f46; }
            .badge-blue   { background:#dbeafe; color:#1e40af; }
            .badge-purple { background:#ede9fe; color:#4c1d95; }
            .badge-amber  { background:#fef3c7; color:#78350f; }
            .badge-red    { background:#fee2e2; color:#7f1d1d; }
            .badge-gray   { background:#f3f4f6; color:#374151; }
            /* Table */
            .data-table { width:100%; border-collapse:collapse; font-size:13px; }
            .data-table th { background:#f9fafb; text-align:left; padding:10px 12px; font-weight:700; border-bottom:2px solid #e5e7eb; }
            .data-table td { padding:9px 12px; border-bottom:1px solid #f3f4f6; vertical-align:top; }
            .data-table tr:hover td { background:#f9fafb; }
            /* Muted */
            .muted { color:#6b7280; font-size:13px; }
            /* CTA row */
            .cta-row { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
            .btn { display:inline-block; padding:10px 16px; border-radius:12px; text-decoration:none; font-weight:800; font-size:14px; border:none; cursor:pointer; }
            .btn-primary { background:#4f46e5; color:#fff; }
            .btn-primary:hover { background:#4338ca; }
            .btn-secondary { background:#f3f4f6; color:#111827; border:1px solid #e5e7eb; }
            .btn-secondary:hover { background:#e5e7eb; }
            .section-hdr { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
            /* Loading */
            .loading { color:#6b7280; padding:20px; text-align:center; }
            @media(max-width:600px) {
                .sector-title { font-size:17px; }
                .s-card { padding:14px; }
                .btn { font-size:13px; padding:9px 12px; }
            }
        </style>
    </head>
    <body>
    <div class="sector-wrap">
        <div class="sector-topbar">
            <a class="back-btn" href="<?= htmlspecialchars($baseUrl) ?>">← <?= htmlspecialchars($backLabel) ?></a>
            <div>
                <p class="sector-title"><?= htmlspecialchars($title) ?></p>
                <p class="sector-subtitle">Módulo Plano Sector — <?= $sectorType === 'commercial' ? 'Sector Comercial' : 'Sector Industrial' ?></p>
            </div>
        </div>

        <nav class="sector-tabs">
            <a href="<?= htmlspecialchars($baseUrl) ?>"
               class="sector-tab<?= ($activeTab === 'overview') ? ' active' : '' ?>">🏠 Resumen</a>
            <a href="<?= htmlspecialchars($baseUrl) ?>&tab=institucional"
               class="sector-tab<?= ($activeTab === 'institucional') ? ' active' : '' ?>">🏛️ Institucional &amp; Normativo</a>
            <a href="<?= htmlspecialchars($baseUrl) ?>&tab=radar"
               class="sector-tab<?= ($activeTab === 'radar') ? ' active' : '' ?>">🌐 Radar Legal</a>
            <a href="/contacto?tema=juridico" class="sector-tab">📩 Asesoramiento Legal</a>
        </nav>
    <?php
}

function sectorFooter(): void {
    ?>
        <div class="s-card" style="text-align:center;margin-top:24px;">
            <a class="btn btn-secondary" href="/map">← Volver al mapa</a>
        </div>
    </div>
    </body>
    </html>
    <?php
}
