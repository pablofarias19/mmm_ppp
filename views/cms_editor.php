<?php
/**
 * views/cms_editor.php — Editor CMS multilingüe (sólo administradores)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/helpers.php';

if (!isAdmin()) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body><p>Access denied / Acceso denegado.</p></body></html>';
    exit;
}

$db = null;
try {
    $db = \Core\Database::getInstance()->getConnection();
} catch (\Throwable $e) {
    die('Database unavailable / Base de datos no disponible.');
}

$csrfToken = generateCsrfToken();

// Supported CMS languages (all MAPITA_SUPPORTED_LANGS + 'es')
$cmsLangs = array_unique(array_merge(['es'], MAPITA_SUPPORTED_LANGS));
sort($cmsLangs);
$langNames = [
    'ar' => 'العربية (ar)',
    'de' => 'Deutsch (de)',
    'el' => 'Ελληνικά (el)',
    'en' => 'English (en)',
    'es' => 'Español (es)',
    'fr' => 'Français (fr)',
    'it' => 'Italiano (it)',
    'ja' => '日本語 (ja)',
    'ko' => '한국어 (ko)',
    'no' => 'Norsk (no)',
    'pt' => 'Português (pt)',
    'ru' => 'Русский (ru)',
    'tr' => 'Türkçe (tr)',
    'zh' => '中文 (zh)',
];

// Load all pages
$pages = $db->query(
    "SELECT id, slug, module, status FROM cms_pages ORDER BY module, slug"
)->fetchAll(\PDO::FETCH_ASSOC);

// Selected page context
$selectedPageId = (int)($_GET['page_id'] ?? 0);
$selectedLang   = $_GET['lang'] ?? 'es';
$selectedPage   = null;
$translation    = null;
$translations   = [];

if ($selectedPageId > 0) {
    $sp = $db->prepare("SELECT * FROM cms_pages WHERE id = ? LIMIT 1");
    $sp->execute([$selectedPageId]);
    $selectedPage = $sp->fetch(\PDO::FETCH_ASSOC);

    if ($selectedPage) {
        $ts = $db->prepare(
            "SELECT lang, title, body_md, summary, is_machine_draft, review_status
               FROM cms_page_translations
              WHERE page_id = ?
              ORDER BY lang"
        );
        $ts->execute([$selectedPageId]);
        foreach ($ts->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $translations[$row['lang']] = $row;
        }
        $translation = $translations[$selectedLang] ?? null;
    }
}

// Glossary domains
$domains = ['legal', 'tax', 'strategy', 'web', 'branding'];

?><!DOCTYPE html>
<html lang="<?= htmlspecialchars(getUILanguage(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 CMS Editor — Mapita</title>
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <link rel="stylesheet" href="/css/components-buttons.css">
    <link rel="stylesheet" href="/css/components-forms.css">
    <style>
        body { background: var(--bg-tertiary,#f4f6fb); color: var(--text-primary,#1a2340); font-family: var(--font-family-base,sans-serif); margin: 0; padding: 0; }
        .cms-container { max-width: 1100px; margin: 0 auto; padding: 24px 16px; }
        .cms-header { background: linear-gradient(135deg,#1B3B6F,#0d2246); color: #fff; padding: 24px 28px; border-radius: 12px; margin-bottom: 24px; }
        .cms-header h1 { margin: 0 0 4px; font-size: 1.5rem; }
        .cms-header p  { margin: 0; opacity: .75; font-size: .9rem; }
        .cms-grid { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
        @media(max-width:720px){ .cms-grid { grid-template-columns: 1fr; } }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.07); padding: 20px; }
        .card h2 { margin: 0 0 14px; font-size: 1rem; color: #1B3B6F; border-bottom: 2px solid #e8eef7; padding-bottom: 8px; }
        .page-list { list-style: none; padding: 0; margin: 0; }
        .page-list li a { display: block; padding: 8px 10px; border-radius: 8px; text-decoration: none; color: #374151; font-size: .9rem; transition: background .15s; }
        .page-list li a:hover, .page-list li a.active { background: #e8eef7; color: #1B3B6F; font-weight: 600; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: .72rem; font-weight: 700; text-transform: uppercase; margin-left: 6px; }
        .badge-published { background: #d1fae5; color: #065f46; }
        .badge-draft     { background: #fef3c7; color: #92400e; }
        .lang-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
        .lang-tabs a { padding: 5px 12px; border-radius: 8px; font-size: .82rem; text-decoration: none; background: #f1f5f9; color: #374151; border: 1.5px solid #e2e8f0; transition: all .15s; }
        .lang-tabs a:hover  { background: #e8eef7; }
        .lang-tabs a.active { background: #1B3B6F; color: #fff; border-color: #1B3B6F; }
        .lang-tabs a.has-content { border-color: #6ee7b7; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: .84rem; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; box-sizing: border-box; padding: 8px 11px; border: 1.5px solid #d1d5db;
            border-radius: 8px; font-size: .9rem; color: #1a2340; background: #f9fafb; transition: border-color .15s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #1B3B6F; outline: none; background: #fff; }
        .form-group textarea { min-height: 280px; font-family: 'Courier New', monospace; resize: vertical; }
        .btn-primary { background: #1B3B6F; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-size: .9rem; font-weight: 600; transition: background .15s; }
        .btn-primary:hover { background: #0d2246; }
        .btn-secondary { background: #e8eef7; color: #1B3B6F; border: none; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-size: .9rem; font-weight: 600; transition: background .15s; }
        .btn-secondary:hover { background: #d1ddef; }
        .btn-danger { background: #fee2e2; color: #b91c1c; border: none; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-size: .9rem; font-weight: 600; }
        .inline-form { display: inline; }
        .alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: .87rem; }
        .alert-info  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .alert-draft { background: #fefce8; color: #854d0e; border: 1px solid #fde68a; }
        .review-badge { display: inline-block; padding: 2px 9px; border-radius: 12px; font-size: .75rem; font-weight: 700; }
        .review-needs_review   { background: #fef3c7; color: #92400e; }
        .review-reviewed       { background: #d1fae5; color: #065f46; }
        .review-legal_verified { background: #dbeafe; color: #1e40af; }
        .new-page-form { display: none; }
        .new-page-form.open { display: block; }
        .section-toggle { cursor: pointer; user-select: none; }
    </style>
</head>
<body>
<div class="cms-container">
    <div class="cms-header">
        <h1>📝 CMS Multilingüe — Editor</h1>
        <p>Administración de páginas y traducciones de contenido técnico/avanzado</p>
    </div>

    <div class="cms-grid">
        <!-- ── Sidebar: lista de páginas ──────────────────────────────────── -->
        <aside>
            <div class="card">
                <h2>Páginas</h2>
                <ul class="page-list">
                    <?php foreach ($pages as $pg): ?>
                    <li>
                        <a href="?page_id=<?= $pg['id'] ?>&lang=<?= htmlspecialchars($selectedLang) ?>"
                           class="<?= $selectedPageId === (int)$pg['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($pg['slug']) ?>
                            <span class="badge badge-<?= $pg['status'] ?>"><?= $pg['status'] ?></span>
                            <small style="display:block;color:#6b7280;font-size:.75rem;"><?= htmlspecialchars($pg['module']) ?></small>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($pages)): ?>
                    <li><em style="color:#6b7280;font-size:.85rem;">No hay páginas aún.</em></li>
                    <?php endif; ?>
                </ul>

                <div style="margin-top:16px;">
                    <button type="button" class="btn-secondary" onclick="toggleNewPageForm()" style="width:100%;">+ Nueva página</button>
                </div>

                <!-- Formulario nueva página -->
                <div id="new-page-form" class="new-page-form" style="margin-top:14px;">
                    <form id="form-create-page">
                        <div class="form-group">
                            <label>Slug <small style="font-weight:400;color:#6b7280;">(letras, números, guiones)</small></label>
                            <input type="text" id="np-slug" placeholder="ej: legal-assistance" pattern="[a-z0-9\-]+" required>
                        </div>
                        <div class="form-group">
                            <label>Módulo</label>
                            <input type="text" id="np-module" value="advanced">
                        </div>
                        <div class="form-group">
                            <label>Estado</label>
                            <select id="np-status">
                                <option value="draft">Borrador</option>
                                <option value="published">Publicado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%;">Crear página</button>
                    </form>
                    <div id="np-msg" style="margin-top:8px;font-size:.85rem;"></div>
                </div>
            </div>
        </aside>

        <!-- ── Main: editor de traducción ───────────────────────────────── -->
        <main>
            <?php if ($selectedPage): ?>
            <div class="card" style="margin-bottom:16px;">
                <h2>
                    📄 <?= htmlspecialchars($selectedPage['slug']) ?>
                    <span class="badge badge-<?= $selectedPage['status'] ?>"><?= $selectedPage['status'] ?></span>
                    <small style="font-weight:400;color:#6b7280;font-size:.8rem;margin-left:8px;"><?= htmlspecialchars($selectedPage['module']) ?></small>
                </h2>

                <!-- Selector de idioma -->
                <div class="lang-tabs">
                    <?php foreach ($cmsLangs as $lc): ?>
                    <a href="?page_id=<?= $selectedPage['id'] ?>&lang=<?= $lc ?>"
                       class="<?= $selectedLang === $lc ? 'active' : '' ?> <?= isset($translations[$lc]) ? 'has-content' : '' ?>"
                       title="<?= htmlspecialchars($langNames[$lc] ?? $lc) ?>">
                        <?= htmlspecialchars($lc) ?>
                        <?php if (isset($translations[$lc])): ?>
                            <span title="<?= htmlspecialchars($translations[$lc]['review_status']) ?>">✓</span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($translation && $translation['is_machine_draft']): ?>
                <div class="alert alert-draft">⚠️ Borrador automático — revisar antes de publicar.</div>
                <?php elseif (!$translation): ?>
                <div class="alert alert-info">ℹ️ No existe traducción para <strong><?= htmlspecialchars($selectedLang) ?></strong>. Puedes crearla abajo.</div>
                <?php endif; ?>

                <!-- Formulario edición traducción -->
                <form id="form-translation">
                    <input type="hidden" name="page_id" value="<?= $selectedPage['id'] ?>">
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($selectedLang) ?>">

                    <div class="form-group">
                        <label>Título</label>
                        <input type="text" name="title" id="tr-title" value="<?= htmlspecialchars($translation['title'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Resumen <small style="font-weight:400;">(opcional)</small></label>
                        <input type="text" name="summary" id="tr-summary" value="<?= htmlspecialchars($translation['summary'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Contenido (Markdown)</label>
                        <textarea name="body_md" id="tr-body"><?= htmlspecialchars($translation['body_md'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Estado de revisión</label>
                        <select name="review_status" id="tr-review">
                            <?php foreach (['needs_review'=>'Necesita revisión','reviewed'=>'Revisado','legal_verified'=>'Verificado legalmente'] as $rv => $rvLabel): ?>
                            <option value="<?= $rv ?>" <?= ($translation['review_status'] ?? 'needs_review') === $rv ? 'selected' : '' ?>><?= $rvLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                        <button type="submit" class="btn-primary">💾 Guardar traducción</button>
                        <?php if ($translation && $translation['is_machine_draft']): ?>
                        <span class="review-badge review-needs_review">Borrador automático</span>
                        <?php elseif ($translation): ?>
                        <span class="review-badge review-<?= htmlspecialchars($translation['review_status']) ?>"><?= htmlspecialchars($translation['review_status']) ?></span>
                        <?php endif; ?>
                    </div>
                </form>
                <div id="tr-msg" style="margin-top:10px;font-size:.85rem;"></div>
            </div>

            <!-- Glosario técnico para esta página/idioma -->
            <div class="card">
                <h2 class="section-toggle" onclick="toggleGlossary()">📚 Glosario técnico <small style="font-weight:400;font-size:.8rem;">— Dominio: <strong id="gl-domain-label"><?= htmlspecialchars($domains[0]) ?></strong></small> ▾</h2>
                <div id="glossary-section">
                    <form id="form-glossary">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label>Dominio</label>
                                <select name="domain" id="gl-domain" onchange="document.getElementById('gl-domain-label').textContent=this.value">
                                    <?php foreach ($domains as $d): ?>
                                    <option value="<?= $d ?>"><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Clave del término</label>
                                <input type="text" name="term_key" id="gl-key" placeholder="ej: nice_class">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="form-group">
                                <label>Idioma</label>
                                <select name="lang" id="gl-lang">
                                    <?php foreach ($cmsLangs as $lc): ?>
                                    <option value="<?= $lc ?>" <?= $selectedLang === $lc ? 'selected' : '' ?>><?= htmlspecialchars($langNames[$lc] ?? $lc) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Término localizado</label>
                                <input type="text" name="term" id="gl-term" placeholder="Nombre del término en el idioma">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Definición (Markdown)</label>
                            <textarea name="definition_md" id="gl-def" style="min-height:100px;"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Notas adicionales <small style="font-weight:400;">(opcional)</small></label>
                            <textarea name="notes_md" id="gl-notes" style="min-height:60px;"></textarea>
                        </div>
                        <button type="submit" class="btn-primary">💾 Guardar término</button>
                    </form>
                    <div id="gl-msg" style="margin-top:10px;font-size:.85rem;"></div>
                </div>
            </div>

            <?php else: ?>
            <div class="card">
                <p style="color:#6b7280;text-align:center;padding:40px 0;">
                    👈 Selecciona una página de la lista o crea una nueva.
                </p>
            </div>
            <?php endif; ?>
        </main>
    </div><!-- /.cms-grid -->
</div><!-- /.cms-container -->

<script>
const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;

// ── Toggle nueva página ──────────────────────────────────────────────────────
function toggleNewPageForm() {
    const el = document.getElementById('new-page-form');
    el.classList.toggle('open');
}

// ── Toggle glosario ──────────────────────────────────────────────────────────
function toggleGlossary() {
    const el = document.getElementById('glossary-section');
    el.style.display = el.style.display === 'none' ? '' : 'none';
}

// ── API helper ───────────────────────────────────────────────────────────────
async function cmsRequest(method, action, payload) {
    const url = '/api/cms.php?action=' + encodeURIComponent(action);
    const body = JSON.stringify(Object.assign({ action, csrf_token: CSRF_TOKEN }, payload));
    const resp = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body,
    });
    return resp.json();
}

function showMsg(elId, msg, ok) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.textContent = msg;
    el.style.color = ok ? '#065f46' : '#b91c1c';
    setTimeout(() => { el.textContent = ''; }, 4000);
}

// ── Crear página ─────────────────────────────────────────────────────────────
const formCreate = document.getElementById('form-create-page');
if (formCreate) {
    formCreate.addEventListener('submit', async e => {
        e.preventDefault();
        const slug   = document.getElementById('np-slug').value.trim();
        const module = document.getElementById('np-module').value.trim();
        const status = document.getElementById('np-status').value;
        const res = await cmsRequest('POST', 'create_page', { slug, module, status });
        if (res.success) {
            showMsg('np-msg', '✅ Página creada (id=' + res.data.id + ')', true);
            setTimeout(() => location.href = '?page_id=' + res.data.id, 800);
        } else {
            showMsg('np-msg', '❌ ' + (res.error || res.message), false);
        }
    });
}

// ── Guardar traducción ────────────────────────────────────────────────────────
const formTr = document.getElementById('form-translation');
if (formTr) {
    formTr.addEventListener('submit', async e => {
        e.preventDefault();
        const payload = {
            page_id:          parseInt(formTr.querySelector('[name=page_id]').value),
            lang:             formTr.querySelector('[name=lang]').value,
            title:            document.getElementById('tr-title').value,
            summary:          document.getElementById('tr-summary').value,
            body_md:          document.getElementById('tr-body').value,
            review_status:    document.getElementById('tr-review').value,
            is_machine_draft: 0,
        };
        const res = await cmsRequest('PUT', 'upsert_translation', payload);
        if (res.success) {
            showMsg('tr-msg', '✅ Traducción guardada', true);
        } else {
            showMsg('tr-msg', '❌ ' + (res.error || res.message), false);
        }
    });
}

// ── Guardar término de glosario ──────────────────────────────────────────────
const formGl = document.getElementById('form-glossary');
if (formGl) {
    formGl.addEventListener('submit', async e => {
        e.preventDefault();
        const payload = {
            domain:        document.getElementById('gl-domain').value,
            term_key:      document.getElementById('gl-key').value.trim(),
            lang:          document.getElementById('gl-lang').value,
            term:          document.getElementById('gl-term').value.trim(),
            definition_md: document.getElementById('gl-def').value,
            notes_md:      document.getElementById('gl-notes').value,
        };
        const res = await cmsRequest('PUT', 'upsert_glossary', payload);
        if (res.success) {
            showMsg('gl-msg', '✅ Término guardado', true);
        } else {
            showMsg('gl-msg', '❌ ' + (res.error || res.message), false);
        }
    });
}
</script>
</body>
</html>
