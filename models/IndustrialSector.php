<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class IndustrialSector {
    protected $table = 'industrial_sectors';

    const VALID_TYPES            = ['mineria','energia','agro','infraestructura','inmobiliario','industrial'];
    const VALID_STATUSES         = ['proyecto','activo','potencial'];
    const VALID_INVESTMENT_LEVELS = ['bajo','medio','alto'];
    const VALID_RISK_LEVELS      = ['bajo','medio','alto'];

    /**
     * Lista todos los sectores con paginación y filtrado opcional.
     */
    public static function getAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        try {
            $db = Database::getInstance()->getConnection();
            $where  = [];
            $params = [];

            if (!empty($filters['type'])) {
                $where[]  = 'type = ?';
                $params[] = $filters['type'];
            }
            if (!empty($filters['status'])) {
                $where[]  = 'status = ?';
                $params[] = $filters['status'];
            }

            $sql = 'SELECT * FROM industrial_sectors'
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error en IndustrialSector::getAll - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un sector por ID.
     */
    public static function getById(int $id): ?array {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM industrial_sectors WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('Error en IndustrialSector::getById - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un nuevo sector industrial.
     * Devuelve el ID insertado o false.
     */
    public static function create(array $data) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT INTO industrial_sectors
                    (name, type, subtype, geometry, status, investment_level, risk_level, jurisdiction, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $result = $stmt->execute([
                $data['name'],
                $data['type'],
                $data['subtype']          ?? null,
                $data['geometry'],
                $data['status']           ?? 'potencial',
                $data['investment_level'] ?? 'medio',
                $data['risk_level']       ?? 'medio',
                $data['jurisdiction']     ?? null,
                $data['description']      ?? null,
            ]);
            return $result ? (int)$db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log('Error en IndustrialSector::create - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un sector existente.
     */
    public static function update(int $id, array $data): bool {
        try {
            $db     = Database::getInstance()->getConnection();
            $fields = [];
            $params = [];

            $allowed = ['name','type','subtype','geometry','status','investment_level','risk_level','jurisdiction','description'];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            if (empty($fields)) return false;

            $params[] = $id;
            $stmt = $db->prepare('UPDATE industrial_sectors SET ' . implode(', ', $fields) . ' WHERE id = ?');
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log('Error en IndustrialSector::update - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un sector por ID.
     */
    public static function delete(int $id): bool {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare('DELETE FROM industrial_sectors WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('Error en IndustrialSector::delete - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Valida los datos de entrada y devuelve array de errores.
     */
    public static function validate(array $data, bool $requireAll = true): array {
        $errors = [];

        if ($requireAll || isset($data['name'])) {
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') {
                $errors[] = 'El campo "name" es obligatorio.';
            } elseif (mb_strlen($name) > 255) {
                $errors[] = 'El campo "name" no puede superar 255 caracteres.';
            }
        }

        if ($requireAll || isset($data['type'])) {
            if (empty($data['type']) || !in_array($data['type'], self::VALID_TYPES, true)) {
                $errors[] = 'El campo "type" debe ser uno de: ' . implode(', ', self::VALID_TYPES) . '.';
            }
        }

        if (isset($data['subtype']) && $data['subtype'] !== null && mb_strlen((string)$data['subtype']) > 100) {
            $errors[] = 'El campo "subtype" no puede superar 100 caracteres.';
        }

        if ($requireAll || isset($data['geometry'])) {
            $geoError = self::validateGeometry($data['geometry'] ?? null);
            if ($geoError) $errors[] = $geoError;
        }

        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES, true)) {
            $errors[] = 'El campo "status" debe ser uno de: ' . implode(', ', self::VALID_STATUSES) . '.';
        }

        if (isset($data['investment_level']) && !in_array($data['investment_level'], self::VALID_INVESTMENT_LEVELS, true)) {
            $errors[] = 'El campo "investment_level" debe ser uno de: ' . implode(', ', self::VALID_INVESTMENT_LEVELS) . '.';
        }

        if (isset($data['risk_level']) && !in_array($data['risk_level'], self::VALID_RISK_LEVELS, true)) {
            $errors[] = 'El campo "risk_level" debe ser uno de: ' . implode(', ', self::VALID_RISK_LEVELS) . '.';
        }

        return $errors;
    }

    /**
     * Valida que el geometry sea JSON válido con claves "type" y "coordinates" o equivalente GeoJSON.
     * Acepta Geometry objects y Feature objects.
     */
    private static function validateGeometry($geometry): ?string {
        if ($geometry === null || $geometry === '') {
            return 'El campo "geometry" es obligatorio.';
        }
        if (is_array($geometry)) {
            $geo = $geometry;
        } elseif (is_string($geometry)) {
            $geo = json_decode($geometry, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return 'El campo "geometry" no es JSON válido.';
            }
        } else {
            return 'El campo "geometry" debe ser un objeto JSON.';
        }

        if (!isset($geo['type'])) {
            return 'El campo "geometry" debe contener la clave "type".';
        }

        // Feature GeoJSON
        if ($geo['type'] === 'Feature') {
            if (!isset($geo['geometry'])) {
                return 'El campo "geometry" de tipo Feature debe contener la clave "geometry".';
            }
            return null;
        }

        // FeatureCollection
        if ($geo['type'] === 'FeatureCollection') {
            if (!isset($geo['features'])) {
                return 'El campo "geometry" de tipo FeatureCollection debe contener la clave "features".';
            }
            return null;
        }

        // Geometry object
        $geoTypes = ['Point','MultiPoint','LineString','MultiLineString','Polygon','MultiPolygon','GeometryCollection'];
        if (!in_array($geo['type'], $geoTypes, true)) {
            return 'El campo "geometry" tiene un tipo GeoJSON no reconocido.';
        }
        if ($geo['type'] !== 'GeometryCollection' && !isset($geo['coordinates'])) {
            return 'El campo "geometry" debe contener la clave "coordinates".';
        }

        return null;
    }
}
