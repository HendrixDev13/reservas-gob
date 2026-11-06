# Configurar Neon PostgreSQL

## Obtener las credenciales
1. Abre tu proyecto en Neon.
2. Pulsa **Connect**.
3. Selecciona la rama, base de datos y rol que usará la aplicación.
4. Copia host, database, user y password. Usa el hostname y los campos separados en `.env`; no pegues una URL completa en `DB_HOST`.
5. Configura `DB_SSLMODE=require`. Mantén la contraseña únicamente en `backend/.env`.

Ejemplo de estructura (valores ficticios):
```dotenv
DB_HOST=ep-example-pooler.region.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=REEMPLAZAR_CON_LA_CLAVE_REAL
DB_SSLMODE=require
```

## Crear las tablas
En Neon, abre SQL Editor y ejecuta el contenido completo de `database/schema.sql`. Después, opcionalmente ejecuta `database/seed.sql`.

El esquema es PostgreSQL, no MySQL. No lo importes en phpMyAdmin.

## Probar la conexión desde el mismo entorno donde corre PHP
Desde la raíz del proyecto:
```bash
php backend/bin/check_db.php
```
Resultado esperado:
```text
[OK] Conexión PostgreSQL establecida.
Base de datos: neondb
Usuario: ...
Motor: PostgreSQL
```

Si falla:
- Confirma que `DB_HOST` sea solo el hostname de Neon.
- Confirma el nombre exacto de base de datos y rol.
- Copia la contraseña sin espacios al inicio/final.
- Verifica que `DB_SSLMODE=require`.
- Comprueba `php -m` y que esté habilitada `pdo_pgsql`.
- Revisa `backend/storage/logs/AAAA-MM-DD.log`; nunca publiques `.env` ni compartas la contraseña.

Una conexión probada desde Windows no garantiza que el hosting tenga las mismas extensiones PHP. Ejecuta también la prueba en la terminal de cPanel después del despliegue.
