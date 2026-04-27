<?php
// models/Monetization.php
// Persists monetization data directly in the `brands` table (brands.id = marca_id).
// Run migrations/035_add_brand_analysis_fields_to_brands.sql to add the required columns.
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

    /**
     * Returns the monetization row for the given brand.
     * `id` is aliased to `marca_id` so downstream consumers can use the same key.
     */
    public static function findByMarca($db, $marca_id) {
        try {
            $stmt = $db->prepare(
                'SELECT id AS marca_id, valor_activo,
                        fuentes_ingresos, escalabilidad, margen_potencial
                 FROM brands WHERE id = ?'
            );
            $stmt->execute([$marca_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('[Monetization::findByMarca] ' . $e->getMessage());
            throw $e;
        }
    }

    public static function save($db, $data) {
        try {
            $stmt = $db->prepare(
                'UPDATE brands
                 SET fuentes_ingresos=?, escalabilidad=?, margen_potencial=?, valor_activo=?
                 WHERE id=?'
            );
            return $stmt->execute([
                $data['fuentes_ingresos'],
                $data['escalabilidad'],
                $data['margen_potencial'],
                $data['valor_activo'],
                $data['marca_id']
            ]);
        } catch (\Throwable $e) {
            error_log('[Monetization::save] ' . $e->getMessage());
            throw $e;
        }
    }
}
