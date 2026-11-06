# Despliegue en cPanel con Neon PostgreSQL

## Arquitectura recomendada
1. Frontend Angular: document root del dominio institucional, con solo el contenido compilado de `frontend/dist/reservas-institucional/browser/`.
2. API PHP: subdominio separado (por ejemplo `api.TU-DOMINIO`) con document root en `backend/public`.
3. Código privado del backend (`.env`, `src`, `vendor`, `storage`, `bin`, `cron`) fuera del directorio público siempre que el hosting lo permita.
4. Neon PostgreSQL se conecta por TLS usando `DB_SSLMODE=require`; no se abre una conexión a `localhost`.

## Configuración backend
- PHP 8.2+.
- Extensiones PHP: `pdo_pgsql`, `openssl`, `mbstring`, `json`.
- No requiere Composer ni paquetes PHP externos; la autenticación JWT HS256 usa funciones nativas de PHP.
- `backend/.env` debe tener los valores de Neon y `FRONTEND_ORIGIN` exacto, sin barra final.
- Comprueba permisos de escritura para `backend/storage/logs`.

## Rutas
Si el document root del subdominio API es `backend/public`, la API queda en la raíz del subdominio:
- Salud: `https://api.TU-DOMINIO/health`
- Login: `https://api.TU-DOMINIO/auth/login`
En ese caso `frontend/src/environments/environment.ts` debe usar `apiUrl: 'https://api.TU-DOMINIO'` (sin `/api`).

Si la API se publica bajo una ruta `/api` en el dominio principal, configura el rewrite del hosting para enviar `/api/*` a `backend/public/index.php`. Entonces `apiUrl` debe terminar en `/api`. No uses esta modalidad sin comprobar la regla en cPanel.

## Angular SPA fallback
En el document root del frontend, Apache puede usar:
```apache
Options -Indexes
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [L]
</IfModule>
```
No pongas ese fallback en el document root de la API.

## Seguridad y logs
- No coloques `.env`, logs ni `create_user.php` en una carpeta pública.
- No uses permisos `777` como solución general.
- Nunca habilites `display_errors` en producción.
- La conexión Neon debe usar el host, database, username y password exactos de la pantalla Connect de Neon.
