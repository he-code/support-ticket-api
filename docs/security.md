# Seguridad

Esta guia resume las protecciones actuales de la API y las practicas esperadas para consumirla desde el frontend.

## Autenticacion

- La API usa Laravel Sanctum con tokens Bearer.
- Las rutas privadas requieren `Authorization: Bearer {token}`.
- Las respuestas de autenticacion usan `UserResource`, por lo que no exponen el hash de password.
- Al cerrar sesion, `POST /api/logout` revoca el token actual.

## Login

`POST /api/login` esta protegido contra entradas mal formadas y abuso basico:

- `email` debe ser string, email valido y maximo 255 caracteres.
- `password` debe ser string y maximo 255 caracteres.
- Se rechazan arreglos u objetos, incluyendo payloads estilo NoSQL como `{"$ne": null}`.
- El email se normaliza antes de consultar: espacios fuera y minusculas.
- La busqueda del usuario usa Eloquent y parametros preparados.
- No se construye SQL manual con datos del usuario.
- Despues de 5 intentos fallidos por email + IP, la API responde `429`.
- Un login correcto limpia el contador de intentos fallidos.

Respuestas esperadas:

| Codigo | Significado |
| --- | --- |
| `200` | Login exitoso |
| `401` | Credenciales invalidas |
| `422` | Validacion fallida |
| `429` | Demasiados intentos |

## Registro

- `name` es requerido y maximo 255 caracteres.
- `email` es requerido, unico y maximo 255 caracteres.
- `password` es requerido, string, minimo 8 caracteres y maximo 255.
- El password se guarda con `Hash::make`.

## Roles y autorizacion

Roles:

- `user`
- `support_agent`
- `admin`

Reglas principales:

- `user` solo accede a sus propios tickets y datos.
- `support_agent` atiende tickets y usa herramientas de soporte.
- `admin` gestiona usuarios, configuracion, integraciones e importaciones.
- La importacion masiva de usuarios solo esta permitida para `admin`.

## Importacion de usuarios

`POST /api/users/import` y `GET /api/users/imports` requieren `admin`.

La autorizacion se valida antes de procesar el archivo. Si el usuario no es admin, responde:

```json
{
  "message": "Unauthorized"
}
```

con codigo `403`.

Formatos soportados:

- CSV
- TXT
- XLSX

Para XLSX el servidor necesita la extension PHP `zip` (`ZipArchive`). Si no esta activa, la API responde `422` y no procesa el archivo.

## Adjuntos

- Los adjuntos se guardan en storage privado.
- La descarga pasa por controlador y validacion de permisos.
- Adjuntos con `is_internal=true` solo son visibles para staff.
- Usuarios finales no pueden subir adjuntos internos.

## Recomendaciones para frontend

- Enviar siempre `Accept: application/json`.
- No reutilizar token despues de logout.
- Mostrar el mensaje de `429` como bloqueo temporal, no como error de credenciales.
- Para carga masiva, ocultar la opcion si el usuario autenticado no tiene rol `admin`.
- En formularios de login, enviar strings simples para `email` y `password`.

## Verificacion automatizada

La suite incluye pruebas para:

- Login correcto.
- Credenciales invalidas.
- Rechazo de payloads estilo NoSQL.
- Rechazo de intentos tipo SQL injection.
- Rate limit de login.
- No exposicion de password en respuestas.
- Importacion masiva restringida a `admin`.
