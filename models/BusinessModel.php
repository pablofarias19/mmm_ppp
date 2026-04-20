<?php
// models/BusinessModel.php
class BusinessModel {
    public $id;
    public $marca_id;
    public $tipo;
    public $descripcion;
    public $created_at;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->marca_id = $data['marca_id'] ?? null;
        $this->tipo = $data['tipo'] ?? '';
        $this->descripcion = $data['descripcion'] ?? '';
        $this->created_at = $data['created_at'] ?? null;
    }

    public static function allByMarca($db, $marca_id) {
        $stmt = $db->prepare('SELECT * FROM modelos_negocio WHERE marca_id = ?');
        $stmt->execute([$marca_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($db, $data) {
        $stmt = $db->prepare('INSERT INTO modelos_negocio (marca_id, tipo, descripcion) VALUES (?, ?, ?)');
        $stmt->execute([
            $data['marca_id'],
            $data['tipo'],
            $data['descripcion']
        ]);
        return $db->lastInsertId();
    }

    public static function delete($db, $id) {
        $stmt = $db->prepare('DELETE FROM modelos_negocio WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
