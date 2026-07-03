# Roles and Permissions

Este documento describe los roles disponibles y los permisos principales de cada uno.

## Roles del sistema

- `user`
- `support_agent`
- `admin`

## Rol: user

Representa a un usuario final o cliente.

### Puede hacer

- Registrarse e iniciar sesion.
- Consultar y actualizar su perfil.
- Cambiar su password.
- Crear tickets.
- Ver, actualizar y eliminar sus propios tickets segun las reglas del sistema.
- Comentar en sus propios tickets.
- Subir y descargar adjuntos de sus propios tickets.
- Ver actividad de sus propios tickets.
- Ver sus propias notificaciones.
- Marcar sus notificaciones como leidas.
- Listar catalogos publicos o activos necesarios para crear tickets.
- Responder encuesta de satisfaccion de sus tickets.

### No puede hacer

- Ver tickets de otros usuarios.
- Ver notas internas.
- Ver adjuntos internos.
- Crear notas internas.
- Escalar tickets.
- Asignar tickets.
- Ver todos los usuarios.
- Cambiar roles.
- Importar usuarios.
- Gestionar integraciones, automatizaciones o configuraciones administrativas.
- Ver reportes globales o auditoria global.

## Rol: support_agent

Representa a un agente de soporte.

### Puede hacer

- Iniciar sesion.
- Consultar y actualizar su perfil.
- Cambiar su password.
- Ver tickets de todos los usuarios.
- Comentar en cualquier ticket.
- Cambiar estados de tickets.
- Asignar o desasignar tickets segun reglas del sistema.
- Ver tickets asignados a si mismo o sin asignar.
- Crear y ver notas internas.
- Crear menciones a otros miembros de staff.
- Crear y resolver escalaciones.
- Subir, descargar y previsualizar adjuntos de tickets.
- Subir y ver adjuntos internos.
- Ver actividad de tickets.
- Usar respuestas rapidas y base de conocimiento.
- Ver dashboard operativo.
- Exportar reportes de tickets.
- Listar agentes de soporte.
- Ver y gestionar sus notificaciones.

### No puede hacer

- Gestionar usuarios.
- Cambiar roles.
- Importar usuarios.
- Ver historial de importacion de usuarios.
- Gestionar API keys o webhooks.
- Gestionar configuracion administrativa sensible.
- Cambiar su propio rol.

## Rol: admin

Representa al administrador del sistema.

### Puede hacer

- Todo lo permitido para staff.
- Ver tickets de todos los usuarios.
- Gestionar usuarios.
- Cambiar roles de otros usuarios.
- Importar usuarios por CSV/XLSX.
- Ver historial de importaciones.
- Gestionar categorias, canales, equipos, tags y campos personalizados.
- Gestionar horarios laborales y feriados.
- Gestionar respuestas rapidas, base de conocimiento y automatizaciones.
- Gestionar API keys y webhooks.
- Ver entregas de webhooks.
- Ver auditoria.
- Exportar reportes.
- Ver dashboard global.

### Restricciones

- No puede cambiar su propio rol desde el endpoint de gestion de usuarios.
- La carga XLSX requiere que el servidor tenga habilitada la extension PHP `zip` (`ZipArchive`).

## Resumen de permisos

| Accion | user | support_agent | admin |
| --- | ---: | ---: | ---: |
| Crear tickets | Si | Si | Si |
| Ver sus propios tickets | Si | Si | Si |
| Ver todos los tickets | No | Si | Si |
| Actualizar sus propios tickets | Si | Si | Si |
| Actualizar tickets de otros usuarios | No | Si | Si |
| Eliminar sus propios tickets | Si | No | Si |
| Eliminar tickets de otros usuarios | No | No | Si |
| Comentar en sus tickets | Si | Si | Si |
| Comentar en cualquier ticket | No | Si | Si |
| Ver notas internas | No | Si | Si |
| Crear notas internas | No | Si | Si |
| Ver adjuntos internos | No | Si | Si |
| Crear escalaciones | No | Si | Si |
| Asignar tickets | No | Si | Si |
| Ver dashboard global | No | Si | Si |
| Exportar reportes | No | Si | Si |
| Gestionar usuarios | No | No | Si |
| Cambiar roles | No | No | Si |
| Importar usuarios | No | No | Si |
| Ver historial de importacion | No | No | Si |
| Gestionar categorias | No | No | Si |
| Gestionar canales/equipos/tags | No | No | Si |
| Gestionar campos personalizados | No | No | Si |
| Gestionar automatizaciones | No | No | Si |
| Gestionar integraciones | No | No | Si |
| Ver notificaciones propias | Si | Si | Si |
| Cambiar perfil propio | Si | Si | Si |
| Cambiar password propio | Si | Si | Si |

## Notas importantes

- Los usuarios normales solo pueden acceder a informacion relacionada con sus propios tickets.
- Los agentes de soporte pueden trabajar sobre tickets de todos los usuarios, pero no administran usuarios ni integraciones.
- Los administradores tienen acceso a la gestion administrativa del sistema.
- La importacion masiva esta bloqueada desde el request y desde el controlador para cualquier usuario que no sea `admin`.
