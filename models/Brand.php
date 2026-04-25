<?php
// models/Brand.php
class Brand {
    public $id;
    public $nombre;
    public $rubro;
    public $ubicacion;
    public $lat;
    public $lng;
    public $estado;
    public $usuario_id;
    public $created_at;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->nombre = $data['nombre'] ?? '';
        $this->rubro = $data['rubro'] ?? '';
        $this->ubicacion = $data['ubicacion'] ?? '';
        $this->lat = $data['lat'] ?? null;
        $this->lng = $data['lng'] ?? null;
        $this->estado = $data['estado'] ?? '';
        $this->usuario_id = $data['usuario_id'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }

    public static function all($db) {
        $stmt = $db->query('SELECT * FROM marcas');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($db, $id) {
        $stmt = $db->prepare('SELECT * FROM marcas WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function allWithNiza($db) {
        $sql = "SELECT m.*, cn.clase_principal, cn.clases_complementarias, cn.riesgo_colision,
                      aa.distintividad, aa.riesgo_confusion, aa.nivel_proteccion,
                      mn.fuentes_ingresos, mn.escalabilidad, mn.margen_potencial, mn.valor_activo,
                      rl.riesgo_oposicion, rl.riesgo_nulidad, rl.riesgo_infraccion
               FROM marcas m
               LEFT JOIN clasificacion_niza cn ON m.id = cn.marca_id
               LEFT JOIN analisis_marcario aa ON m.id = aa.marca_id
               LEFT JOIN monetizacion mn ON m.id = mn.marca_id
               LEFT JOIN riesgo_legal rl ON m.id = rl.marca_id
               WHERE m.estado = 'activa'";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allWithCoordinates($db) {
        $queries = [];
        $hasBrands = self::tableExists($db, 'brands');
        $hasMarcas = self::tableExists($db, 'marcas');

        $hasBrandsLat = $hasBrands && self::columnExists($db, 'brands', 'lat');
        $hasBrandsLng = $hasBrands && self::columnExists($db, 'brands', 'lng');
        if ($hasBrands && $hasBrandsLat && $hasBrandsLng) {
            $hasBrandsVisible = self::columnExists($db, 'brands', 'visible');
            $queries[] = "
                SELECT
                    b.id,
                    " . self::columnExpr($db, 'brands', 'b', 'nombre', 'name') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'rubro') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'lat') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'lng') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'website') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'clase_principal') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'nivel_proteccion') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'riesgo_oposicion') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'valor_activo') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'mapita_id') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'extended_description') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'tiene_zona') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'zona_radius_km') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'tiene_licencia') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'es_franquicia') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'zona_exclusiva') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'zona_exclusiva_radius_km') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'created_at') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'description') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'estado') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'inpi_registrada') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'inpi_numero') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'inpi_fecha_registro') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'inpi_vencimiento') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'inpi_tipo') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'franchise_details') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'whatsapp') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'founded_year') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'country_code') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'language_code') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'currency_code') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'registry_authority') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'registry_number') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'registry_date') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'registry_expiry') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'registry_type') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'crear_franquicia') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'franquicia_descripcion') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'franquicia_condiciones') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'franquicia_exclusividad') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'franquicia_territorio') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'franquicia_productos') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'franquicia_garantias') . ",
                    " . self::columnExpr($db, 'brands', 'b', 'franquicia_url') . ",
                    'brands' AS fuente
                FROM brands b
                WHERE " . ($hasBrandsVisible ? 'b.visible = 1' : '1=1') . "
                  AND b.lat IS NOT NULL
                  AND b.lng IS NOT NULL
            ";
        }

        $hasMarcasLat = $hasMarcas && self::columnExists($db, 'marcas', 'lat');
        $hasMarcasLng = $hasMarcas && self::columnExists($db, 'marcas', 'lng');
        if ($hasMarcas && $hasMarcasLat && $hasMarcasLng) {
            $hasClasificacionNiza = self::tableExists($db, 'clasificacion_niza');
            $hasAnalisisMarcario = self::tableExists($db, 'analisis_marcario');
            $hasMonetizacion = self::tableExists($db, 'monetizacion');
            $hasRiesgoLegal = self::tableExists($db, 'riesgo_legal');

            $canJoinClasificacionNiza = $hasClasificacionNiza && self::columnExists($db, 'clasificacion_niza', 'marca_id');
            $canJoinAnalisisMarcario = $hasAnalisisMarcario && self::columnExists($db, 'analisis_marcario', 'marca_id');
            $canJoinMonetizacion = $hasMonetizacion && self::columnExists($db, 'monetizacion', 'marca_id');
            $canJoinRiesgoLegal = $hasRiesgoLegal && self::columnExists($db, 'riesgo_legal', 'marca_id');

            $joinClasificacionNiza = $canJoinClasificacionNiza ? 'LEFT JOIN clasificacion_niza cn ON m.id = cn.marca_id' : '';
            $joinAnalisisMarcario = $canJoinAnalisisMarcario ? 'LEFT JOIN analisis_marcario aa ON m.id = aa.marca_id' : '';
            $joinMonetizacion = $canJoinMonetizacion ? 'LEFT JOIN monetizacion mn ON m.id = mn.marca_id' : '';
            $joinRiesgoLegal = $canJoinRiesgoLegal ? 'LEFT JOIN riesgo_legal rl ON m.id = rl.marca_id' : '';

            $clasePrincipalExpr = ($canJoinClasificacionNiza && self::columnExists($db, 'clasificacion_niza', 'clase_principal')) ? 'cn.clase_principal AS clase_principal' : 'NULL AS clase_principal';
            $nivelProteccionExpr = ($canJoinAnalisisMarcario && self::columnExists($db, 'analisis_marcario', 'nivel_proteccion')) ? 'aa.nivel_proteccion AS nivel_proteccion' : 'NULL AS nivel_proteccion';
            $riesgoOposicionExpr = ($canJoinRiesgoLegal && self::columnExists($db, 'riesgo_legal', 'riesgo_oposicion')) ? 'rl.riesgo_oposicion AS riesgo_oposicion' : 'NULL AS riesgo_oposicion';
            $valorActivoExpr = ($canJoinMonetizacion && self::columnExists($db, 'monetizacion', 'valor_activo')) ? 'mn.valor_activo AS valor_activo' : 'NULL AS valor_activo';

            $queries[] = "
                SELECT
                    m.id,
                    " . self::columnExpr($db, 'marcas', 'm', 'nombre', 'name') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'rubro') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'lat') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'lng') . ",
                    NULL AS website,
                    {$clasePrincipalExpr},
                    {$nivelProteccionExpr},
                    {$riesgoOposicionExpr},
                    {$valorActivoExpr},
                    " . self::columnExpr($db, 'marcas', 'm', 'mapita_id') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'extended_description') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'tiene_zona') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'zona_radius_km') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'tiene_licencia') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'es_franquicia') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'zona_exclusiva') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'zona_exclusiva_radius_km') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'created_at') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'description') . ",
                    " . self::columnExpr($db, 'marcas', 'm', 'estado') . ",
                    NULL AS inpi_registrada,
                    NULL AS inpi_numero,
                    NULL AS inpi_fecha_registro,
                    NULL AS inpi_vencimiento,
                    NULL AS inpi_tipo,
                    NULL AS franchise_details,
                    " . self::columnExpr($db, 'marcas', 'm', 'whatsapp') . ",
                    NULL AS founded_year,
                    NULL AS country_code,
                    NULL AS language_code,
                    NULL AS currency_code,
                    NULL AS registry_authority,
                    NULL AS registry_number,
                    NULL AS registry_date,
                    NULL AS registry_expiry,
                    NULL AS registry_type,
                    NULL AS crear_franquicia,
                    NULL AS franquicia_descripcion,
                    NULL AS franquicia_condiciones,
                    NULL AS franquicia_exclusividad,
                    NULL AS franquicia_territorio,
                    NULL AS franquicia_productos,
                    NULL AS franquicia_garantias,
                    NULL AS franquicia_url,
                    'marcas' AS fuente
                FROM marcas m
                {$joinClasificacionNiza}
                {$joinAnalisisMarcario}
                {$joinMonetizacion}
                {$joinRiesgoLegal}
                WHERE m.lat IS NOT NULL
                  AND m.lng IS NOT NULL
            ";
        }

        if (!$queries) {
            return [];
        }

        $sql = implode("\nUNION ALL\n", $queries) . "\nORDER BY created_at DESC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static $schemaCache = [];

    private static function tableExists($db, string $table) {
        if (!self::isSafeIdentifier($table)) return false;
        $key = "t:{$table}";
        if (array_key_exists($key, self::$schemaCache)) return self::$schemaCache[$key];
        try {
            $stmt = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
            $stmt->execute([$table]);
            return self::$schemaCache[$key] = (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return self::$schemaCache[$key] = false;
        }
    }

    private static function columnExists($db, string $table, string $column) {
        if (!self::isSafeIdentifier($table)) return false;
        if (!self::isSafeIdentifier($column)) return false;
        $key = "c:{$table}.{$column}";
        if (array_key_exists($key, self::$schemaCache)) return self::$schemaCache[$key];
        try {
            $stmt = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
            $stmt->execute([$table, $column]);
            return self::$schemaCache[$key] = (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return self::$schemaCache[$key] = false;
        }
    }

    private static function columnExpr($db, string $table, string $alias, string $column, ?string $as = null) {
        $out = $as ?: $column;
        $safeOut = self::isSafeIdentifier($out) ? $out : 'value';
        if (!self::isSafeIdentifier($table)) return "NULL AS {$safeOut}";
        if (!self::isSafeIdentifier($alias)) return "NULL AS {$safeOut}";
        if (!self::isSafeIdentifier($column)) return "NULL AS {$safeOut}";
        return self::columnExists($db, $table, $column) ? "{$alias}.{$column} AS {$out}" : "NULL AS {$out}";
    }

    private static function isSafeIdentifier(string $value) {
        return preg_match('/^[a-zA-Z0-9_]+$/', $value) === 1;
    }

    public static function create($db, $data) {
        $stmt = $db->prepare('INSERT INTO marcas (nombre, rubro, ubicacion, lat, lng, estado, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['nombre'],
            $data['rubro'],
            $data['ubicacion'],
            $data['lat'] ?? null,
            $data['lng'] ?? null,
            $data['estado'] ?? 'activa',
            $data['usuario_id']
        ]);
        return $db->lastInsertId();
    }

    public static function update($db, $id, $data) {
        $stmt = $db->prepare('UPDATE marcas SET nombre = ?, rubro = ?, ubicacion = ?, lat = ?, lng = ?, estado = ? WHERE id = ?');
        return $stmt->execute([
            $data['nombre'],
            $data['rubro'],
            $data['ubicacion'],
            $data['lat'] ?? null,
            $data['lng'] ?? null,
            $data['estado'],
            $id
        ]);
    }

    public static function delete($db, $id) {
        $stmt = $db->prepare('DELETE FROM marcas WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
