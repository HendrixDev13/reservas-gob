# API REST

Prefijo: `/api`. Todas las respuestas usan `{"ok":true,"data":...}` o `{"ok":false,"error":{"code":"...","message":"..."}}`.

## Pública
- `GET /api/health`
- `POST /api/auth/login` JSON `{ "correo": "...", "password": "..." }`

## Requiere `Authorization: Bearer <JWT>`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `GET /api/dashboard`
- `GET /api/reservas?from=2026-10-01%2000:00:00&to=2026-11-01%2000:00:00&estado=pendiente`
- `GET /api/salones`
- `GET /api/equipos`

## Solo secretaria
- `POST /api/reservas` JSON: `salon_id`, `titulo`, `solicitante`, `fecha_inicio`, `fecha_fin`; opcionales `dependencia`, `descripcion`.
- `PATCH /api/reservas/{id}/confirmar`
- `PATCH /api/reservas/{id}/cancelar` JSON opcional `{ "motivo":"..." }`
- `PATCH /api/reservas/{id}/finalizar`
- `POST /api/salones` JSON: `nombre`, `capacidad`, opcionales `ubicacion`, `descripcion`.
- `POST /api/equipos` JSON: `nombre`, `cantidad_total`, opcional `descripcion`.
- `GET /api/auditoria`

Fechas: formato SQL `YYYY-MM-DD HH:mm:ss`, hora local `America/Guatemala`. En una ampliación de producción conviene normalizar a UTC y validar inventario de equipos por horario.
