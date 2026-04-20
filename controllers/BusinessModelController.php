<?php
// controllers/BusinessModelController.php
require_once __DIR__ . '/../models/BusinessModel.php';
require_once __DIR__ . '/../core/Database.php';

class BusinessModelController {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index($marca_id) {
        $models = BusinessModel::allByMarca($this->db, $marca_id);
        include __DIR__ . '/../views/brand/business_model_list.php';
    }

    public function create($marca_id, $data) {
        $data['marca_id'] = $marca_id;
        BusinessModel::create($this->db, $data);
        header('Location: /business_model.php?id=' . $marca_id);
        exit;
    }

    public function delete($marca_id, $model_id) {
        BusinessModel::delete($this->db, $model_id);
        header('Location: /business_model.php?id=' . $marca_id);
        exit;
    }
}
