<?php
/**
 * Vista: Preferencias WT (Walkie Talkie) del usuario
 * URL:   /wt-preferencias  o  /views/wt_preferences.php
 */
session_start();
require_once __DIR__ . '/../core/helpers.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Usuario');

setSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>⚙️ Preferencias WT — Mapita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/variables-luxury.css">
    <link rel="stylesheet" href="/css/components-buttons.css">
    <link rel="stylesheet" href="/css/components-cards.css">
    <link rel="stylesheet" href="/css/components-forms.css">
    <style>
        body {
            background: var(--bg-tertiary);
            margin: 0;
            padding: var(--space-md);
            font-family: var(--font-family-base);
            min-height: 100vh;
        }
        .container {
            max-width: 560px;
            margin: 0 auto;
        }
        h1 {
            color: var(--primary);
            font-size: var(--font-size-2xl);
            margin-bottom: var(--space-xs);
        }
        .subtitle {
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
            margin-bottom: var(--space-xl);
        }
        .section-title {
            font-weight: 700;
            color: var(--primary);
            font-size: var(--font-size-md);
            margin: var(--space-lg) 0 var(--space-sm);
        }
        .mode-options {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .mode-option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border: 2px solid var(--color-gray-300);
            border-radius: var(--border-radius-md);
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            background: var(--bg-primary);
        }
        .mode-option:has(input:checked) {
            border-color: var(--primary);
            background: rgba(59, 86, 201, 0.04);
        }
        .mode-option input[type=radio] {
            margin-top: 3px;
            flex-shrink: 0;
            accent-color: var(--primary);
        }
        .mode-label strong {
            display: block;
            font-size: 14px;
        }
        .mode-label small {
            color: var(--text-secondary);
            font-size: 12px;
        }
        .areas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
            margin-top: 8px;
        }
        .area-chip {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border: 1.5px solid var(--color-gray-300);
            border-radius: 999px;
            cursor: pointer;
            font-size: 13px;
            background: var(--bg-primary);
            transition: border-color 0.15s, background 0.15s;
        }
        .area-chip:has(input:checked) {
            border-color: var(--primary);
            background: rgba(59, 86, 201, 0.07);
            color: var(--primary);
            font-weight: 600;
        }
        .area-chip input[type=checkbox] {
            accent-color: var(--primary);
        }
        #areas-section {
            transition: opacity 0.2s;
        }
        #areas-section.hidden {
            opacity: 0.35;
            pointer-events: none;
        }
        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--color-success);
            border: 1px solid var(--color-success);
            border-radius: var(--border-radius-md);
            padding: var(--space-sm) var(--space-md);
            font-size: var(--font-size-sm);
            margin-bottom: var(--space-md);
            display: none;
        }
        .alert-error {
            background: rgba(230, 57, 70, 0.1);
            color: var(--accent-dark);
            border: 1px solid var(--accent-light);
            border-radius: var(--border-radius-md);
            padding: var(--space-sm) var(--space-md);
            font-size: var(--font-size-sm);
            margin-bottom: var(--space-md);
            display: none;
        }
        .blocks-list {
            margin-top: 8px;
        }
        .block-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border: 1px solid var(--color-gray-300);
            border-radius: var(--border-radius-sm);
            background: var(--bg-primary);
            margin-bottom: 6px;
            font-size: 13px;
        }
        .btn-unblock {
            background: transparent;
            border: 1px solid var(--accent-dark);
            color: var(--accent-dark);
            border-radius: 6px;
            padding: 3px 10px;
            font-size: 12px;
            cursor: pointer;
        }
        .btn-unblock:hover {
            background: var(--accent-dark);
            color: #fff;
        }
        .back-link {
            display: inline-block;
            margin-top: var(--space-lg);
            color: var(--primary);
            text-decoration: none;
            font-size: var(--font-size-sm);
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>⚙️ Preferencias WT</h1>
    <p class="subtitle">Controlá quién puede comunicarse contigo por el canal Walkie Talkie (WT) en el mapa.</p>

    <div id="alert-success" class="alert-success"></div>
    <div id="alert-error"   class="alert-error"></div>

    <div class="card" style="padding:var(--space-lg);">
        <div class="section-title">📡 Modo de canal</div>
        <form id="form-prefs">
            <div class="mode-options">
                <label class="mode-option">
                    <input type="radio" name="wt_mode" value="open">
                    <span class="mode-label">
                        <strong>🔓 Abierto al 100%</strong>
                        <small>Cualquier usuario puede enviarte mensajes WT.</small>
                    </span>
                </label>
                <label class="mode-option">
                    <input type="radio" name="wt_mode" value="selective">
                    <span class="mode-label">
                        <strong>🎯 Selectivo por áreas</strong>
                        <small>Solo usuarios con al menos un área en común pueden contactarte.</small>
                    </span>
                </label>
                <label class="mode-option">
                    <input type="radio" name="wt_mode" value="closed">
                    <span class="mode-label">
                        <strong>🔒 Desactivado</strong>
                        <small>Nadie puede enviarte mensajes WT.</small>
                    </span>
                </label>
            </div>

            <div id="areas-section" class="hidden" style="margin-top:var(--space-lg);">
                <div class="section-title" style="margin-top:0;">🏷️ Mis áreas de interés</div>
                <p style="font-size:12px;color:var(--text-secondary);margin:0 0 8px;">
                    Elegí las áreas que representan tus intereses. Solo usuarios con áreas en común podrán contactarte.
                </p>
                <div class="areas-grid" id="areas-grid"></div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:var(--space-lg);width:100%;">
                💾 Guardar preferencias
            </button>
        </form>
    </div>

    <div class="card" style="padding:var(--space-lg);margin-top:var(--space-lg);">
        <div class="section-title">🚫 Usuarios bloqueados</div>
        <p style="font-size:12px;color:var(--text-secondary);margin:0 0 10px;">
            Usuarios a los que has bloqueado el canal WT. Pueden ver tus negocios pero no enviarte mensajes.
        </p>
        <div class="blocks-list" id="blocks-list">
            <span style="font-size:13px;color:var(--text-tertiary);">Cargando...</span>
        </div>
    </div>

    <a href="/views/business/map.php" class="back-link">← Volver al mapa</a>
</div>

<script>
// Build areas grid
(function buildGrid() {
    const grid = document.getElementById('areas-grid');
    if (!grid) return;
    const areasObj = <?= json_encode(WT_AREAS_JS(), JSON_UNESCAPED_UNICODE) ?>;
    Object.entries(areasObj).forEach(([slug, label]) => {
        const chip = document.createElement('label');
        chip.className = 'area-chip';
        chip.innerHTML = `<input type="checkbox" name="areas[]" value="${slug}"> ${label}`;
        grid.appendChild(chip);
    });
})();

// Toggle areas section
document.querySelectorAll('input[name=wt_mode]').forEach(radio => {
    radio.addEventListener('change', () => {
        const sec = document.getElementById('areas-section');
        if (radio.value === 'selective' && radio.checked) {
            sec.classList.remove('hidden');
        } else if (radio.checked) {
            sec.classList.add('hidden');
        }
    });
});

function showAlert(type, msg) {
    const el = document.getElementById('alert-' + type);
    el.textContent = msg;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

// Load current preferences
fetch('/api/wt_preferences.php')
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const prefs = data.data.prefs;
        // Set mode
        const modeRadio = document.querySelector('input[name=wt_mode][value="' + prefs.wt_mode + '"]');
        if (modeRadio) {
            modeRadio.checked = true;
            if (prefs.wt_mode === 'selective') {
                document.getElementById('areas-section').classList.remove('hidden');
            }
        }
        // Set areas
        if (Array.isArray(prefs.areas)) {
            prefs.areas.forEach(slug => {
                const cb = document.querySelector('input[name="areas[]"][value="' + slug + '"]');
                if (cb) cb.checked = true;
            });
        }
    })
    .catch(() => {});

// Save preferences
document.getElementById('form-prefs').addEventListener('submit', async function(e) {
    e.preventDefault();
    const modeEl = document.querySelector('input[name=wt_mode]:checked');
    if (!modeEl) { showAlert('error', 'Elegí un modo primero.'); return; }
    const mode = modeEl.value;
    const areas = [...document.querySelectorAll('input[name="areas[]"]:checked')].map(c => c.value);
    try {
        const res = await fetch('/api/wt_preferences.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'save', wt_mode: mode, areas })
        });
        const data = await res.json();
        if (data.success) {
            showAlert('success', '✅ Preferencias guardadas correctamente.');
        } else {
            showAlert('error', data.error || 'No se pudo guardar.');
        }
    } catch {
        showAlert('error', 'Error de red al guardar preferencias.');
    }
});

// Load blocked users
async function loadBlocks() {
    const list = document.getElementById('blocks-list');
    try {
        const res = await fetch('/api/wt_preferences.php?action=blocks');
        const data = await res.json();
        if (!data.success) { list.innerHTML = '<span style="color:red;font-size:13px;">Error al cargar bloqueos.</span>'; return; }
        const blocks = data.data.blocks || [];
        if (blocks.length === 0) {
            list.innerHTML = '<span style="font-size:13px;color:var(--text-tertiary);">Sin usuarios bloqueados.</span>';
            return;
        }
        list.innerHTML = blocks.map(b =>
            `<div class="block-row">
                <span>👤 ${escapeHtml(b.username)}</span>
                <button class="btn-unblock" data-uid="${b.blocked_user_id}">Desbloquear</button>
             </div>`
        ).join('');
        list.querySelectorAll('.btn-unblock').forEach(btn => {
            btn.addEventListener('click', async () => {
                const uid = parseInt(btn.dataset.uid, 10);
                const res = await fetch('/api/wt_preferences.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'unblock', user_id: uid })
                });
                const data = await res.json();
                if (data.success) { loadBlocks(); showAlert('success', 'Usuario desbloqueado.'); }
                else showAlert('error', data.error || 'Error al desbloquear.');
            });
        });
    } catch {
        list.innerHTML = '<span style="font-size:13px;color:var(--text-tertiary);">No se pudo cargar.</span>';
    }
}
loadBlocks();

function escapeHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
</body>
</html>
<?php
// Helper para pasar WT_AREAS a JS sin redeclarar la constante del otro archivo
function WT_AREAS_JS(): array {
    return [
        'salud'           => '🏥 Salud',
        'educacion'       => '📚 Educación',
        'tecnologia'      => '💻 Tecnología',
        'gastronomia'     => '🍽️ Gastronomía',
        'entretenimiento' => '🎭 Entretenimiento',
        'deporte'         => '⚽ Deporte',
        'arte_cultura'    => '🎨 Arte y Cultura',
        'servicios'       => '🔧 Servicios',
        'comercio'        => '🛒 Comercio',
        'inmobiliaria'    => '🏠 Inmobiliaria',
        'manga_anime'     => '🎌 Manga / Anime',
        'turismo'         => '✈️ Turismo',
        'automotor'       => '🚗 Automotor',
        'moda'            => '👗 Moda',
        'musica'          => '🎵 Música',
        'juegos'          => '🎮 Juegos',
    ];
}
