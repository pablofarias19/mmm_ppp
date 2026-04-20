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

## Migraciones nuevas (WT)

Para habilitar Walkie Talkie (WT) en popups ejecutar:

```sql
source migrations/002_wt_tables.sql
```

## Seguridad

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
