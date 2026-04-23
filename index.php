<?php
/**
 * Mapita - Front Controller
 */

// Set security headers early (before any output)
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self' https:; script-src 'self' https://unpkg.com https://cdnjs.cloudflare.com 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://unpkg.com; img-src 'self' data: https: blob:; font-src 'self' data: https:; connect-src 'self' https: wss:; frame-src 'self' https:; media-src https:;");

// Parse the request URI
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($uri, PHP_URL_PATH);

// Remove base path prefix if it exists
$basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath && $basePath !== '/' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . ltrim($uri, '/');

// Route definitions
$routes = [
    '/'              => __DIR__ . '/views/business/map.php',
    '/map'           => __DIR__ . '/views/business/map.php',
    '/mis-negocios'  => __DIR__ . '/business/my_businesses.php',
    '/negocios'      => __DIR__ . '/views/business/map.php',
    '/login'         => __DIR__ . '/auth/login.php',
    '/register'      => __DIR__ . '/auth/register.php',
    '/logout'        => __DIR__ . '/auth/logout.php',
    '/reset'         => __DIR__ . '/auth/reset_password.php',
    '/add'           => __DIR__ . '/business/add_business.php',
    '/edit'          => __DIR__ . '/business/add_business.php',
    '/view'          => __DIR__ . '/business/view_business.php',
    '/negocio'       => __DIR__ . '/business/view_business.php',
    '/businesses'    => __DIR__ . '/business/my_businesses.php',
    '/admin'         => __DIR__ . '/admin/dashboard.php',
    '/brands'        => __DIR__ . '/views/brand/brand_map.php',
    '/brand_map'     => __DIR__ . '/views/brand/brand_map.php',
    '/brand_form'    => __DIR__ . '/views/brand/form.php',
    '/brand_new'     => __DIR__ . '/views/brand/form.php',
    '/brand_edit'    => __DIR__ . '/views/brand/form.php',
    '/dashboard_brands' => __DIR__ . '/views/brand/dashboard_brands.php',
    '/brand_detail'  => __DIR__ . '/views/brand/brand_detail_micro.php',
    '/marcas'        => __DIR__ . '/views/brand/brand_map.php',
    '/monetization'  => __DIR__ . '/monetization.php',
    '/legal_risk'    => __DIR__ . '/legal_risk.php',
    '/niza_classification' => __DIR__ . '/niza_classification.php',
    '/brand_analysis' => __DIR__ . '/brand_analysis.php',
    '/business_model' => __DIR__ . '/business_model.php',
    '/panel-disponibles' => __DIR__ . '/business/panel_disponibles.php',
    '/panel-trabajo'     => __DIR__ . '/business/panel_trabajo.php',
    '/industrias'        => __DIR__ . '/views/industry/dashboard_industries.php',
    '/industry_new'      => __DIR__ . '/views/industry/form.php',
    '/industry_edit'     => __DIR__ . '/views/industry/form.php',
];

// Route the request
if (isset($routes[$uri]) && file_exists($routes[$uri])) {
    require $routes[$uri];
    exit;
}

// Default - show map
if (file_exists(__DIR__ . '/views/business/map.php')) {
    require __DIR__ . '/views/business/map.php';
} else {
    http_response_code(404);
    echo "404 - Not Found";
}