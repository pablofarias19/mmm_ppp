/**
 * consultas-panel.js
 * Módulo CONSULTAS MASIVAS — UI completa
 *
 * Depende de:
 *   - SESSION_USER_ID  (PHP injected global, int, 0 si no logueado)
 *   - mapa             (Leaflet global from map.php)
 *   - BUSINESS_TYPE_LABELS (optional — map.php global for human-readable labels)
 *
 * Funciones públicas (window.*):
 *   openConsultaModal(tipo)   — abre modal de envío (llamado desde sidebar)
 *   startGeoSelect(tipo)      — activa modo de dibujo de área en el mapa
 *   CQPanel.open()            — abre el panel flotante
 *   CQPanel.refresh()         — recarga las listas
 *   cqSendReply(id, bizId)    — envía respuesta desde hilo (llamado desde HTML inline)
 */

(function () {
    'use strict';

    /* ── Estado interno ───────────────────────────────────────────────────── */
    const state = {
        panelOpen:        false,
        activeTab:        'sent',       // 'sent' | 'received'
        geoMode:          false,
        geoPendingTipo:   null,
        geoBounds:        null,         // {north,south,east,west}
        lastReplyId:      0,
        pollTimer:        null,
        threadConsultaId: null,
        threadBizId:      null,
    };

    /* ── Bootstrap ─────────────────────────────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        buildDOM();
        wireEvents();
        if (typeof SESSION_USER_ID !== 'undefined' && SESSION_USER_ID > 0) {
            CQPanel.refresh();
            startPolling();
        }
    }

    /* ── Build DOM ─────────────────────────────────────────────────────────── */
    function buildDOM() {
        if (!document.getElementById('cq-geo-overlay')) {
            const ov = document.createElement('div');
            ov.id = 'cq-geo-overlay';
            document.body.appendChild(ov);
        }
        if (!document.getElementById('cq-geo-toast')) {
            const t = document.createElement('div');
            t.id = 'cq-geo-toast';
            t.textContent = '📍 Shift + arrastrar para seleccionar el área geográfica · Esc para cancelar';
            document.body.appendChild(t);
        }

        /* Modal de envío */
        if (!document.getElementById('cq-modal-overlay')) {
            document.body.insertAdjacentHTML('beforeend', `
<div id="cq-modal-overlay" role="dialog" aria-modal="true" aria-label="Enviar consulta">
  <div id="cq-modal">
    <div id="cq-modal-header">
      <span id="cq-modal-icon" style="font-size:20px;">📣</span>
      <h2 id="cq-modal-title">Consulta Masiva</h2>
      <button id="cq-modal-close" aria-label="Cerrar">✕</button>
    </div>
    <div id="cq-modal-body">
      <!-- Filtro por tipo de negocio (masiva y general) -->
      <div id="cq-biztype-row">
        <label for="cq-biztype-sel">Filtrar por tipo de negocio <span style="color:#9ca3af;font-weight:400;">(opcional — todos si no elegís)</span></label>
        <select id="cq-biztype-sel">
          <option value="">⬛ Todos los tipos</option>
        </select>
      </div>
      <!-- Rubro proveedor (global_proveedor únicamente) -->
      <div id="cq-rubro-row" style="display:none;">
        <label for="cq-rubro-sel">Rubro / Tipo de proveedor <span style="color:#e53935;">*</span></label>
        <select id="cq-rubro-sel">
          <option value="">Cargando rubros…</option>
        </select>
      </div>
      <div>
        <label for="cq-texto">Tu consulta <span style="color:#e53935;">*</span></label>
        <textarea id="cq-texto" rows="4" maxlength="500"
                  placeholder="Escribí tu consulta (máx. 500 caracteres)…"></textarea>
        <div class="cq-char-count"><span id="cq-char-n">0</span>/500</div>
      </div>
      <div id="cq-modal-preview" style="display:none;">
        Se enviará a <strong id="cq-preview-count">—</strong> negocios.
      </div>
      <div id="cq-modal-error"></div>
    </div>
    <div id="cq-modal-footer">
      <button class="cq-modal-cancel" id="cq-modal-cancel-btn">Cancelar</button>
      <button class="cq-modal-submit" id="cq-modal-submit-btn">📤 Enviar consulta</button>
    </div>
  </div>
</div>`);
        }

        /* Panel flotante */
        if (!document.getElementById('cq-panel')) {
            document.body.insertAdjacentHTML('beforeend', `
<button id="cq-panel-toggle-btn" aria-label="Abrir mis consultas" title="Mis Consultas">
  💬 Consultas <span id="cq-panel-badge" style="display:none;">0</span>
</button>
<div id="cq-panel" class="cq-minimized" role="region" aria-label="Panel de consultas masivas">
  <div id="cq-panel-header">
    <span style="font-size:16px;">💬</span>
    <span id="cq-panel-title">Mis Consultas</span>
    <button class="cq-panel-hdr-btn" id="cq-panel-minimize" title="Minimizar" aria-label="Minimizar">━</button>
    <button class="cq-panel-hdr-btn" id="cq-panel-close-btn" title="Cerrar" aria-label="Cerrar">✕</button>
  </div>
  <div id="cq-panel-tabs">
    <button class="cq-tab active" data-tab="sent">✉️ Enviadas</button>
    <button class="cq-tab" data-tab="received">📨 Recibidas</button>
  </div>
  <div id="cq-panel-body">
    <div id="cq-list-view">
      <div id="cq-sent-list"    class="cq-tab-pane"><div class="cq-empty">⏳ Cargando…</div></div>
      <div id="cq-received-list" class="cq-tab-pane" style="display:none;"><div class="cq-empty">⏳ Cargando…</div></div>
    </div>
    <div id="cq-thread-view">
      <button class="cq-back-btn" id="cq-back-btn">← Volver</button>
      <div id="cq-thread-content"></div>
    </div>
  </div>
</div>`);
        }
    }

    /* ── Wire events ───────────────────────────────────────────────────────── */
    function wireEvents() {
        document.getElementById('cq-modal-close')?.addEventListener('click', closeModal);
        document.getElementById('cq-modal-cancel-btn')?.addEventListener('click', closeModal);
        document.getElementById('cq-modal-overlay')?.addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
        document.getElementById('cq-modal-submit-btn')?.addEventListener('click', handleSubmit);

        document.getElementById('cq-texto')?.addEventListener('input', function () {
            const el = document.getElementById('cq-char-n');
            if (el) el.textContent = this.value.length;
            debouncedPreview();
        });
        document.getElementById('cq-rubro-sel')?.addEventListener('change',   debouncedPreview);
        document.getElementById('cq-biztype-sel')?.addEventListener('change', debouncedPreview);

        document.getElementById('cq-panel-toggle-btn')?.addEventListener('click', CQPanel.open);
        document.getElementById('cq-panel-minimize')?.addEventListener('click',   CQPanel.minimize);
        document.getElementById('cq-panel-close-btn')?.addEventListener('click',  CQPanel.close);
        document.getElementById('cq-back-btn')?.addEventListener('click', backToList);

        document.querySelectorAll('.cq-tab').forEach(btn => {
            btn.addEventListener('click', function () { switchTab(this.dataset.tab); });
        });

        /* Leaflet boxZoom end → finaliza la selección de área */
        if (typeof mapa !== 'undefined' && mapa) {
            mapa.on('boxzoomend', function (e) {
                if (!state.geoMode) return;
                finishGeoSelect(e.boxZoomBounds);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && state.geoMode) cancelGeoSelect();
        });
    }

    /* ── Geo selection ─────────────────────────────────────────────────────── */
    window.startGeoSelect = function (tipo) {
        if (!requireLogin()) return;
        state.geoPendingTipo = tipo;
        state.geoMode        = true;
        state.geoBounds      = null;

        if (typeof mapa !== 'undefined' && mapa) {
            mapa.dragging?.disable();
            mapa.boxZoom.enable();
        }
        document.getElementById('cq-geo-overlay')?.classList.add('active');
        document.getElementById('cq-geo-toast')?.classList.add('active');
        const mapEl = document.getElementById('map');
        if (mapEl) mapEl.style.cursor = 'crosshair';
    };

    function finishGeoSelect(bounds) {
        state.geoMode   = false;
        state.geoBounds = {
            north: bounds.getNorth(),
            south: bounds.getSouth(),
            east:  bounds.getEast(),
            west:  bounds.getWest(),
        };
        restoreMapState();
        openConsultaModal(state.geoPendingTipo);
    }

    function cancelGeoSelect() {
        state.geoMode   = false;
        state.geoBounds = null;
        restoreMapState();
    }

    function restoreMapState() {
        document.getElementById('cq-geo-overlay')?.classList.remove('active');
        document.getElementById('cq-geo-toast')?.classList.remove('active');
        const mapEl = document.getElementById('map');
        if (mapEl) mapEl.style.cursor = '';
        if (typeof mapa !== 'undefined' && mapa) {
            mapa.boxZoom.disable();
            mapa.dragging?.enable();
        }
    }

    /* ── Modal ─────────────────────────────────────────────────────────────── */
    const MODAL_CONFIG = {
        masiva:           { icon: '📣', title: 'Consulta Masiva',             biztypeFilter: true,  rubroFilter: false },
        general:          { icon: '🏛️', title: 'Consulta General',            biztypeFilter: true,  rubroFilter: false },
        global_proveedor: { icon: '📦', title: 'Consulta Global Proveedores', biztypeFilter: false, rubroFilter: true  },
        envio:            { icon: '🚚', title: 'Consulta Envío',              biztypeFilter: false, rubroFilter: false },
    };

    window.openConsultaModal = function (tipo) {
        if (!requireLogin()) return;
        const cfg = MODAL_CONFIG[tipo];
        if (!cfg) return;

        /* Resetear campos */
        document.getElementById('cq-modal-icon').textContent   = cfg.icon;
        document.getElementById('cq-modal-title').textContent  = cfg.title;
        document.getElementById('cq-modal-error').textContent  = '';
        document.getElementById('cq-texto').value              = '';
        document.getElementById('cq-char-n').textContent       = '0';
        document.getElementById('cq-modal-preview').style.display = 'none';
        document.getElementById('cq-modal-submit-btn').disabled = false;
        document.getElementById('cq-modal-submit-btn').textContent = '📤 Enviar consulta';

        /* Mostrar/ocultar filtros */
        const biztypeRow = document.getElementById('cq-biztype-row');
        const rubroRow   = document.getElementById('cq-rubro-row');
        biztypeRow.style.display = cfg.biztypeFilter ? 'block' : 'none';
        rubroRow.style.display   = cfg.rubroFilter   ? 'block' : 'none';

        if (cfg.biztypeFilter) loadBizTypes();
        if (cfg.rubroFilter)   loadRubrosProveedor();

        document.getElementById('cq-modal-overlay').dataset.tipo = tipo;
        document.getElementById('cq-modal-overlay').classList.add('active');
        document.getElementById('cq-texto').focus();

        /* Preview inmediato para tipos sin geo */
        if (tipo === 'general') fetchPreview(tipo, null, null, null);
    };

    function closeModal() {
        document.getElementById('cq-modal-overlay')?.classList.remove('active');
        document.getElementById('cq-modal-error').textContent = '';
    }

    /* Carga tipos de negocio disponibles para el filtro (masiva y general) */
    async function loadBizTypes() {
        const sel = document.getElementById('cq-biztype-sel');
        sel.innerHTML = '<option value="">⬛ Todos los tipos</option>';
        try {
            const res  = await fetch('/api/consultas.php?action=biz_types');
            const data = await res.json();
            if (data.success && data.data.length) {
                data.data.forEach(t => {
                    const label = (typeof BUSINESS_TYPE_LABELS !== 'undefined' && BUSINESS_TYPE_LABELS[t])
                        ? BUSINESS_TYPE_LABELS[t] : t;
                    const opt = document.createElement('option');
                    opt.value = t;
                    opt.textContent = label;
                    sel.appendChild(opt);
                });
            }
        } catch { /* silencioso */ }
    }

    async function loadRubrosProveedor() {
        const sel = document.getElementById('cq-rubro-sel');
        sel.innerHTML = '<option value="">Cargando rubros…</option>';
        try {
            const res  = await fetch('/api/consultas.php?action=rubros_proveedor');
            const data = await res.json();
            if (data.success && data.data.length) {
                sel.innerHTML = '<option value="">— Seleccioná un rubro —</option>' +
                    data.data.map(r => {
                        const label = (typeof BUSINESS_TYPE_LABELS !== 'undefined' && BUSINESS_TYPE_LABELS[r])
                            ? BUSINESS_TYPE_LABELS[r] : r;
                        return `<option value="${esc(r)}">${esc(label)}</option>`;
                    }).join('');
            } else {
                sel.innerHTML = '<option value="">Sin rubros disponibles</option>';
            }
        } catch {
            sel.innerHTML = '<option value="">Error al cargar</option>';
        }
    }

    let previewDebounce = null;
    function debouncedPreview() {
        clearTimeout(previewDebounce);
        previewDebounce = setTimeout(function () {
            const tipo    = document.getElementById('cq-modal-overlay')?.dataset.tipo;
            if (!tipo) return;
            const cfg     = MODAL_CONFIG[tipo] || {};
            const rubro   = cfg.rubroFilter   ? (document.getElementById('cq-rubro-sel')?.value   || null) : null;
            const bizType = cfg.biztypeFilter ? (document.getElementById('cq-biztype-sel')?.value || null) : null;
            fetchPreview(tipo, state.geoBounds, rubro, bizType);
        }, 400);
    }

    async function fetchPreview(tipo, bounds, rubro, bizType) {
        const params = new URLSearchParams({ action: 'preview', tipo });
        if (rubro)   params.set('rubro',      rubro);
        if (bizType) params.set('biz_type',   bizType);
        if (bounds)  params.set('geo_bounds', JSON.stringify(bounds));
        try {
            const res  = await fetch('/api/consultas.php?' + params.toString());
            const data = await res.json();
            if (data.success) {
                document.getElementById('cq-preview-count').textContent = data.data.count;
                document.getElementById('cq-modal-preview').style.display = 'block';
            }
        } catch { /* silencioso */ }
    }

    async function handleSubmit() {
        const tipo    = document.getElementById('cq-modal-overlay')?.dataset.tipo;
        const texto   = document.getElementById('cq-texto')?.value.trim();
        const errEl   = document.getElementById('cq-modal-error');
        const submitB = document.getElementById('cq-modal-submit-btn');
        const cfg     = MODAL_CONFIG[tipo] || {};

        errEl.textContent = '';
        if (!texto) { errEl.textContent = 'Escribí tu consulta antes de enviar.'; return; }

        const rubro   = cfg.rubroFilter   ? (document.getElementById('cq-rubro-sel')?.value   || null) : null;
        const bizType = cfg.biztypeFilter ? (document.getElementById('cq-biztype-sel')?.value || null) : null;

        if (cfg.rubroFilter && !rubro) { errEl.textContent = 'Seleccioná un rubro.'; return; }

        const body = { action: 'send', tipo, texto };
        if (rubro)           body.rubro      = rubro;
        if (bizType)         body.biz_type   = bizType;
        if (state.geoBounds) body.geo_bounds = state.geoBounds;

        submitB.disabled    = true;
        submitB.textContent = '⏳ Enviando…';
        try {
            const res  = await fetch('/api/consultas.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(body),
            });
            const data = await res.json();
            if (data.success) {
                closeModal();
                state.geoBounds = null;
                CQPanel.open();
                CQPanel.refresh();
                showMapToast('✅ ' + data.message);
            } else {
                errEl.textContent       = data.error || 'Error al enviar.';
                submitB.disabled        = false;
                submitB.textContent     = '📤 Enviar consulta';
            }
        } catch {
            errEl.textContent   = 'Error de red.';
            submitB.disabled    = false;
            submitB.textContent = '📤 Enviar consulta';
        }
    }

    /* ── Panel flotante ─────────────────────────────────────────────────────── */
    const CQPanel = window.CQPanel = {
        open: function () {
            const panel = document.getElementById('cq-panel');
            const btn   = document.getElementById('cq-panel-toggle-btn');
            if (!panel) return;
            panel.classList.remove('cq-minimized');
            if (btn) btn.classList.remove('visible');
            state.panelOpen = true;
            CQPanel.refresh();
        },
        minimize: function () {
            const panel = document.getElementById('cq-panel');
            const btn   = document.getElementById('cq-panel-toggle-btn');
            if (!panel) return;
            panel.classList.add('cq-minimized');
            if (btn) btn.classList.add('visible');
            state.panelOpen = false;
        },
        close: function () {
            const panel = document.getElementById('cq-panel');
            const btn   = document.getElementById('cq-panel-toggle-btn');
            if (!panel) return;
            panel.classList.add('cq-minimized');
            if (btn) btn.classList.remove('visible');
            state.panelOpen = false;
        },
        refresh: async function () {
            await Promise.all([loadSentList(), loadReceivedList()]);
        },
    };

    function switchTab(tab) {
        state.activeTab = tab;
        document.querySelectorAll('.cq-tab').forEach(b =>
            b.classList.toggle('active', b.dataset.tab === tab)
        );
        document.getElementById('cq-sent-list').style.display     = (tab === 'sent')     ? 'block' : 'none';
        document.getElementById('cq-received-list').style.display  = (tab === 'received') ? 'block' : 'none';
        backToList();
    }

    function backToList() {
        state.threadConsultaId = null;
        state.threadBizId      = null;
        document.getElementById('cq-list-view').style.display  = 'block';
        document.getElementById('cq-thread-view').classList.remove('active');
    }

    async function loadSentList() {
        const el = document.getElementById('cq-sent-list');
        if (!el) return;
        try {
            const res  = await fetch('/api/consultas.php?action=my_consultas');
            const data = await res.json();
            if (!data.success) { el.innerHTML = '<div class="cq-empty">Error al cargar.</div>'; return; }
            if (!data.data.length) { el.innerHTML = '<div class="cq-empty">No enviaste consultas aún.</div>'; return; }
            el.innerHTML = data.data.map(renderConsultaItem).join('');
            el.querySelectorAll('.cq-item').forEach(item => {
                item.addEventListener('click', () => openThread(item.dataset.id, null));
            });
        } catch { el.innerHTML = '<div class="cq-empty">Error de red.</div>'; }
    }

    async function loadReceivedList() {
        const el = document.getElementById('cq-received-list');
        if (!el) return;
        try {
            const res  = await fetch('/api/consultas.php?action=pending');
            const data = await res.json();
            if (!data.success) { el.innerHTML = '<div class="cq-empty">Error al cargar.</div>'; return; }
            if (!data.data.length) { el.innerHTML = '<div class="cq-empty">Sin consultas recibidas pendientes.</div>'; return; }
            el.innerHTML = data.data.map(renderReceivedItem).join('');
            el.querySelectorAll('.cq-item').forEach(item => {
                item.addEventListener('click', () => openThread(item.dataset.id, item.dataset.bizId));
            });
        } catch { el.innerHTML = '<div class="cq-empty">Error de red.</div>'; }
    }

    function tipoLabel(tipo) {
        return { masiva:'Masiva', general:'General', global_proveedor:'Proveedor', envio:'Envío' }[tipo] || tipo;
    }

    function renderConsultaItem(c) {
        return `<div class="cq-item" data-id="${c.id}" role="button" tabindex="0">
            <div class="cq-item-header">
                <span class="cq-item-tipo tipo-${esc(c.tipo)}">${tipoLabel(c.tipo)}</span>
                ${c.rubro ? `<span style="font-size:11px;color:#6b7280;">${esc(c.rubro)}</span>` : ''}
                <span class="cq-item-date">${esc(c.created_at)}</span>
            </div>
            <div class="cq-item-texto">"${esc(c.texto)}"</div>
            <div class="cq-item-stats">
                <span>🏪 ${c.total_dest} dest.</span>
                <span>💬 ${c.total_resp} resp.</span>
            </div>
        </div>`;
    }

    function renderReceivedItem(c) {
        const nuevo = !c.leido_en
            ? '<span style="background:#e53935;color:#fff;font-size:10px;padding:1px 6px;border-radius:50px;margin-left:4px;">Nuevo</span>'
            : '';
        return `<div class="cq-item" data-id="${c.id}" data-biz-id="${c.business_id}" role="button" tabindex="0">
            <div class="cq-item-header">
                <span class="cq-item-tipo tipo-${esc(c.tipo)}">${tipoLabel(c.tipo)}</span>
                <span style="font-size:11px;color:#6b7280;">→ ${esc(c.business_name)}</span>
                <span class="cq-item-date">${esc(c.created_at)}</span>
                ${nuevo}
            </div>
            <div class="cq-item-texto">"${esc(c.texto)}"</div>
        </div>`;
    }

    async function openThread(consultaId, businessId) {
        state.threadConsultaId = parseInt(consultaId, 10);
        state.threadBizId      = businessId ? parseInt(businessId, 10) : null;

        document.getElementById('cq-list-view').style.display = 'none';
        const tv = document.getElementById('cq-thread-view');
        tv.classList.add('active');
        document.getElementById('cq-thread-content').innerHTML =
            '<div class="cq-empty">⏳ Cargando hilo…</div>';

        /* Marcar como leído */
        if (businessId) {
            fetch('/api/consultas.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    action:      'mark_read',
                    consulta_id: parseInt(consultaId, 10),
                    business_id: parseInt(businessId, 10),
                }),
            }).catch(() => {});
        }

        try {
            const res  = await fetch('/api/consultas.php?action=thread&consulta_id=' + consultaId);
            const data = await res.json();
            if (!data.success) {
                document.getElementById('cq-thread-content').innerHTML =
                    '<div class="cq-empty">No se pudo cargar el hilo.</div>';
                return;
            }
            renderThread(data.data, state.threadBizId);
        } catch {
            document.getElementById('cq-thread-content').innerHTML =
                '<div class="cq-empty">Error de red.</div>';
        }
    }

    function renderThread(data, replyBizId) {
        const c    = data.consulta;
        const resp = data.respuestas || [];
        let html   = `<div class="cq-thread-consulta">
            <strong>${tipoLabel(c.tipo)} · ${esc(c.created_at)}</strong>
            ${esc(c.texto)}
        </div>`;

        if (resp.length) {
            html += resp.map(r => `
                <div class="cq-resp-item">
                    <div style="min-width:0;flex:1;">
                        <div class="cq-resp-biz">🏪 ${esc(r.business_name)}</div>
                        <div class="cq-resp-text">${esc(r.texto)}</div>
                    </div>
                    <div class="cq-resp-date">${esc(r.created_at)}</div>
                </div>`).join('');
        } else {
            html += '<div class="cq-empty" style="padding:12px 0 4px;">Sin respuestas aún.</div>';
        }

        /* Formulario de respuesta (solo si es destinatario) */
        if (replyBizId) {
            html += `<div class="cq-reply-form">
                <textarea id="cq-reply-text-${c.id}" maxlength="500"
                          placeholder="Tu respuesta (máx. 500 caracteres)…"></textarea>
                <button class="cq-reply-send"
                        onclick="cqSendReply(${c.id}, ${replyBizId})">📨 Responder</button>
                <div id="cq-reply-err-${c.id}" style="font-size:11px;color:#c0392b;min-height:14px;"></div>
            </div>`;
        }

        document.getElementById('cq-thread-content').innerHTML = html;
    }

    window.cqSendReply = async function (consultaId, businessId) {
        const textEl = document.getElementById('cq-reply-text-' + consultaId);
        const errEl  = document.getElementById('cq-reply-err-'  + consultaId);
        const texto  = textEl?.value.trim();
        if (!texto) { if (errEl) errEl.textContent = 'Escribí tu respuesta.'; return; }

        try {
            const res  = await fetch('/api/consultas.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    action:      'reply',
                    consulta_id: consultaId,
                    business_id: businessId,
                    texto,
                }),
            });
            const data = await res.json();
            if (data.success) {
                textEl.value = '';
                openThread(consultaId, businessId);
            } else {
                if (errEl) errEl.textContent = data.error || 'Error al enviar.';
            }
        } catch {
            if (errEl) errEl.textContent = 'Error de red.';
        }
    };

    /* ── Polling ────────────────────────────────────────────────────────────── */
    function startPolling() {
        if (state.pollTimer) return;
        state.pollTimer = setInterval(pollReplies, 8000);
    }

    async function pollReplies() {
        if (typeof SESSION_USER_ID === 'undefined' || SESSION_USER_ID <= 0) return;
        try {
            const res  = await fetch('/api/consultas.php?action=reply_count&since_id=' + state.lastReplyId);
            const data = await res.json();
            if (!data.success) return;

            const newReplies     = data.data.new_replies      || 0;
            const pendingReceived = data.data.pending_received || 0;
            const total           = newReplies + pendingReceived;

            const badge     = document.getElementById('cq-panel-badge');
            const toggleBtn = document.getElementById('cq-panel-toggle-btn');

            if (badge) {
                badge.textContent    = total;
                badge.style.display  = total > 0 ? 'inline-block' : 'none';
            }
            if (toggleBtn) {
                toggleBtn.classList.toggle('visible', total > 0 && !state.panelOpen);
            }

            if (state.panelOpen && newReplies > 0) {
                CQPanel.refresh();
                if (state.threadConsultaId) {
                    openThread(state.threadConsultaId, state.threadBizId);
                }
            }
        } catch { /* silencioso */ }
    }

    /* ── Utils ─────────────────────────────────────────────────────────────── */
    function requireLogin() {
        if (typeof SESSION_USER_ID === 'undefined' || SESSION_USER_ID <= 0) {
            showMapToast('⚠️ Debés registrarte para usar consultas masivas.');
            return false;
        }
        return true;
    }

    function esc(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function showMapToast(msg) {
        const t = document.createElement('div');
        t.className = 'selection-mode-toast';
        t.textContent = msg;
        t.style.cssText = [
            'position:fixed;top:70px;left:50%;transform:translateX(-50%);',
            'background:rgba(21,101,192,.92);color:#fff;padding:11px 20px;',
            'border-radius:10px;font-size:13px;font-weight:700;',
            'z-index:9999;pointer-events:none;',
            'box-shadow:0 4px 18px rgba(0,0,0,.25);white-space:nowrap;',
        ].join('');
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3200);
    }

})();
