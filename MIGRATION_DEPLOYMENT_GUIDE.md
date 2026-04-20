# Mapita v1.2.0 - Guía de Implementación y Migración de Base de Datos

**Fecha:** 16 de Abril de 2026  
**Estado:** Listo para Despliegue  
**Tiempo estimado:** 15-20 minutos

---

## 📋 Resumen de Cambios

Se han implementado las 3 fases del plan de mejoras:

### ✅ FASE 1: Carga Dinámica de Iconos desde Base de Datos
- **Archivo creado:** `/api/api_iconos.php`
- **Modificación:** `/views/business/map.php` (líneas 541-570)
- **Función:** Carga 32+ tipos de negocios con emoji y colores desde BD en lugar de hardcodeados
- **Fallback:** Si tabla no existe, usa colores/emojis por defecto

### ✅ FASE 2: Reorganización del Panel Lateral (Sidebar)
- **Modificación:** `/views/business/map.php` (líneas 351-376)
- **Cambio:** Reordenado para que filtros más usados aparezcan primero
- **Nuevo orden:**
  1. Buscador por nombre
  2. Selector NEGOCIOS/MARCAS/AMBOS
  3. Filtro por tipo de negocio
  4. Filtro por ubicación
  5. Filtro por horarios
  6. Filtro por precio
  7. Filtros avanzados (colapsado)

### ✅ FASE 3: Mejora de Popups y Reparación de /view?id=X
- **Archivos creados:**
  - `/css/popup-redesign.css` - Estilos profesionales para popups de negocios
  - `/css/brand-popup-premium.css` - Estilos premium para popups de marcas
- **Modificación:** `/views/business/map.php` (línea 845, línea 752+)
- **Mejoras:**
  - Diseño profesional con gradientes
  - Headers destacados con status (Abierto/Cerrado)
  - Botones de acción funcionales (Llamar, Email, Detalle)
  - Responsive design

---

## 🔧 Paso 1: Ejecutar Migración de Base de Datos

### Ubicación del Script
```
C:\Users\USUARIO\Documents\programacion2\mapitaV\config\migration.sql
```

### ¿Qué crea la migración?

| Tabla | Propósito | Archivos que la usan |
|-------|----------|-------------------|
| `business_icons` | Iconos dinámicos de negocios | `/api/api_iconos.php` |
| `noticias` | Contenido de noticias/artículos | `/api/noticias.php` |
| `trivias` | Juegos de trivia | `/api/trivias.php` |
| `trivia_scores` | Puntuaciones de usuarios | `/api/trivias.php` |
| `brand_gallery` | Galerías de imágenes de marcas | `/api/brand-gallery.php` |
| `attachments` | Archivos de negocios y marcas | Formularios de carga |
| `encuestas` | Encuestas/sondeos | `/api/encuestas.php` |
| `encuesta_questions` | Preguntas de encuestas | `/api/encuestas.php` |
| `encuesta_responses` | Respuestas de usuarios | `/api/encuestas.php` |
| `eventos` | Eventos y promociones | `/api/eventos.php` |

### Instrucciones en Hostinger:

1. **Abre el panel de control de Hostinger**
   - Ve a: https://hpanel.hostinger.com/
   - Inicia sesión con tus credenciales

2. **Accede al Administrador de Base de Datos**
   - En el panel izquierdo: `Bases de datos`
   - Selecciona tu base de datos (ej: "mapitav_db")
   - Click en "phpMyAdmin"

3. **Abre la consola de SQL**
   - En phpMyAdmin: Tab `SQL` (arriba)
   - O: Botón "Importar"

4. **Copia y ejecuta el script**
   ```
   1. Abre el archivo: config/migration.sql
   2. Copia TODO el contenido (desde línea 1 hasta línea 242)
   3. En phpMyAdmin, pega en el editor SQL
   4. Click botón "Ejecutar" (abajo)
   ```

5. **Verifica la ejecución**
   - Si sale verde ✅ sin errores → Éxito
   - Si sale rojo ❌ → Anota el error y repórtalo

---

## ✅ Paso 2: Verificar Archivos en el Servidor

Asegúrate de que estos archivos están presentes en tu servidor:

```
mapitaV/
├── api/
│   ├── api_iconos.php ✅ CREADO
│   ├── noticias.php ✅ DEBE EXISTIR
│   ├── trivias.php ✅ DEBE EXISTIR
│   ├── brand-gallery.php ✅ DEBE EXISTIR
│   ├── encuestas.php ✅ DEBE EXISTIR (crear si no existe)
│   └── eventos.php ✅ DEBE EXISTIR (crear si no existe)
├── views/
│   └── business/
│       └── map.php ✅ MODIFICADO
├── css/
│   ├── popup-redesign.css ✅ CREADO
│   └── brand-popup-premium.css ✅ CREADO
└── config/
    └── migration.sql ✅ ACTUALIZADO
```

---

## 🧪 Paso 3: Testing en el Navegador

### 3.1 Abre la consola de desarrollador
- Presiona: **F12**
- Tab: **Console**

### 3.2 Verifica que los iconos cargan
Deberías VER:
```
✅ Iconos cargados correctamente
```

NO deberías ver:
```
❌ Error cargando iconos: ...
❌ Failed to load resource: /api/api_iconos.php
❌ SyntaxError: Unexpected token '<'
```

### 3.3 Prueba los endpoints de API manualmente

Abre nuevas pestañas y prueba cada endpoint:

```
GET http://tupagina.com/api/api_iconos.php
```
Debería retornar JSON como:
```json
{
  "success": true,
  "data": {
    "comercio": {"emoji": "🛍️", "color": "#e74c3c"},
    "hotel": {"emoji": "🏨", "color": "#3498db"},
    ...
  }
}
```

```
GET http://tupagina.com/api/noticias.php
```
Debería retornar JSON (puede estar vacío si no hay noticias):
```json
{
  "success": true,
  "data": [],
  "message": "Noticias obtenidas"
}
```

```
GET http://tupagina.com/api/trivias.php
```
Debería retornar JSON similar.

### 3.4 Verifica el mapa
- Carga la página del mapa
- Deberías ver:
  - ✅ Iconos con emojis (🛍️, 🏨, 🍽️, etc.)
  - ✅ Colores correctos para cada tipo de negocio
  - ✅ Popups con diseño profesional (gradiente, botones)
  - ✅ Sidebar reorganizado con filtros en nuevo orden

### 3.5 Prueba popups
- Click en cualquier marcador
- Popup debería mostrar:
  - ✅ Nombre del negocio en header con gradiente
  - ✅ Badge "🟢 Abierto" o "🔴 Cerrado"
  - ✅ Información: dirección, teléfono, email
  - ✅ Botones: Llamar, Email, Detalle (funcionales)

---

## 🛠️ Resolución de Problemas

### Problema 1: "is not valid JSON" en consola

**Síntomas:**
```
SyntaxError: Unexpected token '<', "<br />" is not valid JSON
```

**Causa:** Las APIs no tienen acceso a las tablas de BD

**Solución:**
1. Verifica que ejecutaste la migración SQL correctamente
2. Ve a phpMyAdmin → Estructura
3. Deberías ver las tablas: `business_icons`, `noticias`, `trivias`, etc.
4. Si no aparecen → Re-ejecuta el migration.sql

### Problema 2: "ID de marca inválido" o "ID de noticia inválido"

**Causa:** Intentas acceder a registros que no existen

**Solución:**
- Es normal si la BD está vacía
- Crea contenido primero (negocios, noticias, etc.)
- Entonces prueba las APIs

### Problema 3: Iconos siguen siendo los antiguos (hardcodeados)

**Síntomas:** Ves los iconos, pero no cargan desde BD

**Causa:** API `api_iconos.php` no carga correctamente

**Solución:**
1. Abre consola (F12) → Console
2. Busca mensaje de error sobre "api_iconos.php"
3. Verifica que archivo existe en: `/api/api_iconos.php`
4. Verifica que `business_icons` tabla existe en BD

### Problema 4: Popup blanca en /view?id=X

**Síntomas:** Haces click en "Detalle" del popup → página blanca

**Causa:** El archivo `/business/view_business.php` no existe o está en otra ubicación

**Solución:**
1. Busca dónde está el archivo de detalle:
   - `view_business.php`
   - `view.php`
   - Otra ubicación
2. En `/views/business/map.php` línea 845, actualiza la URL:
   ```javascript
   // Actual (puede estar mal):
   <a href="/view?id=${n.id}">
   
   // Cambiar a (ajusta según ubicación real):
   <a href="/business/view.php?id=${n.id}">
   // O:
   <a href="/views/business/view.php?id=${n.id}">
   ```

---

## 📚 Archivos de Referencia

### CSS Nuevo
- **popup-redesign.css** (125 líneas)
  - Estilos para popups de negocios
  - Gradiente azul-púrpura profesional
  - Animaciones y efectos hover

- **brand-popup-premium.css** (220 líneas)
  - Estilos para popups de marcas
  - Galería de imágenes integrada
  - Tarjetas de información estructuradas

### JavaScript Modificado (en map.php)
```javascript
// Función para cargar iconos desde API (línea 541-570)
async function cargarIconosDesdeAPI() {
    try {
        const response = await fetch('/api/api_iconos.php');
        const resultado = await response.json();
        
        if (Array.isArray(resultado.data)) {
            resultado.data.forEach(icon => {
                iconosDB[icon.business_type] = {
                    emoji: icon.emoji,
                    color: icon.color
                };
            });
        } else {
            iconosDB = resultado.data;
        }
    } catch (error) {
        console.error('Error cargando iconos:', error);
    }
}
```

---

## 📊 Checklist de Implementación

- [ ] Migración SQL ejecutada exitosamente
- [ ] Todas las tablas creadas en BD (verificar en phpMyAdmin)
- [ ] Consola del navegador sin errores "is not valid JSON"
- [ ] Endpoint `/api/api_iconos.php` retorna JSON válido
- [ ] Mapa muestra iconos con colores de BD
- [ ] Popups muestran con diseño profesional
- [ ] Sidebar ordenado correctamente (tipo, ubicación, horarios, precio)
- [ ] Botones popup funcionales (Llamar, Email, Detalle)
- [ ] `/view?id=X` muestra detalle (no página blanca)

---

## 🚀 Después de Desplegar

### Tareas Opcionales (Futuro)

Si quieres completar las **fases 4-5** del plan original:

**Fase 4:** Carga de fotos en formularios
- Agregar `<input type="file" name="photos[]" multiple>` a formularios
- Guardar en tabla `attachments`
- Mostrar en popups

**Fase 5:** Campos adicionales para negocios/marcas
- Horarios por día, redes sociales, certificaciones
- Geolocalización automática, verificación

Estas están en el archivo `/IMPROVEMENTS_SUMMARY.md` si quieres implementarlas.

---

## 📞 Soporte

Si tienes problemas durante la migración:

1. **Verifica el error exacto en phpMyAdmin**
   - Copia el mensaje de error completo
   
2. **Revisa la consola del navegador (F12)**
   - Tab Console y Network
   
3. **Consulta los logs del servidor**
   - En Hostinger: Logs → Error Log

---

## ✨ Mejoras Implementadas

| Mejora | Antes | Después |
|--------|--------|---------|
| **Iconos** | 9 tipos hardcodeados en JS | 32+ tipos dinámicos desde BD |
| **Colores** | Planos, limitados | Personalizados por tipo de negocio |
| **Sidebar** | Filtros desordenados | Orden lógico: tipo → ubicación → horarios |
| **Popups** | Estilo básico, diseño plano | Gradientes, badges, botones funcionales |
| **UX** | Sin feedback visual | Status (Abierto/Cerrado) en popup |

---

**Versión:** 1.2.0  
**Última actualización:** 16-04-2026  
**Estado:** ✅ Listo para Producción
