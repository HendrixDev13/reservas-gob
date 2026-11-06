# Sistema de Reservas Institucional — Angular + PHP + Neon PostgreSQL

Proyecto de reservas institucionales preparado para configurar y desplegar en cPanel: Angular compilado como archivos estáticos, API REST PHP y PostgreSQL alojado en Neon.

## Funcionalidades incluidas
- API PHP con respuestas JSON consistentes, manejo centralizado de excepciones y logs JSON.
- Autenticación JWT HS256 con funciones criptográficas nativas de PHP y roles `secretaria` / `consulta`.
- Dashboard, consulta de reservas, creación y cambio de estado de reservas.
- Catálogo de salones y equipos.
- Auditoría de acciones.
- Validación de solapamientos dentro de transacciones y restricción PostgreSQL para proteger también ante solicitudes simultáneas.
- Endpoint de salud, creación inicial de usuarios desde CLI y cron de mantenimiento.
- Angular standalone con login, dashboard, reservas, salones y equipos.

## Requisitos
- PHP 8.2+ con `PDO`, extensión `pdo_pgsql`, JSON, OpenSSL y Mbstring.
- Cuenta de Neon con una base PostgreSQL y datos de conexión.
- Node.js 20+ y npm.
- HTTPS activo en producción.

## 1. Preparar Neon
1. Crea un proyecto en Neon y abre **Connect** / **Connection Details**.
2. Selecciona la base y el rol que utilizará la aplicación.
3. Copia host, puerto, nombre de base, usuario y contraseña. No compartas la cadena de conexión ni la contraseña.
4. Ejecuta `database/schema.sql` en el SQL Editor de Neon. El esquema crea roles, usuarios, salones, equipos, reservas, auditoría y tokens revocados.
5. Opcionalmente ejecuta `database/seed.sql` para cargar los tres salones y equipos de ejemplo.

El esquema está diseñado para PostgreSQL. No lo importes en phpMyAdmin/MySQL.

## 2. Configurar el backend
Desde `backend/`, copia `.env.example` como `.env` y completa:
- `DB_HOST`: solo el hostname de Neon, sin `https://` ni `postgresql://`.
- `DB_PORT`: normalmente `5432`.
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: valores de Neon.
- `DB_SSLMODE=require`.
- `JWT_SECRET`: secreto aleatorio largo.
- `FRONTEND_ORIGIN`: origen exacto del frontend, sin barra final.

Genera el secreto JWT con:
```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Comprueba que PHP tenga `pdo_pgsql`:
```bash
php -m
```
Debe aparecer `pdo_pgsql`. En cPanel revisa también la versión/extensiones de PHP seleccionadas para el dominio y la terminal.

Prueba la conexión con `php backend/bin/check_db.php` antes de crear usuarios. Debe ejecutarse desde el mismo entorno donde correrá PHP y tener acceso de red a Neon. Consulta `docs/NEON.md` si falla.

## 3. Crear las primeras cuentas
Una vez que `.env` está configurado y `database/schema.sql` se ejecutó correctamente, desde la raíz del proyecto ejecuta:
```bash
php backend/bin/create_user.php secretaria secretaria@TU-DOMINIO.gob.gt "Secretaria Institucional"
php backend/bin/create_user.php consulta consulta@TU-DOMINIO.gob.gt "Usuario de Consulta"
```
El script pide una contraseña de al menos 12 caracteres. En Windows la entrada puede verse en la terminal; ejecuta el comando en un equipo privado. Usa correos reales y contraseñas diferentes. No se incluye una cuenta con contraseña fija.

## 4. Compilar Angular
Desde `frontend/`:
```bash
npm install
```
Edita `src/environments/environment.ts` para que `apiUrl` apunte a la raíz de la API, sin barra final:
- Si API está publicada en un subdominio cuyo document root es `backend/public`, normalmente `https://api.TU-DOMINIO` (sin `/api`).
- Si configuraste expresamente un rewrite en el dominio principal que publica la API bajo `/api`, usa `https://TU-DOMINIO/api`.

Después:
```bash
npm run build
```
Copia el contenido de `frontend/dist/reservas-institucional/browser/` al document root del frontend.

## 5. Despliegue sugerido en cPanel
Separa el frontend y el backend:
- Frontend: document root del dominio `equipo...`, contiene solo archivos compilados de Angular.
- Backend: preferiblemente subdominio API con document root apuntando a `backend/public`.
- Mantén `.env`, `src/`, `vendor/`, `bin/`, `cron/` y `storage/` fuera del document root público.
- Configura `FRONTEND_ORIGIN` con el origen exacto del frontend y `environment.ts` con la URL pública real de la API.
- Usa HTTPS en ambos sitios.

Si tu plan de cPanel no permite un document root separado para el subdominio, consulta `docs/CPANEL.md` antes de mover archivos. No copies todo el backend a `public_html`.

## 6. Cron Job
Ejemplo a las 02:15 cada noche (sustituye las rutas por las reales):
```cron
15 2 * * * /usr/local/bin/php /home/USUARIO/ruta/backend/cron/maintenance.php >> /home/USUARIO/logs/reservas-cron.log 2>&1
```
El cron elimina tokens revocados expirados y registra el resultado. Comprueba que la carpeta de logs exista y que PHP pueda escribir en ella.

## Seguridad
- Nunca subas `.env` a Git ni publiques secretos.
- `backend/storage/` debe estar fuera del webroot o protegido por Apache.
- `APP_DEBUG=false` en producción; no habilites `display_errors`.
- CORS no sustituye JWT ni los permisos del backend.
- El token Angular se mantiene en memoria y se pierde al recargar; es una decisión deliberada de reducción de persistencia, no una sesión persistente.
- Revisa `docs/PRUEBAS.md` antes de habilitar el sistema a usuarios reales.

## Estado de verificación
El código y el esquema se han adaptado para PostgreSQL/Neon. El backend no requiere Composer ni paquetes externos: el JWT HS256 está implementado con las funciones criptográficas nativas de PHP. Este entorno no dispone de tus credenciales ni de acceso a tu proyecto Neon o cPanel; por tanto, la conexión real, la creación de cuentas y las pruebas de integración deben ejecutarse con tu configuración. No publiques las credenciales para que podamos hacerlas.
