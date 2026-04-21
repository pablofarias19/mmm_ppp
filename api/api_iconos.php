<?php
/**
 * API Iconos - VERSIÓN ROBUSTA
 * Carga dinámica desde BD con FALLBACK completo
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Helper functions FIRST
function respond_success($data, $message = "OK") {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}

function respond_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// Mapeo de iconos por defecto (fallback completo)
$defaultIcons = [
    // Gastronomía
    'restaurante'    => ['emoji' => '🍽️', 'color' => '#e67e22'],
    'cafeteria'      => ['emoji' => '☕',  'color' => '#d35400'],
    'bar'            => ['emoji' => '🍺',  'color' => '#8e44ad'],
    'panaderia'      => ['emoji' => '🥐',  'color' => '#c0392b'],
    'heladeria'      => ['emoji' => '🍦',  'color' => '#3498db'],
    'pizzeria'       => ['emoji' => '🍕',  'color' => '#e74c3c'],
    // Comercio
    'supermercado'   => ['emoji' => '🛒',  'color' => '#27ae60'],
    'comercio'       => ['emoji' => '🛍️', 'color' => '#e74c3c'],
    'autos_venta'    => ['emoji' => '🚗',  'color' => '#2980b9'],
    'motos_venta'    => ['emoji' => '🏍️', 'color' => '#8e44ad'],
    'indumentaria'   => ['emoji' => '👕',  'color' => '#9b59b6'],
    'verduleria'     => ['emoji' => '🥦',  'color' => '#2ecc71'],
    'carniceria'     => ['emoji' => '🥩',  'color' => '#c0392b'],
    'pastas'         => ['emoji' => '🍝',  'color' => '#e67e22'],
    'ferreteria'     => ['emoji' => '🔧',  'color' => '#7f8c8d'],
    'electronica'    => ['emoji' => '📱',  'color' => '#2980b9'],
    'muebleria'      => ['emoji' => '🛋️', 'color' => '#8e6914'],
    'floristeria'    => ['emoji' => '💐',  'color' => '#e91e63'],
    'libreria'       => ['emoji' => '📖',  'color' => '#1abc9c'],
    'productora_audiovisual' => ['emoji' => '🎥', 'color' => '#6c5ce7'],
    'escuela_musicos' => ['emoji' => '🎼', 'color' => '#8e44ad'],
    'taller_artes'   => ['emoji' => '🎨',  'color' => '#e67e22'],
    'biodecodificacion' => ['emoji' => '🧬', 'color' => '#16a085'],
    'libreria_cristiana' => ['emoji' => '📚', 'color' => '#2d6a4f'],
    'kiosco'         => ['emoji' => '🏪',  'color' => '#f39c12'],
    'joyeria'        => ['emoji' => '💍',  'color' => '#f1c40f'],
    'optica'         => ['emoji' => '👓',  'color' => '#2980b9'],
    // Salud
    'farmacia'       => ['emoji' => '💊',  'color' => '#9b59b6'],
    'hospital'       => ['emoji' => '🏥',  'color' => '#e74c3c'],
    'odontologia'    => ['emoji' => '🦷',  'color' => '#3498db'],
    'veterinaria'    => ['emoji' => '🐾',  'color' => '#27ae60'],
    'psicologo'      => ['emoji' => '🧠',  'color' => '#8e44ad'],
    'psicopedagogo'  => ['emoji' => '📚',  'color' => '#9b59b6'],
    'fonoaudiologo'  => ['emoji' => '🗣️', 'color' => '#1abc9c'],
    'grafologo'      => ['emoji' => '✍️', 'color' => '#7f8c8d'],
    // Belleza & Bienestar
    'salon_belleza'  => ['emoji' => '💇',  'color' => '#e91e63'],
    'barberia'       => ['emoji' => '💈',  'color' => '#c0392b'],
    'spa'            => ['emoji' => '💆',  'color' => '#9b59b6'],
    'gimnasio'       => ['emoji' => '💪',  'color' => '#1abc9c'],
    'danza'          => ['emoji' => '💃',  'color' => '#e91e63'],
    // Servicios profesionales
    'banco'          => ['emoji' => '🏦',  'color' => '#16a085'],
    'inmobiliaria'   => ['emoji' => '🏠',  'color' => '#27ae60'],
    'seguros'        => ['emoji' => '🛡️', 'color' => '#2980b9'],
    'abogado'        => ['emoji' => '⚖️', 'color' => '#34495e'],
    'contador'       => ['emoji' => '📊',  'color' => '#2c3e50'],
    'arquitectura'   => ['emoji' => '📐',  'color' => '#2980b9'],
    'ingenieria'     => ['emoji' => '⚙️', 'color' => '#7f8c8d'],
    'taller'         => ['emoji' => '🔩',  'color' => '#7f8c8d'],
    'herreria'       => ['emoji' => '🔨',  'color' => '#95a5a6'],
    'carpinteria'    => ['emoji' => '🪵',  'color' => '#8e6914'],
    'modista'        => ['emoji' => '🧵',  'color' => '#e91e63'],
    'construccion'   => ['emoji' => '🏗️', 'color' => '#e67e22'],
    'centro_vecinal' => ['emoji' => '🏘️', 'color' => '#27ae60'],
    'remate'         => ['emoji' => '🔨',  'color' => '#d35400'],
    // Educación & Turismo
    'academia'       => ['emoji' => '🎓',  'color' => '#2980b9'],
    'idiomas'        => ['emoji' => '🌐',  'color' => '#00b4d8'],
    'escuela'        => ['emoji' => '🏫',  'color' => '#3498db'],
    'hotel'          => ['emoji' => '🏨',  'color' => '#3498db'],
    'turismo'        => ['emoji' => '✈️', 'color' => '#16a085'],
    'cine'           => ['emoji' => '🎬',  'color' => '#8e44ad'],
    // Otros
    'automotriz'     => ['emoji' => '🚗',  'color' => '#7f8c8d'],
    'transporte'     => ['emoji' => '🚌',  'color' => '#2980b9'],
    'fotografia'     => ['emoji' => '📷',  'color' => '#34495e'],
    'eventos'        => ['emoji' => '🎉',  'color' => '#e91e63'],
    'otros'          => ['emoji' => '📍',  'color' => '#667eea'],
];

// Construir fallback data (para retornar si BD falla)
$fallbackData = [];
foreach ($defaultIcons as $type => $info) {
    $fallbackData[$type] = [
        'emoji' => $info['emoji'],
        'color' => $info['color'],
        'icon_class' => 'icon-' . $type
    ];
}

// Intentar cargar Database
if (!file_exists(__DIR__ . '/../core/Database.php')) {
    respond_success($fallbackData, "Iconos (fallback - Database.php no existe)");
    exit;
}

require_once __DIR__ . '/../core/Database.php';

$db = null;
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (Exception $e) {
    error_log("BD no disponible para iconos: " . $e->getMessage());
    respond_success($fallbackData, "Iconos (fallback - BD no disponible)");
    exit;
}

// Intentar obtener iconos de BD
try {
    $sql = "SELECT business_type, emoji, icon_class FROM business_icons ORDER BY business_type ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($result)) {
        // Tabla existe pero está vacía - usar fallback
        respond_success($fallbackData, "Iconos (fallback - tabla vacía)");
        exit;
    }

    // Combinar datos de BD con defaults para colores
    $data = [];
    foreach ($result as $icon) {
        $type = $icon['business_type'] ?? 'otros';
        $data[$type] = [
            'emoji' => $icon['emoji'] ?? ($defaultIcons[$type]['emoji'] ?? '📍'),
            'color' => $defaultIcons[$type]['color'] ?? '#667eea',
            'icon_class' => $icon['icon_class'] ?? ('icon-' . $type)
        ];
    }

    respond_success($data, "Iconos cargados desde BD");

} catch (PDOException $e) {
    error_log("Error BD iconos (tabla no existe): " . $e->getMessage());
    respond_success($fallbackData, "Iconos (fallback - tabla business_icons no existe)");
} catch (Exception $e) {
    error_log("Error general iconos: " . $e->getMessage());
    respond_success($fallbackData, "Iconos (fallback - error general)");
}
