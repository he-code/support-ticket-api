# Support Ticket API

API REST para la gestión de tickets de soporte, construida con Laravel y Laravel Sanctum.

El sistema permite que los usuarios creen tickets, los agentes atiendan solicitudes y los administradores gestionen el flujo operativo. También incluye módulos para soporte interno, automatizaciones, integraciones, reportes, carga masiva de usuarios, base de conocimiento y seguimiento de actividad.

## Funcionalidades principales

* Registro, login y logout con tokens Sanctum.
* Login endurecido con validación estricta, normalización de email y límite de intentos.
* Roles: `user`, `support_agent` y `admin`.
* Gestión de tickets con categorías, canales, equipos, tags, SLA y campos personalizados.
* Comentarios, notas internas, menciones y escalaciones.
* Adjuntos con descarga segura, vista previa y visibilidad interna para staff.
* Base de conocimiento, respuestas rápidas y encuestas de satisfacción.
* Reglas de automatización y auditoría de actividad.
* API keys, webhooks y registro de entregas de integraciones.
* Exportación CSV de tickets.
* Importación masiva de usuarios por CSV/XLSX, permitida solo para `admin`.
* Dashboard, notificaciones internas y perfil de usuario.

## Tecnologías

* PHP 8.3+
* Laravel
* Laravel Sanctum
* SQLite o MySQL
* PHPUnit
* Laravel Pint
* L5 Swagger

## Instalación

Clonar el repositorio:

```bash
git clone https://github.com/he-code/support-ticket-api.git
cd support-ticket-api
```

Instalar dependencias:

```bash
composer install
```

Crear el archivo de entorno:

```bash
cp .env.example .env
```

En Windows puedes copiar `.env.example` manualmente o usar:

```bat
copy .env.example .env
```

Generar la clave de la aplicación:

```bash
php artisan key:generate
```

Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

Levantar el servidor local:

```bash
php artisan serve
```

La API local queda disponible en:

```txt
http://localhost:8000/api
```

## Base de datos

### SQLite

Para usar SQLite, configura en `.env`:

```env
DB_CONNECTION=sqlite
```

Luego crea el archivo:

```txt
database/database.sqlite
```

### MySQL

Para usar MySQL, configura estas variables en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=support_ticket_api
DB_USERNAME=root
DB_PASSWORD=
```

## Usuarios demo

Después de ejecutar:

```bash
php artisan migrate:fresh --seed
```

Se crearán los siguientes usuarios de prueba:

| Rol               | Nombre               | Email                                           | Contraseña |
| ----------------- | -------------------- | ----------------------------------------------- | ---------- |
| Administrador     | Admin User           | [admin@example.com](mailto:admin@example.com)   | password   |
| Agente de soporte | Support Agent        | [agent@example.com](mailto:agent@example.com)   | password   |
| Agente de soporte | Second Support Agent | [agent2@example.com](mailto:agent2@example.com) | password   |
| Usuario normal    | Regular User         | [user@example.com](mailto:user@example.com)     | password   |

## Autenticación

La API usa Laravel Sanctum. Después de iniciar sesión, envía el token en cada ruta protegida:

```http
Authorization: Bearer {token}
```

### Seguridad del login

El endpoint `POST /api/login` aplica:

* `email` y `password` deben ser strings.
* Se rechazan payloads tipo arreglo u objeto, incluyendo intentos estilo NoSQL como `{"$ne": null}`.
* El email se normaliza con `trim` y minúsculas antes de consultar.
* La consulta de usuario se hace con Eloquent, usando parámetros preparados.
* Después de 5 intentos fallidos por email + IP, responde `429` temporalmente.
* Las respuestas de auth no incluyen el hash de password.

## Roles

| Rol             | Descripción                                                                               |
| --------------- | ----------------------------------------------------------------------------------------- |
| `user`          | Usuario final. Gestiona sus propios tickets y datos de perfil.                            |
| `support_agent` | Agente. Atiende tickets, comenta, escala, usa notas internas y ve reportes operativos.    |
| `admin`         | Administrador. Gestiona usuarios, módulos administrativos, integraciones e importaciones. |

La importación masiva de usuarios y el historial de importaciones son exclusivos de `admin`.

## Endpoints principales

### Auth

| Método | Endpoint        | Descripción       |
| ------ | --------------- | ----------------- |
| POST   | `/api/register` | Registrar usuario |
| POST   | `/api/login`    | Iniciar sesión    |
| POST   | `/api/logout`   | Cerrar sesión     |

### Usuarios

| Método | Endpoint                 | Rol         | Descripción                    |
| ------ | ------------------------ | ----------- | ------------------------------ |
| GET    | `/api/users`             | admin       | Listar usuarios                |
| PATCH  | `/api/users/{user}/role` | admin       | Cambiar rol                    |
| GET    | `/api/users/imports`     | admin       | Ver historial de importaciones |
| POST   | `/api/users/import`      | admin       | Importar usuarios por CSV/XLSX |
| GET    | `/api/support-agents`    | autenticado | Listar agentes                 |

### Tickets

| Método | Endpoint                       | Descripción                 |
| ------ | ------------------------------ | --------------------------- |
| GET    | `/api/tickets`                 | Listar tickets con filtros  |
| POST   | `/api/tickets`                 | Crear ticket                |
| GET    | `/api/tickets/{ticket}`        | Ver ticket                  |
| PATCH  | `/api/tickets/{ticket}`        | Actualizar ticket           |
| DELETE | `/api/tickets/{ticket}`        | Eliminar ticket             |
| PATCH  | `/api/tickets/{ticket}/status` | Cambiar estado              |
| PATCH  | `/api/tickets/{ticket}/assign` | Asignar o desasignar ticket |

### Módulos de ticket

| Método   | Endpoint                                                  | Descripción              |
| -------- | --------------------------------------------------------- | ------------------------ |
| GET/POST | `/api/tickets/{ticket}/comments`                          | Comentarios              |
| GET/POST | `/api/tickets/{ticket}/attachments`                       | Adjuntos                 |
| GET      | `/api/tickets/{ticket}/attachments/{attachment}/download` | Descargar adjunto        |
| GET      | `/api/tickets/{ticket}/attachments/{attachment}/preview`  | Vista previa             |
| GET      | `/api/tickets/{ticket}/activities`                        | Actividad                |
| GET/POST | `/api/tickets/{ticket}/internal-notes`                    | Notas internas           |
| GET      | `/api/tickets/{ticket}/mentions`                          | Menciones                |
| GET/POST | `/api/tickets/{ticket}/escalations`                       | Escalaciones             |
| PATCH    | `/api/tickets/{ticket}/escalations/{escalation}/resolve`  | Resolver escalación      |
| GET/POST | `/api/tickets/{ticket}/knowledge-base-articles`           | Artículos vinculados     |
| GET/POST | `/api/tickets/{ticket}/satisfaction`                      | Encuesta de satisfacción |

### Catálogos y flujo

| Método      | Endpoint                       | Descripción                      |
| ----------- | ------------------------------ | -------------------------------- |
| apiResource | `/api/ticket-categories`       | Categorías                       |
| apiResource | `/api/categories`              | Alias compatible para categorías |
| apiResource | `/api/ticket-channels`         | Canales de entrada               |
| apiResource | `/api/support-teams`           | Equipos                          |
| apiResource | `/api/ticket-tags`             | Tags                             |
| apiResource | `/api/quick-replies`           | Respuestas rápidas               |
| apiResource | `/api/knowledge-base-articles` | Base de conocimiento             |
| apiResource | `/api/automation-rules`        | Automatizaciones                 |
| apiResource | `/api/custom-fields`           | Campos personalizados            |

### Horarios, integraciones y reportes

| Método          | Endpoint                                          | Descripción          |
| --------------- | ------------------------------------------------- | -------------------- |
| GET/POST/DELETE | `/api/business-hours`                             | Horarios laborales   |
| GET/POST/DELETE | `/api/business-holidays`                          | Feriados             |
| GET/POST/DELETE | `/api/integrations/api-keys`                      | API keys             |
| apiResource     | `/api/integrations/webhooks`                      | Webhooks             |
| GET             | `/api/integrations/webhooks/{webhook}/deliveries` | Entregas del webhook |
| GET             | `/api/audit-logs`                                 | Auditoría            |
| GET             | `/api/reports/tickets/export`                     | Exportar tickets CSV |
| GET             | `/api/dashboard/stats`                            | Estadísticas         |

## Filtros de tickets

`GET /api/tickets` acepta:

```http
GET /api/tickets?status=open
GET /api/tickets?priority=high
GET /api/tickets?search=login
GET /api/tickets?assigned=me
GET /api/tickets?assigned=unassigned
GET /api/tickets?assigned_to_id=3
GET /api/tickets?category_id=1
GET /api/tickets?channel_id=1
GET /api/tickets?team_id=1
GET /api/tickets?tag_id=1
GET /api/tickets?sla=overdue
GET /api/tickets?created_from=2026-06-01&created_to=2026-06-30
GET /api/tickets?sort_by=created_at&sort_direction=desc
```

## Estados y prioridades

Estados disponibles:

```txt
open
in_progress
waiting_customer
waiting_internal
resolved
closed
reopened
```

Prioridades disponibles:

```txt
low
medium
high
urgent
```

## Importación masiva de usuarios

Ruta:

```http
POST /api/users/import
```

Requiere token de un usuario con rol `admin`.

Campos multipart:

| Campo              | Tipo                 | Requerido | Descripción                                         |
| ------------------ | -------------------- | --------- | --------------------------------------------------- |
| `file`             | archivo CSV/TXT/XLSX | sí        | Archivo con usuarios                                |
| `update_existing`  | boolean              | no        | Actualiza usuarios existentes por email             |
| `default_password` | string               | no        | Password por defecto si el archivo no trae password |

Columnas aceptadas:

```txt
name,email,role,password
```

También se aceptan alias en español como:

```txt
nombre,correo,rol,contrasena
```

Para archivos XLSX, el PHP del servidor debe tener habilitada la extensión `zip` (`ZipArchive`). Si no está disponible, la API responde `422` con un mensaje claro.

## Documentación Swagger

El proyecto incluye L5 Swagger para visualizar la documentación interactiva de la API.

Generar la documentación:

```bash
php artisan l5-swagger:generate
```

Levantar el servidor local:

```bash
php artisan serve
```

Abrir Swagger UI en el navegador:

```txt
http://localhost:8000/api/documentation
```

## Documentación adicional

* [Referencia de API](docs/api-reference.md)
* [Seguridad](docs/security.md)
* [Roles y permisos](docs/roles-and-permissions.md)
* [Testing](docs/testing.md)

## Tests y formato

Ejecutar la suite de pruebas:

```bash
php artisan test
```

Aplicar formato con Laravel Pint:

```bash
vendor/bin/pint
```

En Windows:

```bat
php artisan test
vendor\bin\pint
```

## Estado del proyecto

Backend en desarrollo activo con suite automatizada para autenticación, tickets, módulos de workflow, importación masiva de usuarios y seguridad básica del login.
