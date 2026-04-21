<?php
/**
 * og_image.php — Generador dinámico de imágenes Open Graph (1200×630 px)
 *
 * Parámetros GET:
 *   id        int     ID del negocio
 *   type      string  'business' (default: genérica del sitio)
 *
 * Lógica de fondo:
 *   1. Si el negocio tiene foto → foto real como fondo + overlay oscuro + texto
 *   2. Si no tiene foto         → fondo azul degradado con decoración geométrica
 *   3. Si GD no está            → redirect a /img/og-mapita.png
 */

ini_set('display_errors', 0);
error_reporting(0);

// ── Headers ───────────────────────────────────────────────────────────────────
header('Content-Type: image/png');
header('Cache-Control: public, max-age=21600');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 21600) . ' GMT');

// ── Sin GD → fallback estático ────────────────────────────────────────────────
if (!function_exists('imagecreatetruecolor')) {
    header('Location: /img/og-mapita.png');
    exit;
}

// ── Constantes ────────────────────────────────────────────────────────────────
const W = 1200;
const H = 630;

// ── Datos por defecto ─────────────────────────────────────────────────────────
$titulo    = 'Mapita';
$subtitulo = 'El mapa de negocios y marcas de tu ciudad';
$detalle   = 'Encontrá comercios, restaurantes, servicios y más cerca tuyo';
$tipo      = '';
$fotoPath  = null;   // ruta local al archivo de foto

// Color de acento según tipo (se usa en modo sin-foto)
$acentoMap = [
    'restaurante' => [231,76,60],   'cafeteria'  => [211,84,0],
    'bar'         => [142,68,173],  'panaderia'  => [211,84,0],
    'heladeria'   => [52,152,219],  'pizzeria'   => [231,76,60],
    'supermercado'=> [39,174,96],   'comercio'   => [231,76,60],
    'indumentaria'=> [155,89,182],  'ferreteria' => [127,140,141],
    'electronica' => [41,128,185],  'farmacia'   => [155,89,182],
    'hospital'    => [231,76,60],   'odontologia'=> [52,152,219],
    'veterinaria' => [39,174,96],   'salon_belleza'=>[233,30,99],
    'barberia'    => [192,57,43],   'spa'        => [155,89,182],
    'gimnasio'    => [26,188,156],  'banco'      => [22,160,133],
    'inmobiliaria'=> [39,174,96],   'hotel'      => [52,152,219],
    'academia'    => [41,128,185],  'turismo'    => [22,160,133],
    'cine'        => [142,68,173],  'taller'     => [127,140,141],
    'construccion'=> [230,126,34],  'abogado'    => [52,73,94],
];
$colorAcento = [102, 126, 234]; // default Mapita blue

// ── Cargar datos desde BD ─────────────────────────────────────────────────────
$type    = $_GET['type']     ?? '';
$id      = (int)($_GET['id']      ?? 0);
$brandId = (int)($_GET['brand_id'] ?? 0);

if (($type === 'business' && $id > 0) || ($type === 'brand' && $brandId > 0)) {
    try {
        require_once __DIR__ . '/../includes/db_helper.php';
        $db = getDbConnection();

        // ── NEGOCIO ───────────────────────────────────────────────────────────
        if ($type === 'business' && $id > 0) {
            $stmt = $db->prepare("
                SELECT name, business_type, address, description
                FROM businesses WHERE id = ? AND visible = 1
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $titulo    = $row['name'];
                $tipo      = $row['business_type'] ?? '';
                $subtitulo = ucfirst($tipo) . ($row['address'] ? ' · ' . $row['address'] : '');
                $detalle   = $row['description']
                    ? mb_substr(strip_tags($row['description']), 0, 110)
                    : 'Encontralo en Mapita · mapita.com.ar';

                if (isset($acentoMap[$tipo])) $colorAcento = $acentoMap[$tipo];

                // 1. og_cover dedicado
                $dir = __DIR__ . '/../uploads/businesses/' . $id . '/';
                foreach (['jpg','jpeg','png','webp'] as $ext) {
                    if (file_exists($dir . 'og_cover.' . $ext)) {
                        $fotoPath = $dir . 'og_cover.' . $ext; break;
                    }
                }
                // 2. Primera foto del negocio
                if (!$fotoPath) {
                    $stmtF = $db->prepare("SELECT file_path FROM attachments WHERE business_id = ? AND type = 'photo' ORDER BY id ASC LIMIT 1");
                    $stmtF->execute([$id]);
                    $fRow = $stmtF->fetch(PDO::FETCH_ASSOC);
                    if ($fRow && $fRow['file_path']) {
                        $c = __DIR__ . '/../' . ltrim($fRow['file_path'], '/');
                        if (file_exists($c)) $fotoPath = $c;
                    }
                }
            }

        // ── MARCA ─────────────────────────────────────────────────────────────
        } elseif ($type === 'brand' && $brandId > 0) {
            $row = null;
            $hasTableCheck = function_exists('mapitaTableExists');
            $hasBrandsTable = $hasTableCheck && mapitaTableExists($db, 'brands');
            $hasMarcasTable = $hasTableCheck && mapitaTableExists($db, 'marcas');

            if ($hasBrandsTable) {
                $stmt = $db->prepare("SELECT nombre, rubro, ubicacion, description AS descripcion FROM brands WHERE id = ?");
                $stmt->execute([$brandId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$row && $hasMarcasTable) {
                $stmt = $db->prepare("SELECT nombre, rubro, ubicacion, descripcion FROM marcas WHERE id = ?");
                $stmt->execute([$brandId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($row) {
                $titulo    = $row['nombre'];
                $subtitulo = ($row['rubro'] ?? '') . ($row['ubicacion'] ? ' · ' . $row['ubicacion'] : '');
                $detalle   = $row['descripcion']
                    ? mb_substr(strip_tags($row['descripcion']), 0, 110)
                    : 'Conocé esta marca en Mapita · mapita.com.ar';
                $colorAcento = [102, 126, 234]; // Mapita brand purple

                // 1. og_cover dedicado de marca
                $dir = __DIR__ . '/../uploads/brands/' . $brandId . '/';
                foreach (['jpg','jpeg','png','webp'] as $ext) {
                    if (file_exists($dir . 'og_cover.' . $ext)) {
                        $fotoPath = $dir . 'og_cover.' . $ext; break;
                    }
                }
                // 2. Foto principal de la galería de la marca
                if (!$fotoPath) {
                    $stmtF = $db->prepare("SELECT file_path FROM brand_gallery WHERE brand_id = ? AND es_principal = 1 LIMIT 1");
                    $stmtF->execute([$brandId]);
                    $fRow = $stmtF->fetch(PDO::FETCH_ASSOC);
                    if (!$fRow) {
                        // Cualquier foto de la galería
                        $stmtF = $db->prepare("SELECT file_path FROM brand_gallery WHERE brand_id = ? ORDER BY id ASC LIMIT 1");
                        $stmtF->execute([$brandId]);
                        $fRow = $stmtF->fetch(PDO::FETCH_ASSOC);
                    }
                    if ($fRow && $fRow['file_path']) {
                        $c = __DIR__ . '/../uploads/brands/' . $fRow['file_path'];
                        if (file_exists($c)) $fotoPath = $c;
                    }
                }
            }
        }

    } catch (Exception $e) {
        // Continuar con datos genéricos
    }
}

// ── Marcas sin foto → fallback estático ──────────────────────────────────────
// Para marcas, si no hay og_cover ni foto de galería, usar la imagen genérica
// del sitio en lugar de generar una imagen GD azul sin contenido real.
if ($type === 'brand' && $fotoPath === null) {
    header('Location: /img/og-mapita.png');
    exit;
}

// ── Buscar fuente TTF ─────────────────────────────────────────────────────────
$fontCandidates = [
    'C:/Windows/Fonts/arialbd.ttf',
    'C:/Windows/Fonts/arial.ttf',
    'C:/Windows/Fonts/calibrib.ttf',
    'C:/Windows/Fonts/verdanab.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
    __DIR__ . '/../fonts/OpenSans-Bold.ttf',
];
$fontBold = null;
foreach ($fontCandidates as $fp) {
    if (file_exists($fp)) { $fontBold = $fp; break; }
}

// ── Helper: dibujar texto (TTF o ASCII fallback) ──────────────────────────────
function ogText($img, $text, $x, $y, $size, $color, $font) {
    // Quitar emojis (no soportados por la mayoría de fuentes TTF)
    $text = preg_replace('/[\x{1F000}-\x{1FFFF}]/u', '', $text);
    $text = preg_replace('/[\x{2600}-\x{27BF}]/u', '', $text);
    $text = trim($text);
    if (!$text) return;

    if ($font && file_exists($font)) {
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
    } else {
        $gdFont = match(true) {
            $size >= 36 => 5,
            $size >= 24 => 4,
            $size >= 14 => 3,
            default     => 2,
        };
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        imagestring($img, $gdFont, $x, (int)($y - $size * 0.75), $ascii, $color);
    }
}

// ── Helper: overlay semitransparente con imagecopymerge ───────────────────────
function overlayRect($img, $x1, $y1, $x2, $y2, $r, $g, $b, $alpha) {
    // alpha: 0=opaco … 127=transparente (GD convention)
    $overlay = imagecreatetruecolor($x2 - $x1, $y2 - $y1);
    $color   = imagecolorallocate($overlay, $r, $g, $b);
    imagefill($overlay, 0, 0, $color);
    imagecopymerge($img, $overlay, $x1, $y1, 0, 0, $x2 - $x1, $y2 - $y1, $alpha);
    imagedestroy($overlay);
}

// ── Crear canvas ──────────────────────────────────────────────────────────────
$img = imagecreatetruecolor(W, H);

// Colores fijos
$cWhite   = imagecolorallocate($img, 255, 255, 255);
$cWhite85 = imagecolorallocate($img, 215, 225, 240);
$cWhite55 = imagecolorallocate($img, 155, 170, 195);
$cAcento  = imagecolorallocate($img, $colorAcento[0], $colorAcento[1], $colorAcento[2]);
$cDark    = imagecolorallocate($img, 15,  20,  35);
$cFooter  = imagecolorallocate($img, 10,  15,  30);

// ══════════════════════════════════════════════════════════════════════════════
// MODO A — Con foto real del negocio
// ══════════════════════════════════════════════════════════════════════════════
if ($fotoPath) {

    // Detectar tipo de imagen
    $info = @getimagesize($fotoPath);
    $srcImg = null;
    if ($info) {
        $srcImg = match($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fotoPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($fotoPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fotoPath) : null,
            default        => null,
        };
    }

    if ($srcImg) {
        // Escalar y recortar la foto para cubrir 1200×630 (object-fit: cover)
        $sw = imagesx($srcImg);
        $sh = imagesy($srcImg);

        $scaleW = W / $sw;
        $scaleH = H / $sh;
        $scale  = max($scaleW, $scaleH);

        $newW = (int)($sw * $scale);
        $newH = (int)($sh * $scale);
        $offX = (int)(($newW - W) / 2);
        $offY = (int)(($newH - H) / 3); // 1/3 desde arriba (centra mejor caras/fachadas)

        imagecopyresampled($img, $srcImg, 0, 0, $offX, $offY, W, H, $sw, $sh);
        imagedestroy($srcImg);

        // Overlay oscuro degradado sobre toda la imagen
        // Parte inferior más oscura (donde va el texto)
        overlayRect($img,   0,   0, W,   H, 10, 15, 35,  38); // oscurecer toda
        overlayRect($img,   0, 300, W,   H, 10, 15, 35,  55); // más abajo
        overlayRect($img,   0, 450, W,   H, 10, 15, 35,  70); // muy abajo

        // Franja izquierda de color de acento
        $cAcentoFull = imagecolorallocate($img, $colorAcento[0], $colorAcento[1], $colorAcento[2]);
        imagefilledrectangle($img, 0, 0, 7, H, $cAcentoFull);

        // Footer sólido
        overlayRect($img, 0, H - 68, W, H, 8, 10, 25, 15);

    } else {
        // Foto corrupta → ir al modo sin foto
        $fotoPath = null;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// MODO B — Sin foto: fondo azul con decoración geométrica
// ══════════════════════════════════════════════════════════════════════════════
if (!$fotoPath) {
    $cBg     = imagecolorallocate($img, 20,  40,  90);
    $cMid    = imagecolorallocate($img, 27,  59, 111);
    $cLight  = imagecolorallocate($img, 35,  75, 135);
    $cStripe = imagecolorallocate($img, 32,  65, 120);

    imagefill($img, 0, 0, $cBg);

    // Rectángulo central más claro (simula degradado)
    imagefilledrectangle($img, 0, H/3, W, H*2/3, $cMid);
    imagefilledrectangle($img, 0, H/4, W, H*3/4, $cLight);
    imagefilledrectangle($img, 0, 0,   W, H,      $cBg); // repintar oscuro arriba/abajo
    // Zona central suave
    for ($i = 0; $i <= 200; $i++) {
        $alpha = (int)(127 - ($i / 200) * 60);
        $stripe = imagecolorallocatealpha($img, 50, 100, 180, $alpha);
        imagefilledrectangle($img, 0, H/2 - 100 + $i, W, H/2 - 99 + $i, $stripe);
    }

    // Círculos decorativos esquina derecha
    $cCircle1 = imagecolorallocate($img, 35, 70, 130);
    $cCircle2 = imagecolorallocate($img, 30, 60, 115);
    imagefilledellipse($img, W + 60,  H + 60,  640, 640, $cCircle1);
    imagefilledellipse($img, W - 30,  H - 30,  350, 350, $cCircle2);
    imagefilledellipse($img, W - 80,  -30,      280, 280, $cCircle2);

    // Franja acento izquierda
    imagefilledrectangle($img, 0, 0, 7, H, $cAcento);

    // Línea separadora texto/decoración
    imagefilledrectangle($img, 60, H - 95, W - 60, H - 93, $cAcento);

    // Footer
    $cFoot = imagecolorallocate($img, 15, 30, 65);
    imagefilledrectangle($img, 0, H - 68, W, H, $cFoot);

    // Pin decorativo derecho (círculo + punta)
    $pinX = W - 170; $pinY = 310;
    $cPinBg = imagecolorallocate($img, 35, 70, 130);
    imagefilledellipse($img, $pinX, $pinY - 10, 210, 210, $cPinBg);
    imagefilledellipse($img, $pinX, $pinY,       60,  60, $cWhite);
    imagefilledellipse($img, $pinX, $pinY,       30,  30, $cBg);
    $pts = [$pinX - 20, $pinY + 28, $pinX + 20, $pinY + 28, $pinX, $pinY + 75];
    imagefilledpolygon($img, $pts, 3, $cWhite);
}

// ══════════════════════════════════════════════════════════════════════════════
// TEXTO — igual para ambos modos
// ══════════════════════════════════════════════════════════════════════════════
$pad = 68;

// "mapita.com.ar" tag arriba
ogText($img, 'mapita.com.ar', $pad, 52, 14, $cWhite55, $fontBold);

// Título (nombre del negocio)
$tituloCorto = mb_strlen($titulo) > 38 ? mb_substr($titulo, 0, 36) . '…' : $titulo;
$fsTitulo    = mb_strlen($tituloCorto) > 22 ? 44 : 56;
ogText($img, $tituloCorto, $pad, 200, $fsTitulo, $cWhite, $fontBold);

// Subtítulo (tipo · dirección)
$subCorto = mb_strlen($subtitulo) > 65 ? mb_substr($subtitulo, 0, 63) . '…' : $subtitulo;
ogText($img, $subCorto, $pad, 268, 22, $cWhite85, $fontBold);

// Línea separadora (solo modo sin foto — en modo foto ya hay overlay)
if (!$fotoPath) {
    imagefilledrectangle($img, $pad, 292, $pad + 80, 295, $cAcento);
}

// Descripción
if ($detalle) {
    // Partir en hasta 2 líneas de ~65 chars
    $lines = [];
    $words = explode(' ', $detalle);
    $line  = '';
    foreach ($words as $word) {
        if (mb_strlen($line . ' ' . $word) > 65 && $line !== '') {
            $lines[] = $line;
            $line = $word;
            if (count($lines) >= 2) break;
        } else {
            $line .= ($line ? ' ' : '') . $word;
        }
    }
    if ($line && count($lines) < 2) $lines[] = $line;

    foreach ($lines as $i => $ln) {
        ogText($img, $ln, $pad, 325 + $i * 34, 18, $cWhite55, $fontBold);
    }
}

// Footer izquierda
ogText($img, 'Mapita  —  El mapa de negocios de tu ciudad', $pad, H - 24, 15, $cWhite55, $fontBold);

// Footer derecha — URL en color acento
ogText($img, 'mapita.com.ar', W - 210, H - 24, 15, $cAcento, $fontBold);

// ── Salida PNG ────────────────────────────────────────────────────────────────
imagepng($img, null, 7);
imagedestroy($img);
