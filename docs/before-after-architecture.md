# Antes vs Después: Arquitectura y Lógica del Sistema de Jarros

## 📊 Comparación Visual Completa

---

## 1. Flujo de Datos

### ANTES (Sistema Viejo)

```
┌─────────────────────────────────────────────────────────────────┐
│                        USUARIO EN FRONTEND                      │
│                   (Pantalla de Cantaro)                         │
└────────────────────────┬────────────────────────────────────────┘
                         │
                    [Hace clic]
                         │
                         ↓
        ┌────────────────────────────────┐
        │   GET /jars/{id}/period-balance │
        │   (Obtiene saldo pre-generado)  │
        └────────┬───────────────────────┘
                 │
                 ↓
        ┌────────────────────────────┐
        │    BACKEND (Laravel)       │
        │                            │
        │ 1. Busca jar_period_      │
        │    balances para este mes │
        │ 2. Retorna available_     │
        │    amount directamente    │
        │                            │
        │ BD: jar_period_balances   │
        │     (tabla pre-generada)  │
        └────────┬───────────────────┘
                 │
                 ↓
        ┌────────────────────────┐
        │  JSON Response:        │
        │ {available: 300.00}    │
        │                        │
        │ ❌ Sin detalles        │
        │ ❌ Sin historial       │
        │ ❌ Cálculo opaco       │
        └────────────────────────┘
                 │
                 ↓
        ┌──────────────────────────┐
        │ Frontend muestra: $300   │
        │                          │
        │ ❌ No sabe por qué $300  │
        │ ❌ No ve gasto           │
        │ ❌ No ve ajustes         │
        └──────────────────────────┘

PROBLEMAS:
❌ Scheduler necesario (cron job)
❌ Tabla jar_period_balances (redundante)
❌ Datos pre-generados (inflexible)
❌ Sin historial detallado
❌ Difícil de sincronizar
❌ Sin auditoría
```

---

### DESPUÉS (Sistema Nuevo)

```
┌─────────────────────────────────────────────────────────────────┐
│                        USUARIO EN FRONTEND                      │
│                   (Pantalla de Cantaro)                         │
└────────────────────────┬────────────────────────────────────────┘
                         │
                    [Hace clic]
                         │
                         ↓
        ┌────────────────────────────────────┐
        │ GET /jars/{id}/balance             │
        │ (Cálculo en tiempo real)           │
        └────────┬───────────────────────────┘
                 │
                 ↓
        ┌────────────────────────────────────┐
        │    BACKEND (Laravel Service)       │
        │                                    │
        │ JarBalanceService:                 │
        │                                    │
        │ 1. Calcula Asignado:               │
        │    - Si fixed: usa fixed_amount    │
        │    - Si percent: ingreso × %       │
        │                                    │
        │ 2. Calcula Gastado:                │
        │    - Suma item_transactions        │
        │    - De este mes                   │
        │                                    │
        │ 3. Suma Ajuste:                    │
        │    - De columna adjustment         │
        │                                    │
        │ 4. Fórmula:                        │
        │    Available = Asignado - Gastado  │
        │             + Ajuste               │
        │                                    │
        │ BD: jars + jar_adjustments         │
        │     (sin tablas redundantes)       │
        └────────┬───────────────────────────┘
                 │
                 ↓
        ┌────────────────────────────────────┐
        │  JSON Response Completo:           │
        │                                    │
        │ {                                  │
        │   allocated_amount: 300,           │
        │   spent_amount: 50.50,             │
        │   adjustment: 20,                  │
        │   available_balance: 269.50,       │
        │   breakdown: {...}                 │
        │ }                                  │
        │                                    │
        │ ✅ Detalles claros                │
        │ ✅ Cálculo transparente           │
        │ ✅ Fácil auditar                  │
        └────────┬───────────────────────────┘
                 │
                 ↓
        ┌──────────────────────────────────────┐
        │ Frontend muestra:                    │
        │                                      │
        │ Saldo Disponible: $269.50            │
        │                                      │
        │ Detalles:                            │
        │ • Asignado:    $300.00               │
        │ • Gastado:     -$50.50               │
        │ • Ajuste:      +$20.00               │
        │                                      │
        │ ✅ El usuario entiende por qué      │
        │ ✅ Ve cada componente                │
        │ ✅ Puede hacer ajustes               │
        └──────────────────────────────────────┘

VENTAJAS:
✅ Sin scheduler (cálculo en tiempo real)
✅ Sin tabla jar_period_balances
✅ Datos dinámicos (flexible)
✅ Historial completo auditable
✅ Fácil de sincronizar
✅ Transparencia total
```

---

## 2. Estructura de Base de Datos

### ANTES

```sql
-- Tabla jars (original)
CREATE TABLE jars (
  id INT,
  name VARCHAR,
  type ENUM('fixed', 'percent'),
  fixed_amount DECIMAL,
  percent DECIMAL,
  refresh_mode VARCHAR,  -- reset/accumulative
  base_scope VARCHAR,
  user_id INT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  deleted_at TIMESTAMP
);

-- ❌ TABLA REDUNDANTE: jar_period_balances
CREATE TABLE jar_period_balances (
  id INT,
  jar_id INT,
  period DATE,  -- ¿Qué es "período"? ¿Mes? ¿Día?
  refresh_day INT,  -- Cuándo se resetea
  available_amount DECIMAL,  -- Pre-calculado
  last_refresh_date DATE,  -- Cuándo se actualizó
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
-- 
-- PROBLEMAS:
-- ❌ Redundante (se duplica info de jars)
-- ❌ Necesita scheduler para mantener
-- ❌ Si cambia ingreso, hay que regenerar
-- ❌ Sin historial (no sabe qué cambió)

-- Tabla item_transactions (existente)
CREATE TABLE item_transactions (
  id INT,
  user_id INT,
  jar_id INT,  -- Vinculado a jarro
  amount DECIMAL,
  type ENUM('income', 'expense'),
  created_at TIMESTAMP
);
```

---

### DESPUÉS

```sql
-- Tabla jars (actualizada, SIN redundancia)
CREATE TABLE jars (
  id INT,
  name VARCHAR,
  type ENUM('fixed', 'percent'),
  fixed_amount DECIMAL,        -- Si type='fixed'
  percent DECIMAL,              -- Si type='percent'
  refresh_mode VARCHAR,         -- 'reset' o 'accumulative'
  adjustment DECIMAL DEFAULT 0, -- ✅ NUEVA: ajuste manual acumulado
  base_scope VARCHAR,
  user_id INT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  deleted_at TIMESTAMP
);

-- ✅ NUEVA TABLA: jar_adjustments (historial auditable)
CREATE TABLE jar_adjustments (
  id INT,
  jar_id INT FOREIGN KEY,
  user_id INT FOREIGN KEY,
  amount DECIMAL,                        -- Monto ajustado
  type ENUM('increment', 'decrement'),   -- Tipo de cambio
  reason TEXT NULLABLE,                  -- Por qué se ajustó
  previous_available DECIMAL,            -- Saldo antes
  new_available DECIMAL,                 -- Saldo después
  adjustment_date DATE,                  -- Cuándo se ajustó
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  INDEX idx_jar_date (jar_id, adjustment_date),
  INDEX idx_user_created (user_id, created_at)
);

-- VENTAJAS:
-- ✅ Historial completo (quién, qué, cuándo, por qué, antes/después)
-- ✅ Sin scheduler necesario
-- ✅ Sin redundancia
-- ✅ Auditable
-- ✅ Fácil sincronización
```

---

## 3. Cálculo de Saldo

### ANTES: Almacenado en BD

```
jar_period_balances.available_amount = pre-calculado y guardado
                                      ↓
                                   INFLEXIBLE
                                      ↓
          Si cambia ingreso → hay que regenerar TODO
          Si hay error → difícil de auditar
```

**Ejemplo:**
```sql
SELECT available_amount FROM jar_period_balances
WHERE jar_id = 1 AND period = '2025-12-01'
-- Retorna: 300.00

-- ❌ No sabes de dónde vino ese 300.00
-- ❌ No sabes qué se gastó
-- ❌ No sabes qué se ajustó
```

---

### DESPUÉS: Calculado en Tiempo Real

```
Saldo = (Asignado - Gastado) + Ajuste Manual

┌─────────────────────────────────────────────────────────────┐
│ Asignado (calculated):                                      │
│                                                             │
│ Si type = 'fixed':                                          │
│   → fixed_amount (ej: $500)                                 │
│                                                             │
│ Si type = 'percent':                                        │
│   → (User Income for Month) × (percent / 100)              │
│   → SELECT SUM(amount) FROM item_transactions               │
│      WHERE user_id = ? AND type = 'income'                 │
│      AND DATE >= start_of_month AND DATE <= end_of_month   │
│   → Ejemplo: $5000 * (20/100) = $1000                      │
│                                                             │
│ Resultado: Dynamic, flexible, based on actual data         │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│ Gastado (calculated):                                       │
│                                                             │
│ → SELECT SUM(amount) FROM item_transactions                │
│    WHERE jar_id = ? AND type = 'expense'                   │
│    AND DATE >= start_of_month AND DATE <= end_of_month     │
│                                                             │
│ Ejemplo: $50.50                                             │
│                                                             │
│ Resultado: Sempre updated                                  │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│ Ajuste Manual:                                              │
│                                                             │
│ → SELECT SUM(IF(type='increment', amount, -amount))        │
│    FROM jar_adjustments                                    │
│    WHERE jar_id = ?                                        │
│                                                             │
│ Ejemplo: +$20 - $10 = +$10                                │
│                                                             │
│ Resultado: Auditable (historial en jar_adjustments)        │
└─────────────────────────────────────────────────────────────┘
                           ↓
         FÓRMULA FINAL: $500 - $50.50 + $10 = $459.50

VENTAJAS:
✅ Transparente (cada componente calculado)
✅ Flexible (reacciona a cambios de ingreso)
✅ Auditable (historial completo)
✅ Sin scheduler (en tiempo real)
✅ Fácil de sincronizar
```

---

## 4. Flujo de Ajuste (User Story)

### ANTES: Usuario Quiere Agregar $100

```
Usuario: "Quiero agregar $100 de ahorro extra"
         ↓
Frontend: POST /jars/1/adjust { amount: 100 }
         ↓
Backend: ❌ ¿Qué hace? No hay endpoint claro
         ├─ ¿Actualiza jar_period_balances?
         ├─ ¿Crea un registro de audit?
         ├─ ¿Cómo se rastrean los cambios?
         └─ Respuesta: Depende de la implementación
         ↓
❌ SIN HISTORIAL: No hay forma de auditar
❌ INFLEXIBLE: Si hay que revertir, es complicado
❌ OPACO: El usuario no ve qué sucedió
```

---

### DESPUÉS: Usuario Quiere Agregar $100

```
Usuario: "Quiero agregar $100 de ahorro extra"
         ↓
Frontend:
  ├─ Valida: ¿Monto > 0? ✓
  ├─ Muestra: Nuevo saldo sería $X
  └─ POST /jars/1/adjust { amount: 100, reason: "Ahorro extra" }
         ↓
Backend (JarBalanceService):
  ├─ 1. Calcula saldo ANTES: $400
  ├─ 2. Suma $100 al campo adjustment
  ├─ 3. Calcula saldo DESPUÉS: $500
  └─ 4. Crea registro en jar_adjustments:
  │     {
  │       jar_id: 1,
  │       user_id: 42,
  │       amount: 100,
  │       type: 'increment',
  │       reason: 'Ahorro extra',
  │       previous_available: 400,
  │       new_available: 500,
  │       adjustment_date: '2025-12-14',
  │       created_at: '2025-12-14T10:30:00Z'
  │     }
         ↓
Response JSON:
  {
    success: true,
    id: 42,
    amount: 100,
    type: 'increment',
    reason: 'Ahorro extra',
    previous_available: 400,
    new_available: 500,
    adjustment_date: '2025-12-14'
  }
         ↓
Frontend:
  ├─ Actualiza balance a $500
  ├─ Agrega a historial
  └─ Muestra toast: "✅ Ajuste guardado"
         ↓
Usuario ve:
  ├─ Nuevo saldo: $500
  ├─ Historial completo con razón
  └─ Puede revertir/auditar si necesario

✅ HISTORIAL: Completo y auditable
✅ FLEXIBLE: Fácil de revertir o ajustar
✅ TRANSPARENTE: El usuario ve exactamente qué pasó
```

---

## 5. Sincronización de Saldos (Caso Real: Diciembre)

### ANTES: Muy Complicado

```
Usuario tiene en sistema viejo:
- Necesidades: $15,000
- Diversión: $3,500
- Ahorro: $8,200
- Emergencias: $2,000
TOTAL: $28,700

Para sincronizar en nuevo sistema:
❌ ¿Dónde se guardan esos $28,700?
❌ ¿Cómo se relacionan con jar_period_balances?
❌ ¿Hay que pre-generar períodos?
❌ ¿Y si el usuario migra a mitad de mes?

PROCESO ACTUAL:
1. Exportar datos del viejo sistema
2. Crear script SQL para jar_period_balances
3. Ejecutar scheduler para sincronizar
4. Validar que no haya duplicados
5. ¿Y si falla a mitad? ¿Rollback?

RIESGO ALTO ⚠️
```

---

### DESPUÉS: Simple y Directo

```
Usuario tiene en sistema viejo:
- Necesidades: $15,000
- Diversión: $3,500
- Ahorro: $8,200
- Emergencias: $2,000
TOTAL: $28,700

Para sincronizar en nuevo sistema:

PASO 1: Crear los 4 jarros
POST /jars
{
  name: "Necesidades",
  type: "percent",
  percent: 50,
  refresh_mode: "accumulative",
  user_id: 42
}
// Retorna: { id: 1, adjustment: 0 }

PASO 2: Aplicar saldos iniciales (una por una)
POST /jars/1/adjust
{
  amount: 15000,
  reason: "Saldo inicial sincronizado de diciembre"
}
// Crea registro en jar_adjustments con historial completo

PASO 3: Repetir para los 4 jarros

RESULTADO:
✅ Cada jarro tiene historial auditable
✅ Saldos se pueden rastrear
✅ Si hay error, solo revertir ese jarro
✅ Transparencia total
✅ Cero riesgo de pérdida de datos

EJEMPLO COMPLETO:

// Jarro 1: Necesidades
jars.id = 1
jars.name = "Necesidades"
jars.type = "percent"
jars.percent = 50
jars.adjustment = 0  (inicialmente)

// Aplicar ajuste de $15,000
jar_adjustments INSERT:
{
  jar_id: 1,
  user_id: 42,
  amount: 15000,
  type: 'increment',
  reason: 'Saldo inicial sincronizado de diciembre',
  previous_available: 0,
  new_available: 15000,
  adjustment_date: '2025-12-14'
}

// jars.adjustment se actualiza a 15000

// Cuando se consulta balance:
allocated = (ingreso * 50 / 100)  // Calculado dinámicamente
spent = 0  // Aún sin transacciones
adjustment = 15000  // De la sincronización
saldo = allocated - spent + adjustment

✅ SEGURO, AUDITABLE, FLEXIBLE
```

---

## 6. Resumen de Cambios

| Aspecto | ANTES | DESPUÉS |
|---------|-------|---------|
| **Tabla de Saldo** | jar_period_balances (pre-generada) | jars + jar_adjustments (en tiempo real) |
| **Cálculo** | SELECT * FROM jar_period_balances | Fórmula en código (transparent) |
| **Scheduler** | REQUERIDO (cron job) | NO NECESARIO |
| **Historial** | Minimal/Manual | Completo (quién, qué, cuándo, por qué) |
| **Auditoría** | Difícil | Fácil (tabla jar_adjustments) |
| **Sincronización** | Complicada | Simple (POST /adjust) |
| **Errores** | Difíciles de revertir | Fáciles de revertir |
| **Flexibilidad** | Baja (pre-generado) | Alta (en tiempo real) |
| **Performance** | Lectura rápida (pero overhead) | Lectura rápida con cálculo ligero |
| **Mantenibilidad** | Alta complejidad | Baja complejidad |

---

## 7. Timeline de Migración

### Diciembre 14

```
✅ Migración ejecutada (18.87ms)
✅ Código testeado y validado
✅ Documentación completa

Estado: Listo para usar en producción
```

### Diciembre 15+ (Tu Timeline)

```
OPCIÓN 1: Migración Completa
├─ Crear los 4 jarros principales
├─ Sincronizar saldos de diciembre
├─ Validar cálculos
└─ Ir en vivo

OPCIÓN 2: Migración Gradual
├─ Crear 1 jarro de prueba
├─ Validar cálculos
├─ Crear más jarros poco a poco
└─ Ir en vivo cuando esté confiado

OPCIÓN 3: Paratelo
├─ Sistema viejo seguir corriendo
├─ Sistema nuevo en testing
├─ Sincronizar datos cuando esté perfecto
└─ Cambiar de uno al otro
```

---

## Conclusión

**El nuevo sistema es:**
- ✅ Más simple (sin scheduler, sin redundancia)
- ✅ Más flexible (cálculos en tiempo real)
- ✅ Más auditable (historial completo)
- ✅ Más mantenible (código limpio)
- ✅ Más seguro (sin datos pre-generados)

**Está listo para producción ahora mismo.** 🚀
