<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class Chamber {
    const VALID_STATUSES = ['activa','inactiva'];

    public static function getAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        try {
            $db = Database::getInstance()->getConnection();
            $where = []; $params = [];
            if (!empty($filters['status'])) { $where[] = 'status = ?'; $params[] = $filters['status']; }
            if (!empty($filters['area']))   { $where[] = 'area = ?';   $params[] = $filters['area'];   }
            $sql = 'SELECT * FROM chambers'
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . ' ORDER BY name ASC LIMIT ? OFFSET ?';
            $params[] = $limit; $params[] = $offset;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Chamber::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public static function getById(int $id): ?array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM chambers WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('Chamber::getById - ' . $e->getMessage());
            return null;
        }
    }

    public static function create(array $data) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT INTO chambers (name, area, description, website, email, phone, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ok = $stmt->execute([
                $data['name'], $data['area'],
                $data['description'] ?? null, $data['website'] ?? null,
                $data['email'] ?? null, $data['phone'] ?? null,
                $data['status'] ?? 'activa',
            ]);
            return $ok ? (int)$db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log('Chamber::create - ' . $e->getMessage());
            return false;
        }
    }

    public static function update(int $id, array $data): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $fields = []; $params = [];
            foreach (['name','area','description','website','email','phone','status'] as $f) {
                if (array_key_exists($f, $data)) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
            }
            if (empty($fields)) return false;
            $params[] = $id;
            $stmt = $db->prepare('UPDATE chambers SET ' . implode(', ', $fields) . ' WHERE id = ?');
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log('Chamber::update - ' . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('DELETE FROM chambers WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('Chamber::delete - ' . $e->getMessage());
            return false;
        }
    }

    /** Devuelve sectores vinculados a esta camara */
    public static function getSectors(int $chamberId): array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM chamber_sector WHERE chamber_id = ?');
            $stmt->execute([$chamberId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /** Vincula la camara a un sector (upsert) */
    public static function linkSector(int $chamberId, string $sectorType, int $sectorId): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT IGNORE INTO chamber_sector (chamber_id, sector_type, sector_id) VALUES (?, ?, ?)'
            );
            return $stmt->execute([$chamberId, $sectorType, $sectorId]);
        } catch (Exception $e) {
            error_log('Chamber::linkSector - ' . $e->getMessage());
            return false;
        }
    }

    /** Desvincula la camara de un sector */
    public static function unlinkSector(int $chamberId, string $sectorType, int $sectorId): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'DELETE FROM chamber_sector WHERE chamber_id = ? AND sector_type = ? AND sector_id = ?'
            );
            return $stmt->execute([$chamberId, $sectorType, $sectorId]);
        } catch (Exception $e) {
            error_log('Chamber::unlinkSector - ' . $e->getMessage());
            return false;
        }
    }

    /** Reemplaza todos los sectores vinculados */
    public static function syncSectors(int $chamberId, array $sectors): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            $db->prepare('DELETE FROM chamber_sector WHERE chamber_id = ?')->execute([$chamberId]);
            $stmt = $db->prepare('INSERT INTO chamber_sector (chamber_id, sector_type, sector_id) VALUES (?, ?, ?)');
            foreach ($sectors as $s) {
                $stmt->execute([$chamberId, $s['sector_type'], (int)$s['sector_id']]);
            }
            $db->commit();
            return true;
        } catch (Exception $e) {
            try { $db->rollBack(); } catch (Exception $ex) {}
            error_log('Chamber::syncSectors - ' . $e->getMessage());
            return false;
        }
    }

    /** Camaras vinculadas a un sector */
    public static function getBySector(string $sectorType, int $sectorId): array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'SELECT c.* FROM chambers c
                 JOIN chamber_sector cs ON cs.chamber_id = c.id
                 WHERE cs.sector_type = ? AND cs.sector_id = ?
                 ORDER BY c.name ASC'
            );
            $stmt->execute([$sectorType, $sectorId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Chamber::getBySector - ' . $e->getMessage());
            return [];
        }
    }

    public static function validate(array $data, bool $requireAll = true): array {
        $errors = [];
        if ($requireAll || isset($data['name'])) {
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') $errors[] = 'El campo "name" es obligatorio.';
        }
        if ($requireAll || isset($data['area'])) {
            $area = trim((string)($data['area'] ?? ''));
            if ($area === '') $errors[] = 'El campo "area" es obligatorio.';
        }
        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES, true))
            $errors[] = 'El campo "status" debe ser activa o inactiva.';
        return $errors;
    }
}
