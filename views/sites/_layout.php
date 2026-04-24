<?php
/**
 * views/sites/_layout.php
 * Shared header/nav/footer for the Módulo Avanzado pages.
 */

function siteHeader(string $title, string $activePage = ''): void {
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
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; background: #f9fafb; color: #111827; }
            .site-wrap { max-width: 980px; margin: 0 auto; padding: 22px 14px 48px; }
            .site-topbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 16px; }
            .site-topbar .back-map { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; background: #111827; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 800; font-size: 14px; }
            .site-topbar .back-map:hover { background: #374151; }
            .site-topbar .site-title { font-size: 22px; font-weight: 800; margin: 0; }
            .site-topbar .site-subtitle { font-size: 13px; color: #6b7280; margin: 0; }
            .site-nav { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 20px; }
            .site-nav a { padding: 7px 12px; border-radius: 10px; text-decoration: none; background: #f3f4f6; color: #111827; font-weight: 600; font-size: 13px; border: 1px solid #e5e7eb; }
            .site-nav a:hover, .site-nav a.active { background: #e5e7eb; }
            .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin: 0 0 16px; }
            .card h2 { margin: 0 0 10px; font-size: 18px; }
            .card p { margin: 0 0 10px; line-height: 1.6; }
            .cta-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
            .btn { display: inline-block; padding: 10px 16px; border-radius: 12px; text-decoration: none; font-weight: 800; font-size: 14px; }
            .btn-primary { background: #111827; color: #fff; }
            .btn-primary:hover { background: #374151; }
            .btn-secondary { background: #f3f4f6; color: #111827; border: 1px solid #e5e7eb; }
            .btn-secondary:hover { background: #e5e7eb; }
            .muted { color: #6b7280; font-size: 13px; }
            ul.feature-list { margin: 8px 0 0; padding-left: 20px; }
            ul.feature-list li { margin-bottom: 4px; font-size: 14px; }
            .section-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 16px; }
            @media (max-width: 600px) {
                .site-topbar .site-title { font-size: 18px; }
                .card { padding: 14px; }
                .btn { font-size: 13px; padding: 9px 12px; }
            }
        </style>
    </head>
    <body>
        <div class="site-wrap">
            <div class="site-topbar">
                <a class="back-map" href="/map">← Volver al mapa</a>
                <div>
                    <p class="site-title"><?= htmlspecialchars($title) ?></p>
                    <p class="site-subtitle">Módulo avanzado — Argentina: legal, fiscal, inversión, compliance y expansión.</p>
                </div>
            </div>

            <nav class="site-nav">
                <a href="/avanzado"<?= $activePage === 'avanzado' ? ' class="active"' : '' ?>>Panel avanzado</a>
                <a href="/juridico"<?= $activePage === 'juridico' ? ' class="active"' : '' ?>>⚖️ Jurídico</a>
                <a href="/fiscal"<?= $activePage === 'fiscal' ? ' class="active"' : '' ?>>🧾 Fiscal</a>
                <a href="/inversion"<?= $activePage === 'inversion' ? ' class="active"' : '' ?>>📈 Inversión</a>
                <a href="/compliance"<?= $activePage === 'compliance' ? ' class="active"' : '' ?>>🛡️ Compliance</a>
                <a href="/marca-expansion"<?= $activePage === 'marca' ? ' class="active"' : '' ?>>🚀 Marca y Expansión</a>
                <a href="/tasacion"<?= $activePage === 'tasacion' ? ' class="active"' : '' ?>>💎 Tasación de Marcas</a>
            </nav>
    <?php
}

function siteFooter(): void {
    ?>
            <div class="card" style="text-align:center; margin-top:24px;">
                <a class="btn btn-secondary" href="/map">← Volver al mapa principal</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
