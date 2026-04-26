/**
 * js/geo-search.js
 * Reutilizable: búsqueda de dirección con Nominatim (OpenStreetMap).
 *
 * Uso:
 *   initGeoSearch({
 *     map,           // instancia L.map ya inicializada
 *     getMarker,     // () => marcadorActual | null
 *     setMarker,     // (L.Marker) => void
 *     latInputId,    // id del input de latitud
 *     lngInputId,    // id del input de longitud
 *     searchInputId, // id del input de búsqueda
 *     searchBtnId,   // id del botón Buscar
 *     resultsDivId,  // id del div donde se muestran sugerencias
 *   });
 */
function initGeoSearch(opts) {
    var searchInput  = document.getElementById(opts.searchInputId);
    var searchBtn    = document.getElementById(opts.searchBtnId);
    var resultsDiv   = document.getElementById(opts.resultsDivId);
    var latInput     = document.getElementById(opts.latInputId);
    var lngInput     = document.getElementById(opts.lngInputId);

    if (!searchInput || !searchBtn || !resultsDiv || !latInput || !lngInput) return;

    function clearResults() {
        resultsDiv.innerHTML = '';
        resultsDiv.style.display = 'none';
    }

    function setLocation(lat, lon, displayName) {
        var latlng = L.latLng(parseFloat(lat), parseFloat(lon));
        opts.map.setView(latlng, 16);

        var marker = opts.getMarker();
        if (marker) opts.map.removeLayer(marker);

        var newMarker = L.marker(latlng, { icon: opts.markerIcon || undefined }).addTo(opts.map);
        opts.setMarker(newMarker);

        latInput.value = parseFloat(lat).toFixed(7);
        lngInput.value = parseFloat(lon).toFixed(7);

        // Completar input con resultado seleccionado
        searchInput.value = displayName;
        clearResults();
    }

    function showError(msg) {
        resultsDiv.innerHTML =
            '<div style="padding:10px 12px;color:#b91c1c;font-size:.85em;">' +
            '⚠️ ' + msg + '</div>';
        resultsDiv.style.display = 'block';
    }

    function doSearch() {
        var q = searchInput.value.trim();
        if (!q) return;

        searchBtn.disabled = true;
        searchBtn.textContent = '🔍…';
        clearResults();

        var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' +
                  encodeURIComponent(q);

        var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var timer = setTimeout(function() {
            if (controller) controller.abort();
        }, 8000);

        fetch(url, {
            headers: { 'Accept-Language': 'es' },
            signal: controller ? controller.signal : undefined
        })
        .then(function(r) {
            clearTimeout(timer);
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            searchBtn.disabled = false;
            searchBtn.textContent = '🔍 Buscar';

            if (!data || data.length === 0) {
                showError('No se encontraron resultados para "' + q + '".');
                return;
            }

            if (data.length === 1) {
                setLocation(data[0].lat, data[0].lon, data[0].display_name);
                return;
            }

            // Mostrar lista de resultados
            resultsDiv.style.display = 'block';
            var html = '<ul style="list-style:none;margin:0;padding:0;">';
            data.forEach(function(item) {
                html += '<li data-lat="' + item.lat + '" data-lon="' + item.lon +
                        '" data-name="' + item.display_name.replace(/"/g, '&quot;') + '"' +
                        ' style="padding:9px 12px;cursor:pointer;font-size:.84em;border-bottom:1px solid #f3f4f6;' +
                        'line-height:1.4;transition:background .15s;"' +
                        ' onmouseover="this.style.background=\'#f0f4ff\'"' +
                        ' onmouseout="this.style.background=\'\'">' +
                        '📍 ' + item.display_name + '</li>';
            });
            html += '</ul>';
            resultsDiv.innerHTML = html;

            resultsDiv.querySelectorAll('li').forEach(function(li) {
                li.addEventListener('click', function() {
                    setLocation(li.dataset.lat, li.dataset.lon, li.dataset.name);
                });
            });
        })
        .catch(function(err) {
            clearTimeout(timer);
            searchBtn.disabled = false;
            searchBtn.textContent = '🔍 Buscar';
            if (err.name === 'AbortError') {
                showError('Tiempo de espera agotado. Verificá tu conexión e intentá de nuevo.');
            } else {
                showError('Error de red. Verificá tu conexión e intentá de nuevo.');
            }
        });
    }

    searchBtn.addEventListener('click', doSearch);
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
    });

    // Cerrar lista al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!resultsDiv.contains(e.target) && e.target !== searchInput && e.target !== searchBtn) {
            clearResults();
        }
    });
}
