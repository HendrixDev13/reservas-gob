# Pruebas antes de producción (Neon PostgreSQL)

## Instalación y configuración
1. `php -v`: PHP 8.2 o superior.
2. `php -m`: debe mostrar `pdo_pgsql`, `openssl`, `mbstring` y `json`.
3. Ejecuta `database/schema.sql` en el SQL Editor de Neon.
4. Opcional: ejecuta `database/seed.sql`.
5. Configura `backend/.env`; nunca guardes credenciales en el repositorio.
6. Ejecuta `php backend/tests/smoke.php` y `php backend/tests/jwt.php`.

## Conexión y salud
- Abre `GET /health` si el document root API es `backend/public`; si está publicada bajo `/api`, usa `GET /api/health`.
- La respuesta esperada contiene `ok: true`.
- Si falla la conexión, revisa el log JSON y verifica host, nombre de base, rol, contraseña, SSL y `pdo_pgsql`.

## Usuarios
- Crea secretaria y consulta mediante `backend/bin/create_user.php`.
- Comprueba que los registros se insertaron en `usuarios` y que `password_hash` no contiene la contraseña original.
- Login correcto devuelve token; contraseña incorrecta devuelve HTTP 401.

## Pruebas funcionales
- Sin token, las rutas privadas devuelven HTTP 401.
- `consulta` puede leer dashboard, salones, equipos y reservas; no puede crear ni cambiar estados.
- `secretaria` puede crear y gestionar reservas.
- Dos reservas del mismo salón con horarios superpuestos: la segunda devuelve HTTP 409.
- Reservas contiguas, donde la segunda inicia exactamente al finalizar la primera, deben aceptarse.
- Una reserva cancelada libera el horario.
- Fin igual o anterior al inicio devuelve HTTP 422.
- CORS solo permite el origen exacto configurado.
- Excepciones internas devuelven mensaje genérico en producción e incluyen request ID; los logs no deben contener secretos.
- Cron ejecutado desde CLI devuelve código 0 al éxito y 1 al fallo.
- `.env` y `storage/logs` no deben poder descargarse por HTTP.

Estas pruebas documentan el plan de verificación; la integración con Neon y el cPanel reales debe ejecutarse con las credenciales de la instalación.
