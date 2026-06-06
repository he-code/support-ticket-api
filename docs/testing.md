# Testing

Este documento explica cómo ejecutar las pruebas automatizadas del proyecto.

El backend utiliza PHPUnit junto con las herramientas de testing incluidas en Laravel.

---

## Ejecutar todos los tests

Para ejecutar toda la suite de pruebas:

```bash
php artisan test
```

Este comando ejecuta todos los tests ubicados en:

```txt
tests/Feature
tests/Unit
```

---

## Ejecutar tests por módulo

También se pueden ejecutar pruebas específicas por archivo.

### Autenticación

```bash
php artisan test tests/Feature/AuthTest.php
```

### Tickets

```bash
php artisan test tests/Feature/TicketTest.php
```

### Comentarios de tickets

```bash
php artisan test tests/Feature/TicketCommentTest.php
```

### Historial de actividad

```bash
php artisan test tests/Feature/TicketActivityTest.php
```

### Adjuntos

```bash
php artisan test tests/Feature/TicketAttachmentTest.php
```

### Notificaciones

```bash
php artisan test tests/Feature/NotificationTest.php
```

### Gestión de usuarios

```bash
php artisan test tests/Feature/UserManagementTest.php
```

### Perfil del usuario autenticado

```bash
php artisan test tests/Feature/ProfileTest.php
```

### Categorías de tickets

```bash
php artisan test tests/Feature/TicketCategoryTest.php
```

### Dashboard

```bash
php artisan test tests/Feature/DashboardTest.php
```

### Agentes de soporte

```bash
php artisan test tests/Feature/SupportAgentTest.php
```

---

## Ejecutar un test específico

También puedes ejecutar un solo método de prueba usando `--filter`.

Ejemplo:

```bash
php artisan test --filter=test_authenticated_user_can_create_ticket
```

Esto es útil cuando se está corrigiendo un error puntual y no se quiere ejecutar toda la suite.

---

## Preparar base de datos antes de probar

Si necesitas reiniciar completamente la base de datos local:

```bash
php artisan migrate:fresh --seed
```

Luego puedes ejecutar:

```bash
php artisan test
```

---

## Formatear código con Laravel Pint

El proyecto usa Laravel Pint para mantener un estilo de código consistente.

En Linux, macOS o Git Bash:

```bash
./vendor/bin/pint
```

En Windows también puedes usar:

```bash
vendor\bin\pint
```

---

## Flujo recomendado antes de hacer commit

Antes de crear un commit, se recomienda ejecutar:

```bash
php artisan test
./vendor/bin/pint
git status
```

En Windows:

```bash
php artisan test
vendor\bin\pint
git status
```

Si todos los tests pasan y Pint no deja cambios pendientes, el código está listo para commit.

---

## Comandos útiles de Git durante testing

Ver archivos modificados:

```bash
git status
```

Ver cambios realizados:

```bash
git diff
```

Agregar cambios:

```bash
git add .
```

Crear commit:

```bash
git commit -m "Mensaje descriptivo del cambio"
```

Enviar cambios al repositorio remoto:

```bash
git push origin main
```

---

## Recomendaciones

* Ejecutar los tests después de crear o modificar un módulo.
* Ejecutar tests específicos durante el desarrollo.
* Ejecutar toda la suite antes de cada commit importante.
* No hacer commit si hay tests fallando.
* Revisar siempre `git status` antes de confirmar cambios.
* Usar mensajes de commit claros y descriptivos.
