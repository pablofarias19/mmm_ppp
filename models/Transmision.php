<?php
/**
 * Modelo Transmision — ORM para la tabla `transmisiones`
 */

namespace App\Models;

use Core\Database;
use PDO;

class Transmision
{
    const TIPOS = ['youtube_live', 'youtube_video', 'radio_stream', 'audio_stream', 'video_stream'];

    // ── Lectura ───────────────────────────────────────────────────────────────

    public static function getAllActive(int $limit = 50, int $offset = 0): array
    {
        $db = Database::getInstance()->getConnection();
        $s  = $db->prepare(
            "SELECT * FROM transmisiones
             WHERE activo = 1
             ORDER BY en_vivo DESC, created_at DESC
             LIMIT ? OFFSET ?"
        );
        $s->execute([$limit, $offset]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAll(): array
    {
        $db = Database::getInstance()->getConnection();
        return $db->query(
            "SELECT * FROM transmisiones ORDER BY en_vivo DESC, created_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById(int $id): ?array
    {
        $db = Database::getInstance()->getConnection();
        $s  = $db->prepare("SELECT * FROM transmisiones WHERE id = ?");
        $s->execute([$id]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getLive(): array
    {
        $db = Database::getInstance()->getConnection();
        $now = date('Y-m-d H:i:s');
        $s  = $db->prepare(
            "SELECT * FROM transmisiones
             WHERE activo = 1 AND en_vivo = 1
               AND (fecha_inicio IS NULL OR fecha_inicio <= ?)
               AND (fecha_fin   IS NULL OR fecha_fin   >= ?)
             ORDER BY created_at DESC"
        );
        $s->execute([$now, $now]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getNearby(float $lat, float $lng, float $radio = 50): array
    {
        $db  = Database::getInstance()->getConnection();
        $sql = "SELECT *,
                       (6371 * ACOS(
                           COS(RADIANS(?)) * COS(RADIANS(lat)) *
                           COS(RADIANS(lng) - RADIANS(?)) +
                           SIN(RADIANS(?)) * SIN(RADIANS(lat))
                       )) AS dist_km
                FROM transmisiones
                WHERE activo = 1 AND lat IS NOT NULL AND lng IS NOT NULL
                HAVING dist_km <= ?
                ORDER BY en_vivo DESC, dist_km ASC";
        $s = $db->prepare($sql);
        $s->execute([$lat, $lng, $lat, $radio]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getStats(): array
    {
        $db  = Database::getInstance()->getConnection();
        $row = $db->query(
            "SELECT
                COUNT(*)               AS total,
                SUM(activo = 1)        AS activas,
                SUM(en_vivo = 1)       AS en_vivo,
                SUM(activo = 0)        AS inactivas
             FROM transmisiones"
        )->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['total' => 0, 'activas' => 0, 'en_vivo' => 0, 'inactivas' => 0];
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    public static function create(array $data): bool
    {
        $db   = Database::getInstance()->getConnection();
        $tipo = in_array($data['tipo'] ?? '', self::TIPOS) ? $data['tipo'] : 'youtube_live';

        // Construir datetime desde campos separados si vienen así
        $fecha_inicio = self::buildDatetime($data, 'fecha_inicio', 'hora_inicio');
        $fecha_fin    = self::buildDatetime($data, 'fecha_fin',    'hora_fin');

        $s    = $db->prepare(
            "INSERT INTO transmisiones
                (titulo, descripcion, tipo, stream_url,
                 lat, lng, business_id, evento_id, en_vivo, activo,
                 fecha_inicio, fecha_fin, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())"
        );
        return $s->execute([
            $data['titulo'],
            $data['descripcion']  ?? null,
            $tipo,
            $data['stream_url']   ?? null,
            isset($data['lat']) && $data['lat'] !== '' ? (float)$data['lat'] : null,
            isset($data['lng']) && $data['lng'] !== '' ? (float)$data['lng'] : null,
            isset($data['business_id']) && $data['business_id'] !== '' ? (int)$data['business_id'] : null,
            isset($data['evento_id'])   && $data['evento_id']   !== '' ? (int)$data['evento_id']   : null,
            isset($data['en_vivo']) ? (int)(bool)$data['en_vivo'] : 0,
            isset($data['activo'])  ? (int)(bool)$data['activo']  : 1,
            $fecha_inicio,
            $fecha_fin,
        ]);
    }

    /**
     * Combina fecha + hora en un string datetime, o devuelve el datetime directo.
     * @param array $data
     * @param string $fecha_key
     * @param string $hora_key
     * @return string|null
     */
    private static function buildDatetime(array $data, string $fecha_key, string $hora_key): ?string
    {
        // Primero intentar como datetime combinado (campo único)
        if (!empty($data[$fecha_key]) && strlen($data[$fecha_key]) > 10) {
            // Viene como datetime-local: "2025-06-01T14:30"
            $dt = str_replace('T', ' ', $data[$fecha_key]);
            return $dt ?: null;
        }
        // Combinar fecha + hora separados
        if (!empty($data[$fecha_key])) {
            $fecha = $data[$fecha_key];
            $hora  = !empty($data[$hora_key]) ? $data[$hora_key] : '00:00';
            return $fecha . ' ' . $hora . ':00';
        }
        return null;
    }

    public static function update(int $id, array $data): bool
    {
        $db   = Database::getInstance()->getConnection();
        $cols = ['titulo','descripcion','tipo','stream_url','lat','lng','business_id','evento_id'];
        $upd  = []; $vals = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $data)) {
                $upd[]  = "$col = ?";
                $vals[] = ($data[$col] === '') ? null : $data[$col];
            }
        }
        foreach (['en_vivo','activo'] as $b) {
            if (isset($data[$b])) { $upd[] = "$b = ?"; $vals[] = (int)(bool)$data[$b]; }
        }
        foreach (['fecha_inicio','fecha_fin'] as $dt) {
            if (array_key_exists($dt, $data)) {
                $upd[]  = "$dt = ?";
                $vals[] = ($data[$dt] === '' || $data[$dt] === null)
                    ? null
                    : str_replace('T', ' ', $data[$dt]);
            }
        }
        if (empty($upd)) return false;
        $upd[]  = 'updated_at = NOW()';
        $vals[] = $id;
        return $db->prepare("UPDATE transmisiones SET " . implode(', ', $upd) . " WHERE id = ?")
                  ->execute($vals);
    }

    public static function setLive(int $id, bool $live): bool
    {
        return Database::getInstance()->getConnection()
            ->prepare("UPDATE transmisiones SET en_vivo = ?, updated_at = NOW() WHERE id = ?")
            ->execute([(int)$live, $id]);
    }

    public static function activate(int $id): bool
    {
        return Database::getInstance()->getConnection()
            ->prepare("UPDATE transmisiones SET activo = 1, updated_at = NOW() WHERE id = ?")
            ->execute([$id]);
    }

    public static function deactivate(int $id): bool
    {
        return Database::getInstance()->getConnection()
            ->prepare("UPDATE transmisiones SET activo = 0, updated_at = NOW() WHERE id = ?")
            ->execute([$id]);
    }

    public static function delete(int $id): bool
    {
        return Database::getInstance()->getConnection()
            ->prepare("DELETE FROM transmisiones WHERE id = ?")
            ->execute([$id]);
    }
}
