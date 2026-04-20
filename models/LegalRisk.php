<?php
// models/LegalRisk.php
class LegalRisk {
    public $id;
    public $marca_id;
    public $riesgo_oposicion;
    public $riesgo_nulidad;
    public $riesgo_infraccion;
    public $estrategias_defensivas;
    public $created_at;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->marca_id = $data['marca_id'] ?? null;
        $this->riesgo_oposicion = $data['riesgo_oposicion'] ?? '';
        $this->riesgo_nulidad = $data['riesgo_nulidad'] ?? '';
        $this->riesgo_infraccion = $data['riesgo_infraccion'] ?? '';
        $this->estrategias_defensivas = $data['estrategias_defensivas'] ?? '';
        $this->created_at = $data['created_at'] ?? null;
    }

    public static function findByMarca($db, $marca_id) {
        $stmt = $db->prepare('SELECT * FROM riesgo_legal WHERE marca_id = ?');
        $stmt->execute([$marca_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($db, $data) {
        $existing = self::findByMarca($db, $data['marca_id']);
        if ($existing) {
            $stmt = $db->prepare('UPDATE riesgo_legal SET riesgo_oposicion=?, riesgo_nulidad=?, riesgo_infraccion=?, estrategias_defensivas=? WHERE marca_id=?');
            return $stmt->execute([
                $data['riesgo_oposicion'],
                $data['riesgo_nulidad'],
                $data['riesgo_infraccion'],
                $data['estrategias_defensivas'],
                $data['marca_id']
            ]);
        } else {
            $stmt = $db->prepare('INSERT INTO riesgo_legal (marca_id, riesgo_oposicion, riesgo_nulidad, riesgo_infraccion, estrategias_defensivas) VALUES (?, ?, ?, ?, ?)');
            return $stmt->execute([
                $data['marca_id'],
                $data['riesgo_oposicion'],
                $data['riesgo_nulidad'],
                $data['riesgo_infraccion'],
                $data['estrategias_defensivas']
            ]);
        }
    }
}
