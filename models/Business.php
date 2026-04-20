<?php
namespace App\Models;

use Core\Database;
use Exception;
use PDO;

class Business {
    protected $table = 'businesses';

    /**
     * Busca negocios en la base de datos según criterios opcionales.
     *
     * @param array $criteria Filtros (user_id, business_type, visible, status)
     * @param int   $limit    Máximo de resultados
     * @param int   $offset   Desplazamiento
     * @return array          Lista de negocios
     */
    public static function search($criteria = [], $limit = 100, $offset = 0) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM businesses";
            $params = [];
            $where = [];

            foreach ($criteria as $field => $value) {
                $allowed = ['user_id', 'business_type', 'visible', 'status'];
                if (in_array($field, $allowed)) {
                    $where[] = "$field = ?";
                    $params[] = $value;
                }
            }

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error en Business::search - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los negocios de tipo 'comercio' con sus datos específicos desde la tabla comercios.
     *
     * @return array Lista de comercios con datos extendidos
     */
    public static function getAllComerciosConDatos() {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT b.*, c.tipo_comercio, c.horario_apertura, c.horario_cierre, c.dias_cierre, c.categorias_productos
                    FROM businesses b
                    INNER JOIN comercios c ON b.id = c.business_id
                    WHERE b.visible = 1 AND b.business_type = 'comercio'
                    ORDER BY b.created_at DESC";

            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error al obtener comercios con datos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los negocios independientemente de su tipo.
     *
     * @param bool $onlyVisible Si es true, solo incluye negocios visibles.
     * @return array            Lista de todos los negocios
     */
    public static function getAllBusinesses($onlyVisible = true) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM businesses";
            $params = [];

            // Filtrar solo visibles si se requiere
            if ($onlyVisible) {
                $sql .= " WHERE visible = 1";
            }

            // Ordenar por fecha de creación descendente
            $sql .= " ORDER BY created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error en Business::getAllBusinesses - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los negocios (cualquiera sea su business_type),
     * incluyendo datos de la tabla 'comercios' si existen (LEFT JOIN).
     *
     * @param bool $onlyVisible Si es true, solo incluye negocios visibles.
     * @return array            Lista de todos los negocios con datos extra de 'comercios'.
     */
    public static function getAllWithComercioData($onlyVisible = false) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT
                        b.*,
                        c.tipo_comercio,
                        c.horario_apertura,
                        c.horario_cierre,
                        c.dias_cierre,
                        c.categorias_productos
                    FROM businesses b
                    LEFT JOIN comercios c ON b.id = c.business_id";

            if ($onlyVisible) {
                $sql .= " WHERE b.visible = 1";
            }

            $sql .= " ORDER BY b.created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Error en Business::getAllWithComercioData: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los negocios con sus fotos/attachments.
     * Devuelve array con estructura:
     * [ id, name, lat, lng, ..., photos: [url1, url2, ...] ]
     *
     * @param bool $onlyVisible Si es true, solo incluye negocios visibles
     * @return array Lista de negocios con fotos
     */
    public static function getAllWithPhotos($onlyVisible = true) {
        try {
            $db = Database::getInstance()->getConnection();

            $hasRematesTable = self::tableExists($db, 'remates');
            $hasVehiculosTable = self::tableExists($db, 'vehiculos_venta');
            $hasOfertasTable = self::tableExists($db, 'ofertas');
            $hasOfertaActivaId = self::columnExists($db, 'businesses', 'oferta_activa_id');
            $hasOfertaDestacadaFlag = self::columnExists($db, 'ofertas', 'es_destacada');

            // Obtener negocios con comercios data + extensiones opcionales
            $select = "SELECT
                        b.*,
                        c.tipo_comercio,
                        c.horario_apertura,
                        c.horario_cierre,
                        c.dias_cierre,
                        c.categorias_productos";
            $joins = " FROM businesses b
                    LEFT JOIN comercios c ON b.id = c.business_id";

            if ($hasRematesTable) {
                $select .= ",
                        r.id AS remate_id,
                        r.titulo AS remate_titulo,
                        r.descripcion AS remate_descripcion,
                        r.fecha_inicio AS remate_fecha_inicio,
                        r.fecha_fin AS remate_fecha_fin,
                        r.fecha_cierre AS remate_fecha_cierre,
                        r.activo AS remate_activo";
                $joins .= "
                    LEFT JOIN remates r ON r.id = (
                        SELECT rr.id FROM remates rr
                        WHERE rr.business_id = b.id
                        ORDER BY rr.activo DESC, rr.fecha_inicio DESC, rr.id DESC
                        LIMIT 1
                    )";
            }

            if ($hasVehiculosTable) {
                $select .= ",
                        vv.id AS vehiculo_id,
                        vv.tipo_vehiculo AS vehiculo_tipo_vehiculo,
                        vv.marca AS vehiculo_marca,
                        vv.modelo AS vehiculo_modelo,
                        vv.anio AS vehiculo_anio,
                        vv.km AS vehiculo_km,
                        vv.precio AS vehiculo_precio,
                        vv.contacto AS vehiculo_contacto";
                $joins .= "
                    LEFT JOIN vehiculos_venta vv ON vv.id = (
                        SELECT v1.id FROM vehiculos_venta v1
                        WHERE v1.business_id = b.id
                        ORDER BY v1.activo DESC, v1.created_at DESC, v1.id DESC
                        LIMIT 1
                    )";
            }

            if ($hasOfertasTable && $hasOfertaActivaId) {
                $select .= ",
                        od.id AS oferta_destacada_id,
                        od.nombre AS oferta_destacada_nombre,
                        od.descripcion AS oferta_destacada_descripcion,
                        od.precio_normal AS oferta_destacada_precio_normal,
                        od.precio_oferta AS oferta_destacada_precio_oferta,
                        od.fecha_expiracion AS oferta_destacada_fecha_expiracion,
                        od.imagen_url AS oferta_destacada_imagen_url";
                if ($hasOfertaDestacadaFlag) {
                    $select .= ",
                        od.es_destacada AS oferta_destacada_es_destacada";
                    $joins .= "
                    LEFT JOIN ofertas od ON od.id = b.oferta_activa_id AND od.activo = 1 AND od.es_destacada = 1";
                } else {
                    $joins .= "
                    LEFT JOIN ofertas od ON od.id = b.oferta_activa_id AND od.activo = 1";
                }
            }

            $sql = $select . $joins;

            if ($onlyVisible) {
                $sql .= " WHERE b.visible = 1";
            }

            $sql .= " ORDER BY b.created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute();
            $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Para cada negocio, obtener sus fotos
            foreach ($businesses as &$business) {
                $photosStmt = $db->prepare(
                    "SELECT file_path FROM attachments
                     WHERE business_id = ? AND type = 'photo'
                     ORDER BY uploaded_at ASC"
                );
                $photosStmt->execute([$business['id']]);
                $photos = $photosStmt->fetchAll(PDO::FETCH_COLUMN);
                $business['photos'] = $photos;
                $business['primary_photo'] = $photos[0] ?? null;

                // Normalizar campos opcionales para evitar warnings en front
                $business['remate_id'] = $business['remate_id'] ?? null;
                $business['vehiculo_id'] = $business['vehiculo_id'] ?? null;
                $business['oferta_destacada_id'] = $business['oferta_destacada_id'] ?? null;
            }

            return $businesses;

        } catch (Exception $e) {
            error_log("Error en Business::getAllWithPhotos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene fotos de un negocio específico
     *
     * @param int $businessId ID del negocio
     * @return array Lista de rutas de fotos
     */
    public static function getPhotos($businessId) {
        try {
            $db = Database::getInstance()->getConnection();

            $stmt = $db->prepare(
                "SELECT file_path FROM attachments
                 WHERE business_id = ? AND type = 'photo'
                 ORDER BY uploaded_at ASC"
            );
            $stmt->execute([$businessId]);

            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (Exception $e) {
            error_log("Error en Business::getPhotos: " . $e->getMessage());
            return [];
        }
    }

    private static function tableExists(PDO $db, string $table): bool {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return false;
        try {
            $stmt = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }

    private static function columnExists(PDO $db, string $table, string $column): bool {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return false;
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) return false;
        try {
            $stmt = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }
}
