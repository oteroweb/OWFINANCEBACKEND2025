# Guía Rápida: Configurar Jarros en Diciembre 2024

## 🎯 Objetivo
Sincronizar tus saldos de jarros de tu sistema anterior y configurar diciembre con los montos disponibles.

---

## ✅ Paso 1: Ejecutar la Migración

```bash
cd /Users/joseluisoterolopez/Desktop/dev/OWFINANCEBackend2025

# Ejecutar la migración
php artisan migrate
```

Esto crea:
- Nuevas columnas en `jars` table (`adjustment`, `refresh_mode`)
- Nueva tabla `jar_adjustments` (historial de ajustes)

---

## 🏺 Paso 2: Crear o Actualizar tus Jarros

### Ejemplo: Crear 4 jarros para diciembre

```bash
# Terminal o cliente API (Postman, etc)

# 1. Necesidades (50% de ingresos, acumulativo)
POST /api/v1/users/1/jars
{
  "name": "Necesidades",
  "type": "percent",
  "percent": 50,
  "base_scope": "all_income",
  "refresh_mode": "accumulative",
  "refresh_day": 1,
  "color": "#FF6B6B"
}

# 2. Diversión (15% de ingresos, reset mensual)
POST /api/v1/users/1/jars
{
  "name": "Diversión",
  "type": "percent",
  "percent": 15,
  "base_scope": "all_income",
  "refresh_mode": "reset",
  "refresh_day": 1,
  "color": "#FFD93D"
}

# 3. Ahorro (20% de ingresos, acumulativo)
POST /api/v1/users/1/jars
{
  "name": "Ahorro",
  "type": "percent",
  "percent": 20,
  "base_scope": "all_income",
  "refresh_mode": "accumulative",
  "refresh_day": 1,
  "color": "#6BCB77"
}

# 4. Emergencias (monto fijo, acumulativo)
POST /api/v1/users/1/jars
{
  "name": "Emergencias",
  "type": "fixed",
  "fixed_amount": 500,
  "refresh_mode": "accumulative",
  "refresh_day": 1,
  "color": "#4D96FF"
}
```

---

## 💾 Paso 3: Sincronizar Saldos del Sistema Anterior

Supongamos que tu sistema anterior tiene estos saldos:

| Jarro | Saldo |
|-------|-------|
| Necesidades | $15,000 |
| Diversión | $3,500 |
| Ahorro | $8,200 |
| Emergencias | $2,000 |

Ajusta cada uno:

```bash
# 1. Necesidades
POST /api/v1/users/1/jars/1/force-balance
{
  "available_amount": 15000.00,
  "date": "2024-12-01",
  "reason": "Saldo inicial sincronizado desde sistema anterior"
}

# 2. Diversión
POST /api/v1/users/1/jars/2/force-balance
{
  "available_amount": 3500.00,
  "date": "2024-12-01",
  "reason": "Saldo inicial sincronizado desde sistema anterior"
}

# 3. Ahorro
POST /api/v1/users/1/jars/3/force-balance
{
  "available_amount": 8200.00,
  "date": "2024-12-01",
  "reason": "Saldo inicial sincronizado desde sistema anterior"
}

# 4. Emergencias
POST /api/v1/users/1/jars/4/force-balance
{
  "available_amount": 2000.00,
  "date": "2024-12-01",
  "reason": "Saldo inicial sincronizado desde sistema anterior"
}
```

---

## 🔍 Paso 4: Verificar que Todo está Bien

```bash
# Ver saldo de Necesidades
GET /api/v1/users/1/jars/1/balance

# Respuesta:
{
  "status": "OK",
  "code": 200,
  "data": {
    "jar_id": 1,
    "jar_name": "Necesidades",
    "type": "percent",
    "refresh_mode": "accumulative",
    "allocated_amount": 0.00,        # Sin ingresos aún
    "spent_amount": 0.00,
    "available_amount": 15000.00,    # Tu saldo sincronizado
    "period_start": "2024-12-01",
    "period_end": "2024-12-31"
  }
}
```

---

## 💳 Paso 5: Registrar Gastos Normalmente

Cuando registres una transacción (gasto), asígnala a un jarro:

```bash
POST /api/v1/transactions
{
  "name": "Compra de alimentos",
  "amount": 250.50,
  "date": "2024-12-15",
  "account_id": 1,
  "items": [
    {
      "name": "Comida",
      "amount": 250.50,
      "jar_id": 1,  # Asignar a jarro "Necesidades"
      "category_id": 5
    }
  ]
}
```

---

## 📊 Paso 6: Ver tu Progreso

```bash
# Ver todos los saldos de tus jarros
GET /api/v1/users/1/jars

# Respuesta: Lista con el nuevo campo "available_amount"
[
  {
    "id": 1,
    "name": "Necesidades",
    "available_amount": 14749.50,   # 15000 - 250.50
    ...
  },
  {
    "id": 2,
    "name": "Diversión",
    "available_amount": 3500.00,
    ...
  },
  ...
]
```

---

## ⏰ Cada Nuevo Mes (Enero 2025)

El saldo se calcula automáticamente en tiempo real según el modo:

**Para Necesidades (acumulativo)**:
```
Enero:
- Allocated: 50% de $10,000 = $5,000
- Spent: $250 (gastos del mes)
- Adjustment: $15,000 (del sincronización)
- Available: $5,000 - $250 + $15,000 = $19,750
```

**Para Diversión (reset)**:
```
Enero:
- Allocated: 15% de $10,000 = $1,500
- Spent: $300
- Adjustment: $0 (se limpió por reset)
- Available: $1,500 - $300 + $0 = $1,200
```

---

## 🚀 Ver tu Saldo Actual

```bash
# En cualquier momento
GET /api/v1/users/1/jars/{jarId}/balance

# Respuesta:
{
  "allocated_amount": 500.00,
  "spent_amount": 150.00,
  "adjustment": 15000.00,
  "available_balance": 15350.00
}
```

---

## 💡 Tips

1. **Empieza sincronizando**: Configura los saldos iniciales con `/adjust`
2. **Registra gastos normalmente**: El saldo se actualiza automáticamente
3. **Monitorea regularmente**: Usa `/balance` para ver disponible vs gastado
4. **Ajusta si necesitas**: Usa `/adjust` en cualquier momento para correcciones

---

## ❓ Dudas Frecuentes

**¿Qué pasa si gasto más que lo disponible?**
El sistema permite gastar más. El `available_balance` puede ser negativo (en rojo).

**¿Puedo cambiar el saldo manualmente después?**
Sí, usa `/adjust` en cualquier momento.

**¿Se pierden los ajustes después?**
Depende del modo:
- **Reset**: Se limpian al final del mes
- **Accumulative**: Se mantienen y acumulan

**¿Cada mes tengo que ajustar manualmente?**
No, el sistema lo hace automáticamente si el jarro es `type='percent'` (basado en ingresos).
Para `type='fixed'`, simplemente suma el monto.
