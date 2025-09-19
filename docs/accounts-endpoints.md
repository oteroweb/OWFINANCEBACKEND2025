# Accounts API

Resumen de endpoints clave para cuentas, balance y ajustes.

Base URL: `/api/v1/accounts`
Auth: Sanctum (token o sesión)

## Campos de Account
- id
- name
- currency_id
- account_type_id
- initial (decimal)
- active (0/1)
- balance_cached (decimal, cache incremental del balance)
- balance_calculado (campo calculado añadido en listados cuando se solicita; si existe balance_cached se usa ese valor)

`balance_calculado` = `initial` + suma de transacciones activas (`active=1`) con `include_in_balance=1`.

## Estrategia de Balance
1. On-demand: cálculo al vuelo como `initial + ∑(amount)` de transacciones activas con `include_in_balance=1` (coste de query).
2. Caché incremental: `balance_cached` se mantiene con un Observer de `Transaction` que aplica deltas.
3. Recalculo forzado: endpoint para sincronizar si hubo scripts masivos o inconsistencias.

## Endpoints

### Listar cuentas
GET `/api/v1/accounts`
Parámetros opcionales: `page, per_page, sort_by, descending, search, currency_id, account_type_id, user_id, user, is_owner`.

Respuesta: array de cuentas con `balance_calculado`.

### Listar activas
GET `/api/v1/accounts/active`

### Listar incluyendo soft-deleted
GET `/api/v1/accounts/all`

### Obtener una cuenta
GET `/api/v1/accounts/{id}`

### Crear cuenta
POST `/api/v1/accounts`
Body:
```json
{
  "name": "Caja USD",
  "currency_id": 1,
  "initial": 1000.00,
  "account_type_id": 2
}
```

### Actualizar cuenta
PUT `/api/v1/accounts/{id}`
Body parcial con campos a modificar.

### Cambiar estado (activar/desactivar)
PATCH `/api/v1/accounts/{id}/status`

### Eliminar (soft delete)
DELETE `/api/v1/accounts/{id}`

### Ajustar balance
POST `/api/v1/accounts/{id}/adjust-balance`

Uso: llegar a un saldo objetivo. Soporta 2 modos controlados por `include_in_balance`.

Body (usa target_balance o balance como alias):
```json
{
  "target_balance": 2500.00,
  "include_in_balance": true,
  "description": "Ajuste inventario cierre"
}
```
Alias válido:
```json
{
  "balance": 2500.00,
  "include_in_balance": false
}
```

Lógica:
- Calcula balance actual (on-demand = `initial + ∑included`).
- Determina objetivo: precedence `amount` (si se envía) > `target_balance` > `balance`.
- Diferencia = objetivo - actual (o `amount` explícito si se provee).
- Si |diferencia| < 0.01 => responde 200 "No adjustment needed" sin cambios.
- Si `include_in_balance = true`: crea transacción de ajuste por la diferencia y recalcula.
- Si `include_in_balance = false`: NO crea transacción; ajusta `initial` para que el cálculo llegue al objetivo y recalcula.

Respuesta 200 (modo include_in_balance=true):
```json
{
  "status": "OK",
  "code": 200,
  "message": "Balance adjusted",
  "data": {
    "account": { "id": 5, "balance_calculado": 2500.0 },
    "adjustment_transaction": { "id": 321, "amount": 300.0, "include_in_balance": true },
    "previous_balance": 2200.0,
    "new_balance": 2500.0
  }
}
```

Respuesta 200 (modo include_in_balance=false):
```json
{
  "status": "OK",
  "code": 200,
  "message": "Balance adjusted",
  "data": {
    "account": { "id": 5, "balance_calculado": 2500.0 },
    "adjustment_transaction": null,
    "previous_balance": 2200.0,
    "new_balance": 2500.0
  }
}
```

### Recalcular balance (forzar sincronización)
POST `/api/v1/accounts/{id}/recalculate-account`

Uso: si se hicieron scripts bulk que omitieron observers, o para validar integridad.

Respuesta 200:
```json
{
  "status": "OK",
  "code": 200,
  "message": "Balance recalculated",
  "data": {
    "id": 5,
    "balance_calculado": 2500.0,
    "balance_cached": 2500.0
  }
}
```

### Ejemplos de Flujo

1. Crear cuenta (initial=1000) => balance inicial calculado = 1000 (el cálculo usa `initial`).
2. Registrar transacción ingreso +500 (include_in_balance=1) => observer suma +500 => balance_cached=1500.
3. Ajustar balance a 1800:
  - si include_in_balance=true => crea transacción ajuste +300 => balance_cached=1800.
  - si include_in_balance=false => ajusta `initial` para reflejar 1800 => balance_cached=1800.
4. Recalcular manualmente => endpoint `recalculate-account` devuelve 1800 confirmando consistencia.

### Notas sobre include_in_balance
- Transferencias internas pueden marcarse con `include_in_balance=false` si quieres excluirlas del saldo neto.
- Ajustes informativos (auditoría) también pueden excluirse.
- Cambiar include_in_balance en una transacción existente dispara delta (observer) al actualizar.

### Errores comunes
- 404: cuenta inexistente.
- 400: validación fallida en ajuste (falta target_balance, etc.).
- 500: error inesperado.

## Próximos pasos sugeridos
- Agregar endpoint de listado rápido de balances resumidos (si se necesita dashboard).
- Agregar histórico de snapshots diarios (cron) para reportes.

