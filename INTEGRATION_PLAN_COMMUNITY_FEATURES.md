# 📋 PLAN DE INTEGRACIÓN - CARACTERÍSTICAS COMUNITARIAS

**Documento:** Plan estratégico de integración de encuestas, noticias, eventos, trivias, cursos y transmisiones  
**Fecha:** 2026-04-16  
**Versión:** 1.0  
**Estado:** Diseño y especificación

---

## 🎯 VISIÓN GENERAL

Mapita v1.2.0 fue diseñado como **plataforma comunitaria** para negocios y marcas. Además del mapa y detalles de negocios, el sistema integra elementos de **engagement y participación comunitaria** que fosteen actividades específicas dirigidas por administradores.

### Elementos a Integrar:

| Elemento | Estado BD | Estado UI | Permisos | Propósito |
|----------|-----------|-----------|----------|-----------|
| **Encuestas** | ✅ Existe | ❌ No implementado | Admin | Recopilar opiniones geográficas |
| **Eventos** | ✅ Existe | ⚠️ Parcial | Admin | Actividades y encuentros locales |
| **Trivias** | ✅ Existe | ❌ No implementado | Admin | Gamificación e interacción |
| **Noticias/Artículos** | ❌ Crear | ❌ No implementado | Admin | Comunicados y novedades |
| **Cursos (Zoom)** | ❌ Crear | ❌ No implementado | Admin | Educación y capacitación |
| **Transmisiones en Vivo** | ❌ Crear | ❌ No implementado | Admin | YouTube/Audio en tiempo real |

---

## 📊 ANÁLISIS DE TABLAS EXISTENTES

### 1. ENCUESTAS (3 tablas)

```sql
-- Tabla principal
CREATE TABLE `encuestas` (
  `id` INT PRIMARY KEY,
  `titulo` VARCHAR(255),
  `descripcion` TEXT,
  `lat` DECIMAL(10,8),      -- Ubicación geográfica
  `lng` DECIMAL(11,8),
  `fecha_creacion` DATE,
  `fecha_expiracion` DATE,  -- Período activo
  `link` VARCHAR(255),      -- URL externa o interna
  `activo` TINYINT(1),
  `created_at` TIMESTAMP,
  `updated_at` TIMESTAMP
);

-- Tabla de participaciones
CREATE TABLE `encuesta_participaciones` (
  `id` INT PRIMARY KEY,
  `encuesta_id` INT,
  `user_id` INT,
  `fecha_participacion` TIMESTAMP
);

-- Tablas de respuestas
CREATE TABLE `preguntas_encuesta` (...);
CREATE TABLE `respuestas_encuesta` (...);
```

**Estado:** Tablas creadas, datos de prueba existentes (5 encuestas)  
**Necesario:** UI para crear/editar/visualizar, admin panel

---

### 2. EVENTOS (1 tabla)

```sql
CREATE TABLE `eventos` (
  `id` INT PRIMARY KEY,
  `titulo` VARCHAR(255),
  `descripcion` TEXT,
  `fecha` DATE,
  `hora` TIME,
  `organizador` VARCHAR(255),
  `lat` DECIMAL(10,8),      -- Ubicación del evento
  `lng` DECIMAL(11,8),
  `dest_lat` DECIMAL(10,8), -- Destino (si aplica)
  `dest_lng` DECIMAL(11,8),
  `activo` TINYINT(1),
  `youtube_link` VARCHAR(255), -- Para transmisiones
  `created_at` TIMESTAMP,
  `updated_at` TIMESTAMP
);
```

**Estado:** Tabla creada, 5 eventos de muestra  
**Campo especial:** `youtube_link` ya existe para transmisiones  
**Necesario:** UI mejorada, integración con sistema de cursos

---

### 3. TRIVIAS (2 tablas)

```sql
CREATE TABLE `trivia_games` (
  `game_id` VARCHAR(32) PRIMARY KEY,
  `question_order` TEXT,       -- Preguntas en orden
  `current_question_ptr` INT,  -- Pregunta actual
  ...
);

CREATE TABLE `trivia_stats` (
  `stat_id` INT PRIMARY KEY,
  `game_id` VARCHAR(32),
  `total_score` INT,
  ...
);
```

**Estado:** Tablas creadas  
**Necesario:** UI de juego, preguntero, sistema de puntuación

---

## ❌ ELEMENTOS A CREAR

### 4. NOTICIAS / ARTÍCULOS

**Nueva tabla a crear:**

```sql
CREATE TABLE `articulos` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `titulo` VARCHAR(255) NOT NULL,
  `contenido` LONGTEXT NOT NULL,
  `resumen` TEXT,              -- Vista previa
  `imagen_portada` VARCHAR(255),
  `autor_id` INT,              -- FK a users (admin)
  `categoria` VARCHAR(100),    -- Ej: Noticia, Comunicado, Blog
  `fecha_publicacion` DATETIME,
  `fecha_actualizacion` DATETIME,
  `publicado` TINYINT(1) DEFAULT 1,
  `visible` TINYINT(1) DEFAULT 1,
  `vistas` INT DEFAULT 0,
  `tags` VARCHAR(255),         -- Etiquetas
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (autor_id) REFERENCES users(id)
);
```

---

### 5. CURSOS (VÍA ZOOM)

**Nueva tabla a crear:**

```sql
CREATE TABLE `cursos` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `titulo` VARCHAR(255) NOT NULL,
  `descripcion` LONGTEXT,
  `instructor_id` INT,              -- FK a users (admin/instructor)
  `categoria` VARCHAR(100),
  `nivel` ENUM('principiante', 'intermedio', 'avanzado'),
  
  -- Detalles Zoom
  `zoom_meeting_id` VARCHAR(255),   -- ID de reunión Zoom
  `zoom_start_time` DATETIME,       -- Inicio programado
  `zoom_duration_minutes` INT,      -- Duración en minutos
  `zoom_join_url` TEXT,             -- URL para unirse
  `zoom_password` VARCHAR(255),     -- Contraseña (si aplica)
  
  -- Capacidad
  `max_participantes` INT,
  `inscritos` INT DEFAULT 0,
  
  -- Estado
  `estado` ENUM('programado', 'en_vivo', 'finalizado', 'cancelado'),
  `visible` TINYINT(1) DEFAULT 1,
  
  -- Grabación
  `grabacion_url` TEXT,             -- Enlace a grabación
  `grabacion_disponible` TINYINT(1) DEFAULT 0,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (instructor_id) REFERENCES users(id)
);

-- Tabla de inscripciones
CREATE TABLE `curso_inscripciones` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `curso_id` INT,
  `usuario_id` INT,
  `estado` ENUM('inscrito', 'asistio', 'certificado', 'abandono'),
  `fecha_inscripcion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (curso_id) REFERENCES cursos(id),
  FOREIGN KEY (usuario_id) REFERENCES users(id)
);
```

---

### 6. TRANSMISIONES EN VIVO

**Nueva tabla a crear:**

```sql
CREATE TABLE `transmisiones_vivo` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `titulo` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `tipo` ENUM('youtube', 'audio_streaming', 'zoom_publico'),
  
  -- YouTube
  `youtube_channel_url` VARCHAR(255),
  `youtube_stream_key` VARCHAR(255),
  `youtube_stream_url` TEXT,
  
  -- Audio (Ej: Spotify, Apple Podcasts, etc)
  `audio_provider` VARCHAR(100),    -- 'spotify', 'apple_podcasts', etc
  `audio_stream_url` TEXT,
  
  -- General
  `organizador_id` INT,             -- FK a users (admin)
  `fecha_inicio` DATETIME,
  `fecha_fin` DATETIME,
  `estado` ENUM('programada', 'en_vivo', 'finalizada', 'cancelada'),
  `visitas` INT DEFAULT 0,
  `visible` TINYINT(1) DEFAULT 1,
  
  -- Grabación/Archivo
  `archivo_url` TEXT,               -- Archivo disponible después
  `archivo_disponible` TINYINT(1) DEFAULT 0,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (organizador_id) REFERENCES users(id)
);

-- Tabla de participantes en vivo
CREATE TABLE `transmision_participantes` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `transmision_id` INT,
  `usuario_id` INT,
  `fecha_union` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `duracion_minutos` INT,
  FOREIGN KEY (transmision_id) REFERENCES transmisiones_vivo(id),
  FOREIGN KEY (usuario_id) REFERENCES users(id)
);
```

---

## 🔐 ESTRUCTURA DE PERMISOS

### Roles con acceso a crear/editar

```php
// Solo ADMIN puede:
- Crear encuestas
- Crear eventos
- Crear trivias
- Publicar noticias/artículos
- Crear cursos Zoom
- Iniciar transmisiones en vivo
- Editar/Eliminar cualquiera de lo anterior

// Usuario propietario de negocio/marca PUEDE:
- Ver encuestas de su zona (no crear)
- Participar en encuestas
- Ver eventos cercanos (no crear)
- Participar en cursos (si aplica)
- Ver transmisiones (no crear)

// Usuarios normales PUEDEN:
- Ver toda la información pública
- Participar en encuestas
- Participar en trivias
- Ver eventos
- Inscribirse a cursos
- Ver transmisiones en vivo
```

---

## 📱 INTERFAZ DE USUARIO - ESTRUCTURA

### Dashboard Admin

```
/admin/
├── /encuestas
│   ├── dashboard.php          (listar todas)
│   ├── crear.php              (formulario nuevo)
│   ├── editar.php?id=X        (modificar)
│   └── estadisticas.php?id=X  (resultados)
├── /eventos
│   ├── dashboard.php
│   ├── crear.php
│   ├── editar.php?id=X
│   └── calendario.php
├── /trivias
│   ├── dashboard.php
│   ├── crear.php
│   ├── editar.php?id=X
│   └── preguntas.php?id=X
├── /noticias
│   ├── dashboard.php
│   ├── crear.php
│   ├── editar.php?id=X
│   └── editor.php (rich text editor)
├── /cursos
│   ├── dashboard.php
│   ├── crear.php
│   ├── editar.php?id=X
│   ├── inscripciones.php?id=X
│   └── integracion_zoom.php
└── /transmisiones
    ├── dashboard.php
    ├── crear.php
    ├── editar.php?id=X
    ├── en_vivo.php?id=X
    └── configurar_stream.php
```

### Interfaz Pública (Mapa)

```
/views/community/
├── encuestas_widget.php       (widget en mapa)
├── eventos_widget.php         (widget en mapa)
├── trivias_widget.php         (widget en mapa)
├── noticias_panel.php         (panel lateral)
├── cursos_panel.php           (panel lateral)
└── transmisiones_live.php     (banner superior)
```

---

## 🔄 FLUJO DE INTEGRACIÓN (Fases)

### **FASE 1: ENCUESTAS** (2-3 días)
✅ BD existe | Tablas: `encuestas`, `encuesta_participaciones`, `preguntas_encuesta`, `respuestas_encuesta`

1. Crear API `/api/encuestas.php` (GET/POST/UPDATE)
2. Dashboard admin para CRUD
3. Widget en mapa para ver y responder encuestas
4. Página de resultados/estadísticas
5. Permisos: solo admin crea

---

### **FASE 2: EVENTOS** (2-3 días)
✅ BD existe | Tabla: `eventos` | Campo YouTube ya existe

1. Mejorar API `/api/eventos.php` existente
2. Dashboard admin mejorado
3. Widget en mapa con calendario
4. Integración de `youtube_link` para transmisiones
5. Detalles de evento con mapa de ubicación

---

### **FASE 3: TRIVIAS** (2-3 días)
✅ BD existe | Tablas: `trivia_games`, `trivia_stats`

1. Crear API `/api/trivias.php`
2. Dashboard admin para agregar preguntas
3. Widget de trivia interactivo en mapa
4. Sistema de puntuación y ranking
5. Premios/Badges (opcional)

---

### **FASE 4: NOTICIAS/ARTÍCULOS** (3-4 días)
❌ BD no existe | Crear tabla + UI

1. Crear tabla `articulos` en BD
2. Crear API `/api/articulos.php`
3. Panel admin con editor de rich text
4. Panel "Noticias" en sidebar del mapa
5. Vista detalle de artículo

---

### **FASE 5: CURSOS (ZOOM)** (3-4 días)
❌ BD no existe | Crear tablas + integración

1. Crear tablas `cursos`, `curso_inscripciones`
2. Integración con API de Zoom
3. API `/api/cursos.php`
4. Dashboard admin para programar cursos
5. Panel de inscripciones para usuarios
6. Sistema de certificados

---

### **FASE 6: TRANSMISIONES EN VIVO** (3-4 días)
❌ BD no existe | Crear tablas + streaming

1. Crear tabla `transmisiones_vivo`, `transmision_participantes`
2. Integración YouTube Live
3. Integración Audio Streaming (Spotify, etc)
4. API `/api/transmisiones.php`
5. Widget "En VIVO" prominent en mapa
6. Chat/Comentarios en vivo (opcional)

---

## 📊 RESUMEN TÉCNICO

### Base de Datos

| Elemento | Tablas Nuevas | Tablas Existentes | Total | Requeridas |
|----------|---------------|-------------------|-------|-----------|
| Encuestas | 0 | 4 | 4 | ✅ |
| Eventos | 0 | 1 | 1 | ✅ |
| Trivias | 0 | 2 | 2 | ✅ |
| Noticias | 1 | 0 | 1 | ❌ |
| Cursos | 2 | 0 | 2 | ❌ |
| Transmisiones | 2 | 0 | 2 | ❌ |
| **TOTAL** | **5** | **7** | **12** | |

### APIs a Crear

```
/api/
├── encuestas.php             (GET/POST/UPDATE)
├── eventos.php               (mejorar existente)
├── trivias.php               (GET/POST/UPDATE)
├── articulos.php             (GET/POST/UPDATE)
├── cursos.php                (GET/POST/UPDATE + Zoom integration)
└── transmisiones.php         (GET/POST/UPDATE + YouTube/Audio)
```

### Controladores Admin

```
/admin/
├── EncuestasController.php
├── EventosController.php
├── TriviasController.php
├── ArticulosController.php
├── CursosController.php
└── TransmisionesController.php
```

### Vistas Públicas

```
/views/community/
├── EncuestasWidget.php
├── EventosWidget.php
├── TriviasWidget.php
├── ArticulosPanel.php
├── CursosPanel.php
└── TransmisionesLive.php
```

---

## 🚀 COMENZAR

### Próximos pasos inmediatos:

1. ✅ Crear tabla `articulos` en BD
2. ✅ Crear tablas `cursos` y `curso_inscripciones`
3. ✅ Crear tablas `transmisiones_vivo` y `transmision_participantes`
4. ✅ Ejecutar migraciones en producción
5. ✅ Comenzar FASE 1 (Encuestas)

---

## 📝 NOTAS

- **Permisos:** Todo controlado por `$_SESSION['is_admin']`
- **Responsabilidad:** Admin crea contenido, usuarios consumen
- **Engagement:** Estos elementos crean participación recurrente
- **Mapeo:** Todos los elementos están geo-ubicados (lat/lng)
- **Integraciones externas:** Zoom, YouTube, Audio Providers

---

**Documento creado:** 2026-04-16  
**Próxima revisión:** Después de implementar FASE 1
