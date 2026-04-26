<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class Competency {
    const VALID_ROLES        = ['aprobar','rechazar','controlar','auditar','sancionar','dictamen','emitir','fiscalizar'];
    const VALID_SOURCE_TYPES = ['chamber','agency'];

    public static function getAll(array $filters = [], int $limit = 200, int $offset = 0): array {
        try {
            $db = Database::getInstance()->getConnection();
            $where = []; $params = [];
            if (!empty($filters['source_type'])) { $where[] = 'source_type = ?'; $params[] = $filters['source_type']; }
            if (!empty($filters['source_id']))   { $where[] = 'source_id = ?';   $params[] = (int)$filters['source_id']; }
            if (!empty($filters['role']))        { $where[] = 'role = ?';        $params[] = $filters['role']; }
            $sql = 'SELECT * FROM competencies'
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . ' ORDER BY organism ASC, role ASC LIMIT ? OFFSET ?';
            $params[] = $limit; $params[] = $offset;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Competency::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public static function getById(int $id): ?array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM competencies WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('Competency::getById - ' . $e->getMessage());
            return null;
        }
    }

    /** Competencias de camaras/agencias vinculadas a un sector */
    public static function getBySector(string $sectorType, int $sectorId): array {
        try {
            $db = Database::getInstance()->getConnection();

            $chamberIds = $db->prepare(
                'SELECT chamber_id FROM chamber_sector WHERE sector_type = ? AND sector_id = ?'
            );
            $chamberIds->execute([$sectorType, $sectorId]);
            $cIds = array_column($chamberIds->fetchAll(PDO::FETCH_ASSOC), 'chamber_id');

            $agencyIds = $db->prepare(
                'SELECT agency_id FROM agency_sector WHERE sector_type = ? AND sector_id = ?'
            );
            $agencyIds->execute([$sectorType, $sectorId]);
            $aIds = array_column($agencyIds->fetchAll(PDO::FETCH_ASSOC), 'agency_id');

            if (empty($cIds) && empty($aIds)) return [];

            $orCond = []; $params = [];
            if (!empty($cIds)) {
                $ph = implode(',', array_fill(0, count($cIds), '?'));
                $orCond[] = "(source_type='chamber' AND source_id IN ($ph))";
                $params = array_merge($params, $cIds);
            }
            if (!empty($aIds)) {
                $ph = implode(',', array_fill(0, count($aIds), '?'));
                $orCond[] = "(source_type='agency' AND source_id IN ($ph))";
                $params = array_merge($params, $aIds);
            }

            $sql = 'SELECT * FROM competencies WHERE ' . implode(' OR ', $orCond)
                 . ' ORDER BY organism ASC, role ASC';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Competency::getBySector - ' . $e->getMessage());
            return [];
        }
    }

    public static function create(array $data) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT INTO competencies
                    (source_type, source_id, role, organism, organ, responsible, scope, legal_basis)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ok = $stmt->execute([
                $data['source_type'], (int)$data['source_id'],
                $data['role'], $data['organism'],
                $data['organ']       ?? null,
                $data['responsible'] ?? null,
                $data['scope']       ?? null,
                $data['legal_basis'] ?? null,
            ]);
            return $ok ? (int)$db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log('Competency::create - ' . $e->getMessage());
            return false;
        }
    }

    public static function update(int $id, array $data): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $fields = []; $params = [];
            foreach (['source_type','source_id','role','organism','organ','responsible','scope','legal_basis'] as $f) {
                if (array_key_exists($f, $data)) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
            }
            if (empty($fields)) return false;
            $params[] = $id;
            $stmt = $db->prepare('UPDATE competencies SET ' . implode(', ', $fields) . ' WHERE id = ?');
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log('Competency::update - ' . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('DELETE FROM competencies WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('Competency::delete - ' . $e->getMessage());
            return false;
        }
    }

    public static function validate(array $data, bool $requireAll = true): array {
        $errors = [];
        if ($requireAll || isset($data['source_type'])) {
            if (!in_array($data['source_type'] ?? '', self::VALID_SOURCE_TYPES, true))
                $errors[] = 'source_type debe ser chamber o agency.';
        }
        if ($requireAll || isset($data['source_id'])) {
            if (empty($data['source_id']) || (int)$data['source_id'] <= 0)
                $errors[] = 'source_id es obligatorio.';
        }
        if ($requireAll || isset($data['role'])) {
            if (!in_array($data['role'] ?? '', self::VALID_ROLES, true))
                $errors[] = 'role debe ser uno de: ' . implode(', ', self::VALID_ROLES) . '.';
        }
        if ($requireAll || isset($data['organism'])) {
            if (trim((string)($data['organism'] ?? '')) === '')
                $errors[] = 'El campo "organism" es obligatorio.';
        }
        return $errors;
    }
}
