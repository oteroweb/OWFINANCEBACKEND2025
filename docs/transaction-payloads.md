# Transaction payloads (frontend → backend)

Este documento define los payloads soportados por el backend y las reglas de validación principales, alineadas al comportamiento actual.

Resumen rápido:

El sistema soporta 3 enfoques simultáneos:
1. On-demand: `calculateBalance(account_id)` = `initial + ∑(amount)` de transacciones activas con include_in_balance=1.
2. Caché persistente: columna `accounts.balance_cached` actualizada por observer de transacciones (creación, actualización, borrado/restauración) aplicando deltas.

Endpoints relevantes:
- POST `/api/v1/accounts/{id}/adjust-balance`: Ajusta al saldo objetivo. Si `include_in_balance=true`, crea transacción de ajuste. Si `false`, modifica `initial`.

Campo en transacciones:

Notas de integridad:
- Si cambias manualmente transacciones históricas por script, ejecuta luego `/recalculate-account` para sincronizar caché.
- Los montos deben venir ya con su signo correcto desde el frontend (ej: egreso negativo, ingreso positivo, transfer destino positivo / origen negativo en payments).

- name: string
- amount: number (Ingreso + | Egreso - | Transfer +)
  - Signo gestionado por frontend según tipo; backend no infiere.
- date: 'YYYY-MM-DD HH:mm:ss'
- provider_id: number|null (presente siempre; puede ser null)
- transaction_type_id: number|null
- url_file: string|null
  - Úsalo solo cuando NO envías payments[]. Para cruces de moneda, usa payments[].
- items?: Array<{ name: string; amount: number; item_category_id?: number|null; tax_id?: number|null }>
- payments?: Array<{ account_id: number; amount: number; rate: number|null; tax_id?: number|null }>
  - amount: en moneda de la cuenta del pago
  - rate: User→Account (si monedas difieren; si igual, null o 1)
  - tax_id (opcional): impuesto por pago (IGTF/Comisión Pago Móvil)

## Validaciones clave (backend)

- Items vs amount: si vienen items y amount, se compara abs(amount) con sum(items)
- Payments vs amount:
  - userAmount = amount_account / rate
  - Transfer (signos mixtos en pagos): se exige match neto (|∑userAmount − amount| ≤ 0.01)
  - Ingreso/Egreso avanzado (sin signos mixtos): se permite match por valor absoluto (| |∑userAmount| − |amount| | ≤ 0.01)
  - rate != 0; si misma moneda, rate puede ser null o 1


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
    { "account_id": 7, "amount": -730, "rate": 36.5, "tax_id": null }
  ]
}
```

Notas:
- payments[0].amount = -730 en moneda de la cuenta (VES); rate=36.5 (User USD→Account VES)
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
    { "account_id": 3, "amount": 50, "rate": 1, "tax_id": null },
    { "account_id": 7, "amount": 3650, "rate": 36.5, "tax_id": 11 }
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
    { "account_id": 3, "amount": -200, "rate": 1, "tax_id": null },
    { "account_id": 9, "amount": 7300, "rate": 36.5, "tax_id": null }
  ]
}
```

Notas:
- -200/1 + 7300/36.5 = -200 + 200 = 0; el amount top-level representa el monto transferido (200) y la validación usa match neto para signos mixtos.

## Impuestos

- items[].tax_id (opcional): permitido si tax.applies_to ∈ {item, both}
- payments[].tax_id (opcional): permitido si tax.applies_to ∈ {payment, both}
- amount_tax top-level: 0 (los impuestos se reflejan por línea o por pago)

## Categorías

Hay dos conceptos distintos y complementarios:

- items[].item_category_id (ItemCategory, catálogo de ítems)
  - Clasificación de productos/servicios del catálogo (árbol de Item Categories).
  - Se usa para reportes de ítems y para precargar/recordar la categoría de un mismo ítem.
  - En modo simple: si hay un único ítem, toma este valor desde el selector principal del UI.

- items[].category_id (Category, categoría de la transacción)
  - Clasificación operativa para reglas de frascos (Jars), presupuestos y análisis por categorías históricas.
  - Es independiente de item_category_id; pueden coincidir o no según el flujo.
  - Puede omitirse en modo simple; el backend puede dejarlo null o mapearlo según reglas configuradas.

Recomendación de uso
- Modo simple: enviar solo item_category_id en el ítem (desde el selector principal). category_id es opcional.
- Modo detallado: puedes enviar ambos si necesitas diferenciar catálogo (item_category_id) de clasificación operativa (category_id).
## Reglas de validación (resumen)

- Items vs amount: abs(amount) = suma(abs(items[].amount)) ± 0.01 (si ambos vienen). Se permite que los items vengan con signo para egresos.
- Payments vs amount:
  - userAmount = amount_account / rate
  - Transfer (signos mixtos): match neto
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
