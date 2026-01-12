# 🏺 Sistema de Saldo de Jarros - Resumen Visual

## ✅ Implementación Completada

Se ha rediseñado el sistema de saldo de jarros de forma **simple y eficiente**:

### 📦 Archivos Creados/Modificados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `database/migrations/2025_12_14_000001_add_jar_adjustments_system.php` | ✨ Nuevo | Migración para agregar campos y tabla de ajustes |
| `app/Services/JarBalanceService.php` | 🔄 Rediseñado | Lógica simplificada del cálculo de saldos |
| `app/Models/Entities/JarAdjustment.php` | ✨ Nuevo | Modelo para historial de ajustes |
| `app/Http/Controllers/Api/JarBalanceController.php` | 🔄 Rediseñado | 4 endpoints: balance, adjust, history, reset |
| `routes/api/users.php` | 🔄 Modificado | Nuevas rutas para balance management |
| `docs/jar-balance-system.md` | ✨ Nuevo | Documentación completa con ejemplos |

---

## 🎯 La Fórmula (Lo más importante)

```
SALDO DISPONIBLE = (Monto Asignado - Gastos) + Ajuste Manual

Ejemplo:
Jarro Diversión (fijo $500):
- Monto asignado:    $500
- Gastos:           -$150
- Ajuste manual:    +$100 (lo que agregaste manualmente)
                   ─────
- DISPONIBLE:        $450
```

---

## 📊 Dos Tipos de Jarro

### 1️⃣ Jarro Fijo (`type: "fixed"`)

```
Enero:
├─ Monto:       $500
├─ Gastos:      -$150
├─ Ajuste:      $0
└─ Disponible:  $350

Febrero (si refresh_mode = "reset"):
├─ Monto:       $500  ← Vuelve a $500
├─ Gastos:      -$200
├─ Ajuste:      $0    ← Se limpia
└─ Disponible:  $300

Febrero (si refresh_mode = "accumulative"):
├─ Monto:       $500 + $350 anterior = $850
├─ Gastos:      -$200
├─ Ajuste:      $0
└─ Disponible:  $650
```

### 2️⃣ Jarro Porcentaje (`type: "percent"`)

```
Enero:
├─ Ingresos:       $1000
├─ Porcentaje:     10%
├─ Monto:          $100
├─ Gastos:         -$60
├─ Ajuste:         $0
└─ Disponible:     $40

Febrero (10% de $2000 de ingresos):
├─ Ingresos:       $2000
├─ Porcentaje:     10%
├─ Monto:          $200
├─ Gastos:         -$180
├─ Ajuste:         $0
└─ Disponible:     $20
```

---

## 🔄 Modo de Refresco

### RESET: Empieza de cero cada mes
```
✓ Jarro de gastos ocasionales (Vacaciones, Mantenimiento)
✓ Presupuesto fijo mensual que no debe acumular

Comportamiento:
Enero: Disponible $100
Febrero 1°: Reset → Disponible $500 (nuevo período)
```

### ACCUMULATIVE: Suma saldos previos
```
✓ Ahorros a largo plazo
✓ Fondos de emergencia
✓ Inversiones

Comportamiento:
Enero: Disponible $100
Febrero: Disponible $100 + $500 nuevo = $600
```

---

## 🎛️ Ajustes Manuales (Lo especial)

Un ajuste **no afecta gastos**, es simplemente:
- ➕ Agregar dinero (incremento)
- ➖ Quitar dinero (decremento)

### ✅ Cuándo Usarlos

| Escenario | Ejemplo | Comando |
|-----------|---------|---------|
| Sincronizar sistema anterior | Tenías $15,000 en "Necesidades" | `POST /adjust` con amount: +15000 |
| Corregir error | Gastaste $100 que no era del jarro | `POST /adjust` con amount: -100 |
| Transferencia entre jarros | Moves $200 de Ahorros a Diversión | Adjust Diversión +200, Ahorros -200 |
| Bonificación | Te dieron bono y quieres agregar | `POST /adjust` con amount: +2000 |

### Historial Completo

```json
GET /api/v1/users/1/jars/5/adjustments

[
  {
    "id": 1,
    "amount": 15000.00,
    "type": "increment",
    "reason": "Sincronización desde sistema anterior",
    "previous_available": 0.00,
    "new_available": 15000.00,
    "date": "2025-01-01",
    "adjusted_by": "José Luis"
  },
  {
    "id": 2,
    "amount": 100.00,
    "type": "decrement",
    "reason": "Corrección de gasto duplicado",
    "previous_available": 15000.00,
    "new_available": 14900.00,
    "date": "2025-01-15",
    "adjusted_by": "José Luis"
  }
]
```

---

## 🚀 Flujo de Uso

### Paso 1️⃣: Crear Jarro
```bash
POST /api/v1/users/1/jars
{
  "name": "Necesidades",
  "type": "percent",
  "percent": 50,
  "refresh_mode": "accumulative",
  "categories": [1, 2, 3]
}
```

### Paso 2️⃣: Sincronizar Saldo Anterior (si existe)
```bash
POST /api/v1/users/1/jars/1/adjust
{
  "amount": 15000,
  "reason": "Saldo inicial del sistema anterior",
  "date": "2024-12-01"
}
```

### Paso 3️⃣: Registrar Gastos Normalmente
```bash
POST /api/v1/transactions
{
  "items": [
    {
      "name": "Compras",
      "amount": 250,
      "category_id": 1,  ← Vinculada al jarro automáticamente
      "jar_id": 1
    }
  ]
}
```

### Paso 4️⃣: Ver Saldo Actual
```bash
GET /api/v1/users/1/jars/1/balance

Respuesta:
{
  "allocated_amount": 750.50,      (50% de $1501 de ingresos)
  "spent_amount": 250.00,          (del paso anterior)
  "adjustment": 15000.00,          (del paso 2)
  "available_balance": 15500.50    (750.50 - 250 + 15000)
}
```

---

## 📋 Ejemplos Rápidos

### Caso 1: Sincronización de Diciembre 2024

Tenías estos saldos en sistema anterior:

```
Necesidades:  $15,000
Diversión:     $3,500
Ahorro:        $8,200
Emergencias:   $2,000
─────────────────────
TOTAL:        $28,700
```

**Solución en 5 minutos:**

```bash
# 1. Crear los 4 jarros (si no existen)
# 2. Ajustar cada uno:

POST /api/v1/users/1/jars/1/adjust
{ "amount": 15000, "reason": "Saldo inicial", "date": "2024-12-01" }

POST /api/v1/users/1/jars/2/adjust
{ "amount": 3500, "reason": "Saldo inicial", "date": "2024-12-01" }

POST /api/v1/users/1/jars/3/adjust
{ "amount": 8200, "reason": "Saldo inicial", "date": "2024-12-01" }

POST /api/v1/users/1/jars/4/adjust
{ "amount": 2000, "reason": "Saldo inicial", "date": "2024-12-01" }

# ✅ Listo. Sistema nuevo con saldos sincronizados
```

### Caso 2: Corregir Gasto Mal Registrado

```
Tu estado actual:
- Saldo disponible: $450

Error: Registraste $100 en el jarro equivocado

Solución:
POST /api/v1/users/1/jars/1/adjust
{
  "amount": -100,
  "reason": "Corrección: gasto fue en otra categoría"
}

Nuevo saldo: $350
```

### Caso 3: Jarro de Emergencia (Reset Mensual)

```
ENERO:
Jarro: Emergencias ($500 fijos, RESET)
├─ Monto: $500
├─ Gastos: $0
├─ Disponible: $500

Enero 20: Emergencia!
POST /api/v1/users/1/jars/4/adjust
{ "amount": -400, "reason": "Reparación urgente" }

Nuevo disponible: $100

FEBRERO 1° (Automático al cambiar mes):
El jarro se resetea:
├─ Monto: $500 (fresco)
├─ Gastos: $0
├─ Ajuste: $0 (se limpió)
├─ Disponible: $500 (como nuevo)
```

---

## 📊 Comparación: Antes vs Después

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| **Tablas** | jar_period_balances + complex refresh logic | 2 columnas + jar_adjustments |
| **Scheduler** | Necesitaba cron automático | ❌ No necesita scheduler |
| **Cálculo** | Pre-generado en tabla | ✅ Calculado en tiempo real |
| **Ajustes** | No existían | ✅ Soportados con historial |
| **Reset/Accum** | Ambos soportados | ✅ Ambos soportados |
| **Complejidad** | Alta (7 métodos, relaciones complejas) | ✅ Simple (4 métodos, una fórmula) |

---

## 🛠️ Próximos Pasos

### 1. Ejecutar Migración
```bash
php artisan migrate
```

### 2. (Opcional) Crear Jarros de Prueba
```bash
# Necesidades (50%, acumulativo)
POST /api/v1/users/1/jars
{
  "name": "Necesidades",
  "type": "percent",
  "percent": 50,
  "refresh_mode": "accumulative"
}

# Diversión ($500 fijos, reset)
POST /api/v1/users/1/jars
{
  "name": "Diversión",
  "type": "fixed",
  "fixed_amount": 500,
  "refresh_mode": "reset"
}
```

### 3. Sincronizar Saldos Anteriores
```bash
# Si viniste de otro sistema, ajusta aquí
POST /api/v1/users/1/jars/1/adjust
{ "amount": <tu_saldo_anterior> }
```

### 4. Empezar a Usar
- Registra gastos normalmente
- Los saldos se actualizan automáticamente
- Ver balance con: `GET /api/v1/users/1/jars/{jarId}/balance`

---

## ❓ Preguntas Rápidas

**P: ¿Se pierden los ajustes cada mes?**
R: Solo si `refresh_mode = "reset"`. Si es `"accumulative"`, se mantienen.

**P: ¿Dónde veo el historial de ajustes?**
R: `GET /api/v1/users/1/jars/{jarId}/adjustments`

**P: ¿El ajuste afecta los gastos?**
R: No. Son independientes. Ajuste = corrección manual. Gasto = transacción real.

**P: ¿Puedo ajustar un jarro de mes anterior?**
R: Sí. Especifica `date` en el POST.

**P: ¿Qué pasa si gasto más que lo disponible?**
R: El saldo se pone negativo (en rojo). El sistema lo permite como alerta.

---

## 📚 Documentación Completa

Consulta `docs/jar-balance-system.md` para:
- API endpoints detallados
- Ejemplos de request/response
- Fórmulas técnicas
- FAQ extendido
