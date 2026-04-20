<?php
// dashboard_brands.php
require_once __DIR__ . '/controllers/BrandController.php';
$controller = new BrandController();
$controller->index();
