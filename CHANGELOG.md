# Historial de Cambios - Mapita

## Última Actualización: 15/04/2026

---

## Cambios Recientes

### 1. Condiciones de Marca en Popup

Ahora cada marca muestra sus condiciones específicas en el popup al hacer click:

**Campos añadidos a la tabla `marcas`:**
- `tiene_zona` (tinyint) - Si la marca tiene zona de influencia
- `zona_radius_km` (int) - Radio de la zona en km
- `tiene_licencia` (tinyint) - Si tiene licencia
- `licencia_detalle` (varchar) - Detalle de la licencia
- `es_franquicia` (tinyint) - Si es franquicia
- `franchise_details` (varchar) - Detalles de la franquicia
- `zona_exclusiva` (tinyint) - Si tiene zona exclusiva
- `zona_exclusiva_radius_km` (int) - Radio de zona exclusiva

**Archivos modificados:**
- `config/migration.sql` - Añadida migración con nuevas columnas
- `views/business/map.php` - Popup ahora muestra badges de condiciones

---

## Archivos Principales del Proyecto

### Mapa Principal
- `views/business/map.php` - Mapa interactivo con negocios y marcas

### API
- `api/brands.php` - API de marcas
- `api/businesses.php` - API de negocios

### Modelos
- `models/Brand.php` - Modelo de marcas
- `models/Business.php` - Modelo de negocios

### Configuración
- `config/database.php` - Conexión a BD
- `config/migration.sql` - Migraciones de BD
- `index.php` - Front controller

---

## Funcionalidades del Mapa

### Toggle Ver
- 🏪 Negocios
- 🏷️ Marcas
- 👁️ Ambos

### Condiciones de Marca (sidebar)
- 🌐 Zonas - Muestra zonas de influencia
- 📜 Licencias - Muestra marcas con licencia
- 🏢 Franquicias - Muestra franquicias
- 🎯 Zonas Exclusivas - Muestra zonas exclusivas

### Opciones de Negocio (sidebar)
- 🏢 Mis negocios - Ver tus negocios
- 🏠 Zonas Inmobiliarias - Ver zonas de inmobiliarias

### Botones
- 📍 Ubicación - Centrar en tu ubicación
- 🧾 PDF - Exportar a PDF

---

## Archivos de Prueba (no subir)

Los siguientes archivos son de prueba y deben excluirse:
- `test*.php`, `test*.html`
- `hello.php`, `t1.php`, `t2.php`
- `index_minimal.php`, `simple_test.html`
- `test_db_check.php`, `test_db2.php`, etc.

---

## Notas para Producción

1. Ejecutar `config/migration.sql` en la base de datos
2. Las marcas existentes no tendrán condiciones hasta ser editadas
3. Verificar que las rutas en `index.php` funcionen correctamente

---

## Historial de Cambios Anteriores

- ✅ Error 500 en index.php corregido (rutas incorrectas)
- ✅ API de marcas creada (`/api/brands.php`)
- ✅ Modelo Brand actualizado con métodos de coordenadas
- ✅ Mapa muestra negocios Y marcas
- ✅ Toggle selector Negocio/Marca/Ambos
- ✅ Popup mejorado con datos de marca
- ✅ Zonas de influencia para marcas (círculos)
- ✅ Checkboxes para Licencias, Franquicias, Zonas Exclusivas
- ✅ Funcionalidades equivalentes para negocios
- ✅ Sidebar mejorado con botones ordenados
- ✅ favicon.svg añadido