# Testing

Este documento explica como ejecutar las pruebas automatizadas del proyecto.

El backend utiliza PHPUnit junto con las herramientas de testing de Laravel.

## Ejecutar todos los tests

```bash
php artisan test
```

La suite actual cubre autenticacion, tickets, comentarios, adjuntos, notificaciones, categorias, dashboard, perfil, usuarios, modulos de workflow, importacion y seguridad basica del login.

Ultima verificacion local:

```txt
145 tests passed
486 assertions
```

## Ejecutar tests por modulo

### Autenticacion y seguridad de login

```bash
php artisan test tests/Feature/AuthTest.php
```

Cubre:

- Login correcto.
- Credenciales invalidas.
- Respuesta sin password.
- Payloads estilo NoSQL rechazados.
- Intentos tipo SQL injection rechazados.
- Rate limit por intentos fallidos.

### Tickets

```bash
php artisan test tests/Feature/TicketTest.php
```

### Comentarios

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

### Gestion de usuarios

```bash
php artisan test tests/Feature/UserManagementTest.php
```

### Perfil

```bash
php artisan test tests/Feature/ProfileTest.php
```

### Categorias

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

### Modulos de workflow

```bash
php artisan test tests/Feature/WorkflowModulesTest.php
```

Cubre categorias por alias `/api/categories`, equipos, tags, SLA, respuestas rapidas, base de conocimiento, satisfaccion y automatizaciones.

### Modulos adicionales e importacion

```bash
php artisan test tests/Feature/AdditionalModulesTest.php
```

Cubre canales, notas internas, menciones, escalaciones, campos personalizados, webhooks, adjuntos internos, reportes e importacion masiva solo para `admin`.

## Ejecutar un test especifico

```bash
php artisan test --filter=test_login_rate_limits_repeated_failed_attempts
```

## Preparar base de datos local

Para reiniciar la base de datos local:

```bash
php artisan migrate:fresh --seed
```

Luego ejecuta:

```bash
php artisan test
```

## Formatear codigo con Laravel Pint

En Linux, macOS o Git Bash:

```bash
./vendor/bin/pint
```

En Windows:

```bat
vendor\bin\pint
```

## Flujo recomendado antes de commit

```bash
php artisan test
vendor/bin/pint
git status
```

En Windows:

```bat
php artisan test
vendor\bin\pint
git status
```

## Notas

- Ejecuta tests especificos mientras desarrollas un modulo.
- Ejecuta toda la suite antes de entregar cambios.
- No hagas commit si hay tests fallando.
- Revisa siempre `git status` antes de confirmar cambios.
