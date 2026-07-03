# API Reference

Todas las rutas protegidas requieren:

```http
Authorization: Bearer {token}
Accept: application/json
```

Las respuestas de listados paginados incluyen una llave de datos y un bloque `pagination`.

## Auth

| Metodo | Ruta | Acceso | Descripcion |
| --- | --- | --- | --- |
| POST | `/api/register` | publico | Registra usuario y devuelve token |
| POST | `/api/login` | publico | Inicia sesion y devuelve token |
| POST | `/api/logout` | autenticado | Revoca el token actual |

### POST /api/login

Body:

```json
{
  "email": "admin@example.com",
  "password": "password123"
}
```

Respuestas:

- `200`: login correcto.
- `401`: credenciales invalidas.
- `422`: validacion fallida.
- `429`: demasiados intentos fallidos.

## Perfil y notificaciones

| Metodo | Ruta | Descripcion |
| --- | --- | --- |
| GET | `/api/me` | Usuario autenticado |
| PATCH | `/api/profile` | Actualizar nombre/email |
| PATCH | `/api/password` | Cambiar password |
| GET | `/api/notifications` | Listar notificaciones |
| PATCH | `/api/notifications/{notification}/read` | Marcar una notificacion como leida |
| PATCH | `/api/notifications/read-all` | Marcar todas como leidas |

## Usuarios

| Metodo | Ruta | Acceso | Descripcion |
| --- | --- | --- | --- |
| GET | `/api/users` | admin | Listar usuarios |
| PATCH | `/api/users/{user}/role` | admin | Cambiar rol de otro usuario |
| GET | `/api/users/imports` | admin | Historial de importaciones |
| POST | `/api/users/import` | admin | Carga masiva por CSV/XLSX |
| GET | `/api/support-agents` | autenticado | Lista de agentes de soporte |

### POST /api/users/import

Content type: `multipart/form-data`

Campos:

| Campo | Tipo | Requerido | Notas |
| --- | --- | --- | --- |
| `file` | archivo | si | `csv`, `txt` o `xlsx`, maximo 10 MB |
| `update_existing` | boolean | no | Actualiza usuarios existentes por email |
| `default_password` | string | no | Minimo 6 caracteres |

Columnas soportadas:

```txt
name,email,role,password
```

Alias soportados:

```txt
nombre,correo,rol,contrasena
```

Roles validos en archivo:

```txt
user
support_agent
admin
```

## Tickets

| Metodo | Ruta | Descripcion |
| --- | --- | --- |
| GET | `/api/tickets` | Listar tickets |
| POST | `/api/tickets` | Crear ticket |
| GET | `/api/tickets/{ticket}` | Detalle |
| PATCH | `/api/tickets/{ticket}` | Actualizar |
| DELETE | `/api/tickets/{ticket}` | Eliminar |
| PATCH | `/api/tickets/{ticket}/status` | Cambiar estado |
| PATCH | `/api/tickets/{ticket}/assign` | Asignar/desasignar |

### Campos para crear ticket

```json
{
  "title": "No puedo iniciar sesion",
  "description": "El sistema no acepta mis credenciales.",
  "priority": "high",
  "category_id": 1,
  "channel_id": 1,
  "team_id": 1,
  "tag_ids": [1, 2],
  "custom_fields": {
    "invoice_number": "FAC-100"
  }
}
```

Campos requeridos: `title`, `description`, `priority`.

## Filtros de tickets

`GET /api/tickets` acepta:

| Query | Tipo | Notas |
| --- | --- | --- |
| `status` | string | Estado valido |
| `priority` | string | Prioridad valida |
| `search` | string | Busca por titulo, descripcion, usuario, agente, categoria, canal, equipo o tag |
| `assigned` | string | `me` o `unassigned` |
| `assigned_to_id` | integer | Usuario asignado |
| `category_id` | integer | Categoria |
| `channel_id` | integer | Canal |
| `team_id` | integer | Equipo |
| `tag_id` | integer | Tag |
| `tag_ids[]` | array | Varios tags |
| `created_from` | date | Fecha inicial |
| `created_to` | date | Fecha final |
| `due_before` | date | Vencimientos antes de fecha |
| `overdue` | boolean | Tickets vencidos |
| `sla` | string | `on_track`, `overdue`, `first_response_overdue`, `resolution_overdue` |
| `sort_by` | string | Campo de ordenamiento permitido |
| `sort_direction` | string | `asc` o `desc` |

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

## Comentarios, adjuntos y actividad

| Metodo | Ruta | Descripcion |
| --- | --- | --- |
| GET | `/api/tickets/{ticket}/comments` | Listar comentarios |
| POST | `/api/tickets/{ticket}/comments` | Crear comentario |
| DELETE | `/api/tickets/{ticket}/comments/{comment}` | Eliminar comentario |
| GET | `/api/tickets/{ticket}/attachments` | Listar adjuntos |
| POST | `/api/tickets/{ticket}/attachments` | Subir adjunto |
| GET | `/api/tickets/{ticket}/attachments/{attachment}/download` | Descargar |
| GET | `/api/tickets/{ticket}/attachments/{attachment}/preview` | Previsualizar |
| DELETE | `/api/tickets/{ticket}/attachments/{attachment}` | Eliminar adjunto |
| GET | `/api/tickets/{ticket}/activities` | Historial |

Los adjuntos pueden recibir `is_internal=true`; solo staff puede subirlos y verlos.

## Trabajo interno

| Metodo | Ruta | Descripcion |
| --- | --- | --- |
| GET | `/api/tickets/{ticket}/internal-notes` | Listar notas internas |
| POST | `/api/tickets/{ticket}/internal-notes` | Crear nota interna |
| DELETE | `/api/tickets/{ticket}/internal-notes/{internalNote}` | Eliminar nota |
| GET | `/api/tickets/{ticket}/mentions` | Listar menciones |
| GET | `/api/tickets/{ticket}/escalations` | Listar escalaciones |
| POST | `/api/tickets/{ticket}/escalations` | Crear escalacion |
| PATCH | `/api/tickets/{ticket}/escalations/{escalation}/resolve` | Resolver escalacion |

## Base de conocimiento y satisfaccion

| Metodo | Ruta | Descripcion |
| --- | --- | --- |
| GET | `/api/tickets/{ticket}/knowledge-base-articles` | Articulos vinculados |
| POST | `/api/tickets/{ticket}/knowledge-base-articles` | Vincular articulo |
| DELETE | `/api/tickets/{ticket}/knowledge-base-articles/{knowledgeBaseArticle}` | Quitar vinculo |
| GET | `/api/tickets/{ticket}/satisfaction` | Ver encuesta |
| POST | `/api/tickets/{ticket}/satisfaction` | Crear encuesta |

## Catalogos

Estas rutas usan comportamiento `apiResource`: `index`, `store`, `show`, `update`, `destroy`.

| Ruta | Descripcion |
| --- | --- |
| `/api/ticket-categories` | Categorias |
| `/api/categories` | Alias compatible para frontend |
| `/api/ticket-channels` | Canales |
| `/api/support-teams` | Equipos |
| `/api/ticket-tags` | Tags |
| `/api/quick-replies` | Respuestas rapidas |
| `/api/knowledge-base-articles` | Base de conocimiento |
| `/api/automation-rules` | Automatizaciones |
| `/api/custom-fields` | Campos personalizados |

## Horarios y feriados

| Metodo | Ruta | Descripcion |
| --- | --- | --- |
| GET | `/api/business-hours` | Listar horarios |
| POST | `/api/business-hours` | Crear horario |
| DELETE | `/api/business-hours/{businessHour}` | Eliminar horario |
| GET | `/api/business-holidays` | Listar feriados |
| POST | `/api/business-holidays` | Crear feriado |
| DELETE | `/api/business-holidays/{businessHoliday}` | Eliminar feriado |

## Integraciones y reportes

| Metodo | Ruta | Descripcion |
| --- | --- | --- |
| GET | `/api/integrations/api-keys` | Listar API keys |
| POST | `/api/integrations/api-keys` | Crear API key |
| DELETE | `/api/integrations/api-keys/{apiKey}` | Revocar API key |
| apiResource | `/api/integrations/webhooks` | Gestionar webhooks |
| GET | `/api/integrations/webhooks/{webhook}/deliveries` | Entregas |
| GET | `/api/audit-logs` | Auditoria |
| GET | `/api/reports/tickets/export` | Exportar tickets CSV |
| GET | `/api/dashboard/stats` | Dashboard |
