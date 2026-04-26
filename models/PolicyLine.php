<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class PolicyLine {
    const VALID_SOURCE_TYPES = ['chamber','agency'];
    const VALID_LINE_TYPES   = ['propia','gobierno'];
    const VALID_STATUSES     = ['vigente','vencida','derogada'];

    public static function getAll(array $filters = [], int $limit = 100, int $offset = 0): array {
        try {
            $db = Database::getInstance()->getConnection();
            $where = []; $params = [];
            if (!empty($filters['source_type'])) { $where[] = 'source_type = ?'; $params[] = $filters['source_type']; }
            if (!empty($filters['source_id']))   { $where[] = 'source_id = ?';   $params[] = (int)$filters['source_id']; }
            if (!empty($filters['line_type']))   { $where[] = 'line_type = ?';   $params[] = $filters['line_type']; }
            if (!empty($filters['status']))      { $where[] = 'status = ?';      $params[] = $filters['status']; }
            if (!empty($filters['area']))        { $where[] = 'area = ?';        $params[] = $filters['area']; }
            $sql = 'SELECT * FROM policy_lines'
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . ' ORDER BY published_at DESC, created_at DESC LIMIT ? OFFSET ?';
            $params[] = $limit; $params[] = $offset;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PolicyLine::getAll - ' . $e->getMessage());
            return [];
        }
    }

    public static function getById(int $id): ?array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT * FROM policy_lines WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log('PolicyLine::getById - ' . $e->getMessage());
            return null;
        }
    }

    /** Lineas aplicables a un sector (via todas las camaras y agencias vinculadas) */
    public static function getBySector(string $sectorType, int $sectorId, array $filters = []): array {
        try {
            $db = Database::getInstance()->getConnection();
            $where = []; $params = [];
            // chambers linked
            $chamberIds = $db->prepare(
                'SELECT chamber_id FROM chamber_sector WHERE sector_type = ? AND sector_id = ?'
            );
            $chamberIds->execute([$sectorType, $sectorId]);
            $cIds = array_column($chamberIds->fetchAll(PDO::FETCH_ASSOC), 'chamber_id');

            // agencies linked
            $agencyIds = $db->prepare(
                'SELECT agency_id FROM agency_sector WHERE sector_type = ? AND sector_id = ?'
            );
            $agencyIds->execute([$sectorType, $sectorId]);
            $aIds = array_column($agencyIds->fetchAll(PDO::FETCH_ASSOC), 'agency_id');

            if (empty($cIds) && empty($aIds)) return [];

            $orCond = [];
            if (!empty($cIds)) {
                $ph = implode(',', array_fill(0, count($cIds), '?'));
                $orCond[] = "(source_type = 'chamber' AND source_id IN ($ph))";
                $params = array_merge($params, $cIds);
            }
            if (!empty($aIds)) {
                $ph = implode(',', array_fill(0, count($aIds), '?'));
                $orCond[] = "(source_type = 'agency' AND source_id IN ($ph))";
                $params = array_merge($params, $aIds);
            }
            $where[] = '(' . implode(' OR ', $orCond) . ')';

            if (!empty($filters['line_type'])) { $where[] = 'line_type = ?'; $params[] = $filters['line_type']; }
            if (!empty($filters['status']))    { $where[] = 'status = ?';    $params[] = $filters['status']; }

            $sql = 'SELECT * FROM policy_lines WHERE ' . implode(' AND ', $where)
                 . ' ORDER BY published_at DESC, created_at DESC';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('PolicyLine::getBySector - ' . $e->getMessage());
            return [];
        }
    }

    public static function create(array $data) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                'INSERT INTO policy_lines
                    (source_type, source_id, title, summary, line_type, jurisdiction,
                     source_link, published_at, valid_from, valid_until, tags, area, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ok = $stmt->execute([
                $data['source_type'], (int)$data['source_id'],
                $data['title'],
                $data['summary']      ?? null,
                $data['line_type']    ?? 'propia',
                $data['jurisdiction'] ?? null,
                $data['source_link']  ?? null,
                $data['published_at'] ?? null,
                $data['valid_from']   ?? null,
                $data['valid_until']  ?? null,
                $data['tags']         ?? null,
                $data['area']         ?? null,
                $data['status']       ?? 'vigente',
            ]);
            return $ok ? (int)$db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log('PolicyLine::create - ' . $e->getMessage());
            return false;
        }
    }

    public static function update(int $id, array $data): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $fields = []; $params = [];
            $allowed = ['source_type','source_id','title','summary','line_type','jurisdiction',
                        'source_link','published_at','valid_from','valid_until','tags','area','status'];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
            }
            if (empty($fields)) return false;
            $params[] = $id;
            $stmt = $db->prepare('UPDATE policy_lines SET ' . implode(', ', $fields) . ' WHERE id = ?');
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log('PolicyLine::update - ' . $e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('DELETE FROM policy_lines WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('PolicyLine::delete - ' . $e->getMessage());
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
        if ($requireAll || isset($data['title'])) {
            $title = trim((string)($data['title'] ?? ''));
            if ($title === '') $errors[] = 'El campo "title" es obligatorio.';
        }
        if (isset($data['line_type']) && !in_array($data['line_type'], self::VALID_LINE_TYPES, true))
            $errors[] = 'line_type debe ser propia o gobierno.';
        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES, true))
            $errors[] = 'status debe ser vigente, vencida o derogada.';
        return $errors;
    }
}
