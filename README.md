# Support Ticket API

API REST para la gestión de tickets de soporte, desarrollada con Laravel y Laravel Sanctum.

El sistema permite que usuarios creen tickets de soporte, agentes atiendan solicitudes y administradores gestionen usuarios, categorías y el flujo general del sistema.

## Funcionalidades principales

* Registro e inicio de sesión de usuarios.
* Autenticación mediante tokens con Laravel Sanctum.
* Gestión de tickets de soporte.
* Filtros, búsqueda, ordenamiento y paginación de tickets.
* Roles de usuario: `user`, `support_agent` y `admin`.
* Comentarios en tickets.
* Asignación de tickets a agentes de soporte.
* Historial de actividad por ticket.
* Adjuntos en tickets con descarga segura.
* Notificaciones internas.
* Dashboard de estadísticas.
* Gestión de usuarios por administrador.
* Perfil del usuario autenticado.
* Categorías de tickets.

## Tecnologías utilizadas

* PHP
* Laravel
* Laravel Sanctum
* SQLite / MySQL
* PHPUnit
* Laravel Pint

## Requisitos

* PHP 8.3 o superior
* Composer
* SQLite o MySQL
* Git

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

Copiar el archivo de entorno:

```bash
cp .env.example .env
```

En Windows también puedes copiarlo manualmente o usar:

```bash
copy .env.example .env
```

Generar la clave de la aplicación:

```bash
php artisan key:generate
```

Configurar la base de datos en el archivo `.env`.

Para SQLite, se puede usar:

```env
DB_CONNECTION=sqlite
```

Y crear el archivo de base de datos:

```bash
touch database/database.sqlite
```

En Windows, puedes crear manualmente el archivo:

```txt
database/database.sqlite
```

Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

Levantar el servidor local:

```bash
php artisan serve
```

La API estará disponible en:

```txt
http://localhost:8000/api
```

## Autenticación

La API utiliza Laravel Sanctum.

Después de iniciar sesión, las rutas protegidas deben recibir el token en el header:

```http
Authorization: Bearer {token}
```

## Roles del sistema

| Rol             | Descripción                                                                     |
| --------------- | ------------------------------------------------------------------------------- |
| `user`          | Usuario normal. Puede crear y gestionar sus propios tickets.                    |
| `support_agent` | Agente de soporte. Puede ver y atender tickets de todos los usuarios.           |
| `admin`         | Administrador. Puede gestionar usuarios, categorías y recursos administrativos. |

## Endpoints principales

### Auth

| Método | Endpoint        | Descripción       |
| ------ | --------------- | ----------------- |
| POST   | `/api/register` | Registrar usuario |
| POST   | `/api/login`    | Iniciar sesión    |
| POST   | `/api/logout`   | Cerrar sesión     |

### Profile

| Método | Endpoint        | Descripción                 |
| ------ | --------------- | --------------------------- |
| GET    | `/api/me`       | Obtener usuario autenticado |
| PATCH  | `/api/profile`  | Actualizar perfil           |
| PATCH  | `/api/password` | Cambiar contraseña          |

### Tickets

| Método | Endpoint                       | Descripción                 |
| ------ | ------------------------------ | --------------------------- |
| GET    | `/api/tickets`                 | Listar tickets              |
| POST   | `/api/tickets`                 | Crear ticket                |
| GET    | `/api/tickets/{ticket}`        | Ver ticket                  |
| PATCH  | `/api/tickets/{ticket}`        | Actualizar ticket           |
| DELETE | `/api/tickets/{ticket}`        | Eliminar ticket             |
| PATCH  | `/api/tickets/{ticket}/status` | Cambiar estado              |
| PATCH  | `/api/tickets/{ticket}/assign` | Asignar o desasignar ticket |

### Comentarios

| Método | Endpoint                                   | Descripción         |
| ------ | ------------------------------------------ | ------------------- |
| GET    | `/api/tickets/{ticket}/comments`           | Listar comentarios  |
| POST   | `/api/tickets/{ticket}/comments`           | Crear comentario    |
| DELETE | `/api/tickets/{ticket}/comments/{comment}` | Eliminar comentario |

### Adjuntos

| Método | Endpoint                                                  | Descripción       |
| ------ | --------------------------------------------------------- | ----------------- |
| GET    | `/api/tickets/{ticket}/attachments`                       | Listar adjuntos   |
| POST   | `/api/tickets/{ticket}/attachments`                       | Subir adjunto     |
| GET    | `/api/tickets/{ticket}/attachments/{attachment}/download` | Descargar adjunto |
| DELETE | `/api/tickets/{ticket}/attachments/{attachment}`          | Eliminar adjunto  |

### Categorías

| Método | Endpoint                                  | Descripción          |
| ------ | ----------------------------------------- | -------------------- |
| GET    | `/api/ticket-categories`                  | Listar categorías    |
| POST   | `/api/ticket-categories`                  | Crear categoría      |
| GET    | `/api/ticket-categories/{ticketCategory}` | Ver categoría        |
| PATCH  | `/api/ticket-categories/{ticketCategory}` | Actualizar categoría |
| DELETE | `/api/ticket-categories/{ticketCategory}` | Eliminar categoría   |

### Notificaciones

| Método | Endpoint                                 | Descripción                    |
| ------ | ---------------------------------------- | ------------------------------ |
| GET    | `/api/notifications`                     | Listar notificaciones          |
| PATCH  | `/api/notifications/{notification}/read` | Marcar notificación como leída |
| PATCH  | `/api/notifications/read-all`            | Marcar todas como leídas       |

### Administración

| Método | Endpoint                 | Descripción                  |
| ------ | ------------------------ | ---------------------------- |
| GET    | `/api/users`             | Listar usuarios              |
| PATCH  | `/api/users/{user}/role` | Cambiar rol de usuario       |
| GET    | `/api/support-agents`    | Listar agentes de soporte    |
| GET    | `/api/dashboard/stats`   | Ver estadísticas del sistema |

## Filtros de tickets

El endpoint `GET /api/tickets` permite usar filtros por query string.

Ejemplos:

```http
GET /api/tickets?status=open
GET /api/tickets?priority=high
GET /api/tickets?search=login
GET /api/tickets?assigned=me
GET /api/tickets?assigned=unassigned
GET /api/tickets?assigned_to_id=3
GET /api/tickets?category_id=1
GET /api/tickets?sort_by=created_at&sort_direction=desc
```

## Estados de ticket

```txt
open
in_progress
resolved
closed
```

## Prioridades de ticket

```txt
low
medium
high
```

## Ejecutar tests

Ejecutar todos los tests:

```bash
php artisan test
```

Ejecutar tests por módulo:

```bash
php artisan test tests/Feature/TicketTest.php
php artisan test tests/Feature/TicketCommentTest.php
php artisan test tests/Feature/TicketAttachmentTest.php
php artisan test tests/Feature/NotificationTest.php
php artisan test tests/Feature/TicketCategoryTest.php
```

## Formatear código

```bash
./vendor/bin/pint
```

En Windows:

```bash
vendor\bin\pint
```

## Documentación adicional

* [Documentación de API](API_DOCUMENTATION.md)
* [Roles y permisos](docs/roles-and-permissions.md)
* [Testing](docs/testing.md)

## Estado del proyecto

Backend en desarrollo activo.

Módulos implementados:

* Autenticación
* Tickets
* Comentarios
* Adjuntos
* Actividades
* Notificaciones
* Categorías
* Perfil
* Gestión de usuarios
* Dashboard
