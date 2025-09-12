# Transaction payloads (frontend → backend)

Este documento define los payloads soportados por el backend y las reglas de validación principales, alineadas al comportamiento actual.

Resumen rápido:
- transaction_type_id: number
- amount: Ingreso (+), Egreso (-), Transfer (+)
- items[].amount: siempre positivo; sum(items[].amount) = abs(amount)
- payments[].amount: en moneda de la cuenta; payments[].rate = User→Account; conversión: userAmount = accountAmount / rate
- amount_tax: 0 (impuestos por línea o por pago, si se envían)
- items[].tax_id aplica a “item|both”; payments[].tax_id aplica a “payment|both”

## Campos comunes

- name: string
- amount: number (Ingreso + | Egreso - | Transfer +)
- amount_tax: 0
- date: 'YYYY-MM-DD HH:mm:ss'
- provider_id: number|null
- transaction_type_id: number|null
- url_file: string|null
- account_id: number|null
  - Úsalo solo cuando NO envías payments[]. Para cruces de moneda, usa payments[].
- items?: Array<{ name: string; amount: number; category_id?: number|null; tax_id?: number|null }>
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

---

## Casos

### Ingreso/Egreso — simple (misma moneda, sin payments)
- amount en moneda del usuario
- items suman abs(amount)
- account_id presente; sin payments[]

```json
{
  "name": "Venta",
  "amount": 1500,
  "amount_tax": 0,
  "date": "2025-02-01 10:30:00",
  "provider_id": 12,
  "transaction_type_id": 1,
  "url_file": null,
  "account_id": 3,
  "items": [
    { "name": "Venta", "amount": 1500, "category_id": 8 }
  ]
}
```

### Ingreso/Egreso — simple (cruce de moneda)
- Usa payments[] para indicar rate por pago
- Omite account_id tope

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
    { "name": "Pago", "amount": 20, "category_id": null }
  ],
  "payments": [
    { "account_id": 7, "amount": -730, "rate": 36.5, "tax_id": null }
  ]
}
```

Notas:
- payments[0].amount = -730 en moneda de la cuenta (VES); rate=36.5 (User USD→Account VES)
- Conversión: -730 / 36.5 = -20 (match con amount)

### Ingreso/Egreso — detalle (factura)
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
    { "name": "Producto A", "amount": 58.0, "category_id": 2, "tax_id": 10 },
    { "name": "Producto B", "amount": 58.0, "category_id": 5 }
  ]
}
```

### Ingreso/Egreso — pago avanzado (múltiples cuentas)
- Usa payments[]; suma convertida a moneda del usuario debe igualar amount
- payments[].tax_id opcional (IGTF/Pago Móvil; applies_to=payment|both)

```json
{
  "name": "Cobro mixto",
  "amount": 100,
  "amount_tax": 0,
  "date": "2025-02-03 14:15:00",
  "provider_id": null,
  "transaction_type_id": 1,
  "url_file": null,
  "items": [ { "name": "Cobro", "amount": 100, "category_id": 8 } ],
  "payments": [
    { "account_id": 3, "amount": 50, "rate": 1, "tax_id": null },
    { "account_id": 7, "amount": 3650, "rate": 36.5, "tax_id": 11 }
  ]
}
```

### Transferencia — usando payments[]
- Un pago negativo (origen) y uno positivo (destino)
- Ambos con rate = User→Account correspondiente
- amount (tope) positivo y debe coincidir con la suma neta en moneda del usuario

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

- items[].category_id: number|null
- Si es null, el backend acepta el valor; clasificación automática puede ejecutarse offline con `php artisan items:autocategorize`.

## Reglas de validación (resumen)

- Items vs amount: abs(amount) = suma(items[].amount) ± 0.01 (si ambos vienen)
- Payments vs amount:
  - userAmount = amount_account / rate
  - Transfer (signos mixtos): match neto
  - Ingreso/Egreso avanzado (signo único): match por valor absoluto
  - rate != 0; si misma moneda, rate puede ser null o 1

---

## Ajuste de saldo (endpoint dedicado)

- Endpoint: POST /api/accounts/{id}/adjust-balance
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
- include_in_balance controla si el ajuste se refleja en el cálculo del balance en tiempo real.
