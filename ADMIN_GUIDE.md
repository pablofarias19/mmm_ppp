# 🛠️ Panel de Administración - Guía de Uso

**Acceso:** `https://tupagina.com/admin/`  
**Versión:** 1.2.0  
**Última actualización:** 16-04-2026

---

## 🎯 ¿Qué es?

Panel de administración intuitivo para gestionar:
- 📰 **Noticias** - Artículos y contenido
- 📅 **Eventos** - Promociones y eventos especiales
- 🎯 **Trivias** - Juegos y concursos
- 📋 **Encuestas** - Sondeos para usuarios

---

## 🚀 Acceso Rápido

### URL del Panel
```
https://tupagina.com/admin/
```

Abrirá el panel con las siguientes pestañas:

| Pestaña | Función | Icono |
|---------|---------|-------|
| Noticias | Crear/editar artículos | 📰 |
| Eventos | Crear/editar eventos | 📅 |
| Trivias | Crear/editar juegos | 🎯 |
| Encuestas | Crear/editar sondeos | 📋 |

---

## 📝 Crear una Noticia

### Paso 1: Abrir pestaña
Click en la pestaña **📰 Noticias**

### Paso 2: Crear nueva
Click en botón **+ Crear Noticia**

### Paso 3: Llenar formulario
```
Título:      "Inauguración nueva sucursal"
Contenido:   "Nos complace anunciar la apertura de..."
Categoría:   "Negocios" (opcional)
Imagen:      "https://ejemplo.com/foto.jpg" (opcional)
Activo:      ☑ (activado por defecto)
```

### Paso 4: Guardar
Click en **Guardar**

**Resultado:** Noticia visible en sidebar bajo "Noticias" y en la página de noticias

---

## 📅 Crear un Evento

### Paso 1: Abrir pestaña
Click en **📅 Eventos**

### Paso 2: Crear nuevo
Click en **+ Crear Evento**

### Paso 3: Llenar formulario
```
Título:         "Gran Liquidación de Verano"
Descripción:    "Descuentos hasta 50% en..."
Fecha y Hora:   "2026-04-20 14:30" (ej)
Ubicación:      "Centro Comercial, Avenida Principal"
Activo:         ☑
```

### Paso 4: Guardar
Click en **Guardar**

**Resultado:** Evento aparece en el mapa y sidebar

---

## 🎯 Crear una Trivia

### Paso 1: Abrir pestaña
Click en **🎯 Trivias**

### Paso 2: Crear nueva
Click en **+ Crear Trivia**

### Paso 3: Llenar formulario
```
Título:          "Trivia de Geografía"
Descripción:     "¿Cuánto sabes de mapas?"
Dificultad:      "Medio" (Fácil/Medio/Difícil)
Tiempo Límite:   "30" segundos
```

### Paso 4: Guardar
Click en **Guardar**

**Resultado:** Trivia disponible en widget de trivias

---

## 📋 Crear una Encuesta

### Paso 1: Abrir pestaña
Click en **📋 Encuestas**

### Paso 2: Crear nueva
Click en **+ Crear Encuesta**

### Paso 3: Llenar formulario
```
Título:      "¿Qué servicio prefieres?"
Descripción: "Ayúdanos a mejorar..."
Preguntas:   "Atención personalizada|Chat|Email|Teléfono"
             (separadas por |)
Activo:      ☑
```

### Paso 4: Guardar
Click en **Guardar**

**Resultado:** Encuesta disponible para usuarios

---

## ✏️ Editar Contenido

### Desde el Panel
1. Abre el panel y ve a la pestaña deseada
2. Busca el item en la lista
3. Click en botón **Editar**
4. Modifica los campos
5. Click en **Guardar**

### Desde la Lista
Cada item muestra:
- **Título**
- **Resumen** (primeras 80 caracteres)
- **Fecha**
- **Estado** (Activo/Inactivo)
- **Acciones** (Editar/Eliminar)

---

## 🗑️ Eliminar Contenido

### Opción 1: Panel
1. En la lista del item
2. Click en **Eliminar**
3. Confirmar en diálogo

### Opción 2: Desactivar
En lugar de eliminar, puedes desactivar:
1. Click **Editar**
2. Desmarcar ☐ **Activo**
3. Click **Guardar**

> **Nota:** Desactivar no elimina, solo oculta el item

---

## 🔄 Actualizaciones en Tiempo Real

Los cambios se reflejan automáticamente:

| Cambio | Dónde aparece |
|--------|--------------|
| Crear Noticia | Sidebar, Página noticias |
| Crear Evento | Mapa, Sidebar |
| Crear Trivia | Widget trivia |
| Crear Encuesta | Widget encuesta |

**Tiempo:** Inmediato (sin recargar página)

---

## 🎨 Mejor Selector NEGOCIOS/MARCAS

Se ha mejorado el selector flotante en el mapa:

### Antes (4 botones)
```
[🏪 NEGOCIOS] [🏷️ MARCAS] [👁️ AMBOS] [✖ NINGUNO]
```
❌ Opciones mutuamente excluyentes  
❌ Menos eficiente  

### Ahora (2 toggles independientes)
```
[🏪 NEGOCIOS] [🏷️ MARCAS]
```
✅ Cada botón es un toggle ON/OFF  
✅ Clickea para mostrar/ocultar  
✅ Más intuitivo y eficiente  

#### Cómo usar:
- **Click en 🏪 NEGOCIOS** → Muestra/oculta negocios
- **Click en 🏷️ MARCAS** → Muestra/oculta marcas
- **Ambos activos** = Ver negocios y marcas
- **Solo negocios** = Solo negocios
- **Solo marcas** = Solo marcas
- **Ninguno activo** = No ver nada (vacío)

**Ventajas:**
- Más clicks posibles (2 en lugar de 4)
- Mejor UX (toggles son más intuitivos)
- Menos confusión (sin opción "Ambos" explícita)
- Más rápido cambiar entre vistas

---

## 📊 Ejemplo: Flujo Completo

### Objetivo: Crear evento de promoción

1. **Accede al panel**
   ```
   https://tupagina.com/admin/
   ```

2. **Click pestaña Eventos**
   ```
   📅 Eventos
   ```

3. **Click "Crear Evento"**
   ```
   + Crear Evento
   ```

4. **Llena el formulario**
   ```
   Título:      "Black Friday Adelantado"
   Descripción: "Hasta 70% en ropa y accesorios"
   Fecha:       "2026-05-01 10:00"
   Ubicación:   "Local principal - Centro"
   Activo:      ☑ Sí
   ```

5. **Guardar**
   ```
   Click [Guardar]
   ```

6. **Resultado**
   - ✅ Evento aparece en panel
   - ✅ Visible en mapa
   - ✅ Visible en sidebar
   - ✅ Usuarios pueden verlo

---

## 🔐 Seguridad (Futuro)

Actualmente el panel está sin protección (para desarrollo).  
En producción, agregar:
- Autenticación (usuario/contraseña)
- Permisos por rol (admin, editor, etc.)
- Historial de cambios
- Validación de datos

---

## 📱 Responsive

El panel funciona en:
- ✅ Desktop (recomendado)
- ✅ Tablet
- ✅ Mobile (interfaz adaptada)

---

## ⚡ Shortcuts de Teclado

| Tecla | Acción |
|-------|--------|
| ESC | Cerrar modal |
| Tab | Navegar entre campos |
| Enter | Enviar formulario |

---

## 🆘 Troubleshooting

### P: Creé un evento pero no aparece
**R:** 
1. Verifica que esté **Activo** ✓
2. Recarga el mapa (F5)
3. Abre consola (F12) para ver errores

### P: ¿Por qué no me deja editar?
**R:** 
1. Algunos campos son obligatorios (*)
2. Verifica que hayas llenado todo
3. Intenta nuevamente

### P: ¿Se elimina permanentemente?
**R:** 
1. Sí, la eliminación es permanente
2. Usa Desactivar para ocultar sin eliminar
3. Siempre haz backup antes

### P: ¿Cuántos items puedo crear?
**R:** 
1. Ilimitados (depende de BD)
2. Recomendado: máx 1000 por tipo
3. Usa filtros para mejor rendimiento

---

## 💡 Tips & Tricks

### Tip 1: Usar Categorías
```
Organiza noticias por categoría:
- General
- Negocios
- Promociones
- Emergencias
```

### Tip 2: Imagen de Evento
```
Las imágenes hacen eventos más atractivos:
- Usa URLs de buena calidad
- Tamaño recomendado: 400x300px
- Formato: JPG o PNG
```

### Tip 3: Trivia Efectiva
```
Para mejor engagement:
- Títulos claros y atractivos
- 30-60 segundos de tiempo
- Dificultad media (atrae más)
- Descripciones breves
```

### Tip 4: Encuestas
```
Preguntas efectivas:
- Máximo 5 opciones
- Lenguaje claro
- Opciones balanceadas
- Separadas por |
```

---

## 📞 Soporte

Si tienes problemas:

1. **Revisa consola (F12)**
   - Errors: Tab "Console"
   - Network: Tab "Network"

2. **Verifica datos**
   - ¿Títulos vacíos?
   - ¿Fechas válidas?
   - ¿URLs correctas?

3. **Contacta soporte**
   - Email: [tu email]
   - Adjunta: screenshot + error

---

**Versión:** 1.2.0  
**Estado:** ✅ Funcional  
**Última actualización:** 16-04-2026  

¡Disfruta gestionar tu contenido! 🎉
