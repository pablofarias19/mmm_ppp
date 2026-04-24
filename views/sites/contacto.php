<?php
/**
 * views/sites/contacto.php
 * Página: Contacto — Módulo Avanzado de Mapita
 * Formulario responsive con validación server-side y token CSRF.
 */
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/helpers.php';
setSecurityHeaders();

// ── Pre-fill tema from query string (e.g. /contacto?tema=fiscal) ──────────────
$allowedTemas = ['juridico', 'fiscal', 'inversion', 'compliance', 'marca', 'tasacion'];
$temaPreselect = '';
if (isset($_GET['tema']) && in_array($_GET['tema'], $allowedTemas, true)) {
    $temaPreselect = $_GET['tema'];
}

// ── Handle POST submission ─────────────────────────────────────────────────────
$formSuccess = false;
$formErrors  = [];
$formData    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    // Sanitize inputs
    $formData['nombre']  = trim(htmlspecialchars($_POST['nombre']  ?? '', ENT_QUOTES, 'UTF-8'));
    $formData['email']   = trim(filter_var($_POST['email']  ?? '', FILTER_SANITIZE_EMAIL));
    $formData['telefono']= trim(preg_replace('/[^0-9+\-\s()]/', '', $_POST['telefono'] ?? ''));
    $formData['tema']    = in_array($_POST['tema'] ?? '', $allowedTemas, true) ? $_POST['tema'] : '';
    $formData['mensaje'] = trim(htmlspecialchars($_POST['mensaje'] ?? '', ENT_QUOTES, 'UTF-8'));

    // Validate
    if (strlen($formData['nombre']) < 2 || strlen($formData['nombre']) > 100) {
        $formErrors['nombre'] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }
    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $formErrors['email'] = 'Ingresá un correo electrónico válido.';
    }
    if ($formData['telefono'] !== '' && strlen($formData['telefono']) > 30) {
        $formErrors['telefono'] = 'El teléfono no puede superar los 30 caracteres.';
    }
    if (empty($formData['tema'])) {
        $formErrors['tema'] = 'Seleccioná un tema de consulta.';
    }
    if (strlen($formData['mensaje']) < 10 || strlen($formData['mensaje']) > 2000) {
        $formErrors['mensaje'] = 'El mensaje debe tener entre 10 y 2000 caracteres.';
    }

    if (empty($formErrors)) {
        // No mandatory email integration — log to a simple file and confirm.
        $logDir  = __DIR__ . '/../../storage';
        $logFile = $logDir . '/contacto_requests.log';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0750, true);
        }
        $entry = json_encode([
            'ts'       => date('Y-m-d H:i:s'),
            'nombre'   => $formData['nombre'],
            'email'    => $formData['email'],
            'telefono' => $formData['telefono'],
            'tema'     => $formData['tema'],
            'mensaje'  => $formData['mensaje'],
        ]) . "\n";
        $written = file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

        if ($written === false) {
            $formErrors['general'] = 'No se pudo procesar tu consulta en este momento. Por favor intentá de nuevo más tarde.';
        } else {
            $formSuccess = true;
        }
        $formData    = []; // clear after success
    }
}

$pageTitle     = 'Contacto y Asesoramiento';
$pageIcon      = '📩';
$pageSubtitle  = 'Consultanos sobre estructuración legal, planificación fiscal, inversiones, compliance o expansión de marca.';
$activeSection = 'contacto';

$temaLabels = [
    'juridico'   => 'Jurídico / Patrimonial',
    'fiscal'     => 'Fiscal / Contable',
    'inversion'  => 'Inversión / Financiamiento',
    'compliance' => 'Compliance / Regulación',
    'marca'      => 'Expansión de Marca',
    'tasacion'   => 'Tasación de Marcas',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📩 Contacto y Asesoramiento — Mapita</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f7;
            color: #1a202c;
            min-height: 100vh;
        }

        /* ── HEADER ──────────────────────────────────────────────── */
        .adv-header {
            background: linear-gradient(135deg, #1B3B6F 0%, #0d2247 100%);
            color: white;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            height: 60px;
            box-shadow: 0 4px 20px rgba(0,0,0,.28);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .adv-header-logo {
            font-size: 1.25em; font-weight: 800; letter-spacing: -0.5px;
            text-decoration: none; color: white; white-space: nowrap;
        }
        .adv-header-logo span { opacity: .6; font-weight: 400; font-size: .7em; margin-left: 6px; }
        .adv-header-nav {
            margin-left: auto; display: flex; align-items: center; gap: 4px; flex-wrap: wrap;
        }
        .adv-nav-link {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 13px; border-radius: 8px; font-size: .82em; font-weight: 600;
            text-decoration: none; color: rgba(255,255,255,.8);
            border: 1.5px solid transparent; transition: all .2s; white-space: nowrap;
        }
        .adv-nav-link:hover { background: rgba(255,255,255,.12); color: white; }
        .adv-nav-link.active { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.35); color: white; }
        .adv-nav-back {
            color: rgba(255,255,255,.7); font-size: .82em; font-weight: 600;
            text-decoration: none; padding: 7px 13px; border: 1.5px solid rgba(255,255,255,.25);
            border-radius: 8px; display: inline-flex; align-items: center; gap: 5px; transition: all .2s;
        }
        .adv-nav-back:hover { background: rgba(255,255,255,.1); color: white; }

        /* ── MOBILE NAV ──────────────────────────────────────────── */
        .adv-mobile-nav {
            display: none; background: #0d2247; padding: 8px 12px;
            overflow-x: auto; gap: 6px; border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .adv-mobile-nav a {
            display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px;
            border-radius: 20px; font-size: .8em; font-weight: 600; text-decoration: none;
            color: rgba(255,255,255,.8); background: rgba(255,255,255,.08); white-space: nowrap;
            border: 1px solid transparent; transition: all .2s;
        }
        .adv-mobile-nav a.active { background: rgba(255,255,255,.2); border-color: rgba(255,255,255,.3); color: white; }

        /* ── HERO ────────────────────────────────────────────────── */
        .adv-hero {
            background: linear-gradient(135deg, #1B3B6F 0%, #163260 60%, #0d2247 100%);
            color: white; padding: 48px 24px 44px; text-align: center;
        }
        .adv-hero-icon { font-size: 3rem; margin-bottom: 12px; display: block; }
        .adv-hero h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); font-weight: 800; margin-bottom: 10px; }
        .adv-hero p  { font-size: 1.05em; opacity: .85; max-width: 640px; margin: 0 auto; line-height: 1.6; }

        /* ── MAIN ────────────────────────────────────────────────── */
        .adv-main { max-width: 760px; margin: 0 auto; padding: 36px 20px 72px; }

        /* ── FORM CARD ───────────────────────────────────────────── */
        .contact-card {
            background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.09);
            padding: 36px 40px; border-top: 5px solid #1B3B6F;
        }
        .contact-card h2 { font-size: 1.3em; font-weight: 800; color: #1B3B6F; margin-bottom: 6px; }
        .contact-card .subtitle { color: #64748b; font-size: .92em; margin-bottom: 28px; }

        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: .88em; font-weight: 700; color: #374151; margin-bottom: 6px;
        }
        .form-group label .required { color: #ef4444; margin-left: 2px; }
        .form-control {
            width: 100%; padding: 11px 14px; border: 1.5px solid #d1d5db;
            border-radius: 9px; font-size: .95em; color: #1a202c;
            background: #f9fafb; transition: border-color .2s, background .2s; outline: none;
            font-family: inherit;
        }
        .form-control:focus { border-color: #1B3B6F; background: white; }
        .form-control.is-error { border-color: #ef4444; background: #fff5f5; }
        .form-error-msg { color: #dc2626; font-size: .82em; margin-top: 5px; display: flex; align-items: center; gap: 4px; }
        .form-error-general {
            background: #fff5f5; border: 1.5px solid #ef4444; border-radius: 9px;
            padding: 12px 16px; margin-bottom: 20px; color: #dc2626; font-size: .92em;
        }
        textarea.form-control { resize: vertical; min-height: 130px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .btn-submit {
            width: 100%; padding: 14px; background: #1B3B6F; color: white; border: none;
            border-radius: 10px; font-size: 1em; font-weight: 700; cursor: pointer;
            transition: background .2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { background: #0d2247; }

        /* ── SUCCESS ─────────────────────────────────────────────── */
        .success-box {
            background: #f0fdf4; border: 2px solid #22c55e; border-radius: 14px;
            padding: 32px 28px; text-align: center;
        }
        .success-box .success-icon { font-size: 2.8rem; margin-bottom: 14px; display: block; }
        .success-box h2 { color: #15803d; font-size: 1.45em; font-weight: 800; margin-bottom: 8px; }
        .success-box p  { color: #166534; font-size: .97em; line-height: 1.6; }
        .success-box a {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 20px; padding: 12px 24px; background: #1B3B6F; color: white;
            border-radius: 10px; font-weight: 700; font-size: .92em; text-decoration: none; transition: background .2s;
        }
        .success-box a:hover { background: #0d2247; }

        /* ── SIDEBAR INFO ────────────────────────────────────────── */
        .contact-layout { display: grid; grid-template-columns: 1fr; gap: 24px; }
        .info-box {
            background: white; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,.07);
            padding: 24px 22px;
        }
        .info-box h3 { font-size: 1em; font-weight: 700; color: #1B3B6F; margin-bottom: 14px; }
        .info-topic {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: .9em;
        }
        .info-topic:last-child { border-bottom: none; }
        .info-topic a { color: #1B3B6F; text-decoration: none; font-weight: 600; }
        .info-topic a:hover { text-decoration: underline; }

        /* ── FOOTER ──────────────────────────────────────────────── */
        .adv-footer {
            background: #1B3B6F; color: rgba(255,255,255,.7); text-align: center;
            padding: 24px 20px; font-size: .85em;
        }
        .adv-footer a { color: rgba(255,255,255,.85); text-decoration: none; }
        .adv-footer a:hover { color: white; }

        /* ── RESPONSIVE ──────────────────────────────────────────── */
        @media (max-width: 768px) {
            .adv-header-nav { display: none; }
            .adv-mobile-nav { display: flex; }
            .adv-hero { padding: 32px 16px 30px; }
            .adv-main { padding: 20px 12px 56px; }
            .contact-card { padding: 24px 18px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="adv-header">
    <a class="adv-header-logo" href="/">🗺️ Mapita <span>Módulo Avanzado</span></a>
    <nav class="adv-header-nav">
        <a href="/" class="adv-nav-back">← Volver al Mapa</a>
        <a href="/avanzado"        class="adv-nav-link">🚀 Avanzado</a>
        <a href="/juridico"        class="adv-nav-link">⚖️ Jurídico</a>
        <a href="/fiscal"          class="adv-nav-link">📊 Fiscal</a>
        <a href="/inversion"       class="adv-nav-link">💰 Inversión</a>
        <a href="/compliance"      class="adv-nav-link">🛡️ Compliance</a>
        <a href="/marca-expansion" class="adv-nav-link">🏷️ Expansión de Marca</a>
        <a href="/contacto"        class="adv-nav-link active">📩 Contacto</a>
    </nav>
</header>

<!-- MOBILE NAV -->
<nav class="adv-mobile-nav">
    <a href="/">← Mapa</a>
    <a href="/avanzado">🚀 Avanzado</a>
    <a href="/juridico">⚖️ Jurídico</a>
    <a href="/fiscal">📊 Fiscal</a>
    <a href="/inversion">💰 Inversión</a>
    <a href="/compliance">🛡️ Compliance</a>
    <a href="/marca-expansion">🏷️ Marca</a>
    <a href="/contacto" class="active">📩 Contacto</a>
</nav>

<!-- HERO -->
<section class="adv-hero">
    <span class="adv-hero-icon">📩</span>
    <h1>Contacto y Asesoramiento</h1>
    <p>Consultanos sobre estructuración legal, planificación fiscal, inversiones, compliance o expansión de marca.</p>
</section>

<main class="adv-main">

    <!-- INFO TOPICS -->
    <div class="info-box" style="margin-bottom:24px;">
        <h3>¿Sobre qué tema podemos ayudarte?</h3>
        <div class="info-topic"><span>⚖️</span><div><a href="/juridico">Jurídico y Patrimonial</a><br><small style="color:#64748b;">Estructura societaria, separación de patrimonio, contratos y fideicomisos.</small></div></div>
        <div class="info-topic"><span>📊</span><div><a href="/fiscal">Fiscal y Contable</a><br><small style="color:#64748b;">Régimen impositivo, planificación fiscal y checklist contable.</small></div></div>
        <div class="info-topic"><span>💰</span><div><a href="/inversion">Inversión y Financiamiento</a><br><small style="color:#64748b;">Capitalización, créditos, fideicomisos e inversión extranjera.</small></div></div>
        <div class="info-topic"><span>🛡️</span><div><a href="/compliance">Compliance y Regulación</a><br><small style="color:#64748b;">Programas de cumplimiento, defensa del consumidor y prevención de lavado.</small></div></div>
        <div class="info-topic"><span>🏷️</span><div><a href="/marca-expansion">Expansión de Marca</a><br><small style="color:#64748b;">Franquicias, licencias, agencias y valoración de intangibles.</small></div></div>
    </div>

    <?php if ($formSuccess): ?>
    <!-- SUCCESS MESSAGE -->
    <div class="success-box">
        <span class="success-icon">✅</span>
        <h2>¡Consulta enviada con éxito!</h2>
        <p>Recibimos tu mensaje. Nuestro equipo se pondrá en contacto con vos en los próximos días hábiles con una respuesta personalizada.</p>
        <a href="/avanzado">🚀 Volver al Módulo Avanzado</a>
    </div>

    <?php else: ?>
    <!-- CONTACT FORM -->
    <div class="contact-card">
        <h2>📋 Envianos tu consulta</h2>
        <p class="subtitle">Completá el formulario y te respondemos con un análisis personalizado.</p>

        <?php if (isset($formErrors['general'])): ?>
        <div class="form-error-general">⚠️ <?= htmlspecialchars($formErrors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" action="/contacto" novalidate>
            <?= csrfField() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre completo <span class="required">*</span></label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        class="form-control<?= isset($formErrors['nombre']) ? ' is-error' : '' ?>"
                        value="<?= htmlspecialchars($formData['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="100"
                        autocomplete="name"
                        required>
                    <?php if (isset($formErrors['nombre'])): ?>
                    <span class="form-error-msg">⚠️ <?= htmlspecialchars($formErrors['nombre']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control<?= isset($formErrors['email']) ? ' is-error' : '' ?>"
                        value="<?= htmlspecialchars($formData['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="254"
                        autocomplete="email"
                        required>
                    <?php if (isset($formErrors['email'])): ?>
                    <span class="form-error-msg">⚠️ <?= htmlspecialchars($formErrors['email']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telefono">Teléfono <span style="color:#9ca3af;font-weight:400;">(opcional)</span></label>
                    <input
                        type="tel"
                        id="telefono"
                        name="telefono"
                        class="form-control<?= isset($formErrors['telefono']) ? ' is-error' : '' ?>"
                        value="<?= htmlspecialchars($formData['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        maxlength="30"
                        autocomplete="tel"
                        placeholder="+54 11 1234-5678">
                    <?php if (isset($formErrors['telefono'])): ?>
                    <span class="form-error-msg">⚠️ <?= htmlspecialchars($formErrors['telefono']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="tema">Tema de consulta <span class="required">*</span></label>
                    <select
                        id="tema"
                        name="tema"
                        class="form-control<?= isset($formErrors['tema']) ? ' is-error' : '' ?>"
                        required>
                        <option value="">— Seleccioná un tema —</option>
                        <?php foreach ($temaLabels as $val => $label): ?>
                        <option value="<?= htmlspecialchars($val) ?>"
                            <?= (($formData['tema'] ?? $temaPreselect) === $val) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($formErrors['tema'])): ?>
                    <span class="form-error-msg">⚠️ <?= htmlspecialchars($formErrors['tema']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="mensaje">Mensaje <span class="required">*</span></label>
                <textarea
                    id="mensaje"
                    name="mensaje"
                    class="form-control<?= isset($formErrors['mensaje']) ? ' is-error' : '' ?>"
                    maxlength="2000"
                    required
                    placeholder="Describí brevemente tu consulta, el tipo de negocio/marca/industria y lo que estás buscando..."><?= htmlspecialchars($formData['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <?php if (isset($formErrors['mensaje'])): ?>
                <span class="form-error-msg">⚠️ <?= htmlspecialchars($formErrors['mensaje']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-submit">
                📩 Enviar consulta
            </button>
        </form>
    </div>
    <?php endif; ?>

</main>

<footer class="adv-footer">
    <p>
        🗺️ <strong>Mapita</strong> — Módulo Avanzado de Desarrollo Estratégico
        &nbsp;|&nbsp;
        <a href="/avanzado">Hub Avanzado</a>
        &nbsp;|&nbsp;
        <a href="/">Volver al Mapa</a>
    </p>
</footer>

</body>
</html>
