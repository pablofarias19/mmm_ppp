<?php
// models/BrandAnalysis.php
// Persists brand-analysis data directly in the `brands` table (brands.id = marca_id).
// Run migrations/035_add_brand_analysis_fields_to_brands.sql to add the required columns.
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

    /**
     * Returns the analysis row for the given brand.
     * `id` is aliased to `marca_id` so downstream consumers (form views,
     * controllers) can use the same key regardless of which table is queried.
     */
    public static function findByMarca($db, $marca_id) {
        try {
            $stmt = $db->prepare(
                'SELECT id AS marca_id, nivel_proteccion,
                        distintividad, riesgo_confusion, conflictos_clases, expansion_internacional
                 FROM brands WHERE id = ?'
            );
            $stmt->execute([$marca_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('[BrandAnalysis::findByMarca] ' . $e->getMessage());
            throw $e;
        }
    }

    public static function save($db, $data) {
        try {
            $stmt = $db->prepare(
                'UPDATE brands
                 SET distintividad=?, riesgo_confusion=?, conflictos_clases=?,
                     nivel_proteccion=?, expansion_internacional=?
                 WHERE id=?'
            );
            return $stmt->execute([
                $data['distintividad'],
                $data['riesgo_confusion'],
                $data['conflictos_clases'],
                $data['nivel_proteccion'],
                $data['expansion_internacional'],
                $data['marca_id']
            ]);
        } catch (\Throwable $e) {
            error_log('[BrandAnalysis::save] ' . $e->getMessage());
            throw $e;
        }
    }
}
