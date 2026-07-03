# Support Ticket API

API REST para gestion de tickets de soporte, construida con Laravel y Laravel Sanctum.

El sistema permite que usuarios creen tickets, agentes atiendan solicitudes y administradores gestionen el flujo operativo. Tambien incluye modulos de trabajo para soporte interno, automatizaciones, integraciones, reportes y carga masiva de usuarios.

## Funcionalidades principales

- Registro, login y logout con tokens Sanctum.
- Login endurecido con validacion estricta, normalizacion de email y limite de intentos.
- Roles: `user`, `support_agent` y `admin`.
- Gestion de tickets con categorias, canales, equipos, tags, SLA y campos personalizados.
- Comentarios, notas internas, menciones y escalaciones.
- Adjuntos con descarga segura, vista previa y visibilidad interna para staff.
- Base de conocimiento, respuestas rapidas y encuestas de satisfaccion.
- Reglas de automatizacion y auditoria de actividad.
- API keys, webhooks y registro de entregas de integraciones.
- Exportacion CSV de tickets.
- Importacion masiva de usuarios por CSV/XLSX, permitida solo para `admin`.
- Dashboard, notificaciones internas y perfil de usuario.

## Tecnologias

- PHP 8.3+
- Laravel
- Laravel Sanctum
- SQLite o MySQL
- PHPUnit
- Laravel Pint
- L5 Swagger

## Instalacion

```bash
git clone https://github.com/he-code/support-ticket-api.git
cd support-ticket-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

En Windows puedes copiar `.env.example` manualmente o usar:

```bat
copy .env.example .env
```

La API local queda disponible en:

```txt
http://localhost:8000/api
```

## Base de datos

Para SQLite:

```env
DB_CONNECTION=sqlite
```

Crea el archivo:

```txt
database/database.sqlite
```

Para MySQL, configura las variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `.env`.

## Autenticacion

La API usa Laravel Sanctum. Despues de iniciar sesion, envia el token en cada ruta protegida:

```http
Authorization: Bearer {token}
```

### Seguridad del login

El endpoint `POST /api/login` aplica:

- `email` y `password` deben ser strings.
- Se rechazan payloads tipo arreglo u objeto, incluyendo intentos estilo NoSQL como `{"$ne": null}`.
- El email se normaliza con `trim` y minusculas antes de consultar.
- La consulta de usuario se hace con Eloquent, usando parametros preparados.
- Despues de 5 intentos fallidos por email + IP, responde `429` temporalmente.
- Las respuestas de auth no incluyen el hash de password.

## Roles

| Rol | Descripcion |
| --- | --- |
| `user` | Usuario final. Gestiona sus propios tickets y datos de perfil. |
| `support_agent` | Agente. Atiende tickets, comenta, escala, usa notas internas y ve reportes operativos. |
| `admin` | Administrador. Gestiona usuarios, modulos administrativos, integraciones e importaciones. |

La importacion masiva de usuarios y el historial de importaciones son exclusivos de `admin`.

## Endpoints principales

### Auth

| Metodo | Endpoint | Descripcion |
| --- | --- | --- |
| POST | `/api/register` | Registrar usuario |
| POST | `/api/login` | Iniciar sesion |
| POST | `/api/logout` | Cerrar sesion |

### Usuarios

| Metodo | Endpoint | Rol | Descripcion |
| --- | --- | --- | --- |
| GET | `/api/users` | admin | Listar usuarios |
| PATCH | `/api/users/{user}/role` | admin | Cambiar rol |
| GET | `/api/users/imports` | admin | Ver historial de importaciones |
| POST | `/api/users/import` | admin | Importar usuarios por CSV/XLSX |
| GET | `/api/support-agents` | autenticado | Listar agentes |


## Usuarios demo

Después de ejecutar:

    php artisan migrate:fresh --seed

Se crearán los siguientes usuarios de prueba:

| Rol | Nombre | Email | Contraseña |
|---|---|---|---|
| Administrador | Admin User | admin@example.com | password |
| Agente de soporte | Support Agent | agent@example.com | password |
| Agente de soporte | Second Support Agent | agent2@example.com | password |
| Usuario normal | Regular User | user@example.com | password |


### Tickets

| Metodo | Endpoint | Descripcion |
| --- | --- | --- |
| GET | `/api/tickets` | Listar tickets con filtros |
| POST | `/api/tickets` | Crear ticket |
| GET | `/api/tickets/{ticket}` | Ver ticket |
| PATCH | `/api/tickets/{ticket}` | Actualizar ticket |
| DELETE | `/api/tickets/{ticket}` | Eliminar ticket |
| PATCH | `/api/tickets/{ticket}/status` | Cambiar estado |
| PATCH | `/api/tickets/{ticket}/assign` | Asignar o desasignar ticket |

### Modulos de ticket

| Metodo | Endpoint | Descripcion |
| --- | --- | --- |
| GET/POST | `/api/tickets/{ticket}/comments` | Comentarios |
| GET/POST | `/api/tickets/{ticket}/attachments` | Adjuntos |
| GET | `/api/tickets/{ticket}/attachments/{attachment}/download` | Descargar adjunto |
| GET | `/api/tickets/{ticket}/attachments/{attachment}/preview` | Vista previa |
| GET | `/api/tickets/{ticket}/activities` | Actividad |
| GET/POST | `/api/tickets/{ticket}/internal-notes` | Notas internas |
| GET | `/api/tickets/{ticket}/mentions` | Menciones |
| GET/POST | `/api/tickets/{ticket}/escalations` | Escalaciones |
| PATCH | `/api/tickets/{ticket}/escalations/{escalation}/resolve` | Resolver escalacion |
| GET/POST | `/api/tickets/{ticket}/knowledge-base-articles` | Articulos vinculados |
| GET/POST | `/api/tickets/{ticket}/satisfaction` | Encuesta de satisfaccion |

### Catalogos y flujo

| Metodo | Endpoint | Descripcion |
| --- | --- | --- |
| apiResource | `/api/ticket-categories` | Categorias |
| apiResource | `/api/categories` | Alias compatible para categorias |
| apiResource | `/api/ticket-channels` | Canales de entrada |
| apiResource | `/api/support-teams` | Equipos |
| apiResource | `/api/ticket-tags` | Tags |
| apiResource | `/api/quick-replies` | Respuestas rapidas |
| apiResource | `/api/knowledge-base-articles` | Base de conocimiento |
| apiResource | `/api/automation-rules` | Automatizaciones |
| apiResource | `/api/custom-fields` | Campos personalizados |

### Horarios, integraciones y reportes

| Metodo | Endpoint | Descripcion |
| --- | --- | --- |
| GET/POST/DELETE | `/api/business-hours` | Horarios laborales |
| GET/POST/DELETE | `/api/business-holidays` | Feriados |
| GET/POST/DELETE | `/api/integrations/api-keys` | API keys |
| apiResource | `/api/integrations/webhooks` | Webhooks |
| GET | `/api/integrations/webhooks/{webhook}/deliveries` | Entregas del webhook |
| GET | `/api/audit-logs` | Auditoria |
| GET | `/api/reports/tickets/export` | Exportar tickets CSV |
| GET | `/api/dashboard/stats` | Estadisticas |

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

Requiere token de un usuario `admin`.

Campos multipart:

| Campo | Tipo | Requerido | Descripcion |
| --- | --- | --- | --- |
| `file` | archivo CSV/TXT/XLSX | si | Archivo con usuarios |
| `update_existing` | boolean | no | Actualiza usuarios existentes por email |
| `default_password` | string | no | Password por defecto si el archivo no trae password |

Columnas aceptadas:

```txt
name,email,role,password
```

Tambien se aceptan alias en espanol como `nombre`, `correo`, `rol` y `contrasena`.

Para XLSX, el PHP del servidor debe tener habilitada la extension `zip` (`ZipArchive`). Si no esta disponible, la API responde `422` con un mensaje claro.

## Documentacion adicional

- [Referencia de API](docs/api-reference.md)
- [Seguridad](docs/security.md)
- [Roles y permisos](docs/roles-and-permissions.md)
- [Testing](docs/testing.md)

## Tests y formato

```bash
php artisan test
vendor/bin/pint
```

En Windows:

```bat
php artisan test
vendor\bin\pint
```

## Estado del proyecto

Backend en desarrollo activo con suite automatizada para autenticacion, tickets, modulos de workflow, importacion y seguridad basica del login.
