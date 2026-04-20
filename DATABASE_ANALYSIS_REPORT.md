# 📊 ANÁLISIS DE INCONSISTENCIAS - BASE DE DATOS MAPITA

## 🔍 RESUMEN EJECUTIVO

Se encontraron **TRES BASES DE DATOS** definidas en archivos SQL:
- **u580580751_lulu.sql** - BD para artículos/noticias (NO USADA)
- **u580580751_map1.sql** - BD alternativa con tablas `negocios`, `usuarios`, `eventos`
- **u580580751_map.sql** - BD ACTIVA en production con tablas `businesses`, `brands`

**PROBLEMA CRÍTICO:** El código PHP estaba referenciando tablas de **u580580751_map1** cuando debería usar **u580580751_map**

---

## 🔴 DISCREPANCIAS ENCONTRADAS

### 1. Tabla de Negocios
| Aspecto | u580580751_map.sql | u580580751_map1.sql | Código PHP | Status |
|---------|-------------------|-------------------|-----------|--------|
| Tabla | `businesses` | `negocios` | ✓ Usa `businesses` | ✓ CORRECTO |
| Nombre negocio | `name` | `nombre_comercial` | ✓ Usa `name` | ✓ CORRECTO |
| Tipo negocio | `business_type` | `id_categoria` | ✓ Usa `business_type` | ✓ CORRECTO |
| Geolocalización | `lat`, `lng` | `latitud`, `longitud` | ✓ Usa `lat`, `lng` | ✓ CORRECTO |
| Visible | `visible` (tinyint) | `activo` (tinyint) | ✓ Usa `visible` | ✓ CORRECTO |

### 2. Tabla de Marcas/Brands
| Aspecto | u580580751_map.sql | u580580751_map1.sql | Código PHP | Status |
|---------|-------------------|-------------------|-----------|--------|
| Tabla | `brands` | `marcas` | ❌ Estaba usando `marcas` | ⚠️ ARREGLADO |
| Nombre marca | `nombre` | NO EXISTE | ✓ Ahora usa `nombre` | ✓ CORRECTO |
| Geolocalización | `lat`, `lng` | NO EXISTE | ✓ Ahora usa `lat`, `lng` | ✓ CORRECTO |
| Visible | `visible` | NO EXISTE | ✓ Ahora usa `visible` | ✓ CORRECTO |

### 3. Tabla de Fotos
| Aspecto | u580580751_map.sql | u580580751_map1.sql | Código PHP | Status |
|---------|-------------------|-------------------|-----------|--------|
| Tabla | `attachments` ✓ | NO EXISTE | ✓ Usa `attachments` | ✓ CORRECTO |
| Campos | business_id, brand_id, file_path | NO EXISTE | ✓ Correcto | ✓ CORRECTO |

### 4. Tabla de Iconos
| Aspecto | u580580751_map.sql | Código JS | Status |
|---------|-------------------|-----------|--------|
| Tabla | `business_icons` ✓ | No consultada (hardcoded) | ⚠️ NO USADA |
| Datos | Contiene 550+ tipos de negocio | Usa tipoEmojis literal | ⚠️ INEFICIENTE |

---

## 🔧 CORRECCIONES REALIZADAS

### ✅ 1. Arreglado: Brand.php
**Antes:**
```php
$sql = "SELECT m.* FROM marcas m WHERE m.estado = 'activa'";
```

**Después:**
```php
$sql = "SELECT b.* FROM brands b WHERE b.visible = 1";
```

### ✅ 2. Tabla Attachments Creada
Se creó exitosamente la tabla `attachments` en la BD

---

## ⚠️ PROBLEMAS PENDIENTES

### Problema 1: Brands NO aparecen en el mapa
- ✓ API devuelve 5 marcas correctamente
- ❌ Pero no se renderizan en el mapa
- **Posible causa:** El floating selector está en "NEGOCIOS" por defecto
- **Solución:** Click en "AMBOS" para ver marcas

### Problema 2: business_icons no se usa
- Tabla existe con 550+ tipos de negocio
- Código JS usa diccionario hardcoded con solo ~50 tipos
- **Impacto:** Negocios con tipos no mapeados muestran emoji default

### Problema 3: Estructura Data Inconsistente
- Map.php espera ciertos campos en JSON de API
- Pero algunos campos están NULL en la BD
- **Ejemplo:** horario_apertura, horario_cierre están NULL

---

## 📝 COORDINACIÓN REQUERIDA

### Entre SQL y PHP:

| Proceso | Tabla | Campos | Visibilidad |
|---------|-------|--------|-------------|
| Mostrar negocios | businesses | name, lat, lng, business_type, visible=1 | ✓ |
| Mostrar marcas | brands | nombre as name, lat, lng, visible=1 | ✓ |
| Cargar fotos | attachments | business_id/brand_id, file_path | ✓ |
| Mostrar iconos | business_icons | NO USADO (hardcoded) | ✗ |

### Entre PHP y JavaScript:

| Componente | Espera | Recibe | Status |
|-----------|--------|--------|--------|
| API Negocios | businesses array | 5 registros | ✓ |
| API Marcas | brands array | 5 registros | ✓ |
| Map.js | nombre/name | Existe | ✓ |
| Map.js | lat, lng | Existe | ✓ |
| Map.js | business_type | Existe para negocios | ✓ |
| Map.js | Emoji dict lookup | Hardcoded 50 tipos | ⚠️ |

---

## 🚀 RECOMENDACIONES FINALES

### URGENTE:
1. ✅ Verificar que marcas aparezcan con floating selector en "AMBOS"
2. Confirmar que los 5 negocios y 5 marcas se muestren correctamente

### MEJORA:
1. Usar tabla `business_icons` en lugar de hardcoded emojis
2. Llenar datos en tabla `comercios` o integrar campos en `businesses`
3. Poblar horarios en BD para mostrar "Abierto/Cerrado"

### MANTENIMIENTO:
1. Documentar cada tabla y su propósito
2. Sincronizar entre u580580751_map1.sql (backup) y u580580751_map.sql (producción)
3. Eliminar referencia a u580580751_lulu.sql si no se usa

---

## 📊 ESTADO ACTUAL

```
✅ CORRECCIONES APLICADAS:
   - Brand.php ahora usa tabla 'brands' ✓
   - Columna 'nombre' mapeada como 'name' ✓
   - Tabla 'attachments' creada ✓

⚠️ POR VERIFICAR:
   - ¿Se ven 5 negocios en el mapa? 
   - ¿Se ven 5 marcas al seleccionar "AMBOS"?
   - ¿Los iconos aparecen dentro de los pines?

❌ REVISAR SI DATOS DESAPARECIERON:
   - Usar: verify_data.php
```

---

**Última actualización:** 2026-04-16 07:18:00
**BD ACTIVA:** u580580751_map
