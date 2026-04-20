<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class Encuesta {
    protected $table = 'encuestas';

    /**
     * Obtiene todas las encuestas activas con paginación
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getAllActive($limit = 100, $offset = 0) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM encuestas
                    WHERE activo = 1
                    AND (fecha_expiracion IS NULL OR fecha_expiracion >= NOW())
                    ORDER BY fecha_creacion DESC
                    LIMIT ? OFFSET ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([(int)$limit, (int)$offset]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Encuesta::getAllActive - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todas las encuestas (admin)
     * @return array
     */
    public static function getAll() {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM encuestas
                    ORDER BY fecha_creacion DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Encuesta::getAll - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una encuesta por ID con sus preguntas
     * @param int $id
     * @return array|null
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM encuestas WHERE id = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);

            $encuesta = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($encuesta) {
                // Obtener preguntas
                $sql_preguntas = "SELECT * FROM preguntas_encuesta
                                  WHERE encuesta_id = ?
                                  ORDER BY id ASC";

                $stmt_preguntas = $db->prepare($sql_preguntas);
                $stmt_preguntas->execute([$id]);

                $encuesta['preguntas'] = $stmt_preguntas->fetchAll(PDO::FETCH_ASSOC);
            }

            return $encuesta;
        } catch (Exception $e) {
            error_log("Error en Encuesta::getById - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene encuestas cercanas a una ubicación (radio en km)
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
                    FROM encuestas
                    WHERE activo = 1
                    AND (fecha_expiracion IS NULL OR fecha_expiracion >= NOW())
                    HAVING distancia <= ?
                    ORDER BY distancia ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute([$lat, $lng, $lat, $radio]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Encuesta::getNearby - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea una nueva encuesta
     * @param array $data
     * @return int|false ID de la encuesta creada o false
     */
    public static function create($data) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "INSERT INTO encuestas
                    (titulo, descripcion, lat, lng, fecha_creacion, fecha_expiracion, link, activo)
                    VALUES (?, ?, ?, ?, NOW(), ?, ?, 1)";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $data['titulo'] ?? null,
                $data['descripcion'] ?? null,
                $data['lat'] ?? null,
                $data['lng'] ?? null,
                $data['fecha_expiracion'] ?? null,
                $data['link'] ?? null
            ]);

            return $result ? $db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("Error en Encuesta::create - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza una encuesta
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        try {
            $db = Database::getInstance()->getConnection();

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
            if (isset($data['fecha_expiracion'])) {
                $update[] = "fecha_expiracion = ?";
                $params[] = $data['fecha_expiracion'];
            }
            if (isset($data['activo'])) {
                $update[] = "activo = ?";
                $params[] = $data['activo'];
            }
            if (isset($data['link'])) {
                $update[] = "link = ?";
                $params[] = $data['link'];
            }

            if (empty($update)) return false;

            $update[] = "updated_at = NOW()";
            $params[] = $id;

            $sql = "UPDATE encuestas SET " . implode(", ", $update) . " WHERE id = ?";

            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error en Encuesta::update - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactiva una encuesta
     * @param int $id
     * @return bool
     */
    public static function deactivate($id) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "UPDATE encuestas SET activo = 0, updated_at = NOW() WHERE id = ?";

            $stmt = $db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error en Encuesta::deactivate - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas de una encuesta
     * @param int $id
     * @return array
     */
    public static function getStats($id) {
        try {
            $db = Database::getInstance()->getConnection();

            $stats = [
                'total_participantes' => 0,
                'preguntas' => []
            ];

            // Total de participantes únicos
            $sql = "SELECT COUNT(DISTINCT user_id) as total
                    FROM encuesta_participaciones
                    WHERE encuesta_id = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_participantes'] = (int)$result['total'];

            // Estadísticas por pregunta
            $sql_preguntas = "SELECT p.*,
                              COUNT(r.id) as respuestas_totales
                              FROM preguntas_encuesta p
                              LEFT JOIN respuestas_encuesta r ON p.id = r.pregunta_id
                              WHERE p.encuesta_id = ?
                              GROUP BY p.id";

            $stmt_preguntas = $db->prepare($sql_preguntas);
            $stmt_preguntas->execute([$id]);
            $preguntas = $stmt_preguntas->fetchAll(PDO::FETCH_ASSOC);

            foreach ($preguntas as $pregunta) {
                $pregunta_id = $pregunta['id'];

                // Contar respuestas por opción
                $sql_respuestas = "SELECT respuesta, COUNT(*) as cantidad
                                   FROM respuestas_encuesta
                                   WHERE pregunta_id = ?
                                   GROUP BY respuesta";

                $stmt_respuestas = $db->prepare($sql_respuestas);
                $stmt_respuestas->execute([$pregunta_id]);
                $respuestas = $stmt_respuestas->fetchAll(PDO::FETCH_ASSOC);

                $pregunta['respuestas'] = $respuestas;
                $stats['preguntas'][] = $pregunta;
            }

            return $stats;
        } catch (Exception $e) {
            error_log("Error en Encuesta::getStats - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registra una participación en la encuesta
     * @param int $encuesta_id
     * @param int $user_id
     * @return bool
     */
    public static function addParticipant($encuesta_id, $user_id) {
        try {
            $db = Database::getInstance()->getConnection();

            // Verificar si ya participó
            $sql_check = "SELECT id FROM encuesta_participaciones
                          WHERE encuesta_id = ? AND user_id = ?";

            $stmt_check = $db->prepare($sql_check);
            $stmt_check->execute([$encuesta_id, $user_id]);

            if ($stmt_check->fetch()) {
                return false; // Ya participó
            }

            // Agregar participante
            $sql = "INSERT INTO encuesta_participaciones
                    (encuesta_id, user_id, fecha_participacion)
                    VALUES (?, ?, NOW())";

            $stmt = $db->prepare($sql);
            return $stmt->execute([$encuesta_id, $user_id]);
        } catch (Exception $e) {
            error_log("Error en Encuesta::addParticipant - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra una respuesta a una pregunta
     * @param int $pregunta_id
     * @param int $user_id
     * @param string $respuesta
     * @return bool
     */
    public static function addResponse($pregunta_id, $user_id, $respuesta) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "INSERT INTO respuestas_encuesta
                    (pregunta_id, user_id, respuesta)
                    VALUES (?, ?, ?)";

            $stmt = $db->prepare($sql);
            return $stmt->execute([$pregunta_id, $user_id, $respuesta]);
        } catch (Exception $e) {
            error_log("Error en Encuesta::addResponse - " . $e->getMessage());
            return false;
        }
    }
}
