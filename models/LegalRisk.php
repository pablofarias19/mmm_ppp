<?php
// models/LegalRisk.php
// Persists legal-risk data directly in the `brands` table (brands.id = marca_id).
// Run migrations/035_add_brand_analysis_fields_to_brands.sql to add the required columns.
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

    /**
     * Returns the legal-risk row for the given brand.
     * `id` is aliased to `marca_id` so downstream consumers can use the same key.
     */
    public static function findByMarca($db, $marca_id) {
        try {
            $stmt = $db->prepare(
                'SELECT id AS marca_id, riesgo_oposicion,
                        riesgo_nulidad, riesgo_infraccion, estrategias_defensivas
                 FROM brands WHERE id = ?'
            );
            $stmt->execute([$marca_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('[LegalRisk::findByMarca] ' . $e->getMessage());
            throw $e;
        }
    }

    public static function save($db, $data) {
        try {
            $stmt = $db->prepare(
                'UPDATE brands
                 SET riesgo_oposicion=?, riesgo_nulidad=?, riesgo_infraccion=?, estrategias_defensivas=?
                 WHERE id=?'
            );
            return $stmt->execute([
                $data['riesgo_oposicion'],
                $data['riesgo_nulidad'],
                $data['riesgo_infraccion'],
                $data['estrategias_defensivas'],
                $data['marca_id']
            ]);
        } catch (\Throwable $e) {
            error_log('[LegalRisk::save] ' . $e->getMessage());
            throw $e;
        }
    }
}
