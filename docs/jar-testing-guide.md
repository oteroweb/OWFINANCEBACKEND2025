# 🧪 Guía de Pruebas - Sistema de Saldo de Jarros

## ✅ Pre-requisitos

1. Ejecutar migración:
   ```bash
   php artisan migrate
   ```

2. Tener al menos un usuario con ID 1 (generalmente el admin)

3. Tener jarros creados (mínimo 2-3 para pruebas)

---

## 🚀 Test Case 1: Jarro Fijo (Fixed) - RESET

**Objetivo:** Verificar que jarro fijo se resetea cada mes

### Setup
```bash
POST /api/v1/users/1/jars
{
  "name": "Test Fixed RESET",
  "type": "fixed",
  "fixed_amount": 500,
  "refresh_mode": "reset"
}
# Respuesta: Creado jarro ID 10
```

### Test 1.1: Ver saldo inicial
```bash
GET /api/v1/users/1/jars/10/balance

Esperado:
{
  "allocated_amount": 500.00,
  "spent_amount": 0.00,
  "adjustment": 0.00,
  "available_balance": 500.00
}
```

### Test 1.2: Hacer ajuste positivo
```bash
POST /api/v1/users/1/jars/10/adjust
{
  "amount": 100,
  "reason": "Depósito adicional de prueba",
  "date": "2025-01-10"
}

Esperado:
{
  "previous_available": 500.00,
  "new_available": 600.00
}

GET /api/v1/users/1/jars/10/balance
# Debe mostrar available_balance: 600.00
```

### Test 1.3: Hacer ajuste negativo
```bash
POST /api/v1/users/1/jars/10/adjust
{
  "amount": -50,
  "reason": "Corrección de gasto",
  "date": "2025-01-15"
}

Esperado:
{
  "previous_available": 600.00,
  "new_available": 550.00
}

GET /api/v1/users/1/jars/10/balance
# Debe mostrar available_balance: 550.00
```

### Test 1.4: Ver historial de ajustes
```bash
GET /api/v1/users/1/jars/10/adjustments

Esperado: Array con 2 items
[
  {
    "amount": 50.00,
    "type": "decrement",
    "reason": "Corrección de gasto",
    "date": "2025-01-15"
  },
  {
    "amount": 100.00,
    "type": "increment",
    "reason": "Depósito adicional de prueba",
    "date": "2025-01-10"
  }
]
```

### Test 1.5: Resetear para siguiente período
```bash
POST /api/v1/users/1/jars/10/reset-adjustment

Esperado:
{
  "adjustment": 0.00,
  "refresh_mode": "reset"
}

GET /api/v1/users/1/jars/10/balance
# Ahora: available_balance debe ser 500.00 (ajustes olvidados)
# Pero historial en adjustments sigue siendo visible
```

---

## 🚀 Test Case 2: Jarro Fijo (Fixed) - ACCUMULATIVE

**Objetivo:** Verificar que jarro acumulativo mantiene saldos

### Setup
```bash
POST /api/v1/users/1/jars
{
  "name": "Test Fixed ACCUMULATIVE",
  "type": "fixed",
  "fixed_amount": 300,
  "refresh_mode": "accumulative"
}
# Respuesta: Creado jarro ID 11
```

### Test 2.1: Ajustar a $800
```bash
POST /api/v1/users/1/jars/11/adjust
{
  "amount": 800,
  "reason": "Saldo inicial"
}

GET /api/v1/users/1/jars/11/balance
# Esperado: available_balance: 1100.00 (300 fijo + 800 ajuste)
```

### Test 2.2: Reducir en $200
```bash
POST /api/v1/users/1/jars/11/adjust
{
  "amount": -200,
  "reason": "Gasto"
}

GET /api/v1/users/1/jars/11/balance
# Esperado: available_balance: 900.00 (300 - 200 + 800)
```

### Test 2.3: Resetear (NO debería afectar en ACCUMULATIVE)
```bash
POST /api/v1/users/1/jars/11/reset-adjustment

GET /api/v1/users/1/jars/11/balance
# Esperado: available_balance: 900.00 (se mantiene igual porque es ACCUMULATIVE)
```

---

## 🚀 Test Case 3: Jarro Porcentaje (Percent) - RESET

**Objetivo:** Verificar cálculo de porcentaje de ingresos

### Setup
```bash
POST /api/v1/users/1/jars
{
  "name": "Test Percent RESET",
  "type": "percent",
  "percent": 20,
  "refresh_mode": "reset"
}
# Respuesta: Creado jarro ID 12
```

### Test 3.1: Ver saldo (sin ingresos)
```bash
GET /api/v1/users/1/jars/12/balance?date=2025-01-15

Esperado:
{
  "allocated_amount": 0.00,    ← Sin ingresos en enero
  "spent_amount": 0.00,
  "adjustment": 0.00,
  "available_balance": 0.00
}
```

### Test 3.2: Registrar ingreso (si tienes transacciones de ingresos)
```bash
POST /api/v1/transactions
{
  "type": "income",
  "amount": 1000,
  "date": "2025-01-10"
}

GET /api/v1/users/1/jars/12/balance?date=2025-01-15
# Esperado: allocated_amount: 200.00 (20% de $1000)
```

### Test 3.3: Ajustar
```bash
POST /api/v1/users/1/jars/12/adjust
{
  "amount": 50,
  "reason": "Bonificación"
}

GET /api/v1/users/1/jars/12/balance
# Esperado: available_balance: 250.00 (200 + 50)
```

---

## 🚀 Test Case 4: Sincronización Sistema Anterior

**Objetivo:** Simular migración desde sistema contable anterior

### Setup
```bash
# Crear 4 jarros básicos
POST /api/v1/users/1/jars { "name": "Necesidades", "type": "percent", "percent": 50, "refresh_mode": "accumulative" }
POST /api/v1/users/1/jars { "name": "Diversión", "type": "fixed", "fixed_amount": 500, "refresh_mode": "reset" }
POST /api/v1/users/1/jars { "name": "Ahorro", "type": "percent", "percent": 20, "refresh_mode": "accumulative" }
POST /api/v1/users/1/jars { "name": "Emergencias", "type": "fixed", "fixed_amount": 1000, "refresh_mode": "accumulative" }
```

### Test 4.1: Sincronizar saldos
```bash
# Necesidades: $15,000
POST /api/v1/users/1/jars/13/adjust
{
  "amount": 15000,
  "reason": "Saldo inicial sincronizado desde sistema anterior",
  "date": "2024-12-01"
}

# Diversión: $3,500
POST /api/v1/users/1/jars/14/adjust
{
  "amount": 3500,
  "reason": "Saldo inicial sincronizado desde sistema anterior",
  "date": "2024-12-01"
}

# Ahorro: $8,200
POST /api/v1/users/1/jars/15/adjust
{
  "amount": 8200,
  "reason": "Saldo inicial sincronizado desde sistema anterior",
  "date": "2024-12-01"
}

# Emergencias: $2,000
POST /api/v1/users/1/jars/16/adjust
{
  "amount": 2000,
  "reason": "Saldo inicial sincronizado desde sistema anterior",
  "date": "2024-12-01"
}
```

### Test 4.2: Verificar cada saldo
```bash
GET /api/v1/users/1/jars/13/balance
# available_balance: 15000.00 ✓

GET /api/v1/users/1/jars/14/balance
# available_balance: 3500.00 ✓

GET /api/v1/users/1/jars/15/balance
# available_balance: 8200.00 ✓

GET /api/v1/users/1/jars/16/balance
# available_balance: 2000.00 ✓
```

### Test 4.3: Total sincronizado
```bash
# Suma: 15000 + 3500 + 8200 + 2000 = 28700
# Este es tu patrimonio total sincronizado ✓
```

---

## 🎯 Test Case 5: Casos Edge

### Test 5.1: Saldo negativo (allowed)
```bash
POST /api/v1/users/1/jars/10/adjust
{
  "amount": -1000,  # Más de lo disponible
  "reason": "Gasto grande"
}

GET /api/v1/users/1/jars/10/balance
# available_balance: -450.00 (negativo, pero permitido)
```

### Test 5.2: Ajuste en fecha pasada
```bash
POST /api/v1/users/1/jars/10/adjust
{
  "amount": 100,
  "reason": "Ajuste retroactivo",
  "date": "2025-01-05"
}

# Debería registrarse con esa fecha
GET /api/v1/users/1/jars/10/adjustments?from=2025-01-05&to=2025-01-05
# Debe incluir este ajuste
```

### Test 5.3: Sin razón en ajuste
```bash
POST /api/v1/users/1/jars/10/adjust
{
  "amount": 50
  # Sin "reason"
}

# Debería funcionar (reason es opcional)
GET /api/v1/users/1/jars/10/adjustments
# reason: null
```

---

## 📊 Validaciones Esperadas

| Escenario | Validación | Resultado |
|-----------|-----------|-----------|
| Monto negativo en jarro | Sin restricción | ✅ Permitido |
| Ajuste sin razón | Sin restricción | ✅ Permitido |
| Ajuste sin fecha | Usa hoy | ✅ Funciona |
| Fecha futura en ajuste | Sin restricción | ✅ Permitido |
| Historial después reset | Se mantiene | ✅ Historial persiste |
| Múltiples ajustes | Se suman | ✅ Acumulación correcta |

---

## 🔍 Debugging

### Ver logs de ajustes
```bash
# En tu base de datos
SELECT * FROM jar_adjustments ORDER BY created_at DESC;

# Ver historial de un jarro
SELECT * FROM jar_adjustments WHERE jar_id = 10 ORDER BY created_at DESC;
```

### Validar cálculo manual
```bash
# Obtener jarro
jar_balance_service = new JarBalanceService();
balance = jar_balance_service.getDetailedBalance(jar);

# Verificar:
# allocated = calculateAllocatedAmount()
# spent = calculateSpentAmount()
# adjustment = jar.adjustment
# available = allocated - spent + adjustment
```

---

## ✅ Checklist de Pruebas

- [ ] Test 1.1: Saldo inicial fijo
- [ ] Test 1.2: Ajuste positivo
- [ ] Test 1.3: Ajuste negativo
- [ ] Test 1.4: Ver historial
- [ ] Test 1.5: Reset para siguiente período
- [ ] Test 2.1: Jarro acumulativo con ajuste
- [ ] Test 2.2: Reducción en acumulativo
- [ ] Test 2.3: Reset no afecta acumulativo
- [ ] Test 3.1: Porcentaje sin ingresos
- [ ] Test 3.2: Porcentaje con ingresos
- [ ] Test 3.3: Ajuste en porcentaje
- [ ] Test 4.1: Sincronización 4 jarros
- [ ] Test 4.2: Verificar cada saldo
- [ ] Test 4.3: Total sincronizado
- [ ] Test 5.1: Saldo negativo
- [ ] Test 5.2: Ajuste retroactivo
- [ ] Test 5.3: Sin razón

---

## 🎓 Tips de Prueba

1. **Usa Postman o similar** para probar endpoints fácilmente
2. **Valida respuestas** contra lo "Esperado"
3. **Verifica base de datos** directamente si hay dudas
4. **Prueba combinaciones** (fijo + reset, porcentaje + acumulativo)
5. **Intenta casos edge** (negativos, retroactivos, etc.)

---

## 📞 Problemas Comunes

**P: `jar_id` not found**
R: Verifica que el ID del jarro existe: `SELECT * FROM jars WHERE user_id = 1;`

**P: available_balance no cambia después de ajuste**
R: Verifica que se guardó en DB: `SELECT * FROM jar_adjustments WHERE jar_id = X;`

**P: Adjustment se olvidó después de reset**
R: Es correcto si `refresh_mode = "reset"`. El historial persiste en `jar_adjustments`.

**P: Porcentaje da 0**
R: Verifica que hay ingresos en ese mes: `SELECT * FROM item_transactions WHERE type = 'income';`
