<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🗺️ Mapita</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="/css/map-styles.css">
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: sans-serif; display: flex; height: 100vh; }
        #sidebar { width: 280px; background: #667eea; padding: 20px; color: white; overflow-y: auto; }
        #map { flex: 1; }
        h1 { margin-bottom: 15px; }
        .btn { display: block; width: 100%; padding: 12px; margin-bottom: 8px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; background: white; color: #333; }
        .btn.active { background: #ffeb3b; }
        #lista { margin-top: 20px; }
        .item { background: rgba(255,255,255,0.2); padding: 10px; margin-bottom: 8px; border-radius: 8px; cursor: pointer; }
        .item:hover { background: rgba(255,255,255,0.4); }
    </style>
</head>
<body>
    <div id="sidebar">
        <h1>🗺️ Mapita</h1>
        <p style="margin-bottom:15px;opacity:0.8">Negocios y Marcas</p>
        <button class="btn active" onclick="vista='negocios';actualizar()">🏪 Negocios</button>
        <button class="btn" onclick="vista='marcas'">🏷️ Marcas</button>
        <button class="btn" onclick="vista='ambos'">👁️ Ambos</button>
        <div id="stats" style="margin:15px 0;font-weight:bold"></div>
        <input type="text" id="buscar" placeholder="Buscar..." oninput="actualizar()" style="width:100%;padding:10px;border-radius:8px;border:none;">
        <div id="lista"></div>
    </div>
    <div id="map"></div>
    <script>
        let mapa, markers = [], negocios = [], marcas = [], vista = 'negocios';
        
        function init() {
            mapa = L.map('map').setView([-34.6,-58.4], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{}).addTo(mapa);
            cargarDatos();
        }
        
        function cargarDatos() {
            Promise.all([
                fetch('/api/api_comercios.php').then(r=>r.json()).catch(()=>({data:[]})),
                fetch('/api/brands.php').then(r=>r.json()).catch(()=>({data:[]}))
            ]).then(([n,m])=>{
                if(n.data) negocios = n.data;
                if(m.data) marcas = m.data;
                actualizar();
            });
        }
        
        function actualizar() {
            markers.forEach(x=>mapa.removeLayer(x));
            markers = [];
            
            let items = [];
            if(vista==='negocios'||vista==='ambos') items = items.concat(negocios.map(x=>({...x,tipo:'n'})));
            if(vista==='marcas'||vista==='ambos') items = items.concat(marcas.map(x=>({...x,tipo:'m'})));
            
            let buscar = document.getElementById('buscar').value.toLowerCase();
            if(buscar) items = items.filter(x=>(x.name||x.nombre||'').toLowerCase().includes(buscar));
            
            document.getElementById('stats').innerHTML = `Neg: ${negocios.length} | Mar: ${marcas.length}`;
            
            let lista = document.getElementById('lista');
            lista.innerHTML = '';
            items.forEach(x=>{
                if(x.lat && x.lng) {
                    let c = x.tipo==='m'?'#9c27b0':'#1976d2';
                    let m = L.circleMarker([x.lat,x.lng],{radius:10,fillColor:c,color:'#fff',fillOpacity:0.8}).addTo(mapa);
                    m.bindPopup((x.nombre||x.name||''));
                    markers.push(m);
                }
                let d = document.createElement('div');
                d.className = 'item';
                d.innerHTML = (x.nombre||x.name||'Sin nombre') + '<br><small>' + (x.business_type||x.rubro||'') + '</small>';
                lista.appendChild(d);
            });
        }
        
        document.addEventListener('DOMContentLoaded',init);
    </script>
</body>
</html>