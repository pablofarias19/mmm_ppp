<?php
// controllers/MonetizationController.php
require_once __DIR__ . '/../models/Monetization.php';
require_once __DIR__ . '/../core/Database.php';

class MonetizationController {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function show($marca_id) {
        $monet = Monetization::findByMarca($this->db, $marca_id);
        include __DIR__ . '/../views/brand/monetization_form.php';
    }

    public function save($marca_id, $data) {
        $data['marca_id'] = $marca_id;
        Monetization::save($this->db, $data);
        header('Location: /brand_detail.php?id=' . $marca_id);
        exit;
    }
}
