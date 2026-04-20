<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class Evento {
    protected $table = 'eventos';

    /**
     * Obtiene todos los eventos activos con paginación
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getAllActive($limit = 100, $offset = 0) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM eventos
                    WHERE activo = 1
                    AND (fecha IS NULL OR fecha >= CURDATE() - INTERVAL 1 DAY)
                    ORDER BY fecha ASC, hora ASC, created_at DESC
                    LIMIT ? OFFSET ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([(int)$limit, (int)$offset]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Evento::getAllActive - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los eventos (admin)
     * @return array
     */
    public static function getAll() {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM eventos
                    ORDER BY fecha DESC, hora DESC, created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Evento::getAll - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un evento por ID
     * @param int $id
     * @return array|null
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM eventos WHERE id = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Evento::getById - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene eventos cercanos a una ubicación (radio en km)
     * @param float $lat
     * @param float $lng
     * @param float $radio Distancia en km
     * @return array
     */
    public static function getNearby($lat, $lng, $radio = 5) {
        try {
            $db = Database::getInstance()->getConnection();

            // Fórmula Haversine para distancia entre coordenadas
            $sql = "SELECT *,
                    (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(lat)) *
                     COS(RADIANS(lng) - RADIANS(?)) +
                     SIN(RADIANS(?)) * SIN(RADIANS(lat)))) AS distancia
                    FROM eventos
                    WHERE activo = 1
                    AND (fecha IS NULL OR fecha >= CURDATE() - INTERVAL 1 DAY)
                    HAVING distancia <= ?
                    ORDER BY distancia ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute([$lat, $lng, $lat, $radio]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Evento::getNearby - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene eventos por mes (para calendario)
     * @param int $año
     * @param int $mes
     * @return array
     */
    public static function getByMonth($año, $mes) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM eventos
                    WHERE activo = 1
                    AND YEAR(fecha) = ?
                    AND MONTH(fecha) = ?
                    ORDER BY fecha ASC, hora ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute([$año, $mes]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Evento::getByMonth - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea un nuevo evento
     * @param array $data
     * @return int|false ID del evento creado o false
     */
    public static function create($data) {
        try {
            $db = Database::getInstance()->getConnection();
            $hasMapitaId = self::columnExists($db, 'mapita_id');

            if ($hasMapitaId) {
                $sql = "INSERT INTO eventos
                        (titulo, descripcion, lat, lng, fecha, hora, organizador,
                         youtube_link, ubicacion, categoria, mapita_id, activo, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            } else {
                $sql = "INSERT INTO eventos
                        (titulo, descripcion, lat, lng, fecha, hora, organizador,
                         youtube_link, ubicacion, categoria, activo, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            }

            $stmt = $db->prepare($sql);
            if ($hasMapitaId) {
                $result = $stmt->execute([
                    $data['titulo'] ?? null,
                    $data['descripcion'] ?? null,
                    $data['lat'] ?? null,
                    $data['lng'] ?? null,
                    $data['fecha'] ?? null,
                    $data['hora'] ?? null,
                    $data['organizador'] ?? null,
                    $data['youtube_link'] ?? null,
                    $data['ubicacion'] ?? null,
                    $data['categoria'] ?? null,
                    $data['mapita_id'] ?? null
                ]);
            } else {
                $result = $stmt->execute([
                    $data['titulo'] ?? null,
                    $data['descripcion'] ?? null,
                    $data['lat'] ?? null,
                    $data['lng'] ?? null,
                    $data['fecha'] ?? null,
                    $data['hora'] ?? null,
                    $data['organizador'] ?? null,
                    $data['youtube_link'] ?? null,
                    $data['ubicacion'] ?? null,
                    $data['categoria'] ?? null
                ]);
            }

            return $result ? $db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("Error en Evento::create - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un evento
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        try {
            $db = Database::getInstance()->getConnection();
            $hasMapitaId = self::columnExists($db, 'mapita_id');

            $update = [];
            $params = [];

            if (isset($data['titulo'])) {
                $update[] = "titulo = ?";
                $params[] = $data['titulo'];
            }
            if (isset($data['descripcion'])) {
                $update[] = "descripcion = ?";
                $params[] = $data['descripcion'];
            }
            if (isset($data['lat'])) {
                $update[] = "lat = ?";
                $params[] = $data['lat'];
            }
            if (isset($data['lng'])) {
                $update[] = "lng = ?";
                $params[] = $data['lng'];
            }
            if (isset($data['fecha'])) {
                $update[] = "fecha = ?";
                $params[] = $data['fecha'];
            }
            if (isset($data['hora'])) {
                $update[] = "hora = ?";
                $params[] = $data['hora'];
            }
            if (isset($data['organizador'])) {
                $update[] = "organizador = ?";
                $params[] = $data['organizador'];
            }
            if (isset($data['youtube_link'])) {
                $update[] = "youtube_link = ?";
                $params[] = $data['youtube_link'];
            }
            if (isset($data['ubicacion'])) {
                $update[] = "ubicacion = ?";
                $params[] = $data['ubicacion'];
            }
            if (isset($data['categoria'])) {
                $update[] = "categoria = ?";
                $params[] = $data['categoria'];
            }
            if ($hasMapitaId && isset($data['mapita_id'])) {
                $update[] = "mapita_id = ?";
                $params[] = $data['mapita_id'];
            }
            if (isset($data['activo'])) {
                $update[] = "activo = ?";
                $params[] = $data['activo'];
            }

            if (empty($update)) return false;

            $update[] = "updated_at = NOW()";
            $params[] = $id;

            $sql = "UPDATE eventos SET " . implode(", ", $update) . " WHERE id = ?";

            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error en Evento::update - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactiva un evento
     * @param int $id
     * @return bool
     */
    public static function deactivate($id) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "UPDATE eventos SET activo = 0, updated_at = NOW() WHERE id = ?";

            $stmt = $db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error en Evento::deactivate - " . $e->getMessage());
            return false;
        }
    }

    private static function columnExists(PDO $db, string $column): bool {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) return false;
        try {
            $stmt = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = ? LIMIT 1");
            $stmt->execute([$column]);
            return (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene estadísticas de un evento
     * @param int $id
     * @return array
     */
    public static function getStats($id) {
        try {
            $db = Database::getInstance()->getConnection();

            $evento = self::getById($id);
            if (!$evento) return [];

            $stats = [
                'evento' => $evento,
                'detalles' => [
                    'titulo' => $evento['titulo'],
                    'fecha' => $evento['fecha'] ?? null,
                    'ubicacion' => $evento['ubicacion'],
                    'categoria' => $evento['categoria'],
                    'youtube_link' => $evento['youtube_link'],
                ]
            ];

            return $stats;
        } catch (Exception $e) {
            error_log("Error en Evento::getStats - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene eventos próximos (próximos 7 días)
     * @return array
     */
    public static function getUpcoming() {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM eventos
                    WHERE activo = 1
                    AND fecha IS NOT NULL
                    AND fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    ORDER BY fecha ASC, hora ASC
                    LIMIT 10";

            $stmt = $db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Evento::getUpcoming - " . $e->getMessage());
            return [];
        }
    }
}
