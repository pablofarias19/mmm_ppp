<?php
// models/Monetization.php
class Monetization {
    public $id;
    public $marca_id;
    public $fuentes_ingresos;
    public $escalabilidad;
    public $margen_potencial;
    public $valor_activo;
    public $created_at;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->marca_id = $data['marca_id'] ?? null;
        $this->fuentes_ingresos = $data['fuentes_ingresos'] ?? '';
        $this->escalabilidad = $data['escalabilidad'] ?? '';
        $this->margen_potencial = $data['margen_potencial'] ?? '';
        $this->valor_activo = $data['valor_activo'] ?? '';
        $this->created_at = $data['created_at'] ?? null;
    }

    public static function findByMarca($db, $marca_id) {
        $stmt = $db->prepare('SELECT * FROM monetizacion WHERE marca_id = ?');
        $stmt->execute([$marca_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($db, $data) {
        $existing = self::findByMarca($db, $data['marca_id']);
        if ($existing) {
            $stmt = $db->prepare('UPDATE monetizacion SET fuentes_ingresos=?, escalabilidad=?, margen_potencial=?, valor_activo=? WHERE marca_id=?');
            return $stmt->execute([
                $data['fuentes_ingresos'],
                $data['escalabilidad'],
                $data['margen_potencial'],
                $data['valor_activo'],
                $data['marca_id']
            ]);
        } else {
            $stmt = $db->prepare('INSERT INTO monetizacion (marca_id, fuentes_ingresos, escalabilidad, margen_potencial, valor_activo) VALUES (?, ?, ?, ?, ?)');
            return $stmt->execute([
                $data['marca_id'],
                $data['fuentes_ingresos'],
                $data['escalabilidad'],
                $data['margen_potencial'],
                $data['valor_activo']
            ]);
        }
    }
}
