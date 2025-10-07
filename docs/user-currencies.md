# User Currencies API (tasas por usuario)

Este documento describe el CRUD de tasas por usuario/moneda y su interacción con transacciones.

Base URL: /api/v1/user-currencies (auth: Sanctum)

## Modelo

Tabla: USER_CURRENCIES
- user_id: bigint FK users.id
- currency_id: bigint FK currencies.id (moneda de la cuenta a la que aplica la tasa)
- current_rate: decimal(18,6) — valor de la tasa User→Currency
- is_current: boolean — marca de tasa "actual" (pueden existir múltiples actuales)
- is_official: boolean — distingue tasa oficial vs. oferta puntual
- timestamps, soft deletes

## Endpoints

GET /
Parámetros opcionales:
- user_id: filtra por usuario
- currency_id: filtra por moneda
- is_current: true|false
- per_page: tamaño de página (paginado Laravel por defecto)

Respuesta 200:
{
  "status": "OK",
  "code": 200,
  "data": { "current_page": 1, "data": [ { "id": 1, "user_id": 5, "currency_id": 2, "current_rate": 36.5, "is_current": true, "is_official": true, "created_at": "...", "updated_at": "..." }], "...": "..." }
}

POST /
Body:
- user_id (required)
- currency_id (required)
- current_rate (required, > 0)
- is_current (optional, default false)
- is_official (optional, default true)

Comportamiento:
- Crea o reutiliza (firstOrCreate) por (user_id, currency_id, current_rate).
- Si se proveen is_current/is_official, se actualizan en el registro resultante.
- No se desmarcan otros registros current del mismo usuario/moneda.

PUT /{id}
Body (parcial):
- current_rate (optional, > 0)
- is_current (optional)
- is_official (optional)

Comportamiento:
- Actualiza solo campos enviados; múltiples registros pueden quedar is_current=true.

DELETE /{id}
- Soft delete del registro.

## Interacción con transacciones

- En TransactionController, si el payload payments[].rate está presente, se asegura su existencia en USER_CURRENCIES y opcionalmente se marca is_current según payments[].current_rate, sin democión de otros.
- Si no viene payments[].rate, se resuelve la tasa así:
  1) Preferir una tasa del usuario marcada como is_current=true e is_official=true para la moneda de la cuenta;
  2) si no existe, cualquier is_current=true;
  3) si no existe, fallback 1.0.
- Validaciones: rate != 0. Para misma moneda, rate puede ser null o 1.

## Notas
- Varias tasas pueden estar marcadas como actuales a la vez (p. ej., diferentes fuentes/ofertas). Los reportes o pantallas deben seleccionar según contexto.
- Considera añadir controles de dominio (límites máximos/mínimos de tasas) desde UI o reglas adicionales si aplica.
