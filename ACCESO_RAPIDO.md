# ⚡ ACCESO RÁPIDO - URLs y Shortcuts

**Guía rápida de acceso a todas las funciones principales**

---

## 🗺️ MAPA (Público)

```
https://tupagina.com
```

### ¿Qué ves?
- Mapa interactivo con negocios y marcas
- Selector NEGOCIOS/MARCAS mejorado (2 botones)
- Panel lateral con filtros
- Popups profesionales

### Nuevo Selector
```
┌──────────────────┐
│[🏪] [🏷️]       │
└──────────────────┘

🏪 = Mostrar/ocultar negocios
🏷️ = Mostrar/ocultar marcas

Ejemplos:
[🏪] activo  → Solo negocios
[🏷️] activo  → Solo marcas
Ambos activos → Ambos
Ninguno activo → Vacío
```

---

## 🛠️ PANEL DE ADMINISTRACIÓN

```
https://tupagina.com/admin/
```

### ¿Qué puedes hacer?
- 📰 Crear noticias/artículos
- 📅 Crear eventos/promociones
- 🎯 Crear trivias/juegos
- 📋 Crear encuestas

### Pasos para crear una noticia:
```
1. Abre: https://tupagina.com/admin/
2. Click pestaña: 📰 Noticias
3. Click botón: + Crear Noticia
4. Llena el formulario:
   - Título: "Mi noticia"
   - Contenido: "Descripción..."
   - Categoría: (opcional)
   - Imagen: (opcional)
5. Click: Guardar
```

### Pasos para crear un evento:
```
1. Abre: https://tupagina.com/admin/
2. Click pestaña: 📅 Eventos
3. Click botón: + Crear Evento
4. Llena el formulario:
   - Título: "Evento"
   - Descripción: "..."
   - Fecha/Hora: "2026-04-20 10:00"
   - Ubicación: "Dirección"
5. Click: Guardar
```

### Pasos para crear una trivia:
```
1. Abre: https://tupagina.com/admin/
2. Click pestaña: 🎯 Trivias
3. Click botón: + Crear Trivia
4. Llena el formulario:
   - Título: "Trivia título"
   - Descripción: "Sobre qué trata..."
   - Dificultad: Fácil/Medio/Difícil
   - Tiempo: 30 segundos
5. Click: Guardar
```

### Pasos para crear una encuesta:
```
1. Abre: https://tupagina.com/admin/
2. Click pestaña: 📋 Encuestas
3. Click botón: + Crear Encuesta
4. Llena el formulario:
   - Título: "¿Qué prefieres?"
   - Descripción: "Ayúdanos a..."
   - Preguntas: "Opción 1|Opción 2|Opción 3"
5. Click: Guardar
```

---

## 📊 ESTADO DEL SISTEMA

```
https://tupagina.com/status.html
```

### ¿Qué ves?
- ✅ / ❌ Estado de BD
- ✅ / ❌ Tablas creadas
- ✅ / ❌ Archivos encontrados
- Recomendaciones si hay errores

---

## 🔧 APIs (Para desarrolladores)

### Obtener noticias
```
GET https://tupagina.com/api/noticias.php
```
Retorna todas las noticias activas en JSON

### Obtener eventos
```
GET https://tupagina.com/api/eventos.php
```
Retorna todos los eventos en JSON

### Obtener trivias
```
GET https://tupagina.com/api/trivias.php
```
Retorna todas las trivias en JSON

### Obtener encuestas
```
GET https://tupagina.com/api/encuestas.php
```
Retorna todas las encuestas en JSON

### Obtener iconos
```
GET https://tupagina.com/api/api_iconos.php
```
Retorna 32+ iconos con colores

### Diagnóstico
```
GET https://tupagina.com/api/api_diagnostics.php
```
Retorna estado completo del sistema en JSON

---

## 📱 RESPONSIVE

Todas las URLs funcionan en:
- ✅ Desktop
- ✅ Tablet  
- ✅ Mobile

---

## 🎯 CHECKLIST RÁPIDA

Después de las mejoras, verifica:

```
☐ Mapa cargado
☐ Selector NEGOCIOS/MARCAS visible (2 botones)
☐ Consola sin errores (F12)
☐ Panel admin accesible: /admin/
☐ Puedo crear noticia
☐ Puedo crear evento
☐ Puedo crear trivia
☐ Puedo crear encuesta
☐ Los cambios aparecen en mapa
```

---

## 🔗 ESTRUCTURA DE CARPETAS

```
mapitaV/
├── admin/
│   └── index.php              ← Panel de administración
│
├── api/
│   ├── api_iconos.php         ← Iconos dinámicos
│   ├── noticias.php           ← Noticias API
│   ├── eventos.php            ← Eventos API
│   ├── trivias.php            ← Trivias API
│   └── encuestas.php          ← Encuestas API
│
├── views/business/
│   └── map.php                ← Mapa (modificado)
│
├── css/
│   ├── popup-redesign.css     ← Popups profesionales
│   └── brand-popup-premium.css
│
├── status.html                ← Dashboard estado
│
└── ADMIN_GUIDE.md             ← Guía de administración
   CAMBIOS_UX_RESUMEN.md       ← Resumen de cambios
   ACCESO_RAPIDO.md            ← Este archivo
```

---

## 🆘 Ayuda Rápida

### "No puedo acceder al panel admin"
```
1. Verifica URL: https://tupagina.com/admin/
2. Comprueba que /admin/index.php existe en servidor
3. Abre consola: F12 → Console
4. Si ves errores, reporta
```

### "Los cambios no aparecen"
```
1. Recarga página: F5 o Ctrl+Shift+R
2. Espera 2-3 segundos
3. Abre consola F12 → Console
4. Si hay errores, anota
```

### "Selector sigue mostrando 4 botones"
```
1. Recarga: F5 (fuerza caché: Ctrl+Shift+R)
2. Limpia cookies/caché del navegador
3. Prueba en navegador diferente
4. Si persiste, contacta soporte
```

### "Panel admin se ve pero no guarda"
```
1. Abre consola: F12
2. Revisa Network tab al guardar
3. Busca error en response
4. Verifica que tablas de BD existan
5. Ejecuta /config/migration.sql si falta
```

---

## 💡 SHORTCUTS DEL TECLADO

### En el Mapa
```
F12                 = Abrir consola
Ctrl+F              = Buscar en página
Esc                 = Cerrar popup
```

### En Panel Admin
```
ESC                 = Cerrar modal
Tab                 = Navegar campos
Enter (en campo)    = Enviar formulario
```

---

## 📚 DOCUMENTACIÓN DISPONIBLE

| Archivo | Contenido |
|---------|----------|
| `ADMIN_GUIDE.md` | Guía detallada del panel |
| `CAMBIOS_UX_RESUMEN.md` | Resumen de mejoras |
| `ACCESO_RAPIDO.md` | Este archivo |
| `START_HERE.md` | Primeros pasos |
| `MIGRATION_DEPLOYMENT_GUIDE.md` | Migración BD |

---

## ✨ RESUMEN

**Antes de estas mejoras:**
```
❌ Crear contenido = Acceso a BD + SQL
❌ Selector = 4 botones confusos
❌ Solo 9 tipos de negocios
```

**Después (AHORA):**
```
✅ Crear contenido = Panel visual intuitivo
✅ Selector = 2 toggles eficientes
✅ 32+ tipos de negocios dinámicos
```

---

## 🚀 PRÓXIMOS PASOS

1. **Abre el mapa**
   ```
   https://tupagina.com
   ```
   Verifica nuevo selector NEGOCIOS/MARCAS

2. **Accede al panel admin**
   ```
   https://tupagina.com/admin/
   ```
   Intenta crear algo (noticia/evento/trivia)

3. **Revisa estado**
   ```
   https://tupagina.com/status.html
   ```
   Verifica que todo esté ✅

4. **Lee documentación**
   - `ADMIN_GUIDE.md` → Detalles del panel
   - `CAMBIOS_UX_RESUMEN.md` → Qué cambió

---

**¡Listo para usar!** 🎉

Si tienes dudas, consulta `ADMIN_GUIDE.md` para más detalles.
