<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class CommercialSector {
    protected $table = 'commercial_sectors';

    const VALID_TYPES    = ['retail','servicios','gastronomia','tecnologia','salud','educacion','finanzas','transporte','turismo','otro'];
    const VALID_STATUSES = ['proyecto','activo','potencial'];

    public static function getAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        try {
            $db = Database::getInstance()->getConnection();
            $where = []; $params = [];
            if (!empty($filters['type']))   { $where[] = 'type = ?';   $params[] = $filters['type'];   }
            if (!empty($filters['status'])) { $where[] = 'status = ?'; $params[] = $filters['status']; }
            $sql = 'SELECT * FROM commercial_sectors'
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
            $params[] = $limit; $params[] = $offset;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('CommercialSector::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public static function getById(int $id): ?array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM commercial_sectors WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('CommercialSector::getById - ' . $e->getMessage());
            return null;
        }
    }

    public static function create(array $data) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT INTO commercial_sectors (name, type, subtype, status, jurisdiction, description, radar_enabled)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ok = $stmt->execute([
                $data['name'],
                $data['type'],
                $data['subtype']      ?? null,
                $data['status']       ?? 'potencial',
                $data['jurisdiction'] ?? null,
                $data['description']  ?? null,
                isset($data['radar_enabled']) ? (int)$data['radar_enabled'] : 0,
            ]);
            return $ok ? (int)$db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log('CommercialSector::create - ' . $e->getMessage());
            return false;
        }
    }

    public static function update(int $id, array $data): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $fields = []; $params = [];
            $allowed = ['name','type','subtype','status','jurisdiction','description','radar_enabled'];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            if (empty($fields)) return false;
            $params[] = $id;
            $stmt = $db->prepare('UPDATE commercial_sectors SET ' . implode(', ', $fields) . ' WHERE id = ?');
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log('CommercialSector::update - ' . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('DELETE FROM commercial_sectors WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('CommercialSector::delete - ' . $e->getMessage());
            return false;
        }
    }

    public static function validate(array $data, bool $requireAll = true): array {
        $errors = [];
        if ($requireAll || isset($data['name'])) {
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') $errors[] = 'El campo "name" es obligatorio.';
            elseif (mb_strlen($name) > 255) $errors[] = 'El campo "name" no puede superar 255 caracteres.';
        }
        if ($requireAll || isset($data['type'])) {
            if (empty($data['type']) || !in_array($data['type'], self::VALID_TYPES, true))
                $errors[] = 'El campo "type" debe ser uno de: ' . implode(', ', self::VALID_TYPES) . '.';
        }
        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES, true))
            $errors[] = 'El campo "status" debe ser uno de: ' . implode(', ', self::VALID_STATUSES) . '.';
        return $errors;
    }
}
