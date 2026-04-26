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
                // Obtener preguntas con sus opciones parseadas
                $sql_preguntas = "SELECT * FROM preguntas_encuesta
                                  WHERE encuesta_id = ?
                                  ORDER BY orden ASC, id ASC";

                $stmt_preguntas = $db->prepare($sql_preguntas);
                $stmt_preguntas->execute([$id]);

                $rows = $stmt_preguntas->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as &$row) {
                    // Normalizar campo de texto: texto_pregunta es la columna real
                    $row['pregunta'] = $row['texto_pregunta'] ?? ($row['pregunta'] ?? '');
                    // Parsear opciones (separadas por coma)
                    $row['opciones_array'] = self::parseOpciones($row['opciones'] ?? '');
                }
                unset($row);
                $encuesta['preguntas'] = $rows;
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
     * Parsea el campo opciones (separadas por coma) y devuelve un array limpio
     * @param string $opciones
     * @return array
     */
    public static function parseOpciones($opciones) {
        if (empty($opciones)) return [];
        $items = explode(',', $opciones);
        return array_values(array_filter(array_map('trim', $items), 'strlen'));
    }

    /**
     * Guarda preguntas con sus opciones para una encuesta.
     * Borra las preguntas existentes antes de insertar (sólo en la acción crear).
     * @param int $encuesta_id
     * @param array $preguntas  Array de ['texto'=>..., 'opciones'=>[...]]
     * @return bool
     */
    public static function savePreguntas($encuesta_id, array $preguntas) {
        try {
            $db = Database::getInstance()->getConnection();
            $orden = 1;
            foreach ($preguntas as $p) {
                $texto = trim($p['texto'] ?? '');
                if ($texto === '') continue;
                $opts = array_filter(array_map('trim', (array)($p['opciones'] ?? [])), 'strlen');
                $opts = array_slice(array_values($opts), 0, 5);
                $opts_str = implode(',', $opts);
                $stmt = $db->prepare(
                    "INSERT INTO preguntas_encuesta
                        (encuesta_id, texto_pregunta, tipo, opciones, orden)
                     VALUES (?, ?, 'opcion_multiple', ?, ?)"
                );
                $stmt->execute([$encuesta_id, $texto, $opts_str, $orden]);
                $orden++;
            }
            return true;
        } catch (Exception $e) {
            error_log("Error en Encuesta::savePreguntas - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina todas las preguntas de una encuesta
     * @param int $encuesta_id
     * @return bool
     */
    public static function deletePreguntas($encuesta_id) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM preguntas_encuesta WHERE encuesta_id = ?");
            return $stmt->execute([$encuesta_id]);
        } catch (Exception $e) {
            error_log("Error en Encuesta::deletePreguntas - " . $e->getMessage());
            return false;
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

            // Intentar con columnas de panel detalle (migración 023)
            try {
                $sql = "INSERT INTO encuestas
                        (titulo, descripcion, lat, lng, fecha_creacion, fecha_expiracion, link, activo,
                         detalle_activo, graficos_config)
                        VALUES (?, ?, ?, ?, NOW(), ?, ?, 1, ?, ?)";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    $data['titulo'] ?? null,
                    $data['descripcion'] ?? null,
                    $data['lat'] ?? null,
                    $data['lng'] ?? null,
                    $data['fecha_expiracion'] ?? null,
                    $data['link'] ?? null,
                    isset($data['detalle_activo']) ? (int)$data['detalle_activo'] : 1,
                    $data['graficos_config'] ?? 'barras,torta,tendencia',
                ]);
            } catch (\PDOException $e) {
                // Columnas de migración 023 aún no aplicadas — fallback
                if ($e->getCode() !== '42S22' && (int)($e->errorInfo[1] ?? 0) !== 1054) throw $e;
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
                    $data['link'] ?? null,
                ]);
            }

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
            // Panel detalle (migración 023)
            if (array_key_exists('detalle_activo', $data)) {
                $update[] = "detalle_activo = ?";
                $params[] = (int)$data['detalle_activo'];
            }
            if (array_key_exists('graficos_config', $data)) {
                $update[] = "graficos_config = ?";
                $params[] = $data['graficos_config'];
            }

            if (empty($update)) return false;

            $update[] = "updated_at = NOW()";
            $params[] = $id;

            $sql = "UPDATE encuestas SET " . implode(", ", $update) . " WHERE id = ?";

            $stmt = $db->prepare($sql);
            try {
                return $stmt->execute($params);
            } catch (\PDOException $e) {
                // Columnas de migración 023 aún no aplicadas — reconstruir sin ellas
                if ($e->getCode() !== '42S22' && (int)($e->errorInfo[1] ?? 0) !== 1054) throw $e;
                // Quitar detalle_activo y graficos_config del update
                $updateFallback = [];
                $paramsFallback = [];
                $skip = ['detalle_activo', 'graficos_config'];
                $skipIdx = [];
                foreach ($update as $i => $clause) {
                    $colName = trim(explode('=', $clause)[0]);
                    if (in_array($colName, $skip)) { $skipIdx[] = $i; continue; }
                    $updateFallback[] = $clause;
                    $paramsFallback[] = $params[$i];
                }
                if (empty($updateFallback)) return false;
                $sqlFb = "UPDATE encuestas SET " . implode(", ", $updateFallback) . " WHERE id = ?";
                $stmtFb = $db->prepare($sqlFb);
                return $stmtFb->execute($paramsFallback);
            }
        } catch (Exception $e) {
            error_log("Error en Encuesta::update - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina físicamente una encuesta y sus preguntas/respuestas/participaciones.
     * Compatible con el esquema legacy (preguntas_encuesta / respuestas_encuesta /
     * encuesta_participaciones) y con el esquema de migration.sql
     * (encuesta_questions / encuesta_responses).
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        try {
            $db = Database::getInstance()->getConnection();

            // Helper: ejecuta una sentencia ignorando "table doesn't exist" (42S02 / 1146)
            $safeExec = function (string $sql, array $params) use ($db): void {
                try {
                    $db->prepare($sql)->execute($params);
                } catch (\PDOException $e) {
                    $sqlState = $e->getCode();
                    $errCode  = (int)($e->errorInfo[1] ?? 0);
                    if ($sqlState !== '42S02' && $errCode !== 1146) {
                        throw $e;
                    }
                    // Tabla inexistente (migración pendiente) — registrar para diagnóstico
                    error_log("Encuesta::delete — tabla ausente (migración pendiente): " . $e->getMessage());
                }
            };

            // ── Esquema legacy ───────────────────────────────────────────────────
            // Borrar respuestas vinculadas a preguntas de esta encuesta
            $safeExec(
                "DELETE r FROM respuestas_encuesta r
                 INNER JOIN preguntas_encuesta p ON p.id = r.pregunta_id
                 WHERE p.encuesta_id = ?",
                [$id]
            );
            // Borrar participaciones (legacy)
            $safeExec("DELETE FROM encuesta_participaciones WHERE encuesta_id = ?", [$id]);
            // Borrar preguntas (legacy)
            $safeExec("DELETE FROM preguntas_encuesta WHERE encuesta_id = ?", [$id]);

            // ── Esquema nuevo (migration.sql) ────────────────────────────────────
            // Las FK ON DELETE CASCADE de encuesta_questions → encuesta_responses
            // eliminan las respuestas automáticamente, pero borramos explícitamente
            // en caso de que no estén definidas las FK en la instalación.
            $safeExec(
                "DELETE r FROM encuesta_responses r
                 INNER JOIN encuesta_questions q ON q.id = r.question_id
                 WHERE q.encuesta_id = ?",
                [$id]
            );
            $safeExec("DELETE FROM encuesta_questions WHERE encuesta_id = ?", [$id]);

            // ── Borrar la encuesta ───────────────────────────────────────────────
            return $db->prepare("DELETE FROM encuestas WHERE id = ?")->execute([$id]);
        } catch (Exception $e) {
            error_log("Error en Encuesta::delete - " . $e->getMessage());
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
                // Normalizar campo texto
                $pregunta['pregunta'] = $pregunta['texto_pregunta'] ?? ($pregunta['pregunta'] ?? '');

                // Contar respuestas por opción
                $sql_respuestas = "SELECT respuesta, COUNT(*) as cantidad
                                   FROM respuestas_encuesta
                                   WHERE pregunta_id = ?
                                   GROUP BY respuesta";

                $stmt_respuestas = $db->prepare($sql_respuestas);
                $stmt_respuestas->execute([$pregunta_id]);
                $respuestas_rows = $stmt_respuestas->fetchAll(PDO::FETCH_ASSOC);

                // Indexar respuestas por texto para lookup rápido
                $resp_map = [];
                foreach ($respuestas_rows as $r) {
                    $resp_map[$r['respuesta']] = (int)$r['cantidad'];
                }

                // Construir lista completa con opciones predefinidas (incluye ceros)
                $opciones_def = self::parseOpciones($pregunta['opciones'] ?? '');
                $respuestas = [];
                if (!empty($opciones_def)) {
                    foreach ($opciones_def as $opt) {
                        $respuestas[] = [
                            'respuesta' => $opt,
                            'cantidad'  => $resp_map[$opt] ?? 0,
                        ];
                    }
                    // Agregar respuestas no previstas (si existiera alguna)
                    foreach ($resp_map as $txt => $cnt) {
                        $found = false;
                        foreach ($respuestas as $r) {
                            if ($r['respuesta'] === $txt) { $found = true; break; }
                        }
                        if (!$found) $respuestas[] = ['respuesta' => $txt, 'cantidad' => $cnt];
                    }
                } else {
                    $respuestas = $respuestas_rows;
                }

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

            // Intentar con fecha_respuesta (migración 023)
            try {
                $sql = "INSERT INTO respuestas_encuesta
                        (pregunta_id, user_id, respuesta, fecha_respuesta)
                        VALUES (?, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                return $stmt->execute([$pregunta_id, $user_id, $respuesta]);
            } catch (\PDOException $e) {
                if ($e->getCode() !== '42S22' && (int)($e->errorInfo[1] ?? 0) !== 1054) throw $e;
                $sql = "INSERT INTO respuestas_encuesta
                        (pregunta_id, user_id, respuesta)
                        VALUES (?, ?, ?)";
                $stmt = $db->prepare($sql);
                return $stmt->execute([$pregunta_id, $user_id, $respuesta]);
            }
        } catch (Exception $e) {
            error_log("Error en Encuesta::addResponse - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene datos de tendencia temporal para una encuesta (respuestas por día)
     * @param int $id
     * @param string $agrupacion 'dia' | 'semana' | 'mes'
     * @return array
     */
    public static function getTrend($id, $agrupacion = 'dia') {
        try {
            $db = Database::getInstance()->getConnection();

            switch ($agrupacion) {
                case 'mes':
                    $dateExpr = "DATE_FORMAT(r.fecha_respuesta, '%Y-%m')";
                    $dateLabel = "DATE_FORMAT(r.fecha_respuesta, '%m/%Y')";
                    break;
                case 'semana':
                    $dateExpr = "DATE_FORMAT(r.fecha_respuesta, '%Y-%u')";
                    $dateLabel = "CONCAT('S', WEEK(r.fecha_respuesta), '/', YEAR(r.fecha_respuesta))";
                    break;
                default: // dia
                    $dateExpr = "DATE(r.fecha_respuesta)";
                    $dateLabel = "DATE_FORMAT(r.fecha_respuesta, '%d/%m/%Y')";
                    break;
            }

            $sql = "SELECT $dateLabel AS periodo, COUNT(*) AS cantidad
                    FROM respuestas_encuesta r
                    JOIN preguntas_encuesta p ON p.id = r.pregunta_id
                    WHERE p.encuesta_id = ? AND r.fecha_respuesta IS NOT NULL
                    GROUP BY $dateExpr
                    ORDER BY $dateExpr ASC
                    LIMIT 90";

            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en Encuesta::getTrend - " . $e->getMessage());
            return [];
        }
    }
}
