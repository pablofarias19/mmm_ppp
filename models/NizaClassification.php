<?php
// models/NizaClassification.php
class NizaClassification {
    public $id;
    public $marca_id;
    public $clase_principal;
    public $clases_complementarias;
    public $riesgo_colision;
    public $created_at;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->marca_id = $data['marca_id'] ?? null;
        $this->clase_principal = $data['clase_principal'] ?? '';
        $this->clases_complementarias = $data['clases_complementarias'] ?? '';
        $this->riesgo_colision = $data['riesgo_colision'] ?? '';
        $this->created_at = $data['created_at'] ?? null;
    }

    public static function findByMarca($db, $marca_id) {
        $stmt = $db->prepare('SELECT * FROM clasificacion_niza WHERE marca_id = ?');
        $stmt->execute([$marca_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($db, $data) {
        $existing = self::findByMarca($db, $data['marca_id']);
        if ($existing) {
            $stmt = $db->prepare('UPDATE clasificacion_niza SET clase_principal=?, clases_complementarias=?, riesgo_colision=? WHERE marca_id=?');
            return $stmt->execute([
                $data['clase_principal'],
                $data['clases_complementarias'],
                $data['riesgo_colision'],
                $data['marca_id']
            ]);
        } else {
            $stmt = $db->prepare('INSERT INTO clasificacion_niza (marca_id, clase_principal, clases_complementarias, riesgo_colision) VALUES (?, ?, ?, ?)');
            return $stmt->execute([
                $data['marca_id'],
                $data['clase_principal'],
                $data['clases_complementarias'],
                $data['riesgo_colision']
            ]);
        }
    }
}
