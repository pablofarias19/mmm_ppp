<?php
/**
 * views/sites/compliance.php
 * Módulo Avanzado — Compliance y Prevención.
 */
require_once __DIR__ . '/_layout.php';
siteHeader(t('compliance_page_title'), 'compliance');
?>

<div class="card">
    <h2><?= htmlspecialchars(t('compliance_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars(t('compliance_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <ul class="feature-list">
        <li><strong>Programas internos de compliance</strong> — Diseño e implementación de políticas.</li>
        <li><strong>Debida diligencia</strong> — Know Your Customer (KYC) y verificación de contrapartes.</li>
        <li><strong>Prevención de lavado (UIF)</strong> — Obligaciones, reportes y sujetos obligados.</li>
        <li><strong>Responsabilidad penal empresaria</strong> — Ley 27.401 y sus implicancias.</li>
    </ul>
</div>

<div class="section-grid">
    <div class="card">
        <h2>Prevención de Lavado de Activos</h2>
        <p class="muted">
            Normativa UIF, reportes de operaciones sospechosas (ROS), capacitación del personal y
            políticas Anti-Lavado y Anti-Financiamiento del Terrorismo (ALA/CFT).
        </p>
    </div>
    <div class="card">
        <h2>Responsabilidad Penal Empresaria</h2>
        <p class="muted">
            Ley 27.401: cómo proteger a la empresa y a sus directivos. Programas de integridad, canales
            de denuncia y régimen de colaboración eficaz.
        </p>
    </div>
    <div class="card">
        <h2>Prevención de Fraude Interno</h2>
        <p class="muted">
            Riesgos internos, control de caja, auditoría interna continua y señales de alerta tempranas.
        </p>
    </div>
    <div class="card">
        <h2>Defensa del Consumidor</h2>
        <p class="muted">
            Obligaciones de información, publicidad legal, normativa vigente y riesgos de sanción
            por incumplimiento de la Ley 24.240.
        </p>
    </div>
</div>

<div class="card">
    <h2>Medio Ambiente y Habilitaciones</h2>
    <p class="muted">
        Impacto ambiental, regulación provincial y municipal, riesgos de clausura y multas.
        Licencias comerciales, permisos industriales y riesgos administrativos asociados.
    </p>
</div>

<div class="card">
    <h2><?= htmlspecialchars(t('compliance_cta_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="muted"><?= htmlspecialchars(t('compliance_cta_desc'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="cta-row">
        <a class="btn btn-primary" href="/contacto?tema=compliance"><?= htmlspecialchars(t('compliance_cta_btn'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(t('adv_contact_external'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-secondary" href="/avanzado"><?= htmlspecialchars(t('adv_see_all_modules'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>

<?php siteFooter(); ?>
