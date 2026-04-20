<?php
/**
 * Modelo Oferta — ORM para la tabla `ofertas`
 */

namespace App\Models;

use Core\Database;
use PDO;

class Oferta
{
    // ── Lectura ───────────────────────────────────────────────────────────────

    public static function getAllActive(int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance()->getConnection();
        $s  = $db->prepare(
            "SELECT * FROM ofertas
             WHERE activo = 1
               AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?"
        );
        $s->execute([$limit, $offset]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAll(): array
    {
        $db = Database::getInstance()->getConnection();
        return $db->query("SELECT * FROM ofertas ORDER BY created_at DESC")
                  ->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById(int $id): ?array
    {
        $db = Database::getInstance()->getConnection();
        $s  = $db->prepare("SELECT * FROM ofertas WHERE id = ?");
        $s->execute([$id]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getNearby(float $lat, float $lng, float $radio = 10): array
    {
        $db  = Database::getInstance()->getConnection();
        $sql = "SELECT *,
                       (6371 * ACOS(
                           COS(RADIANS(?)) * COS(RADIANS(lat)) *
                           COS(RADIANS(lng) - RADIANS(?)) +
                           SIN(RADIANS(?)) * SIN(RADIANS(lat))
                       )) AS dist_km
                FROM ofertas
                WHERE activo = 1
                  AND lat IS NOT NULL AND lng IS NOT NULL
                  AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())
                HAVING dist_km <= ?
                ORDER BY dist_km ASC";
        $s = $db->prepare($sql);
        $s->execute([$lat, $lng, $lat, $radio]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getStats(): array
    {
        $db  = Database::getInstance()->getConnection();
        $row = $db->query(
            "SELECT
                COUNT(*)                                           AS total,
                SUM(activo = 1 AND (fecha_expiracion IS NULL OR fecha_expiracion >= CURDATE())) AS activas,
                SUM(activo = 0 OR fecha_expiracion < CURDATE())   AS vencidas
             FROM ofertas"
        )->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['total' => 0, 'activas' => 0, 'vencidas' => 0];
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    public static function create(array $data): bool
    {
        $db = Database::getInstance()->getConnection();
        $s  = $db->prepare(
            "INSERT INTO ofertas
                (nombre, descripcion, precio_normal, precio_oferta,
                 fecha_inicio, fecha_expiracion, imagen_url,
                 lat, lng, business_id, activo, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())"
        );
        return $s->execute([
            $data['nombre'],
            $data['descripcion']      ?? null,
            $data['precio_normal']    ?? null,
            $data['precio_oferta']    ?? null,
            $data['fecha_inicio']     ?? date('Y-m-d'),
            $data['fecha_expiracion'] ?? null,
            $data['imagen_url']       ?? null,
            isset($data['lat']) && $data['lat'] !== '' ? (float)$data['lat'] : null,
            isset($data['lng']) && $data['lng'] !== '' ? (float)$data['lng'] : null,
            isset($data['business_id']) && $data['business_id'] !== '' ? (int)$data['business_id'] : null,
            isset($data['activo']) ? (int)(bool)$data['activo'] : 1,
        ]);
    }

    public static function update(int $id, array $data): bool
    {
        $db   = Database::getInstance()->getConnection();
        $cols = ['nombre','descripcion','precio_normal','precio_oferta',
                 'fecha_inicio','fecha_expiracion','imagen_url','lat','lng','business_id'];
        $upd  = []; $vals = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $data)) {
                $upd[]  = "$col = ?";
                $vals[] = ($data[$col] === '') ? null : $data[$col];
            }
        }
        if (isset($data['activo'])) { $upd[] = 'activo = ?'; $vals[] = (int)(bool)$data['activo']; }
        if (empty($upd)) return false;
        $upd[]  = 'updated_at = NOW()';
        $vals[] = $id;
        return $db->prepare("UPDATE ofertas SET " . implode(', ', $upd) . " WHERE id = ?")
                  ->execute($vals);
    }

    public static function activate(int $id): bool
    {
        return Database::getInstance()->getConnection()
            ->prepare("UPDATE ofertas SET activo = 1, updated_at = NOW() WHERE id = ?")
            ->execute([$id]);
    }

    public static function deactivate(int $id): bool
    {
        return Database::getInstance()->getConnection()
            ->prepare("UPDATE ofertas SET activo = 0, updated_at = NOW() WHERE id = ?")
            ->execute([$id]);
    }

    public static function delete(int $id): bool
    {
        return Database::getInstance()->getConnection()
            ->prepare("DELETE FROM ofertas WHERE id = ?")
            ->execute([$id]);
    }
}
