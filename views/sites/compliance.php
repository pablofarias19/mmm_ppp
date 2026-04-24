<?php
/**
 * views/sites/compliance.php
 * Módulo Avanzado — Compliance y Prevención.
 */
require_once __DIR__ . '/_layout.php';
siteHeader('Compliance — Prevención y Gobierno Corporativo', 'compliance');
?>

<div class="card">
    <h2>Compliance y Prevención</h2>
    <p>
        Un programa de compliance sólido protege a la empresa de sanciones legales, penales y reputacionales.
        Este módulo abarca desde la prevención de lavado de activos hasta la responsabilidad penal empresaria.
    </p>
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
    <h2>¿Tu empresa está protegida?</h2>
    <p class="muted">Te ayudamos a diseñar e implementar un programa de compliance a medida para tu negocio.</p>
    <div class="cta-row">
        <a class="btn btn-primary" href="https://fariasortiz.com.ar/contact.html" target="_blank" rel="noopener noreferrer">
            📩 Consultar sobre compliance
        </a>
        <a class="btn btn-secondary" href="/avanzado">Ver todos los módulos</a>
    </div>
</div>

<?php siteFooter(); ?>
