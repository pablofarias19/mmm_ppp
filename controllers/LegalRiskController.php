<?php
// controllers/LegalRiskController.php
require_once __DIR__ . '/../models/LegalRisk.php';
require_once __DIR__ . '/../core/Database.php';

class LegalRiskController {
    private $db;
    public function __construct() {
        $this->db = \Core\Database::getInstance()->getConnection();
    }

    public function show($marca_id) {
        $risk = LegalRisk::findByMarca($this->db, $marca_id);
        include __DIR__ . '/../views/brand/legal_risk_form.php';
    }

    public function save($marca_id, $data) {
        $data['marca_id'] = $marca_id;
        LegalRisk::save($this->db, $data);
        header('Location: /brand_detail?id=' . $marca_id);
        exit;
    }
}
