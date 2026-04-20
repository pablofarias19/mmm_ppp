# 🧪 Guía de Testing - Mapita Mejorada

## Verificación de Implementación Completa

### Fase 1: Verificar Estructura de Directorios

#### ✅ Directorios Creados:
```bash
✓ /uploads/businesses/        # Listo para fotos de negocios
✓ /uploads/brands/            # Listo para logos de marcas
✓ /migrations/                # Scripts SQL
✓ /core/                       # Nuevas utilidades
```

#### ✅ Archivos Creados:
```bash
✓ views/business/add.php                      (Formulario mejorado de negocios)
✓ views/brand/form.php                        (Formulario mejorado de marcas)
✓ core/DatabaseSetup.php                      (Inicializador automático)
✓ core/MigrationRunner.php                    (Helper de migraciones)
✓ migrations/add_professional_fields.sql      (Script de migración)
✓ IMPLEMENTATION_SUMMARY.md                   (Documentación)
✓ TESTING_GUIDE.md                            (Este archivo)
```

#### ✅ Archivos Modificados:
```bash
✓ index.php                                   (Nuevas rutas + DatabaseSetup)
✓ business/process_business.php               (Nuevos campos validados)
✓ views/business/map.php                      (Ya mejorado en fase anterior)
```

---

## Fase 2: Pruebas de Base de Datos

### Test 2.1: Verificar Columnas en `businesses`

**SQL a ejecutar:**
```sql
DESCRIBE businesses;
```

**Debe mostrar estas columnas NUEVAS:**
- ✅ instagram
- ✅ facebook
- ✅ tiktok
- ✅ certifications
- ✅ has_delivery
- ✅ has_card_payment
- ✅ is_franchise
- ✅ verified

**Si NO aparecen:** DatabaseSetup.php las creará automáticamente en el próximo acceso a `/add`

### Test 2.2: Verificar Tabla `attachments`

**SQL a ejecutar:**
```sql
DESCRIBE attachments;
```

**Debe mostrar:**
- ✅ id (INT PRIMARY KEY)
- ✅ business_id (INT FK)
- ✅ brand_id (INT FK)
- ✅ file_path (VARCHAR)
- ✅ type (ENUM: 'photo', 'document', 'logo')
- ✅ uploaded_at (TIMESTAMP)

### Test 2.3: Verificar Columnas en `brands`

**SQL a ejecutar:**
```sql
DESCRIBE brands;
```

**Debe mostrar estas columnas NUEVAS:**
- ✅ scope
- ✅ channels
- ✅ annual_revenue
- ✅ founded_year
- ✅ extended_description

---

## Fase 3: Pruebas Funcionales (UI)

### Test 3.1: Acceder al Formulario de Negocios

**URL:** `https://mapita.com.ar/add`

**Verificar:**
- ✅ Página carga sin errores
- ✅ Header con gradiente azul púrpura
- ✅ Formulario completo visible
- ✅ Mapa Leaflet cargado
- ✅ Estilos profesionales aplicados

**Secciones Visibles:**
- ✅ 📍 Ubicación
- ✅ 📋 Información Básica
- ✅ 📞 Contacto
- ✅ 📸 Fotos del Negocio
- ✅ ✨ Servicios y Características
- ✅ 🏪 Detalles del Comercio (si seleccionas "Comercio")

### Test 3.2: Acceder al Formulario de Marcas

**URL:** `https://mapita.com.ar/brand_form`

**Verificar:**
- ✅ Página carga sin errores
- ✅ Header con gradiente teal
- ✅ Logo upload field visible
- ✅ Secciones expandidas para marcas
- ✅ Estilos consistentes con formulario de negocios

**Secciones Visibles:**
- ✅ 📋 Información de la Marca
- ✅ 🎨 Logo/Imagen
- ✅ 🌍 Disponibilidad y Distribución
- ✅ 💰 Datos Financieros
- ✅ 🛡️ Protección y Condiciones

### Test 3.3: Validación de Formularios

#### 3.3.1 Formulario de Negocios

**Test: Campo "Nombre" requerido**
1. Intenta enviar sin nombre
2. Debe mostrar: "Este campo es obligatorio"
3. ✅ PASS si no permite envío

**Test: Campos de coordenadas**
1. No hagas clic en el mapa
2. Intenta enviar
3. Debe mostrar error
4. Haz clic en el mapa
5. Latitud y longitud se rellenan
6. ✅ PASS si se actualizan automáticamente

**Test: Auto-detectar ubicación**
1. Haz clic en "📍 Auto-detectar"
2. Acepta permiso de geolocalización
3. Debe actualizar lat/lng
4. Debe mover mapa a tu ubicación
5. ✅ PASS si funciona en 2-3 segundos

#### 3.3.2 Foto Upload

**Test: Cargar fotos**
1. Haz clic en "Fotos del Negocio"
2. Selecciona 1-5 imágenes JPG/PNG/WebP
3. Debe mostrar preview en miniaturas
4. ✅ PASS si muestra 100x100px thumbnails

**Test: Eliminar fotos**
1. Con previews visibles
2. Hace clic en foto (hover muestra X)
3. Hace clic en X
4. Photo desaparece de preview
5. ✅ PASS si se elimina correctamente

**Test: Validación de tamaño**
1. Intenta cargar imagen > 2MB
2. JavaScript debe validar
3. ✅ PASS si muestra error o ignora

### Test 3.4: Campos Nuevos en Formularios

#### 3.4.1 Redes Sociales (Negocios)

**Ingresa datos:**
```
Instagram: @miusuario
Facebook: Mi Negocio
TikTok: @miusuario
```

**Verifica:**
- ✅ Campos aceptan hasta 100 caracteres
- ✅ Se guardan correctamente en BD

#### 3.4.2 Certificaciones (Negocios)

**Ingresa:**
```
ISO9001, BPA, Orgánico
```

**Verifica:**
- ✅ Se guarda como texto
- ✅ Máximo 500 caracteres permitido

#### 3.4.3 Servicios (Negocios)

**Marca checkboxes:**
- ✅ Delivery/Envío
- ✅ Acepta tarjeta
- ✅ Es franquicia
- ✅ Verificado

**Verifica:**
- ✅ Se guardan como booleanos (1/0)
- ✅ Se pueden verificar después en BD

#### 3.4.4 Horarios por Día (Comercios)

**Pasos:**
1. Selecciona "Comercio" en tipo de negocio
2. Aparece sección 🏪 Detalles del Comercio
3. Dentro hay sección 🕐 Horarios de Atención
4. Debe haber 7 filas (Lunes-Domingo)

**Ingresa horarios:**
```
Lunes: 09:00 - 18:00
Martes: 09:00 - 18:00
Miércoles: 09:00 - 18:00
Jueves: 09:00 - 18:00
Viernes: 09:00 - 18:00
Sábado: 10:00 - 14:00
Domingo: Cerrado (marcar checkbox)
```

**Verifica:**
- ✅ Campos time aceptan formato hh:mm
- ✅ Checkbox "Cerrado" funciona
- ✅ Se guardan en BD

---

## Fase 4: Pruebas de Guardado

### Test 4.1: Crear Negocio Completo

**Pasos:**
1. Accede a `/add`
2. Completa todos los campos requeridos
3. Carga 2-3 fotos
4. Rellena redes sociales
5. Marca servicios
6. Haz clic en "Guardar Negocio"

**Verifica:**
- ✅ Muestra mensaje de éxito
- ✅ No aparecen errores
- ✅ Los datos se guardan en BD

**En Base de Datos:**
```sql
SELECT * FROM businesses WHERE name = 'Tu Negocio' LIMIT 1;
```

**Debe mostrar:**
- ✅ instagram, facebook, tiktok rellenos
- ✅ has_delivery = 1 o 0
- ✅ has_card_payment = 1 o 0
- ✅ is_franchise = 1 o 0
- ✅ verified = 1 o 0
- ✅ certifications = tu texto

**Verificar fotos:**
```bash
ls -la /uploads/businesses/{id}/
```

**Debe contener:**
- ✅ photo_xxxxx.jpg
- ✅ photo_xxxxx.png
- ✅ etc (según archivos cargados)

### Test 4.2: Crear Marca Completa

**Pasos:**
1. Accede a `/brand_form`
2. Rellena información básica
3. Carga logo
4. Marca opciones de alcance (ej: Nacional + Internacional)
5. Marca canales (ej: E-commerce + Tienda Física)
6. Selecciona rango de ingresos
7. Ingresa año de fundación
8. Haz clic en "Guardar Marca"

**Verifica:**
- ✅ Muestra mensaje de éxito
- ✅ Redirecciona a /brands en 2 segundos
- ✅ Los datos se guardan

**En Base de Datos:**
```sql
SELECT * FROM brands WHERE name = 'Tu Marca' LIMIT 1;
```

**Debe mostrar:**
- ✅ scope = 'nacional,internacional'
- ✅ channels = 'ecommerce,tienda_fisica'
- ✅ annual_revenue = '1m-5m' (o rango seleccionado)
- ✅ founded_year = 2020 (o año ingresado)
- ✅ extended_description = tu texto

**Verificar logo:**
```bash
ls -la /uploads/brands/{id}/
```

**Debe contener:**
- ✅ logo.jpg o logo.png o logo.webp

---

## Fase 5: Pruebas de Responsividad

### Test 5.1: Desktop (1920x1080)

**Verificar:**
- ✅ Layout en 2 columnas
- ✅ Mapa ocupa ancho completo
- ✅ Botones al lado derecho
- ✅ Sin scroll horizontal

### Test 5.2: Tablet (768x1024)

**Verificar:**
- ✅ Layout cambia a 1 columna
- ✅ Inputs tienen tamaño cómodo
- ✅ Mapa visible completamente
- ✅ Botones full-width

### Test 5.3: Mobile (375x812)

**Verificar:**
- ✅ Todo sigue siendo accesible
- ✅ Inputs tienen mínimo 44px altura
- ✅ Fotos preview en grid vertical
- ✅ Checkboxes son clickeables fácilmente

---

## Fase 6: Pruebas de Seguridad

### Test 6.1: CSRF Token

**Pasos:**
1. Inspecciona el HTML del formulario
2. Busca `<input type="hidden" name="csrf_token">`
3. Debe estar presente

**Verifica:**
- ✅ Token está presente
- ✅ Token cambia cada vez que recargas

### Test 6.2: Validación de Archivo

**Intenta:**
```
1. Cargar archivo.exe
2. Cargar archivo.pdf
3. Cargar imagen 5MB
```

**Debe rechazar:**
- ✅ Archivos no-imagen
- ✅ Imágenes > 2MB

### Test 6.3: SQL Injection

**Intenta en campo "Nombre":**
```
'; DROP TABLE businesses; --
```

**Verifica:**
- ✅ No causa error
- ✅ Tabla sigue intacta
- ✅ Texto se guarda escaped

---

## Fase 7: Pruebas de Compatibilidad Navegadores

### Test 7.1: Chrome

- ✅ Formularios cargan
- ✅ Mapa funciona
- ✅ Geolocalización funciona
- ✅ File upload funciona

### Test 7.2: Firefox

- ✅ Estilos se muestran correctamente
- ✅ Gradientes visibles
- ✅ Animaciones suaves

### Test 7.3: Safari

- ✅ Glassmorphism visible (blur effect)
- ✅ System fonts se cargan
- ✅ Responsive design funciona

### Test 7.4: Edge

- ✅ Compatibilidad con variables CSS
- ✅ Grid layout funciona
- ✅ Flexbox centra correctamente

---

## Checklist de Verificación Final

### Base de Datos
- [ ] Tabla `attachments` existe
- [ ] Columnas nuevas en `businesses` existen
- [ ] Columnas nuevas en `brands` existen
- [ ] Índices creados para performance

### Archivos
- [ ] `/views/business/add.php` existe y carga
- [ ] `/views/brand/form.php` existe y carga
- [ ] `/core/DatabaseSetup.php` existe
- [ ] `/uploads/` directories existen con permisos 0755

### Rutas
- [ ] `/add` apunta a nuevo formulario
- [ ] `/brand_form` apunta a nuevo formulario
- [ ] `/brand_new` funciona
- [ ] `/brand_edit?id=X` funciona

### Funcionalidad
- [ ] Foto upload y preview funcionan
- [ ] Formularios guardan datos correctamente
- [ ] Validación rechaza datos inválidos
- [ ] Mensajes de error/éxito aparecen
- [ ] Responsive design en mobile/tablet/desktop

### Estilo
- [ ] Colores profesionales aplicados
- [ ] Tipografía moderna (system fonts)
- [ ] Glassmorphism visible en elementos
- [ ] Animaciones suaves (transiciones)

---

## Troubleshooting

### Problema: Columnas no se crean automáticamente

**Solución:**
1. Accede a `/add` para disparar DatabaseSetup
2. Revisa error_log del servidor
3. Si persiste, ejecuta manualmente:
```bash
mysql -u usuario -p base_datos < migrations/add_professional_fields.sql
```

### Problema: Fotos no se suben

**Solución:**
1. Verifica permisos: `chmod 0755 /uploads/businesses/`
2. Verifica tamaño: máx 2MB por archivo
3. Revisa error_log para detalles

### Problema: Mapa Leaflet no carga

**Solución:**
1. Verifica conexión a internet (CDN)
2. Abre consola de navegador (F12)
3. Busca errores de CORS
4. Verifica que URL de tile layer es válida

### Problema: Formulario lento en móvil

**Solución:**
1. Reduce número de fotos en preview
2. Comprime imágenes antes de cargar
3. Verifica velocidad de conexión

---

## Comandos Útiles

### Verificar datos guardados:
```sql
SELECT id, name, instagram, facebook, certifications FROM businesses;
SELECT id, name, scope, channels, founded_year FROM brands;
```

### Limpiar fotos huérfanas:
```bash
# Las carpetas de negocios/marcas eliminados se pueden borrar manualmente
rm -rf /uploads/businesses/999/  # si business_id 999 no existe
```

### Resetear tabla attachments:
```sql
DELETE FROM attachments;
ALTER TABLE attachments AUTO_INCREMENT = 1;
```

---

## Próximas Mejoras Sugeridas

- [ ] Mostrar fotos en popups del mapa
- [ ] Carrusel de fotos en vista de detalle
- [ ] Redimensionamiento automático de imágenes
- [ ] Compression de imágenes en servidor
- [ ] Caché de miniaturas
- [ ] Galería lightbox

---

**Fecha:** 16 de Abril de 2026  
**Versión:** 1.0  
**Estado:** Listo para Testing
