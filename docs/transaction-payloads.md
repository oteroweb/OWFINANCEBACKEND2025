# Transaction payloads (frontend → backend)

Este documento define los payloads soportados por el backend y las reglas de validación principales, alineadas al comportamiento actual.

Resumen rápido:

El sistema soporta 2 enfoques complementarios para saldos, ahora basados en pagos:
1. Cálculo on-demand: `balance_calculado = initial + ∑(payment_transactions.amount)` de pagos activos cuya transacción padre está activa e `include_in_balance=1`.
2. Caché persistente: columna `accounts.balance_cached`, recalculada tras operaciones relevantes de transacciones/pagos (creación, actualización, borrado/restauración) con la misma semántica de pagos.

Endpoints relevantes:
- POST `/api/v1/accounts/{id}/adjust-balance`: Ajusta al saldo objetivo. Si `include_in_balance=true`, crea transacción de ajuste. Si `false`, modifica `initial`.

Campos en transacciones (create/update):

Notas de integridad:
- Si cambias manualmente transacciones históricas por script, ejecuta luego `/recalculate-account` para sincronizar caché.
- Los montos deben venir ya con su signo correcto desde el frontend (ej: egreso negativo, ingreso positivo, transfer destino positivo / origen negativo en payments).

- name: string
- amount: number|null (Ingreso + | Egreso - | Transfer +)
  - Puede omitirse si envías items[]; el backend lo deriva como suma de ítems (con tolerancia 0.01).
  - El signo lo gestiona el frontend; el backend no infiere el tipo.
- date: 'YYYY-MM-DD HH:mm:ss'
- provider_id: number|null (puede ser null; no es requerido en create)
- transaction_type_id: number|null
- category_id: number|null (categoría de la transacción a nivel top)
- account_id: number|null (cuenta “principal” opcional, útil para compatibilidad o flujos simples)
- include_in_balance: boolean (por defecto true si no se envía)
- active: boolean (por defecto true)
- url_file: string|null
  - Úsalo solo cuando NO envías payments[]. Para cruces de moneda, usa payments[].
- items?: Array<{ name: string; amount: number; item_category_id?: number|null; category_id?: number|null; tax_id?: number|null; quantity?: number }>
- payments: Array<{ account_id: number; amount: number; rate?: number|null; rate_is_current?: boolean; rate_is_official?: boolean }>
  - payments[] es requerido (min: 1). Todas las operaciones se basan en pagos.
  - amount: monto en moneda de la cuenta del pago.
  - rate: tasa User→Account (si monedas difieren; si igual, puede ser null o 1).
  - rate_is_current: si envías rate y este flag=true, se marca como “tasa actual” del usuario para esa moneda. Se garantiza un solo `is_current=true` por (user, currency); el backend desmarca la anterior.
  - rate_is_official: si envías rate y este flag=true, se marca como “tasa oficial histórica” del usuario para esa moneda. NO se desmarcan oficiales previas; cada tasa oficial queda almacenada con timestamp `official_at` para construir historial/gráficas.
  - Alias aceptados: `is_current` o `current_rate` equivalen a `rate_is_current`; `is_official` equivale a `rate_is_official`.
  - Si envías múltiples pagos con `rate_is_current=true` en el mismo request, el último del arreglo prevalece como actual.

## Validaciones clave (backend)

- Items vs amount: si vienen items y amount, se compara abs(amount) con sum(items)
- Payments vs amount:
  - userAmount = amount_account / rate
  - Transfer (signos mixtos en pagos): exactamente 2 payments; las piernas deben ser iguales y opuestas en moneda del usuario y la pierna positiva debe coincidir con `amount` (tolerancia ≤ 0.01)
  - Ingreso/Egreso avanzado (sin signos mixtos): se permite match por valor absoluto (| |∑userAmount| − |amount| | ≤ 0.01)
  - rate != 0; si misma moneda, rate puede ser null o 1

Cardinalidad y modos de operación
- Simple: si envías exactamente 1 payment (y no es transferencia), debes enviar exactamente 1 item.
- Transferencia: exactamente 2 payments con signos opuestos; el amount top-level debe coincidir con la pierna positiva en moneda del usuario (tras dividir por la tasa).
- Múltiples pagos (ingreso/egreso avanzado): se permite N payments; la validación usa suma por valor absoluto en moneda del usuario.


## Casos

### Ingreso/Egreso — simple (misma moneda)
- 1 payment y 1 ítem genérico; provider_id presente (null o id)

```json
{
  "name": "Venta",
  "amount": 1500,
  "amount_tax": 0,
  "date": "2025-02-01 10:30:00",
  "provider_id": 12,
  "transaction_type_id": 1,
  "account_id": 3,
  "items": [
  { "name": "Venta", "amount": 1500, "item_category_id": 8 }
  ]
}
```

### Ingreso/Egreso — simple (cruce de moneda)
- 1 payment con rate por pago; provider_id presente (null o id)

```json
{
  "name": "Pago",
  "amount": -20,
  "amount_tax": 0,
  "date": "2025-02-01 11:00:00",
  "provider_id": null,
  "transaction_type_id": 2,
  "url_file": "https://...",
  "items": [
  { "name": "Pago", "amount": 20, "item_category_id": null }
  ],
  "payments": [
    { "account_id": 7, "amount": -730, "rate": 36.5, "rate_is_current": true }
  ]
}
```

Notas:
- payments[0].amount = -730 en moneda de la cuenta (VES); rate=36.5 (User USD→Account VES)
- rate_is_current=true: la tasa 36.5 se registra/actualiza en la tabla USER_CURRENCIES del usuario y se marca como “actual” para VES desmarcando cualquier otra actual previa.
- Conversión: -730 / 36.5 = -20 (match con amount)

- amount = suma de items (cada línea ya incluye IVA si aplica)
- tax_id por línea opcional (applies_to=item|both)

```json
{
  "name": "Factura 001",
  "amount": 116.0,
  "amount_tax": 0,
  "date": "2025-02-02 09:00:00",
  "provider_id": 4,
  "transaction_type_id": 1,
  "url_file": null,
  "account_id": 3,
  "items": [
  { "name": "Producto A", "amount": 58.0, "item_category_id": 2, "tax_id": 10 },
  { "name": "Producto B", "amount": 58.0, "item_category_id": 5 }
  ]
}
```

### Ingreso/Egreso — pago avanzado (múltiples cuentas)
- N payments; provider_id presente (null o id)

```json
{
  "name": "Cobro mixto",
  "amount": 100,
  "amount_tax": 0,
  "date": "2025-02-03 14:15:00",
  "provider_id": null,
  "transaction_type_id": 1,
  "url_file": null,
  "items": [ { "name": "Cobro", "amount": 100, "item_category_id": 8 } ],
  "payments": [
    { "account_id": 3, "amount": 50, "rate": 1 },
    { "account_id": 7, "amount": 3650, "rate": 36.5 }
  ]
}
```

### Transferencia — usando payments[]
- 2 payments (negativo/positivo), rate por pago; provider_id presente (null o id)

```json
{
  "name": "Traspaso",
  "amount": 200,
  "amount_tax": 0,
  "date": "2025-02-04 12:00:00",
  "provider_id": null,
  "transaction_type_id": 3,
  "url_file": null,
  "items": [],
  "payments": [
    { "account_id": 3, "amount": -200, "rate": 1 },
    { "account_id": 9, "amount": 7300, "rate": 36.5 }
  ]
}
```

Notas:
- -200/1 + 7300/36.5 = -200 + 200 = 0; el amount top-level representa el monto transferido (200). La validación exige 2 pagos con signos opuestos, mismas magnitudes (en moneda del usuario) y que la pierna positiva sea igual a `amount`.

## Impuestos

- items[].tax_id (opcional): permitido si tax.applies_to ∈ {item, both}
- amount_tax top-level: 0 (actualmente los impuestos se modelan por línea de ítem; impuestos por pago no están implementados)

## Categorías

Hay dos conceptos distintos y complementarios:

- items[].item_category_id (ItemCategory, catálogo de ítems)
  - Clasificación de productos/servicios del catálogo (árbol de Item Categories).
  - Se usa para reportes de ítems y para precargar/recordar la categoría de un mismo ítem.
  - En modo simple: si hay un único ítem, toma este valor desde el selector principal del UI.

- category_id (Category a nivel transacción)
  - Clasificación operativa para reglas de frascos (Jars), presupuestos y análisis por categorías históricas aplicada a la transacción completa.
  - También puedes enviar items[].category_id cuando necesites granularidad por línea; ambos conceptos pueden coexistir.
  - Si no la envías, puede quedar null o inferirse según reglas del sistema.

Recomendación de uso
- Modo simple: enviar solo item_category_id en el ítem (desde el selector principal). category_id es opcional.
- Modo detallado: puedes enviar ambos si necesitas diferenciar catálogo (item_category_id) de clasificación operativa (category_id).
## Reglas de validación (resumen)

- Items vs amount: abs(amount) = suma(abs(items[].amount)) ± 0.01 (si ambos vienen). Se permite que los items vengan con signo para egresos.
- Payments vs amount:
  - userAmount = amount_account / rate
  - Transfer (signos mixtos): exactamente 2 pagos opuestos; magnitudes iguales en moneda de usuario y la pierna positiva debe igualar `amount`
  - Ingreso/Egreso avanzado (signo único): match por valor absoluto
  - rate != 0; si misma moneda, rate puede ser null o 1

---

## Ajuste de saldo (endpoint dedicado)

- Endpoint: POST /api/v1/accounts/{id}/adjust-balance
- Auth: Sanctum
- Crea una transacción tipo "ajuste" por la diferencia entre el saldo objetivo y el saldo actual.

Request

```json
{
  "target_balance": 1000.00,
  "include_in_balance": true,
  "description": "Ajuste manual"
}
```

Respuesta (200)

```json
{
  "status": "OK",
  "code": 200,
  "message": "Balance adjusted",
  "data": {
    "account": { "id": 3, "balance_calculado": 1000.0, "...": "..." },
    "adjustment_transaction": { "id": 123, "amount": 250.0, "include_in_balance": true, "transaction_type_id": null },
    "previous_balance": 750.0,
    "new_balance": 1000.0
  }
}
```

Notas
- Si la diferencia es menor a 0.01, responde 200 con "No adjustment needed" y sin crear transacción.
- include_in_balance=true crea una transacción; include_in_balance=false ajusta `initial`.

---

<!-- Sección de filtros GET omitida para mantener el foco en payloads de creación/actualización. -->

---

Tasas de cambio por usuario (USER_CURRENCIES)
- Si envías payments[].rate, el backend garantiza que exista un registro en USER_CURRENCIES para (user_id, currency_id de la cuenta, current_rate=rate). Política actual:
  - `rate_is_current=true`: se marca esa tasa como la única actual (is_current=true) para la moneda (se desmarcan previas actuales).
  - `rate_is_official=true`: se marca esa tasa como oficial histórica (is_official=true) sin desmarcar las anteriores oficiales. Si es la primera vez que se marca como oficial, se asigna `official_at=NOW()`.
  - Se puede enviar ambos flags simultáneamente: la tasa quedará como actual y oficial.
- Si NO envías payments[].rate, el backend resuelve la tasa de forma automática: primero busca una tasa “actual oficial” (is_current=true AND is_official=true); si no hay, toma cualquier “actual”; si tampoco hay, usa 1.0 (mismas monedas).
- rate debe ser distinto de 0. Para misma moneda, puedes enviar null o 1.

Campos relevantes en USER_CURRENCIES:
- user_id, currency_id, current_rate
- is_current (boolean) – máximo uno por (user,currency)
- is_official (boolean) – puede haber múltiples históricos
- official_at (timestamp|null) – fecha de primera marcación oficial (se deja intacta en remarcas posteriores)

Historial oficial y gráficas futuras:
- Puedes construir un historial tomando todos los registros con is_official=true ordenados por official_at ASC/DESC.
- Endpoint sugerido futuro: `GET /api/v1/user_currencies/history?currency_id=XXX&official=1` (no implementado aún) para alimentar gráficas de evolución.

Respuestas y metadatos
- En las respuestas de transacciones, los pagos incluyen la relación anidada de la cuenta: paymentTransactions.account.
- En create/update/delete se incluye meta con balances recalculados:
  - meta.account_balance_after: saldo de la cuenta “principal” si aplica (compatibilidad).
  - meta.account_balances_after: objeto con saldos de todas las cuentas afectadas por los payments.

### 5. Rango manual sin period_type
`/api/v1/transactions?date_from=2025-08-01 00:00:00&date_to=2025-08-31 23:59:59&account_ids=27,23,25`

### 6. Orden por amount ascendente con búsqueda
`/api/v1/transactions?search=provider x&date_from=2025-07-01 00:00:00&date_to=2025-07-31 23:59:59&sort_by=amount&descending=false&account_ids=27,23`

### 7. Filtrar por slug de tipo
`/api/v1/transactions?transaction_type=venta&period_type=month&month=9&year=2025&account_ids=27,23,25`

### 8. Año + intersección account_id y account_ids (si ambos aplican)
`/api/v1/transactions?period_type=year&year=2025&account_id=27&account_ids=27,23,25`

### 9. Quarter + orden descendente por fecha
`/api/v1/transactions?period_type=quarter&quarter=3&year=2025&sort_by=date&descending=true&account_ids=27,23,25,17,22`

### 10. Semana ISO (week)
`/api/v1/transactions?period_type=week&week=37&year=2025&account_ids=27,23`

### 11. Quincena 1 de Septiembre 2025
`/api/v1/transactions?period_type=fortnight&fortnight=1&month=9&year=2025&account_ids=27,23,25`

### 12. Quincena 2 (segunda mitad) con búsqueda
`/api/v1/transactions?period_type=fortnight&fortnight=2&month=9&year=2025&search=venta&account_ids=27,23`

Notas extra periodos:
- week usa semana ISO (startOfWeek lunes). Si no pasas week toma la semana actual.
- fortnight (quincena): requiere month y `fortnight=1` (1-15) o `fortnight=2` (16-fin de mes). Si no pasas fortnight se asume según el día actual.
- month/quarter/semester/year siguen la precedencia habitual: al usar `period_type` se ignoran `date_from` y `date_to`.

### 10. Semestre + búsqueda parcial
`/api/v1/transactions?period_type=semester&semester=2&year=2025&search=cliente&account_ids=27,23`

Notas:
- `descending=true` o `false` (también acepta 1/0).
- Formato de fechas: `YYYY-MM-DD HH:mm:ss`.
- Si envías `transaction_type_id` y `transaction_type`, prevalece el ID.

## Ejemplos con flags de tasa en payments

### Marcar una tasa como actual al crear un pago (ingreso/egreso)

```json
{
  "name": "Pago VES",
  "amount": -20,
  "date": "2025-02-01 11:00:00",
  "transaction_type_id": 2,
  "items": [ { "name": "Pago", "amount": 20 } ],
  "payments": [
    { "account_id": 7, "amount": -730, "rate": 36.5, "rate_is_current": true }
  ]
}
```

Efecto: crea/actualiza un registro en `user_currencies` para la moneda de la cuenta 7, con `current_rate=36.5` y `is_current=true`, desmarcando el anterior `is_current` de esa moneda para el usuario.

### Marcar una tasa como actual y oficial en el mismo pago

```json
{
  "name": "Compra USD→VES",
  "amount": -50,
  "date": "2025-03-10 09:30:00",
  "transaction_type_id": 2,
  "items": [ { "name": "Compra", "amount": 50 } ],
  "payments": [
    { "account_id": 7, "amount": -1825, "rate": 36.5, "rate_is_current": true, "rate_is_official": true }
  ]
}
```

Efecto: registra o reutiliza 36.5 como tasa en `user_currencies`, marcándola como actual (desmarca previa actual) y oficial histórica (conserva previas oficiales). Si es la primera vez que se marca como oficial, setea `official_at`.

### Marcar múltiples tasas oficiales en distintos pagos (histórico)

```json
{
  "name": "Compra serie",
  "amount": -100,
  "date": "2025-03-11 10:00:00",
  "transaction_type_id": 2,
  "items": [ { "name": "Compra", "amount": 100 } ],
  "payments": [
    { "account_id": 7, "amount": -3650, "rate": 36.5, "rate_is_official": true },
    { "account_id": 7, "amount": -3700, "rate": 37.0, "rate_is_official": true }
  ]
}
```

Efecto: ambas tasas (36.5 y 37.0) quedan registradas como oficiales históricas con su `official_at`. Ninguna se desmarca.
