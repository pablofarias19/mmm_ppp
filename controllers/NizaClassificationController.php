<?php
// controllers/NizaClassificationController.php
require_once __DIR__ . '/../models/NizaClassification.php';
require_once __DIR__ . '/../core/Database.php';

class NizaClassificationController {
    private $db;
    public function __construct() {
        $this->db = \Core\Database::getInstance()->getConnection();
    }

    public function show($marca_id) {
        $niza = NizaClassification::findByMarca($this->db, $marca_id);
        include __DIR__ . '/../views/brand/niza_classification_form.php';
    }

    public function save($marca_id, $data) {
        $data['marca_id'] = $marca_id;
        NizaClassification::save($this->db, $data);
        header('Location: /brand_detail?id=' . $marca_id);
        exit;
    }
}
