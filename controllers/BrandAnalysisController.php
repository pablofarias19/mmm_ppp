<?php
// controllers/BrandAnalysisController.php
require_once __DIR__ . '/../models/BrandAnalysis.php';
require_once __DIR__ . '/../core/Database.php';

class BrandAnalysisController {
    private $db;
    public function __construct() {
        $this->db = \Core\Database::getInstance()->getConnection();
    }

    public function show($marca_id) {
        $analysis = BrandAnalysis::findByMarca($this->db, $marca_id);
        include __DIR__ . '/../views/brand/brand_analysis_form.php';
    }

    public function save($marca_id, $data) {
        $data['marca_id'] = $marca_id;
        BrandAnalysis::save($this->db, $data);
        header('Location: /brand_detail?id=' . $marca_id);
        exit;
    }
}
