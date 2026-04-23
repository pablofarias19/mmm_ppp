# Mapita

Aplicación PHP/MySQL para registrar y visualizar negocios locales en un mapa interactivo (Leaflet.js).

## Características

- Autenticación de usuarios (registro, inicio de sesión, recuperación de contraseña)
- Agregar, editar, eliminar y ver negocios con coordenadas en el mapa
- Filtrar negocios por tipo y términos de búsqueda
- Mostrar negocios como marcadores emoji animados en el mapa
- Reseñas y valoraciones por negocio
- Exportar lista de negocios a PDF
- Panel de administración (usuarios y negocios)
- API REST para acceso programático

## Requisitos

- PHP 7.4+
- MySQL 5.7+ o MariaDB 10.3+
- Servidor web (Apache/Nginx) con PHP
- Extensión PDO MySQL habilitada
- (Opcional) [Composer](https://getcomposer.org/) para autoloading y gestión de dependencias

## Instalación

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/pablofarias19/mapita.git
   cd mapita
   ```

2. **Configurar variables de entorno:**
   ```bash
   cp .env.example .env
   # Editar .env con las credenciales reales de la base de datos
   ```

3. **Ejecutar la migración SQL:**
   ```sql
   -- Conectarse a la base de datos y ejecutar:
   source config/migration.sql
   ```

4. **Instalar dependencias de Composer (opcional):**
   ```bash
   composer install
   ```

5. **Instalar dependencias de desarrollo (tests):**
   ```bash
   composer install --dev
   ./vendor/bin/phpunit
   ```

## Estructura de directorios

```
mapita/
├── admin/          Panel de administración
├── api/            Endpoints REST (businesses.php, reviews.php, api_comercios.php)
├── auth/           Autenticación (login, register, logout, reset_password)
├── business/       CRUD de negocios (add, edit, view, my_businesses)
├── config/         Configuración de BD y migration SQL
├── controllers/    Controladores MVC
├── core/           Database singleton, helpers (CSRF, security headers)
├── css/            Estilos
├── includes/       Funciones auxiliares
├── models/         Business, Review
├── public/         Front controller (index.php)
├── tests/          Suite PHPUnit
├── uploads/        Imágenes de negocios (generado automáticamente)
└── views/          Vistas (mapa principal)
```

## API REST

| Método | URL | Descripción | Auth |
|--------|-----|-------------|------|
| GET | `/api/businesses.php` | Lista negocios visibles | No |
| GET | `/api/businesses.php?id=N` | Detalle de un negocio | No |
| GET | `/api/businesses.php?type=X` | Filtrar por tipo | No |
| GET | `/api/businesses.php?q=texto` | Buscar por nombre/dirección | No |
| POST | `/api/businesses.php` | Crear negocio | Sí |
| PUT | `/api/businesses.php?id=N` | Actualizar negocio | Sí |
| DELETE | `/api/businesses.php?id=N` | Eliminar negocio | Sí |
| GET | `/api/reviews.php?business_id=N` | Listar reseñas | No |
| POST | `/api/reviews.php` | Crear/actualizar reseña | Sí |
| DELETE | `/api/reviews.php?business_id=N` | Eliminar propia reseña | Sí |
| GET | `/api/encuestas.php?action=aggregate&ids=1,2,3` | Agrega métricas de encuestas seleccionadas | No |
| GET | `/api/wt.php?action=list&entity_type=negocio&entity_id=10` | Lista mensajes WT por entidad | No |
| POST | `/api/wt.php` + `action=send` | Enviar mensaje WT (máx. 140, 5/min por usuario) | No |
| POST | `/api/wt.php` + `action=heartbeat` | Presencia WT (heartbeat 20s) | No |
| GET | `/api/wt.php?action=status&entity_type=negocio&entity_id=10` | Estado del canal WT entre viewer y propietario | No |
| GET | `/api/wt_preferences.php` | Preferencias WT del usuario logueado | Sí |
| POST | `/api/wt_preferences.php` + `action=save` | Guardar modo WT + áreas | Sí |
| POST | `/api/wt_preferences.php` + `action=block` | Bloquear usuario WT (`user_id`) | Sí |
| POST | `/api/wt_preferences.php` + `action=unblock` | Desbloquear usuario WT (`user_id`) | Sí |
| GET | `/api/wt_preferences.php?action=blocks` | Listar usuarios bloqueados | Sí |

## Migraciones nuevas (WT)

Para habilitar Walkie Talkie (WT) en popups ejecutar:

```sql
source migrations/002_wt_tables.sql
```

## WT Canales Selectivos

Para habilitar los canales selectivos WT (preferencias + bloqueos) ejecutar:

```sql
source migrations/011_wt_preferences.sql
```

### Modos de canal WT por usuario

| Modo | Comportamiento |
|------|----------------|
| `open` (defecto) | Cualquier usuario puede enviar mensajes WT |
| `selective` | Solo usuarios con al menos un área en común |
| `closed` | WT desactivado; nadie puede enviar mensajes |

Los usuarios pueden gestionar sus preferencias en `/views/wt_preferences.php`.

## Migración de delegaciones por entidad

Para habilitar delegación administrativa y transferencias de titularidad ejecutar:

```sql
source migrations/008_entity_delegations.sql
```

## API de delegaciones y transferencias

- `GET /api/users/lookup.php?query=...` → busca usuario por `username` o `email` (requiere sesión)
- `POST /api/business_delegations/create.php` (`business_id`, `user_id`, `password`) → agrega delegado admin (máx. 3)
- `POST /api/business_delegations/revoke.php` (`business_id`, `user_id`, `password`) → revoca delegado
- `GET /api/business_delegations/list.php?business_id=...` → lista delegados
- `POST /api/brand_delegations/create.php` (`brand_id`, `user_id`, `password`) → agrega delegado admin (máx. 3)
- `POST /api/brand_delegations/revoke.php` (`brand_id`, `user_id`, `password`) → revoca delegado
- `GET /api/brand_delegations/list.php?brand_id=...` → lista delegados
- `POST /api/ownership_transfers/initiate.php` (`entity_type`, `entity_id`, `to_user_id`) → inicia transferencia (pending)
- `POST /api/ownership_transfers/accept.php` (`transfer_id`) → acepta y cambia titularidad
- `POST /api/ownership_transfers/reject.php` (`transfer_id`) → rechaza transferencia

## Módulo Sectores Industriales (Catálogo Admin)

> Este módulo es un **catálogo de taxonomía** gestionado por administradores.
> Los usuarios registrados crean sus **Industrias** usando este catálogo como clasificador.

### Migración

```sql
source migrations/014_industrial_sectors.sql
```

### Endpoints

| Método | URL | Descripción | Auth |
|--------|-----|-------------|------|
| GET | `/api/industrial_sectors.php` | Lista todos los sectores | No |
| GET | `/api/industrial_sectors.php?id=N` | Detalle de un sector | No |
| GET | `/api/industrial_sectors.php?type=mineria` | Filtrar por tipo | No |
| GET | `/api/industrial_sectors.php?status=activo` | Filtrar por estado | No |
| GET | `/api/industrial_sectors.php?limit=50&offset=0` | Paginación | No |
| POST | `/api/industrial_sectors.php?action=create` | Crear sector | Admin |
| POST | `/api/industrial_sectors.php?action=update&id=N` | Actualizar sector | Admin |
| POST | `/api/industrial_sectors.php?action=delete&id=N` | Eliminar sector | Admin |

### Valores permitidos

| Campo | Valores |
|-------|---------|
| `type` | `mineria`, `energia`, `agro`, `infraestructura`, `inmobiliario`, `industrial` |
| `status` | `proyecto`, `activo`, `potencial` |
| `investment_level` | `bajo`, `medio`, `alto` |
| `risk_level` | `bajo`, `medio`, `alto` |

### Integración con el mapa

Los sectores industriales se visualizan en el mapa principal como capas GeoJSON.
Activar la capa desde la barra lateral: **🏭 Sectores Industriales**.
Cada tipo se diferencia por color y el estado por opacidad.

---

## Módulo Industrias (Usuario registrado)

Los usuarios registrados pueden crear, editar y archivar sus **Industrias** — entidades
propias con datos completos, asociadas a un sector del catálogo admin.

### Migraciones (ejecutar en orden)

```sql
source migrations/014_industrial_sectors.sql   -- catálogo de sectores
source migrations/015_industries.sql           -- tabla de industrias de usuarios
```

### Rutas UI

| Ruta | Descripción | Auth |
|------|-------------|------|
| `/industrias` | Dashboard: listado con búsqueda y filtros | Usuario |
| `/industry_new` | Formulario para crear una industria | Usuario |
| `/industry_edit?id=N` | Formulario para editar una industria | Owner / Admin |
| `/admin?tab=sectores` | Catálogo de sectores (admin) | Admin |

### Endpoints API

| Método | URL | Descripción | Auth |
|--------|-----|-------------|------|
| GET | `/api/industries.php` | Lista mis industrias | Usuario |
| GET | `/api/industries.php?id=N` | Detalle de una industria | Owner / Admin |
| GET | `/api/industries.php?status=activa` | Filtrar por estado | Usuario |
| GET | `/api/industries.php?sector_id=N` | Filtrar por sector | Usuario |
| GET | `/api/industries.php?search=texto` | Buscar por nombre | Usuario |
| POST | `/api/industries.php?action=create` | Crear industria | Usuario |
| POST | `/api/industries.php?action=update&id=N` | Actualizar industria | Owner / Admin |
| POST | `/api/industries.php?action=archive&id=N` | Archivar industria | Owner / Admin |
| POST | `/api/industries.php?action=delete&id=N` | Eliminar industria | Owner / Admin |

### Campos de la tabla `industries`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | PK |
| `user_id` | INT | Propietario (FK a users) |
| `industrial_sector_id` | INT | Sector del catálogo (FK, nullable) |
| `business_id` | INT | Negocio relacionado (nullable) |
| `brand_id` | INT | Marca relacionada (nullable) |
| `name` | VARCHAR(255) | Nombre de la industria |
| `description` | TEXT | Descripción |
| `website` | VARCHAR(500) | URL del sitio |
| `contact_email` | VARCHAR(255) | Email de contacto |
| `contact_phone` | VARCHAR(50) | Teléfono |
| `country` / `region` / `city` | VARCHAR | Ubicación |
| `employees_range` | ENUM | Rango de empleados |
| `annual_revenue` | ENUM | Escala (micro → corporación) |
| `certifications` | TEXT | Certs separadas por coma |
| `naics_code` / `isic_code` | VARCHAR(20) | Códigos de clasificación |
| `status` | ENUM | `borrador` / `activa` / `archivada` |
| `created_at` / `updated_at` | DATETIME | Timestamps |

### Permisos

- **Usuario registrado**: ve y gestiona solo sus propias industrias.
- **Admin**: puede ver/editar todas las industrias y gestionar el catálogo de sectores.



- Contraseñas hasheadas con `password_hash(PASSWORD_DEFAULT)`
- Protección CSRF en todos los formularios
- Validación y saneamiento de entradas en el servidor
- PDO con prepared statements (prevención de SQL injection)
- Cabeceras HTTP de seguridad (CSP, X-Frame-Options, etc.)
- Credenciales de BD en `.env` (nunca en el código)
- Directorio `uploads/` protegido con `.htaccess`

## Tests

```bash
./vendor/bin/phpunit
```

Los tests cubren validación de negocios, validación de contraseñas y helpers CSRF.
