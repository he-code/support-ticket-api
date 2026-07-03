# Support Ticket API

API REST para una mesa de soporte y gestion de tickets, construida con Laravel, Laravel Sanctum y L5 Swagger.

El backend permite registrar usuarios, autenticar con tokens Bearer, crear y administrar tickets, asignar agentes, comentar, subir adjuntos, gestionar categorias, usuarios, notificaciones, automatizaciones, integraciones, reportes y carga masiva de usuarios.

## Demo

API en Railway:

```txt
https://support-ticket-api-production.up.railway.app
```

Swagger UI:

```txt
https://support-ticket-api-production.up.railway.app/api/documentation
```

## Caracteristicas

- Registro, login y logout con Laravel Sanctum.
- Login endurecido con normalizacion de email, validacion estricta y rate limiting.
- Roles `user`, `support_agent` y `admin`.
- Tickets con estados, prioridades, categorias, canales, equipos, tags, SLA y campos personalizados.
- Comentarios, notas internas, menciones, escalaciones y actividad del ticket.
- Adjuntos con descarga segura, vista previa y visibilidad interna para staff.
- Base de conocimiento, respuestas rapidas y encuestas de satisfaccion.
- Automatizaciones, horarios laborales y feriados.
- API keys, webhooks y registro de entregas.
- Exportacion CSV de tickets.
- Importacion masiva de usuarios por CSV, TXT o XLSX.
- Dashboard, notificaciones internas y perfil de usuario.
- Documentacion interactiva con Swagger.
- Suite de tests feature para los flujos principales.

## Stack

| Tecnologia | Uso |
| --- | --- |
| PHP `^8.3` | Runtime del backend |
| Laravel `^13.8` | Framework de API |
| Laravel Sanctum `^4.3` | Autenticacion por tokens |
| L5 Swagger `^11.0` | Documentacion OpenAPI |
| SQLite / MySQL | Persistencia |
| PHPUnit `^12.5` | Pruebas automatizadas |
| Laravel Pint | Formato de codigo |
| Vite / Tailwind | Assets base del skeleton Laravel |

## Requisitos

- PHP 8.3 o superior.
- Composer.
- Node.js y NPM, solo si vas a compilar assets.
- SQLite o MySQL.
- Extension PHP `zip` si vas a importar usuarios desde XLSX.

## Instalacion rapida

```bash
git clone https://github.com/he-code/support-ticket-api.git
cd support-ticket-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

En Windows puedes crear el entorno con:

```bat
copy .env.example .env
```

La API local queda disponible en:

```txt
http://127.0.0.1:8000/api
```

## Configuracion de entorno

`.env.example` usa SQLite por defecto:

```env
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
MAIL_MAILER=log
```

Para SQLite, crea el archivo:

```txt
database/database.sqlite
```

Para MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=support_ticket_api
DB_USERNAME=root
DB_PASSWORD=
```

## Comandos utiles

| Comando | Descripcion |
| --- | --- |
| `php artisan serve` | Levanta la API local. |
| `php artisan migrate --seed` | Ejecuta migraciones y datos iniciales. |
| `php artisan migrate:fresh --seed` | Recrea la base de datos desde cero. |
| `php artisan test` | Ejecuta la suite de pruebas. |
| `vendor/bin/pint` | Formatea el codigo PHP. |
| `php artisan l5-swagger:generate` | Regenera la documentacion Swagger. |
| `composer run setup` | Instala dependencias, prepara `.env`, migra y compila assets. |
| `composer run dev` | Levanta servidor, cola, logs y Vite en paralelo. |

## Usuarios demo

Despues de ejecutar los seeders:

| Rol | Email | Password |
| --- | --- | --- |
| Administrador | `admin@example.com` | `password` |
| Agente de soporte | `agent@example.com` | `password` |
| Agente de soporte | `agent2@example.com` | `password` |
| Usuario final | `user@example.com` | `password` |

## Autenticacion

Inicia sesion con:

```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

Envia el token recibido en las rutas protegidas:

```http
Authorization: Bearer {token}
Accept: application/json
```

El endpoint de login rechaza payloads no escalares, normaliza el email, limita intentos por email + IP y no expone hashes de password.

## Roles

| Rol | Alcance |
| --- | --- |
| `user` | Gestiona sus tickets, comentarios permitidos, adjuntos y perfil. |
| `support_agent` | Atiende tickets, comenta, asigna, escala y consulta reportes operativos. |
| `admin` | Administra usuarios, catalogos, integraciones, automatizaciones e importaciones. |

## Endpoints principales

### Auth y perfil

| Metodo | Endpoint | Descripcion |
| --- | --- | --- |
| `POST` | `/api/register` | Registrar usuario. |
| `POST` | `/api/login` | Iniciar sesion. |
| `POST` | `/api/logout` | Cerrar sesion. |
| `GET` | `/api/me` | Obtener usuario autenticado. |
| `PATCH` | `/api/profile` | Actualizar perfil. |
| `PATCH` | `/api/password` | Cambiar contrasena. |

### Tickets

| Metodo | Endpoint | Descripcion |
| --- | --- | --- |
| `GET` | `/api/tickets` | Listar tickets con filtros. |
| `POST` | `/api/tickets` | Crear ticket. |
| `GET` | `/api/tickets/{ticket}` | Ver detalle. |
| `PATCH` | `/api/tickets/{ticket}` | Actualizar ticket. |
| `DELETE` | `/api/tickets/{ticket}` | Eliminar ticket. |
| `PATCH` | `/api/tickets/{ticket}/status` | Cambiar estado. |
| `PATCH` | `/api/tickets/{ticket}/assign` | Asignar o desasignar agente. |

### Modulos de ticket

| Endpoint | Descripcion |
| --- | --- |
| `/api/tickets/{ticket}/comments` | Comentarios. |
| `/api/tickets/{ticket}/attachments` | Adjuntos. |
| `/api/tickets/{ticket}/activities` | Actividad. |
| `/api/tickets/{ticket}/internal-notes` | Notas internas. |
| `/api/tickets/{ticket}/mentions` | Menciones. |
| `/api/tickets/{ticket}/escalations` | Escalaciones. |
| `/api/tickets/{ticket}/knowledge-base-articles` | Articulos vinculados. |
| `/api/tickets/{ticket}/satisfaction` | Encuesta de satisfaccion. |

### Administracion y catalogos

| Endpoint | Descripcion |
| --- | --- |
| `/api/users` | Usuarios. |
| `/api/users/import` | Importacion masiva de usuarios. |
| `/api/users/imports` | Historial de importaciones. |
| `/api/support-agents` | Agentes disponibles. |
| `/api/categories` y `/api/ticket-categories` | Categorias. |
| `/api/ticket-channels` | Canales. |
| `/api/support-teams` | Equipos. |
| `/api/ticket-tags` | Tags. |
| `/api/quick-replies` | Respuestas rapidas. |
| `/api/knowledge-base-articles` | Base de conocimiento. |
| `/api/automation-rules` | Automatizaciones. |
| `/api/custom-fields` | Campos personalizados. |

### Operacion

| Endpoint | Descripcion |
| --- | --- |
| `/api/dashboard/stats` | Estadisticas del dashboard. |
| `/api/notifications` | Notificaciones. |
| `/api/audit-logs` | Auditoria. |
| `/api/reports/tickets/export` | Exportacion CSV. |
| `/api/business-hours` | Horarios laborales. |
| `/api/business-holidays` | Feriados. |
| `/api/integrations/api-keys` | API keys. |
| `/api/integrations/webhooks` | Webhooks. |

La referencia extendida vive en [`docs/api-reference.md`](docs/api-reference.md) y [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md).

## Filtros de tickets

`GET /api/tickets` acepta filtros como:

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

Estados:

```txt
open
in_progress
waiting_customer
waiting_internal
resolved
closed
reopened
```

Prioridades:

```txt
low
medium
high
urgent
```

## Importacion masiva de usuarios

Ruta:

```http
POST /api/users/import
```

Requiere usuario `admin` y `multipart/form-data`.

| Campo | Tipo | Requerido | Descripcion |
| --- | --- | --- | --- |
| `file` | CSV, TXT o XLSX | Si | Archivo con usuarios. |
| `update_existing` | boolean | No | Actualiza usuarios existentes por email. |
| `default_password` | string | No | Password por defecto si el archivo no trae password. |

Columnas aceptadas:

```txt
name,email,role,password
nombre,correo,rol,contrasena
```

## Swagger

Genera la documentacion:

```bash
php artisan l5-swagger:generate
```

Abre Swagger UI:

```txt
http://127.0.0.1:8000/api/documentation
```

## Tests y calidad

```bash
php artisan test
vendor/bin/pint
```

En Windows:

```bat
php artisan test
vendor\bin\pint
```

## Estructura

```txt
support-ticket-api/
|-- app/
|   |-- Http/Controllers/   # Controladores REST
|   |-- Http/Requests/      # Validacion de entrada
|   |-- Http/Resources/     # Serializacion JSON
|   |-- Models/             # Modelos Eloquent
|   |-- Notifications/      # Notificaciones de tickets
|   |-- Policies/           # Autorizacion
|   `-- Services/           # Logica de dominio
|-- database/
|   |-- migrations/         # Esquema de base de datos
|   |-- seeders/            # Datos demo
|   `-- factories/          # Factories para tests
|-- docs/                   # Documentacion extendida
|-- routes/api.php          # Rutas REST
|-- tests/Feature/          # Tests de API
|-- composer.json
`-- phpunit.xml
```

## Documentacion adicional

- [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md): referencia general de API.
- [`docs/api-reference.md`](docs/api-reference.md): endpoints y contratos.
- [`docs/security.md`](docs/security.md): consideraciones de seguridad.
- [`docs/roles-and-permissions.md`](docs/roles-and-permissions.md): permisos por rol.
- [`docs/testing.md`](docs/testing.md): estrategia de pruebas.

## Estado del proyecto

El backend cubre el flujo principal de una mesa de soporte: autenticacion, tickets, agentes, usuarios, adjuntos, categorias, notificaciones, importaciones, integraciones y reportes. Las siguientes mejoras naturales son observabilidad en produccion, mas pruebas de autorizacion por rol y documentacion OpenAPI mas exhaustiva para todos los modulos administrativos.
