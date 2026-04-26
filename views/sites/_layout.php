<?php
/**
 * views/sites/_layout.php
 * Shared header/nav/footer for the Módulo Avanzado pages.
 *
 * i18n: Loads core/helpers.php which provides t(), getUILanguage() and setUILanguage().
 * The active language is resolved in this order: ?lang= query param → session → 'es'.
 * To change language, navigate to any advanced panel with ?lang=XX (e.g. ?lang=en).
 */

require_once __DIR__ . '/../../core/helpers.php';

// If a lang query param is present, store it in session before any output.
if (isset($_GET['lang'])) {
    setUILanguage($_GET['lang']);
}

// Language options shown in the selector (subset of MAPITA_SUPPORTED_LANGS).
// To add a new language: 1) create lang/XX.php, 2) add the entry below.
if (!defined('ADV_LANG_OPTIONS')) {
    define('ADV_LANG_OPTIONS', [
        'es' => ['label' => 'Español',   'flag' => '🇦🇷'],
        'en' => ['label' => 'English',   'flag' => '🇬🇧'],
        'de' => ['label' => 'Deutsch',   'flag' => '🇩🇪'],
        'fr' => ['label' => 'Français',  'flag' => '🇫🇷'],
        'pt' => ['label' => 'Português', 'flag' => '🇧🇷'],
        'zh' => ['label' => '中文',       'flag' => '🇨🇳'],
        'ja' => ['label' => '日本語',     'flag' => '🇯🇵'],
        'ko' => ['label' => '한국어',     'flag' => '🇰🇷'],
        'hi' => ['label' => 'हिन्दी',    'flag' => '🇮🇳'],
        'ar' => ['label' => 'العربية',   'flag' => '🇸🇦'],
    ]);
}

function siteHeader(string $title, string $activePage = ''): void {
    $lang    = getUILanguage();
    $isRTL   = ($lang === 'ar');
    $dir     = $isRTL ? 'rtl' : 'ltr';
    $options = ADV_LANG_OPTIONS;
    // Build a clean base URL for the language switcher (strip existing ?lang= param).
    $parsedUrl   = parse_url($_SERVER['REQUEST_URI'] ?? '/');
    $baseUrl     = $parsedUrl['path'] ?? '/';
    $queryParams = [];
    if (!empty($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParams);
    }
    unset($queryParams['lang']);
    $queryString = http_build_query($queryParams);
    $langBase    = $queryString ? $baseUrl . '?' . $queryString . '&lang=' : $baseUrl . '?lang=';
    ?>
    <!doctype html>
    <html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" dir="<?= $dir ?>">
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
            /* ── Language selector ────────────────────────────────────────────── */
            .lang-selector-bar { display: flex; align-items: center; gap: 8px; margin: 0 0 18px; flex-wrap: wrap; }
            .lang-selector-bar label { font-size: 12px; font-weight: 700; color: #6b7280; white-space: nowrap; }
            .lang-selector-bar select { font-size: 13px; padding: 5px 10px; border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; color: #111827; cursor: pointer; }
            .lang-selector-bar select:focus { outline: 2px solid #1B3B6F; }
            @media (max-width: 600px) {
                .site-topbar .site-title { font-size: 18px; }
                .card { padding: 14px; }
                .btn { font-size: 13px; padding: 9px 12px; }
            }
            /* RTL adjustments */
            [dir="rtl"] ul.feature-list { padding-left: 0; padding-right: 20px; }
            [dir="rtl"] .site-topbar { flex-direction: row-reverse; }
        </style>
    </head>
    <body>
        <div class="site-wrap">
            <div class="site-topbar">
                <a class="back-map" href="/map"><?= htmlspecialchars(t('adv_back_map'), ENT_QUOTES, 'UTF-8') ?></a>
                <div>
                    <p class="site-title"><?= htmlspecialchars($title) ?></p>
                    <p class="site-subtitle"><?= htmlspecialchars(t('adv_subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <!-- Language selector: selecting an option navigates to ?lang=XX,
                 which stores the preference in the session via setUILanguage(). -->
            <div class="lang-selector-bar">
                <label for="adv-lang-select"><?= htmlspecialchars(t('translate_option'), ENT_QUOTES, 'UTF-8') ?>:</label>
                <select id="adv-lang-select" name="lang"
                        onchange="window.location.href='<?= htmlspecialchars($langBase, ENT_QUOTES, 'UTF-8') ?>' + this.value">
                    <?php foreach ($options as $code => $opt):
                        $selected = ($code === $lang) ? ' selected' : '';
                        $label    = htmlspecialchars($opt['flag'] . ' ' . $opt['label'], ENT_QUOTES, 'UTF-8');
                    ?>
                    <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"<?= $selected ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <nav class="site-nav">
                <a href="/avanzado"<?= $activePage === 'avanzado'     ? ' class="active"' : '' ?>><?= htmlspecialchars(t('adv_nav_panel'),      ENT_QUOTES, 'UTF-8') ?></a>
                <a href="/juridico"<?= $activePage === 'juridico'     ? ' class="active"' : '' ?>><?= htmlspecialchars(t('adv_mod_juridico'),   ENT_QUOTES, 'UTF-8') ?></a>
                <a href="/fiscal"<?= $activePage === 'fiscal'         ? ' class="active"' : '' ?>><?= htmlspecialchars(t('adv_mod_fiscal'),     ENT_QUOTES, 'UTF-8') ?></a>
                <a href="/inversion"<?= $activePage === 'inversion'   ? ' class="active"' : '' ?>><?= htmlspecialchars(t('adv_mod_inversion'),  ENT_QUOTES, 'UTF-8') ?></a>
                <a href="/compliance"<?= $activePage === 'compliance' ? ' class="active"' : '' ?>><?= htmlspecialchars(t('adv_mod_compliance'), ENT_QUOTES, 'UTF-8') ?></a>
                <a href="/marca-expansion"<?= $activePage === 'marca' ? ' class="active"' : '' ?>><?= htmlspecialchars(t('adv_mod_marca'),      ENT_QUOTES, 'UTF-8') ?></a>
                <a href="/tasacion"<?= $activePage === 'tasacion'     ? ' class="active"' : '' ?>><?= htmlspecialchars(t('adv_mod_tasacion'),   ENT_QUOTES, 'UTF-8') ?></a>
                <a href="/contacto"<?= $activePage === 'contacto'     ? ' class="active"' : '' ?>><?= htmlspecialchars(t('adv_mod_contact'),    ENT_QUOTES, 'UTF-8') ?></a>
            </nav>
    <?php
}

function siteFooter(): void {
    ?>
            <div class="card" style="text-align:center; margin-top:24px;">
                <a class="btn btn-secondary" href="/map"><?= htmlspecialchars(t('adv_back_map_main'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
