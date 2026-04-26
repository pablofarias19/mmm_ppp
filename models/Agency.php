<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class Agency {
    const VALID_STATUSES = ['activa','inactiva'];

    public static function getAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        try {
            $db = Database::getInstance()->getConnection();
            $where = []; $params = [];
            if (!empty($filters['status'])) { $where[] = 'status = ?'; $params[] = $filters['status']; }
            if (!empty($filters['area']))   { $where[] = 'area = ?';   $params[] = $filters['area'];   }
            $sql = 'SELECT * FROM agencies'
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . ' ORDER BY name ASC LIMIT ? OFFSET ?';
            $params[] = $limit; $params[] = $offset;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Agency::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public static function getById(int $id): ?array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM agencies WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('Agency::getById - ' . $e->getMessage());
            return null;
        }
    }

    public static function create(array $data) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT INTO agencies (name, area, description, website, email, phone, status)
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
            error_log('Agency::create - ' . $e->getMessage());
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
            $stmt = $db->prepare('UPDATE agencies SET ' . implode(', ', $fields) . ' WHERE id = ?');
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log('Agency::update - ' . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('DELETE FROM agencies WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('Agency::delete - ' . $e->getMessage());
            return false;
        }
    }

    public static function getSectors(int $agencyId): array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM agency_sector WHERE agency_id = ?');
            $stmt->execute([$agencyId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public static function syncSectors(int $agencyId, array $sectors): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            $db->prepare('DELETE FROM agency_sector WHERE agency_id = ?')->execute([$agencyId]);
            $stmt = $db->prepare('INSERT INTO agency_sector (agency_id, sector_type, sector_id) VALUES (?, ?, ?)');
            foreach ($sectors as $s) {
                $stmt->execute([$agencyId, $s['sector_type'], (int)$s['sector_id']]);
            }
            $db->commit();
            return true;
        } catch (Exception $e) {
            try { $db->rollBack(); } catch (Exception $ex) {}
            error_log('Agency::syncSectors - ' . $e->getMessage());
            return false;
        }
    }

    public static function getBySector(string $sectorType, int $sectorId): array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'SELECT a.* FROM agencies a
                 JOIN agency_sector ag ON ag.agency_id = a.id
                 WHERE ag.sector_type = ? AND ag.sector_id = ?
                 ORDER BY a.name ASC'
            );
            $stmt->execute([$sectorType, $sectorId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Agency::getBySector - ' . $e->getMessage());
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
