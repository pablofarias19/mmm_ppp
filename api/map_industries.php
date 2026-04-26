<?php
/**
 * API de Industrias para mapa — ubicaciones de industrias activas con coordenadas de su negocio vinculado
 *
 * GET /api/map_industries.php
 *   [?sector_id=N]    (filtro por sector industrial específico)
 *   [?sector_type=X]  (filtro por tipo de sector: mineria, energia, agro, etc.)
 *
 * Solo devuelve industrias con estado 'activa' y cuyo negocio vinculado tiene lat/lng.
 */

ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
session_start();

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

function mi_ok($data, string $msg = 'OK'): void {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}

function mi_err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Throwable $e) {
    mi_err('Base de datos no disponible', 503);
}

// Verificar tablas necesarias
try {
    $db->query('SELECT 1 FROM industries LIMIT 1');
} catch (Throwable $e) {
    mi_err('Tabla industries no encontrada. Ejecutar migrations/015_industries.sql', 503);
}

$sectorId   = isset($_GET['sector_id'])   ? (int)$_GET['sector_id']        : 0;
$sectorType = isset($_GET['sector_type']) ? trim($_GET['sector_type'])      : '';

$sql = '
    SELECT
        i.id            AS industry_id,
        i.name          AS industry_name,
        i.description,
        i.industrial_sector_id,
        i.business_id,
        s.name          AS sector_name,
        s.type          AS sector_type,
        b.lat,
        b.lng,
        b.name          AS business_name,
        b.address
    FROM industries i
    LEFT JOIN industrial_sectors s ON i.industrial_sector_id = s.id
    LEFT JOIN businesses         b ON i.business_id = b.id
    WHERE i.status = \'activa\'
      AND b.lat  IS NOT NULL
      AND b.lng  IS NOT NULL
      AND b.visible = 1
';

$params = [];

if ($sectorId > 0) {
    $sql      .= ' AND i.industrial_sector_id = ?';
    $params[]  = $sectorId;
} elseif ($sectorType !== '') {
    $sql      .= ' AND s.type = ?';
    $params[]  = $sectorType;
}

$sql .= ' ORDER BY i.id DESC LIMIT 500';

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    mi_err('Error al consultar industrias: ' . $e->getMessage());
}

// Forzar tipos numéricos para lat/lng
foreach ($rows as &$row) {
    $row['lat'] = $row['lat'] !== null ? (float)$row['lat'] : null;
    $row['lng'] = $row['lng'] !== null ? (float)$row['lng'] : null;
}
unset($row);

mi_ok($rows);
