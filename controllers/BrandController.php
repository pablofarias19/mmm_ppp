<?php
// controllers/BrandController.php
require_once __DIR__ . '/../models/Brand.php';
require_once __DIR__ . '/../core/Database.php';

class BrandController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $brands = Brand::all($this->db);
        include __DIR__ . '/../views/brand/dashboard_brands.php';
    }

    public function show($id) {
        $brand = Brand::find($this->db, $id);
        include __DIR__ . '/../views/brand/brand_detail.php';
    }

    public function create($data) {
        $id = Brand::create($this->db, $data);
        header('Location: /brand.php?id=' . $id);
        exit;
    }

    public function update($id, $data) {
        Brand::update($this->db, $id, $data);
        header('Location: /brand.php?id=' . $id);
        exit;
    }

    public function delete($id) {
        Brand::delete($this->db, $id);
        header('Location: /brands.php');
        exit;
    }
}
