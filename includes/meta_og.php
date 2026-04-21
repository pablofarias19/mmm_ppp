<?php
/**
 * meta_og.php — Open Graph + Twitter Card + WhatsApp meta tags
 *
 * Usar antes del </head>. Requiere que las siguientes variables estén definidas
 * en la página que hace el include:
 *
 *   $og_title       string   Título de la página (obligatorio)
 *   $og_description string   Descripción (obligatorio)
 *   $og_url         string   URL canónica completa  (opcional — se auto-detecta)
 *   $og_image       string   URL absoluta de la imagen (opcional — se usa la genérica)
 *   $og_type        string   'website' | 'article' | 'business.business' (default: website)
 *   $og_locale      string   'es_AR' (default)
 *   $og_site_name   string   Nombre del sitio (default: Mapita)
 *   $twitter_card   string   'summary_large_image' | 'summary' (default: summary_large_image)
 *   $twitter_site   string   @handle de Twitter/X del sitio (opcional)
 */

// ── Defaults ──────────────────────────────────────────────────────────────────
$og_site_name   = $og_site_name   ?? 'MAPITA - Mapa de Marcas y Negocios';
$og_type        = $og_type        ?? 'website';
$og_locale      = $og_locale      ?? 'es_AR';
$twitter_card   = $twitter_card   ?? 'summary_large_image';
$twitter_site   = $twitter_site   ?? '';   // ej: '@mapita_ar'

// ── Auto-detectar URL canónica ─────────────────────────────────────────────────
if (empty($og_url)) {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'mapita.com.ar';
    $path    = $_SERVER['REQUEST_URI'] ?? '/';
    $og_url  = $scheme . '://' . $host . $path;
}

// ── Imagen por defecto (genérica del sitio) ───────────────────────────────────
$scheme_base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host_base   = $_SERVER['HTTP_HOST'] ?? 'mapita.com.ar';
$base_url    = $scheme_base . '://' . $host_base;

if (empty($og_image)) {
    $og_image = $base_url . '/api/og_image.php';
}

// ── Sanitizar ─────────────────────────────────────────────────────────────────
$og_title       = htmlspecialchars($og_title       ?? $og_site_name, ENT_QUOTES);
$og_description = htmlspecialchars($og_description ?? '', ENT_QUOTES);
$og_url         = htmlspecialchars($og_url,   ENT_QUOTES);
$og_image       = htmlspecialchars($og_image, ENT_QUOTES);
$og_type        = htmlspecialchars($og_type,  ENT_QUOTES);

// Limitar descripción a 200 caracteres (límite recomendado)
if (mb_strlen($og_description) > 200) {
    $og_description = mb_substr($og_description, 0, 197) . '…';
}
?>
    <!-- ══ Favicon ════════════════════════════════════════════════════════ -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">

    <!-- ══ Primary Meta Tags ══════════════════════════════════════════════ -->
    <meta name="description" content="<?php echo $og_description; ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo $og_url; ?>">

    <!-- ══ Open Graph / Facebook / WhatsApp / LinkedIn ═══════════════════ -->
    <meta property="og:type"        content="<?php echo $og_type; ?>">
    <meta property="og:site_name"   content="<?php echo htmlspecialchars($og_site_name, ENT_QUOTES); ?>">
    <meta property="og:locale"      content="<?php echo $og_locale; ?>">
    <meta property="og:url"         content="<?php echo $og_url; ?>">
    <meta property="og:title"       content="<?php echo $og_title; ?>">
    <meta property="og:description" content="<?php echo $og_description; ?>">
    <meta property="og:image"              content="<?php echo $og_image; ?>">
    <meta property="og:image:secure_url"   content="<?php echo $og_image; ?>">
    <meta property="og:image:type"         content="image/png">
    <meta property="og:image:width"        content="1200">
    <meta property="og:image:height"       content="630">
    <meta property="og:image:alt"          content="<?php echo $og_title; ?>">

    <!-- ══ Twitter / X Cards ════════════════════════════════════════════ -->
    <meta name="twitter:card"        content="<?php echo $twitter_card; ?>">
    <meta name="twitter:title"       content="<?php echo $og_title; ?>">
    <meta name="twitter:description" content="<?php echo $og_description; ?>">
    <meta name="twitter:image"       content="<?php echo $og_image; ?>">
    <meta name="twitter:image:alt"   content="<?php echo $og_title; ?>">
    <?php if ($twitter_site): ?>
    <meta name="twitter:site"        content="<?php echo htmlspecialchars($twitter_site, ENT_QUOTES); ?>">
    <?php endif; ?>

    <!-- ══ WhatsApp (usa OG, pero esto refuerza) ════════════════════════ -->
    <meta property="og:rich_attachment" content="true">
