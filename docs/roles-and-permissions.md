# Roles and Permissions

Este documento describe los roles disponibles en el sistema y los permisos principales de cada uno.

## Roles del sistema

El sistema maneja tres roles principales:

* `user`
* `support_agent`
* `admin`

---

## Rol: user

El rol `user` representa a un usuario normal del sistema.

### Puede hacer

* Registrarse e iniciar sesión.
* Consultar su perfil.
* Actualizar su perfil.
* Cambiar su contraseña.
* Crear tickets.
* Ver sus propios tickets.
* Actualizar sus propios tickets.
* Eliminar sus propios tickets.
* Comentar en sus propios tickets.
* Subir adjuntos en sus propios tickets.
* Descargar adjuntos de sus propios tickets.
* Ver el historial de actividad de sus propios tickets.
* Ver sus propias notificaciones.
* Marcar sus notificaciones como leídas.
* Listar categorías activas de tickets.

### No puede hacer

* Ver tickets de otros usuarios.
* Comentar tickets de otros usuarios.
* Descargar adjuntos de tickets de otros usuarios.
* Ver historial de tickets ajenos.
* Asignar tickets a agentes.
* Ver todos los usuarios.
* Cambiar roles de usuarios.
* Crear, actualizar o eliminar categorías.
* Ver estadísticas globales del sistema.

---

## Rol: support_agent

El rol `support_agent` representa a un agente de soporte.

### Puede hacer

* Iniciar sesión.
* Consultar su perfil.
* Actualizar su perfil.
* Cambiar su contraseña.
* Ver tickets de todos los usuarios.
* Comentar en cualquier ticket.
* Cambiar el estado de tickets.
* Asignar o desasignar tickets.
* Ver tickets asignados a sí mismo.
* Ver tickets sin asignar.
* Subir adjuntos en tickets.
* Descargar adjuntos de tickets.
* Ver historial de actividad de tickets.
* Ver estadísticas globales del dashboard.
* Listar agentes de soporte.
* Ver sus notificaciones.
* Marcar sus notificaciones como leídas.
* Listar categorías activas de tickets.

### No puede hacer

* Gestionar usuarios.
* Cambiar roles de usuarios.
* Crear categorías.
* Actualizar categorías.
* Eliminar categorías.
* Cambiar su propio rol.
* Gestionar permisos administrativos.

---

## Rol: admin

El rol `admin` representa al administrador del sistema.

### Puede hacer

* Iniciar sesión.
* Consultar su perfil.
* Actualizar su perfil.
* Cambiar su contraseña.
* Ver tickets de todos los usuarios.
* Crear, actualizar y eliminar tickets según las reglas del sistema.
* Comentar tickets.
* Cambiar estados de tickets.
* Asignar o desasignar tickets.
* Ver historial de actividad de tickets.
* Subir, descargar y eliminar adjuntos.
* Ver estadísticas globales del dashboard.
* Listar usuarios.
* Cambiar roles de otros usuarios.
* Gestionar categorías de tickets.
* Listar agentes de soporte.
* Ver y gestionar sus notificaciones.

### Restricciones

* No puede cambiar su propio rol desde el endpoint de gestión de usuarios.

Esta restricción evita que un administrador pierda accidentalmente sus permisos de administración.

---

## Resumen de permisos

| Acción                               | user | support_agent | admin |
| ------------------------------------ | ---: | ------------: | ----: |
| Crear tickets                        |   Sí |            Sí |    Sí |
| Ver sus propios tickets              |   Sí |            Sí |    Sí |
| Ver todos los tickets                |   No |            Sí |    Sí |
| Actualizar sus propios tickets       |   Sí |            Sí |    Sí |
| Actualizar tickets de otros usuarios |   No |            Sí |    Sí |
| Eliminar sus propios tickets         |   Sí |            No |    Sí |
| Eliminar tickets de otros usuarios   |   No |            No |    Sí |
| Comentar en sus tickets              |   Sí |            Sí |    Sí |
| Comentar en cualquier ticket         |   No |            Sí |    Sí |
| Asignar tickets                      |   No |            Sí |    Sí |
| Ver dashboard global                 |   No |            Sí |    Sí |
| Gestionar usuarios                   |   No |            No |    Sí |
| Cambiar roles                        |   No |            No |    Sí |
| Gestionar categorías                 |   No |            No |    Sí |
| Ver notificaciones propias           |   Sí |            Sí |    Sí |
| Cambiar perfil propio                |   Sí |            Sí |    Sí |
| Cambiar contraseña propia            |   Sí |            Sí |    Sí |

---

## Notas importantes

Las reglas de permisos principales se controlan desde las policies y controladores del backend.

La regla más importante del sistema es:

* Los usuarios normales solo pueden acceder a información relacionada con sus propios tickets.
* Los agentes de soporte pueden trabajar sobre tickets de todos los usuarios.
* Los administradores tienen acceso a la gestión administrativa del sistema.
