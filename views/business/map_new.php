<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>🗺️ Mapita - Mapa de Negocios y Marcas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; height: 100vh; overflow: hidden; }
        
        #sidebar {
            width: 300px; min-width: 300px; height: 100%; background: #fff;
            display: flex; flex-direction: column; border-right: 3px solid #667eea;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px; color: white; text-align: center;
        }
        .header h1 { font-size: 1.8em; margin-bottom: 5px; }
        .header p { opacity: 0.8; font-size: 0.9em; }
        
        .filters { padding: 15px; background: #f8f9fa; border-bottom: 1px solid #eee; }
        .filters input {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 14px; margin-bottom: 10px;
        }
        
        .view-modes {
            display: flex; gap: 5px; margin-bottom: 10px;
        }
        .view-btn {
            flex: 1; padding: 10px; border: none; border-radius: 8px; cursor: pointer;
            font-size: 13px; font-weight: bold; transition: all 0.2s;
        }
        .view-btn.negocios { background: #e3f2fd; color: #1976d2; }
        .view-btn.marcas { background: #f3e5f5; color: #7b1fa2; }
        .view-btn.ambos { background: #e8f5e9; color: #388e3c; }
        .view-btn.active { box-shadow: 0 0 0 3px rgba(0,0,0,0.2); transform: scale(1.02); }
        
        .type-select {
            width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;
            background: white; font-size: 14px;
        }
        
        .stats {
            padding: 10px 15px; background: #667eea; color: white;
            display: flex; justify-content: space-around; font-weight: bold;
        }
        
        .actions {
            padding: 10px; display: flex; gap: 10px;
        }
        .actions button {
            flex: 1; padding: 12px; border: none; border-radius: 8px;
            background: #667eea; color: white; cursor: pointer; font-size: 13px;
        }
        
        #lista {
            flex: 1; overflow-y: auto; padding: 10px;
        }
        
        .item {
            padding: 12px; margin-bottom: 8px; border-radius: 8px; cursor: pointer;
            border-left: 4px solid #667eea; background: #f8f9fa; transition: all 0.2s;
        }
        .item:hover { background: #e3f2fd; transform: translateX(3px); }
        .item.marca { border-left-color: #9c27b0; background: #f3e5f5; }
        .item h3 { font-size: 14px; margin-bottom: 4px; color: #333; }
        .item p { font-size: 12px; color: #666; }
        .item .tipo { font-size: 10px; text-transform: uppercase; font-weight: bold; }
        
        .item.negocio .tipo { color: #1976d2; }
        .item.marca .tipo { color: #7b1fa2; }
        
        #map { flex: 1; height: 100%; }
        
        @media (max-width: 768px) {
            body { flex-direction: column; }
            #sidebar { width: 100%; height: 40%; order: 1; }
            #map { height: 60%; }
        }
    </style>
</head>
<body>

<div id="sidebar">
    <div class="header">
        <h1>🗺️ Mapita</h1>
        <p>Negocios y Marcas en Argentina</p>
    </div>
    
    <div class="filters">
        <input type="text" id="busqueda" placeholder="🔍 Buscar..." oninput="filtrar()">
        
        <div class="view-modes">
            <button class="view-btn negocios active" onclick="setVer('negocios')" id="btn-negocios">🏪 Negocios</button>
            <button class="view-btn marcas" onclick="setVer('marcas')" id="btn-marcas">🏷️ Marcas</button>
            <button class="view-btn ambos" onclick="setVer('ambos')" id="btn-ambos">👁️ Ambos</button>
        </div>
        
        <select id="tipo" class="type-select" onchange="filtrar()">
            <option value="">📂 Todos los tipos</option>
            <option value="comercio">🛒 Comercio</option>
            <option value="hotel">🏨 Hotel</option>
            <option value="restaurante">🍽️ Restaurante</option>
            <option value="inmobiliaria">🏠 Inmobiliaria</option>
            <option value="farmacia">💊 Farmacia</option>
            <option value="gimnasio">💪 Gimnasio</option>
            <option value="cafeteria">☕ Cafetería</option>
            <option value="academia">📚 Academia</option>
            <option value="bar">🍺 Bar</option>
            <option value="supermercado">🛍️ Supermercado</option>
            <option value="electricidad">⚡ Electricidad</option>
            <option value="transporte">🚛 Transporte</option>
            <option value="servicio">🔧 Servicios</option>
            <option value="empresa">🏢 Empresa</option>
        </select>
    </div>
    
    <div class="stats">
        <span id="stats-neg">0 Negocios</span>
        <span id="stats-mar">0 Marcas</span>
    </div>
    
    <div class="actions">
        <button onclick="ubicarme()">📍 Mi ubicación</button>
    </div>
    
    <div id="lista"></div>
</div>

<div id="map"></div>

<script>
let negocios = [];
let marcas = [];
let verActual = 'negocios';
let mapa, markers = [], miUbicacion = null;

const iconos = {
    comercio: '🛍️', hotel: '🏨', restaurante: '🍽️', inmobiliaria: '🏠',
    farmacia: '💊', gimnasio: '💪', cafeteria: '☕', academia: '📚',
    bar: '🍺', supermercado: '🛍️', electricidad: '⚡', transporte: '🚛',
    servicio: '🔧', empresa: '🏢', default: '📍'
};

const nizaColors = {'1':'#e74c3c','2':'#e67e22','3':'#f1c40f','4':'#2ecc71','5':'#1abc9c'};

function getIcon(tipo) { return iconos[tipo] || iconos.default; }
function getColor(clase) { return nizaColors[clase ? clase.split(',')[0].trim() : '5'] || '#3498db'; }

function setVer(val) {
    verActual = val;
    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-' + val).classList.add('active');
    filtrar();
}

function filtrar() {
    const tipo = document.getElementById('tipo').value;
    const texto = document.getElementById('busqueda').value.toLowerCase();
    
    let items = [];
    
    if (verActual === 'negocios' || verActual === 'ambos') {
        items = items.concat(negocios.filter(n => 
            (!tipo || n.business_type === tipo) && 
            (!texto || (n.name||'').toLowerCase().includes(texto) || (n.address||'').toLowerCase().includes(texto))
        ).map(n => ({...n, tipo: 'negocio'})));
    }
    
    if (verActual === 'marcas' || verActual === 'ambos') {
        items = items.concat(marcas.filter(m => 
            !texto || (m.nombre||'').toLowerCase().includes(texto)
        ).map(m => ({...m, tipo: 'marca'})));
    }
    
    document.getElementById('stats-neg').textContent = items.filter(i => i.tipo === 'negocio').length + ' Negocios';
    document.getElementById('stats-mar').textContent = items.filter(i => i.tipo === 'marca').length + ' Marcas';
    
    mostrarLista(items);
    if (mapa) mostrarMarkers(items);
}

function mostrarLista(items) {
    const cont = document.getElementById('lista');
    cont.innerHTML = '';
    
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'item ' + item.tipo;
        const nombre = item.nombre || item.name || 'Sin nombre';
        const tipo = item.business_type || item.rubro || item.tipo;
        
        div.innerHTML = `<h3>${item.tipo === 'marca' ? '🏷️' : '🏪'} ${nombre}</h3>
            <p>${item.ubicacion || item.address || 'Sin dirección'}</p>
            <span class="tipo">${tipo}</span>`;
        
        if (item.lat && item.lng) {
            div.onclick = () => mapa.setView([item.lat, item.lng], 15);
        }
        cont.appendChild(div);
    });
}

function mostrarMarkers(items) {
    markers.forEach(m => mapa.removeLayer(m));
    markers = [];
    
    items.forEach(item => {
        if (!item.lat || !item.lng) return;
        
        const isMarca = item.tipo === 'marca';
        let marker;
        
        if (isMarca) {
            marker = L.circleMarker([item.lat, item.lng], {
                radius: 14, fillColor: getColor(item.clase_principal),
                color: '#fff', weight: 2, fillOpacity: 0.85
            });
        } else {
            marker = L.circleMarker([item.lat, item.lng], {
                radius: 10, fillColor: '#1976d2', color: '#fff', weight: 2, fillOpacity: 0.8
            });
        }
        
        const nombre = item.nombre || item.name || 'Sin nombre';
        let popup = `<b>${isMarca ? '🏷️' : '🏪'} ${nombre}</b><br>${item.ubicacion || item.address || ''}`;
        
        if (isMarca && item.clase_principal) popup += `<br>📋 Clase: ${item.clase_principal}`;
        
        marker.bindPopup(popup);
        marker.addTo(mapa);
        markers.push(marker);
    });
}

function inicializar() {
    mapa = L.map('map').setView([-34.6037, -58.3816], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(mapa);
    filtrar();
}

function ubicarme() {
    if (!navigator.geolocation) { alert('Tu navegador no soporta geolocalización'); return; }
    navigator.geolocation.getCurrentPosition(pos => {
        miUbicacion = { lat: pos.coords.latitude, lng: pos.coords.longitude };
        L.circleMarker([miUbicacion.lat, miUbicacion.lng], {
            radius: 8, fillColor: '#4caf50', color: '#fff', weight: 2
        }).addTo(mapa).bindPopup('📍 Estás aquí').openPopup();
        mapa.setView([miUbicacion.lat, miUbicacion.lng], 14);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('Loading data...');
    fetch('/api/api_comercios.php')
        .then(r => r.json())
        .then(res => {
            console.log('Negocios:', res);
            if (res.success) negocios = res.data;
            return fetch('/api/brands.php');
        })
        .then(r => r.json())
        .then(res => {
            console.log('Marcas:', res);
            if (res.success) marcas = res.data;
            inicializar();
        })
        .catch(e => {
            console.error('Error:', e);
            inicializar();
        });
});
</script>
</body>
</html>