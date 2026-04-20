<?php
// models/BrandAnalysis.php
class BrandAnalysis {
    public $id;
    public $marca_id;
    public $distintividad;
    public $riesgo_confusion;
    public $conflictos_clases;
    public $nivel_proteccion;
    public $expansion_internacional;
    public $created_at;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->marca_id = $data['marca_id'] ?? null;
        $this->distintividad = $data['distintividad'] ?? '';
        $this->riesgo_confusion = $data['riesgo_confusion'] ?? '';
        $this->conflictos_clases = $data['conflictos_clases'] ?? '';
        $this->nivel_proteccion = $data['nivel_proteccion'] ?? '';
        $this->expansion_internacional = $data['expansion_internacional'] ?? '';
        $this->created_at = $data['created_at'] ?? null;
    }

    public static function findByMarca($db, $marca_id) {
        $stmt = $db->prepare('SELECT * FROM analisis_marcario WHERE marca_id = ?');
        $stmt->execute([$marca_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($db, $data) {
        $existing = self::findByMarca($db, $data['marca_id']);
        if ($existing) {
            $stmt = $db->prepare('UPDATE analisis_marcario SET distintividad=?, riesgo_confusion=?, conflictos_clases=?, nivel_proteccion=?, expansion_internacional=? WHERE marca_id=?');
            return $stmt->execute([
                $data['distintividad'],
                $data['riesgo_confusion'],
                $data['conflictos_clases'],
                $data['nivel_proteccion'],
                $data['expansion_internacional'],
                $data['marca_id']
            ]);
        } else {
            $stmt = $db->prepare('INSERT INTO analisis_marcario (marca_id, distintividad, riesgo_confusion, conflictos_clases, nivel_proteccion, expansion_internacional) VALUES (?, ?, ?, ?, ?, ?)');
            return $stmt->execute([
                $data['marca_id'],
                $data['distintividad'],
                $data['riesgo_confusion'],
                $data['conflictos_clases'],
                $data['nivel_proteccion'],
                $data['expansion_internacional']
            ]);
        }
    }
}
