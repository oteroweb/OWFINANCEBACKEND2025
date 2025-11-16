# User currencies and rates

This document explains how user-specific currency rates are handled across login, profile, configuration, and transactions.

## Data model

- `currencies`: master currency list.
- `user_currencies`: per-user, per-currency historical rates.
  - fields: `user_id`, `currency_id`, `current_rate`, `is_current`, `is_official`, timestamps.
  - one current and one official per (user, currency) at a time (enforced at app level).

## Backend responses

- Login (POST /api/v1/login): returns
  - `token`: Sanctum token
  - `user`: with relations and a `rates` array built from `user_currencies` where `is_current=true` (dedup by currency)
  - `default_currency_ids`: list of currency ids from user's accounts (fallback `[1]`)

- Profile (GET /api/v1/admin/user): returns
  - `user`: includes `rates` with the same logic as login

## Example login response

Example login response (excerpt):

```json
{
  "token": "...",
  "user": {
    "id": 5,
    "name": "Ana",
    "rates": [
      { "name": "ves", "value": 36.5 },
      { "name": "usd", "value": 1 }
    ]
  },
  "default_currency_ids": [1, 3]
}
```

## Building user.rates

`user.rates` is an array of objects like `{ name: 'ves', value: 36.5 }`, computed from `user_currencies` where `is_current=true`. If multiple records exist per currency, the most recent is chosen.

## Transaction flow and setting rates

When creating/updating a transaction, `payments` can include:

- `rate`: number (optional)
- `rate_is_current`: boolean (optional)
- `rate_is_official`: boolean (optional)

If any flag is `true`, the backend will:
- Clear previous flags for that `(user, currency)`
- Create a new `user_currencies` entry with the provided `rate` and flags.

The currency is inferred from the `account_id` in the payment.

## Frontend prompts

- On login or profile refresh, hydrate `userStore` from `user.rates` and `default_currency_ids`.
- In configuration UI, show historical rates per currency and actions:
  - Make current
  - Make official
  - Add new rate
- In transaction form:
  - When selecting an account, prefill `rate` from `user.rates` by the account's currency code (default `1` if not found)
  - Expose checkboxes to mark as current/official and send the flags in the payment object.

## Validation

- Transfers require exactly 2 opposite payments; their legs must be equal/opposite and the positive leg must equal the transaction amount (in user currency).
- Non-transfer transactions allow absolute sum match between payments and transaction amount.
- `rate` cannot be zero.

## Notes

- Use backend endpoints as the source of truth; treat frontend store as cache.
- Consider adding `effective_at` if you need back-dated rates and queries by date.
 - Si no viene payments[].rate, la tasa se resuelve así:
   1) Preferir una tasa del usuario marcada como is_current=true e is_official=true para la moneda de la cuenta;
   2) si no existe, cualquier is_current=true;
   3) si no existe, fallback 1.0 (misma moneda o no definida).
 - Validaciones: rate != 0. Para misma moneda, rate puede ser null o 1.

## Consideraciones adicionales
- Pueden existir múltiples tasas “actuales” en paralelo para distintas monedas; para una misma moneda, solo hay una “actual” y una “oficial” a la vez por usuario cuando se usan los flags desde transacción/configuración.
- Añade límites de dominio (máximo/mínimo) a nivel UI o reglas del backend si aplica.
- Si no viene payments[].rate, se resuelve la tasa así:
  1) Preferir una tasa del usuario marcada como is_current=true e is_official=true para la moneda de la cuenta;
  2) si no existe, cualquier is_current=true;
  3) si no existe, fallback 1.0.
- Validaciones: rate != 0. Para misma moneda, rate puede ser null o 1.

## Notas
- Varias tasas pueden estar marcadas como actuales a la vez (p. ej., diferentes fuentes/ofertas). Los reportes o pantallas deben seleccionar según contexto.
- Considera añadir controles de dominio (límites máximos/mínimos de tasas) desde UI o reglas adicionales si aplica.
