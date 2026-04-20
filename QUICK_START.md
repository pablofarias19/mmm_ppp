# 🚀 Quick Start - Probar Mejoras Inmediatamente

## ⚡ En 5 Minutos

### Paso 1: Acceder al Nuevo Formulario de Negocios

```
URL: https://mapita.com.ar/add
```

✅ **Verás:**
- Header con gradiente azul profesional
- Mapa Leaflet interactivo
- Secciones organizadas por categoría
- Input moderno con validación

### Paso 2: Completar Formulario

**Rellena lo básico:**
- Nombre: "Mi Negocio Prueba"
- Tipo: "Comercio"
- Dirección: "Calle 123, Ciudad"
- Precio: $$$

**Haz clic en el mapa** para establecer ubicación

### Paso 3: Cargar Fotos

1. Sección "📸 Fotos del Negocio"
2. Selecciona 2-3 imágenes (JPG/PNG/WebP)
3. Verás miniaturas 100x100px

### Paso 4: Agregar Detalles Profesionales

Rellena:
- Instagram: `@miusuario`
- Facebook: `Mi Negocio`
- Servicios: Marca "Delivery" y "Tarjeta"
- Certificaciones: `ISO9001, Orgánico`

### Paso 5: Enviar

Haz clic en "💾 Guardar Negocio"

**Esperado:** Mensaje de éxito ✓

---

## 🎨 Probar Nueva Paleta de Colores

### Dónde Verla:
1. **Mapa Principal:** `https://mapita.com.ar/map`
   - Barra flotante NEGOCIOS/MARCAS está **centrada y mejorada**
   - Colores: azul púrpura (#667eea) principal
   
2. **Formularios Nuevos:** `/add` y `/brand_form`
   - Headers con gradientes
   - Inputs con focus states profesionales

### Colores Principales:
```
🔵 Azul Púrpura:  #667eea  (Primario)
🟢 Verde:         #2ecc71  (Éxito)
🔴 Rojo:          #e74c3c  (Peligro)
🟠 Naranja:       #f39c12  (Advertencia)
🔷 Teal:          #00bfa5  (Secundario - Marcas)
⚫ Gris:           #f5f6fa  (Fondo)
```

---

## 📍 Verificar Filtro de Ubicación Mejorado

### En el Mapa:

1. Accede a `https://mapita.com.ar/map`
2. En el panel lateral, busca "📍 UBICACIÓN"
3. Marca el checkbox "Mostrar solo dentro de X km"
4. Mueve el slider de 1km a 10km

**Deberías ver:**
- ✅ Círculo dashed azul en el mapa
- ✅ Radio que se expande/contrae en tiempo real
- ✅ Tooltip: "📍 Radio de búsqueda: X km"
- ✅ Solo negocios dentro del círculo se muestran

### Botón Auto-detectar:
1. Haz clic en "📍 Auto-detectar"
2. Acepta permiso de geolocalización
3. Verás marcador rojo con tu ubicación
4. Círculo aparece alrededor

---

## 📸 Probar Foto Upload

### Carga de Fotos de Negocio:

1. Accede a `/add`
2. Sección "📸 Fotos del Negocio"
3. Selecciona hasta 5 imágenes

**Features:**
- ✅ Preview en tiempo real
- ✅ Muestra miniatura 100x100px
- ✅ Hover: aparece botón X para eliminar
- ✅ Validación: máx 2MB por imagen

### Carga de Logo de Marca:

1. Accede a `/brand_form`
2. Sección "🎨 Logo/Imagen"
3. Sube una imagen

**Features:**
- ✅ Preview completo del logo
- ✅ Formatos: JPG, PNG, WebP, SVG
- ✅ Máximo 3MB

---

## 🌍 Probar Nuevos Campos

### Para Negocios (`/add`):

**Sección 📞 Contacto (NUEVA):**
```
Instagram:  @miusuario
Facebook:   Mi Negocio
TikTok:     @miusuario
```

**Sección ✨ Servicios (NUEVA):**
```
☑ Delivery/Envío
☑ Acepta tarjeta
☑ Es franquicia
☑ Verificado
```

**Sección 🏪 Detalles del Comercio (MEJORADO):**
- Aparece automáticamente si seleccionas "Comercio"
- **NUEVO:** Horarios por día (Lunes-Domingo)
  - Hora apertura / cierre
  - Checkbox para "Cerrado este día"

---

### Para Marcas (`/brand_form`):

**Sección 🌍 Disponibilidad (NUEVA):**
```
☑ Local
☑ Regional  
☑ Nacional
☑ Internacional
```

**Sección 📦 Distribución (NUEVA):**
```
☑ Tienda Física
☑ E-commerce
☑ Mayorista
☑ Marketplace
```

**Sección 💰 Financiero (NUEVA):**
```
Ingresos Anuales:
  - Menor a $50k
  - $50k - $500k
  - $500k - $1M
  - $1M - $5M
  - Mayor a $5M
```

**Sección 📝 Descripción (MEJORADO):**
```
Año Fundación: 2015
Descripción Extendida: [Tu historia...]
```

---

## 📱 Responsive Design

### Probar en Móvil:

1. Abre `/add` en teléfono
2. Verifica:
   - ✅ Layout en 1 columna
   - ✅ Inputs son clickeables
   - ✅ Fotos se ven bien
   - ✅ Botones full-width

### Con DevTools (F12):

1. Abre formulario en Chrome
2. Presiona F12
3. Haz clic en "Toggle device toolbar" (📱)
4. Cambia entre:
   - iPhone 12
   - iPad
   - Desktop

**Debe verse bien en todos**

---

## 🔍 Verificar Base de Datos

### En tu Cliente MySQL:

**Ver nuevas columnas:**
```sql
DESCRIBE businesses;
-- Busca: instagram, facebook, tiktok, certifications, has_delivery, etc.

DESCRIBE brands;
-- Busca: scope, channels, annual_revenue, founded_year, extended_description
```

**Ver tabla de fotos:**
```sql
DESCRIBE attachments;
-- Debe tener: id, business_id, brand_id, file_path, type, uploaded_at
```

**Ver datos guardados:**
```sql
SELECT id, name, instagram, facebook, has_delivery FROM businesses LIMIT 1;
SELECT id, name, scope, channels, founded_year FROM brands LIMIT 1;
SELECT * FROM attachments LIMIT 1;
```

---

## 📁 Verificar Fotos en Servidor

### En Terminal/CMD:

```bash
# Ver carpeta de negocio (si creaste negocio con ID 1)
ls -la /uploads/businesses/1/
# Debe mostrar: photo_xxxxx.jpg, etc.

# Ver carpeta de marca (si creaste marca con ID 1)
ls -la /uploads/brands/1/
# Debe mostrar: logo.jpg o logo.png, etc.
```

---

## ✨ Probar Estilos Profesionales

### Elementos a Verificar:

**1. Tipografía (System Fonts):**
- Textos se ven "modernos" (no como Arial)
- Línea más thin/elegante

**2. Glassmorphism:**
- En Barra Flotante (NEGOCIOS/MARCAS)
- Efecto blur semi-transparente
- Solo visible en Chrome/Firefox recientes

**3. Gradientes:**
- Header formularios: gradiente suave
- Negocio: azul a púrpura
- Marca: teal a verde

**4. Sombras:**
- Botones elevan en hover
- Cards tienen sombra sutil
- Moderno y profesional

**5. Transiciones:**
- Al hacer hover en botones, mueven -2px up
- Inputs cambian border color suavemente
- Nada es instantáneo

---

## 🛠️ Si Algo No Funciona

### Error: Columnas no existen

**Solución:**
```bash
# Accede a /add en navegador
# Esto disparará DatabaseSetup.php automáticamente
# Las columnas se crearán

# O ejecuta manualmente:
mysql -u usuario -p database_name < migrations/add_professional_fields.sql
```

### Error: Fotos no se suben

**Solución:**
1. Verifica permisos: `chmod 0755 /uploads/businesses/`
2. Verifica espacio en disco
3. Verifica tamaño: máx 2MB por archivo
4. Verifica formato: JPG/PNG/WebP

### Error: Mapa no carga

**Solución:**
1. Verifica conexión a internet
2. Abre F12 → Console → ¿Errores CORS?
3. Verifica que Leaflet CDN es accesible

---

## 🎓 Casos de Uso Ejemplo

### Caso 1: Registrar Panadería

1. Accede a `/add`
2. Nombre: "Panadería La Abuela"
3. Tipo: Comercio
4. Tipo específico: Panadería
5. Precio: $$
6. Dirección: "Avenida 9 de Julio 500, CABA"
7. Haz clic en mapa para ubicación
8. Carga 3 fotos (fachada, productos, interior)
9. Instagram: @panaderialaabuela
10. Servicios: Delivery, Tarjeta
11. Horarios: L-V 7:00-20:00, S 8:00-13:00, D Cerrado
12. Guardar

**Resultado:** Panadería aparece en mapa con:
- Ubicación exacta
- 3 fotos
- Acceso a redes sociales
- Info de horarios

### Caso 2: Registrar Marca de Ropa

1. Accede a `/brand_form`
2. Nombre: "Fashion Brand XYZ"
3. Rubro: Moda
4. Año: 2018
5. Carga logo
6. Alcance: Nacional + Internacional
7. Distribución: E-commerce + Tienda Física
8. Ingresos: $1M - $5M
9. Descripción extendida: Tu historia...
10. Guardar

**Resultado:** Marca creada con información completa profesional

---

## 📊 Timeline de Implementación

```
Fase 1: Mejoras UI/UX (✅ COMPLETADA)
├── Paleta colores profesional
├── Barra flotante rediseñada
└── Filtro ubicación mejorado

Fase 2: Fotos y Campos (✅ COMPLETADA)
├── Upload de fotos
├── Campos redes sociales
├── Campos servicios
├── Horarios por día
└── Tabla attachments

Fase 3: Testing (🔄 AHORA)
├── Verificar formularios
├── Verificar base de datos
├── Verificar responsive
└── Verificar seguridad

Fase 4: Mejoras Futuras (⏳ PRÓXIMA)
├── Fotos en popups del mapa
├── Galería de fotos
└── SEO mejorado
```

---

## 📞 Soporte

### Documentación Disponible:

1. **IMPLEMENTATION_SUMMARY.md** - Detalles técnicos
2. **TESTING_GUIDE.md** - Testing exhaustivo
3. **QUICK_START.md** - Este archivo

### Logs de Error:

```bash
# Ver errores de PHP
tail -f /var/log/php-errors.log

# Ver errores SQL
tail -f /var/log/mysql/error.log

# O en navegador: F12 → Console → errores JavaScript
```

---

## 🎉 ¡Listo!

Tienes todo lo necesario para probar las mejoras profesionales de Mapita.

**Próximo paso:** Accede a `https://mapita.com.ar/add` y crea tu primer negocio con fotos y nuevos campos.

¡Disfruta las mejoras! 🚀

---

**Versión:** 1.0  
**Fecha:** 16 de Abril de 2026  
**Estado:** Listo para Usar
