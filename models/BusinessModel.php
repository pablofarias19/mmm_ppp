<?php
// models/BusinessModel.php
// Uses its own `modelos_negocio` table (one-to-many: multiple models per brand).
// Run migrations/036_create_modelos_negocio.sql to create the table.
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
        try {
            $stmt = $db->prepare('SELECT * FROM modelos_negocio WHERE marca_id = ? ORDER BY created_at DESC');
            $stmt->execute([$marca_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[BusinessModel::allByMarca] ' . $e->getMessage());
            throw $e;
        }
    }

    public static function create($db, $data) {
        try {
            $stmt = $db->prepare('INSERT INTO modelos_negocio (marca_id, tipo, descripcion) VALUES (?, ?, ?)');
            $stmt->execute([
                $data['marca_id'],
                $data['tipo'],
                $data['descripcion']
            ]);
            return $db->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[BusinessModel::create] ' . $e->getMessage());
            throw $e;
        }
    }

    public static function delete($db, $id) {
        try {
            $stmt = $db->prepare('DELETE FROM modelos_negocio WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (\Throwable $e) {
            error_log('[BusinessModel::delete] ' . $e->getMessage());
            throw $e;
        }
    }
}
