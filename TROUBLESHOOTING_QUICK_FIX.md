# 🚨 Troubleshooting Rápido - Error "is not valid JSON"

**Estado Actual:** APIs retornando HTML en lugar de JSON  
**Causa:** Base de datos no ha sido migrada (tablas no existen)  
**Solución:** Ejecutar script de migración SQL

---

## ⚡ SOLUCIÓN RÁPIDA (5 minutos)

### Paso 1: Abrir phpMyAdmin en Hostinger

```
1. Ve a https://hpanel.hostinger.com/
2. Usuario/Contraseña de Hostinger
3. Menú izquierdo: "Bases de datos"
4. Selecciona tu base de datos (ej: mapitav_db)
5. Click botón "phpMyAdmin" → Se abre en nueva ventana
```

### Paso 2: Ejecutar Script de Migración

**Opción A - Por archivo (Recomendado):**
```
1. En phpMyAdmin, top: Tab "Importar"
2. Click "Seleccionar archivo"
3. Busca: C:\Users\USUARIO\Documents\programacion2\mapitaV\config\migration.sql
4. Click "Ejecutar"
5. Espera a que termine (debe decir "Consultas ejecutadas exitosamente")
```

**Opción B - Por copia-pega (si importar no funciona):**
```
1. En phpMyAdmin, top: Tab "SQL"
2. Abre archivo: C:\Users\USUARIO\Documents\programacion2\mapitaV\config\migration.sql
3. Copia TODO el contenido (Ctrl+A, Ctrl+C)
4. En phpMyAdmin, pega en el editor de SQL
5. Click botón "Ejecutar" (abajo)
```

### Paso 3: Verificar Éxito

Después de ejecutar, deberías ver:
```
✅ Se ejecutaron X consultas exitosamente
```

Si ves ❌ error rojo, anota el error exacto y continúa con "Verificar con Diagnostics" abajo.

### Paso 4: Recargar Página del Mapa

```
1. Abre: https://tupagina.com (o localhost)
2. Presiona: F5 o Ctrl+Shift+R (recargar caché)
3. Abre consola: F12 → Tab "Console"
4. Busca errores de JSON
```

**Debería decir:**
```
✅ Iconos cargados correctamente
✅ Noticias obtenidas
✅ Trivias activas obtenidas
```

---

## 🔍 Si Sigue Fallando: Verificar con Diagnostics

### Verificar Estado de Base de Datos

**En el navegador, abre:**
```
https://tupagina.com/api/api_diagnostics.php
```

**Deberías ver JSON como:**
```json
{
  "status": "OK - Todo funciona correctamente",
  "database": {
    "connected": true,
    "name": "mapitav_db"
  },
  "tables": {
    "business_icons": {
      "exists": true,
      "rows": 32,
      "status": "✅"
    },
    "noticias": {
      "exists": true,
      "rows": 0,
      "status": "✅"
    }
    ...
  },
  "ready_for_production": true
}
```

### Interpretar Resultados

**Si ves `"ready_for_production": true`:**
- ✅ Base de datos está bien
- El problema puede estar en otra parte
- Continúa con "Revisar Consola del Navegador"

**Si ves `"ready_for_production": false` y tablas con `"exists": false`:**
- ❌ Migración no fue ejecutada correctamente
- Anota qué tablas faltan
- Intenta ejecutar migración de nuevo

**Si ves `"status": "ERROR"`:**
- Hay problema con conexión a BD
- Verifica credenciales en `.env`
- Contacta soporte Hostinger

---

## 📱 Revisar Consola del Navegador

### Abrir consola
```
Presiona: F12
Click en tab: "Console"
```

### ¿Qué significa cada error?

**Error 1: "SyntaxError: Unexpected token '<'"**
```
Causa: API retorna HTML en lugar de JSON
Solución: Ejecutar migración SQL (paso anterior)
```

**Error 2: "Failed to load resource: 500 Internal Server Error"**
```
Causa: API tiene exception
Solución: 
  1. Verificar migración SQL ejecutada
  2. Revisar que tablas existen en phpMyAdmin
  3. Ver error log de Hostinger
```

**Error 3: "TypeError: Cannot read property of undefined"**
```
Causa: API retorna JSON vacío o mal formado
Solución: Misma que Error 1 - ejecutar migración
```

**Error 4: "NetworkError: A network error occurred"**
```
Causa: No hay conexión a servidor
Solución: 
  1. Verificar URL correcta
  2. Verificar que servidor está activo
  3. Verificar permisos de acceso
```

---

## 🔧 Verificar en phpMyAdmin

### Paso 1: Listar tablas
```
1. Abre phpMyAdmin (pasos anteriores)
2. En la lista izquierda, deberías ver las tablas:
   ✅ business_icons
   ✅ noticias
   ✅ trivias
   ✅ trivia_scores
   ✅ brand_gallery
   ✅ attachments
   ✅ encuestas
   ✅ encuesta_questions
   ✅ encuesta_responses
   ✅ eventos
```

**Si no ves estas tablas:**
- Migración no fue ejecutada
- Vuelve a ejecutar config/migration.sql

### Paso 2: Verificar datos en business_icons
```
1. Click en tabla "business_icons"
2. Tab "Examinar"
3. Deberías ver 32 filas (comercio, hotel, restaurante, etc.)
4. Cada una con: emoji (🛍️, 🏨, etc.) y color (#e74c3c, etc.)
```

**Si está vacía o no existe:**
- Migración falló
- Ver error exacto al ejecutar migración
- Intenta DROP + CREATE manualmente

---

## 📋 Verificar Archivos en Servidor

### Archivos que DEBEN existir

En tu servidor (visible en panel de Hostinger → Archivos):
```
mapitaV/
├── api/
│   ├── api_iconos.php ✅ CRÍTICO
│   ├── api_diagnostics.php ✅ ÚTIL
│   ├── noticias.php ✅
│   ├── trivias.php ✅
│   ├── brand-gallery.php ✅
│   └── ...
├── css/
│   ├── popup-redesign.css ✅
│   └── brand-popup-premium.css ✅
├── views/business/
│   └── map.php ✅ MODIFICADO
└── config/
    └── migration.sql ✅
```

**Si falta alguno:**
- Sube archivo desde computadora a servidor
- O verifica que última subida incluyó todos

---

## 🆘 Última Solución: Reset Completo

Si nada funciona después de todo lo anterior:

### Opción 1: Re-crear tabla manualmente
```sql
-- En phpMyAdmin, Tab SQL, ejecuta:

DROP TABLE IF EXISTS business_icons;

CREATE TABLE business_icons (
    id INT NOT NULL AUTO_INCREMENT,
    business_type VARCHAR(100) NOT NULL UNIQUE,
    emoji VARCHAR(10) NOT NULL,
    icon_class VARCHAR(100) NULL,
    color VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO business_icons (business_type, emoji, icon_class, color) VALUES
('comercio', '🛍️', 'icon-comercio', '#e74c3c'),
('hotel', '🏨', 'icon-hotel', '#3498db'),
('restaurante', '🍽️', 'icon-restaurante', '#e67e22');
-- ... (agregar los 32 tipos)
```

### Opción 2: Contactar Soporte Hostinger
- Información a proporcionar:
  - Error exacto de migración
  - Tabla que no se crea
  - Versión de MySQL
  - Acceso al panel (si autorizas)

---

## 📞 Checklist Rápido

- [ ] Migración SQL ejecutada en phpMyAdmin
- [ ] Tablas visibles en phpMyAdmin (listar tablas)
- [ ] business_icons tiene 32 registros
- [ ] Página del mapa recargada (F5)
- [ ] Consola sin errores de "is not valid JSON"
- [ ] API endpoints retornan JSON (no HTML)
- [ ] Mapa muestra iconos con colores
- [ ] Popups se abren correctamente

---

## ⏱️ Tiempo Estimado

| Paso | Tiempo |
|------|--------|
| Abrir phpMyAdmin | 1 min |
| Ejecutar migración | 2 min |
| Esperar resultado | 1 min |
| Recargar mapa | 1 min |
| Verificar consola | 2 min |
| **TOTAL** | **7 min** |

---

**Si después de ejecutar migración aún ves errores:**

Abre URL de diagnostics:
```
https://tupagina.com/api/api_diagnostics.php
```

Y comparte el resultado (JSON completo) para análisis detallado.

---

**Última actualización:** 16-04-2026
