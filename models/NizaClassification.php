<?php
// models/NizaClassification.php
// Persists Niza-classification data directly in the `brands` table (brands.id = marca_id).
// Run migrations/035_add_brand_analysis_fields_to_brands.sql to add the required columns.
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

    /**
     * Returns the Niza-classification row for the given brand.
     * `id` is aliased to `marca_id` so downstream consumers (form views,
     * controllers) can use the same key regardless of which table is queried.
     */
    public static function findByMarca($db, $marca_id) {
        try {
            $stmt = $db->prepare(
                'SELECT id AS marca_id, clase_principal,
                        clases_complementarias, riesgo_colision
                 FROM brands WHERE id = ?'
            );
            $stmt->execute([$marca_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('[NizaClassification::findByMarca] ' . $e->getMessage());
            throw $e;
        }
    }

    public static function save($db, $data) {
        try {
            $stmt = $db->prepare(
                'UPDATE brands
                 SET clase_principal=?, clases_complementarias=?, riesgo_colision=?
                 WHERE id=?'
            );
            return $stmt->execute([
                $data['clase_principal'],
                $data['clases_complementarias'],
                $data['riesgo_colision'],
                $data['marca_id'],
            ]);
        } catch (\Throwable $e) {
            error_log('[NizaClassification::save] ' . $e->getMessage());
            throw $e;
        }
    }
}
