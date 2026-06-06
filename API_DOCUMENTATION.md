# API Documentation

Documentación general de los endpoints disponibles en la API del sistema de tickets de soporte.

## Base URL

En entorno local:

```txt
http://localhost:8000/api
```

## Autenticación

La API utiliza Laravel Sanctum para autenticación mediante tokens.

Después de iniciar sesión, las rutas protegidas deben recibir el token en el header:

```http
Authorization: Bearer {token}
```

---

# Auth

## Registrar usuario

```http
POST /api/register
```

Body:

```json
{
  "name": "Usuario Demo",
  "email": "usuario@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

## Iniciar sesión

```http
POST /api/login
```

Body:

```json
{
  "email": "usuario@example.com",
  "password": "password"
}
```

## Cerrar sesión

```http
POST /api/logout
```

Requiere autenticación.

---

# Profile

## Obtener usuario autenticado

```http
GET /api/me
```

Requiere autenticación.

## Actualizar perfil

```http
PATCH /api/profile
```

Body:

```json
{
  "name": "Nuevo nombre",
  "email": "nuevo@example.com"
}
```

## Cambiar contraseña

```http
PATCH /api/password
```

Body:

```json
{
  "current_password": "password",
  "password": "new-password",
  "password_confirmation": "new-password"
}
```

---

# Tickets

## Listar tickets

```http
GET /api/tickets
```

Requiere autenticación.

Los usuarios normales solo ven sus propios tickets.

Los agentes de soporte y administradores pueden ver todos los tickets.

### Filtros disponibles

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

## Crear ticket

```http
POST /api/tickets
```

Body:

```json
{
  "title": "No puedo iniciar sesión",
  "description": "El sistema no acepta mis credenciales.",
  "priority": "high",
  "category_id": 1
}
```

## Ver ticket

```http
GET /api/tickets/{ticket}
```

## Actualizar ticket

```http
PATCH /api/tickets/{ticket}
```

Body:

```json
{
  "title": "Título actualizado",
  "description": "Descripción actualizada",
  "priority": "medium",
  "category_id": 1
}
```

## Eliminar ticket

```http
DELETE /api/tickets/{ticket}
```

## Cambiar estado de ticket

```http
PATCH /api/tickets/{ticket}/status
```

Body:

```json
{
  "status": "closed"
}
```

Estados disponibles:

```txt
open
in_progress
resolved
closed
```

## Asignar ticket a agente

```http
PATCH /api/tickets/{ticket}/assign
```

Body:

```json
{
  "assigned_to_id": 3
}
```

## Desasignar ticket

```http
PATCH /api/tickets/{ticket}/assign
```

Body:

```json
{
  "assigned_to_id": null
}
```

---

# Ticket Comments

## Listar comentarios de un ticket

```http
GET /api/tickets/{ticket}/comments
```

## Crear comentario

```http
POST /api/tickets/{ticket}/comments
```

Body:

```json
{
  "body": "Estamos revisando el problema."
}
```

## Eliminar comentario

```http
DELETE /api/tickets/{ticket}/comments/{comment}
```

---

# Ticket Attachments

## Listar adjuntos

```http
GET /api/tickets/{ticket}/attachments
```

## Subir adjunto

```http
POST /api/tickets/{ticket}/attachments
```

Body tipo `multipart/form-data`:

```txt
file: archivo
```

Tipos permitidos:

```txt
jpg, jpeg, png, pdf, txt, doc, docx
```

Tamaño máximo:

```txt
5 MB
```

## Descargar adjunto

```http
GET /api/tickets/{ticket}/attachments/{attachment}/download
```

## Eliminar adjunto

```http
DELETE /api/tickets/{ticket}/attachments/{attachment}
```

---

# Ticket Activities

## Ver historial de actividad del ticket

```http
GET /api/tickets/{ticket}/activities
```

El historial registra eventos como:

```txt
ticket_created
ticket_updated
status_changed
ticket_assigned
ticket_unassigned
comment_created
comment_deleted
attachment_uploaded
attachment_deleted
```

---

# Ticket Categories

## Listar categorías

```http
GET /api/ticket-categories
```

Todos los usuarios autenticados pueden listar categorías activas.

El administrador puede ver todas.

## Crear categoría

```http
POST /api/ticket-categories
```

Solo admin.

Body:

```json
{
  "name": "Soporte técnico",
  "description": "Problemas técnicos del sistema.",
  "is_active": true
}
```

## Ver categoría

```http
GET /api/ticket-categories/{ticketCategory}
```

## Actualizar categoría

```http
PATCH /api/ticket-categories/{ticketCategory}
```

Solo admin.

Body:

```json
{
  "name": "Facturación",
  "description": "Problemas relacionados con pagos.",
  "is_active": true
}
```

## Eliminar categoría

```http
DELETE /api/ticket-categories/{ticketCategory}
```

Solo admin.

---

# Support Agents

## Listar agentes de soporte

```http
GET /api/support-agents
```

Disponible para:

```txt
support_agent
admin
```

---

# Dashboard

## Obtener estadísticas

```http
GET /api/dashboard/stats
```

Usuarios normales ven estadísticas de sus propios tickets.

Agentes de soporte y administradores ven estadísticas globales.

Respuesta ejemplo:

```json
{
  "total_tickets": 10,
  "by_status": {
    "open": 4,
    "in_progress": 2,
    "resolved": 3,
    "closed": 1
  },
  "by_priority": {
    "low": 2,
    "medium": 5,
    "high": 3
  },
  "unassigned_tickets": 4,
  "assigned_to_me": 2
}
```

---

# Notifications

## Listar notificaciones

```http
GET /api/notifications
```

## Marcar una notificación como leída

```http
PATCH /api/notifications/{notification}/read
```

## Marcar todas las notificaciones como leídas

```http
PATCH /api/notifications/read-all
```

---

# User Management

## Listar usuarios

```http
GET /api/users
```

Solo admin.

Permite filtros:

```http
GET /api/users?role=support_agent
GET /api/users?search=carlos
```

## Cambiar rol de usuario

```http
PATCH /api/users/{user}/role
```

Solo admin.

Body:

```json
{
  "role": "support_agent"
}
```

Roles disponibles:

```txt
user
support_agent
admin
```

El administrador no puede cambiar su propio rol desde este endpoint.

---

# Códigos de respuesta comunes

## 200 OK

La operación fue exitosa.

## 201 Created

El recurso fue creado correctamente.

## 401 Unauthorized

El usuario no está autenticado o no envió token.

## 403 Forbidden

El usuario está autenticado, pero no tiene permisos para realizar la acción.

## 404 Not Found

El recurso solicitado no existe.

## 422 Unprocessable Entity

Los datos enviados no pasaron la validación.

## 500 

Error interno del servidor
