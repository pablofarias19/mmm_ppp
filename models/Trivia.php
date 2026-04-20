<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class Trivia {
    protected $table = 'trivia_games';

    /**
     * Obtiene todos los juegos de trivia activos
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getAllActive($limit = 100, $offset = 0) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM trivia_games
                    WHERE activo = 1
                    ORDER BY fecha_creacion DESC
                    LIMIT ? OFFSET ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([(int)$limit, (int)$offset]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Trivia::getAllActive - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los juegos de trivia (admin)
     * @return array
     */
    public static function getAll() {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM trivia_games
                    ORDER BY fecha_creacion DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Trivia::getAll - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un juego de trivia por ID con sus preguntas
     * @param int $id
     * @return array|null
     */
    public static function getById($id) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM trivia_games WHERE id = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);

            $trivia = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($trivia) {
                // Obtener preguntas
                $sql_preguntas = "SELECT * FROM trivia_preguntas
                                  WHERE trivia_id = ?
                                  ORDER BY id ASC";

                $stmt_preguntas = $db->prepare($sql_preguntas);
                $stmt_preguntas->execute([$id]);

                $trivia['preguntas'] = $stmt_preguntas->fetchAll(PDO::FETCH_ASSOC);
            }

            return $trivia;
        } catch (Exception $e) {
            error_log("Error en Trivia::getById - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un nuevo juego de trivia
     * @param array $data
     * @return int|false ID creado o false
     */
    public static function create($data) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "INSERT INTO trivia_games
                    (titulo, descripcion, dificultad, tiempo_limite, activo, fecha_creacion)
                    VALUES (?, ?, ?, ?, 1, NOW())";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $data['titulo'] ?? null,
                $data['descripcion'] ?? null,
                $data['dificultad'] ?? 'medio',
                $data['tiempo_limite'] ?? 30
            ]);

            return $result ? $db->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("Error en Trivia::create - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un juego de trivia
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
            if (isset($data['dificultad'])) {
                $update[] = "dificultad = ?";
                $params[] = $data['dificultad'];
            }
            if (isset($data['tiempo_limite'])) {
                $update[] = "tiempo_limite = ?";
                $params[] = (int)$data['tiempo_limite'];
            }
            if (isset($data['activo'])) {
                $update[] = "activo = ?";
                $params[] = $data['activo'];
            }

            if (empty($update)) return false;

            $params[] = $id;

            $sql = "UPDATE trivia_games SET " . implode(", ", $update) . " WHERE id = ?";

            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error en Trivia::update - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactiva un juego de trivia
     * @param int $id
     * @return bool
     */
    public static function deactivate($id) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "UPDATE trivia_games SET activo = 0 WHERE id = ?";

            $stmt = $db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error en Trivia::deactivate - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas de un juego de trivia
     * @param int $id
     * @return array
     */
    public static function getStats($id) {
        try {
            $db = Database::getInstance()->getConnection();

            $stats = [
                'trivia_id' => $id,
                'total_jugadores' => 0,
                'promedio_puntos' => 0,
                'top_jugadores' => []
            ];

            // Total de jugadores únicos
            $sql = "SELECT COUNT(DISTINCT user_id) as total
                    FROM trivia_stats
                    WHERE trivia_id = ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_jugadores'] = (int)$result['total'];

            // Promedio de puntos
            $sql_avg = "SELECT AVG(puntos) as promedio
                        FROM trivia_stats
                        WHERE trivia_id = ?";

            $stmt_avg = $db->prepare($sql_avg);
            $stmt_avg->execute([$id]);
            $result_avg = $stmt_avg->fetch(PDO::FETCH_ASSOC);
            $stats['promedio_puntos'] = round($result_avg['promedio'] ?? 0, 2);

            // Top 10 jugadores
            $sql_top = "SELECT user_id, puntos, fecha_juego
                        FROM trivia_stats
                        WHERE trivia_id = ?
                        ORDER BY puntos DESC
                        LIMIT 10";

            $stmt_top = $db->prepare($sql_top);
            $stmt_top->execute([$id]);
            $stats['top_jugadores'] = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

            return $stats;
        } catch (Exception $e) {
            error_log("Error en Trivia::getStats - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registra un resultado de trivia
     * @param int $trivia_id
     * @param int $user_id
     * @param int $puntos
     * @param int $respuestas_correctas
     * @return bool
     */
    public static function recordScore($trivia_id, $user_id, $puntos, $respuestas_correctas) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "INSERT INTO trivia_stats
                    (trivia_id, user_id, puntos, respuestas_correctas, fecha_juego)
                    VALUES (?, ?, ?, ?, NOW())";

            $stmt = $db->prepare($sql);
            return $stmt->execute([$trivia_id, $user_id, $puntos, $respuestas_correctas]);
        } catch (Exception $e) {
            error_log("Error en Trivia::recordScore - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el mejor score del usuario en una trivia
     * @param int $trivia_id
     * @param int $user_id
     * @return array|null
     */
    public static function getUserBestScore($trivia_id, $user_id) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM trivia_stats
                    WHERE trivia_id = ? AND user_id = ?
                    ORDER BY puntos DESC
                    LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute([$trivia_id, $user_id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Trivia::getUserBestScore - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene ranking global de trivias
     * @param int $limit
     * @return array
     */
    public static function getGlobalRanking($limit = 50) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT user_id, SUM(puntos) as puntos_totales, COUNT(*) as juegos_jugados,
                           AVG(puntos) as promedio
                    FROM trivia_stats
                    GROUP BY user_id
                    ORDER BY puntos_totales DESC
                    LIMIT ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([(int)$limit]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Trivia::getGlobalRanking - " . $e->getMessage());
            return [];
        }
    }
}
