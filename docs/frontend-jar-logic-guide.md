# Guía Frontend: Lógica del Sistema de Jarros (Cantaros)

## 📚 Tabla de Contenidos
1. [Conceptos Clave](#conceptos-clave)
2. [Flujo de Datos](#flujo-de-datos)
3. [APIs y Endpoints](#apis-y-endpoints)
4. [Ejemplos Prácticos](#ejemplos-prácticos)
5. [Estados Visuales](#estados-visuales)
6. [Manejo de Errores](#manejo-de-errores)
7. [Guía de Implementación Paso a Paso](#guía-de-implementación-paso-a-paso)

---

## Conceptos Clave

### ¿Qué es un Cantaro (Jarro)?

Un cantaro es un "contenedor" de dinero virtual dentro de la plataforma. Cada cantaro tiene:

| Propiedad | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `id` | int | Identificador único | 1 |
| `name` | string | Nombre del cantaro | "Diversión" |
| `type` | enum | `fixed` o `percent` | "fixed" |
| `fixed_amount` | decimal | Monto fijo mensual (si type=fixed) | 500.00 |
| `percent` | decimal | Porcentaje de ingreso (si type=percent) | 20 |
| `refresh_mode` | enum | `reset` o `accumulative` | "reset" |
| `adjustment` | decimal | Ajuste manual acumulado | 100.00 |

### Los Dos Tipos de Cantaros

#### 1. **FIXED** (Monto Fijo)
- Asignación: **Cantidad fija cada mes**
- Ejemplo: $500/mes para "Diversión"

```
Saldo = $500 - $50 (gastado) + $20 (ajuste) = $470
```

#### 2. **PERCENT** (Porcentaje de Ingreso)
- Asignación: **% del ingreso total del mes**
- Ejemplo: 20% de ingreso para "Ahorro"

```
Ingreso del mes: $5,000
Saldo = ($5,000 × 20%) - $200 (gastado) + $0 (ajuste) = $800
```

### Los Dos Modos de Renovación

#### 1. **RESET** (Reinicio Mensual)
- Cada mes comienza de cero
- Ideal para gastos puntuales
- Al final del mes: saldo no utilizado se pierde

```
Dic: $300 → Gasto $50 → Saldo: $250
Ene: $300 (reinicia a $0, saldo anterior se descarta)
```

#### 2. **ACCUMULATIVE** (Acumulativo)
- Los saldos se suman mes a mes
- Ideal para ahorros a largo plazo
- Al final del mes: saldo se suma al siguiente

```
Dic: $1,000 → Gasto $200 → Saldo: $800
Ene: $1,000 + $800 (anterior) = $1,800 disponible
```

---

## Flujo de Datos

### Arquitectura General

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (Vue/React)                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  [Pantalla de Cantaro] ──────────────┐                      │
│         ↓                            │                      │
│  • Mostrar saldo disponible          │                      │
│  • Historial de ajustes              │                      │
│  • Botones: Ajustar, Resetear        │                      │
│                                      ↓                      │
│                         [HTTP Requests]                     │
│                                      ↓                      │
└──────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    BACKEND (Laravel API)                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  JarBalanceService:                                          │
│  ├─ getAvailableBalance()     → Calcula saldo              │
│  ├─ adjustBalance()           → Crea ajuste                │
│  ├─ getAdjustmentHistory()    → Retorna historial          │
│  └─ resetAdjustmentForNewPeriod() → Reinicia para nuevo mes│
│                                                              │
│  Database:                                                   │
│  ├─ jars table (con adjustment, refresh_mode)              │
│  └─ jar_adjustments table (historial auditable)            │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Cálculo del Saldo (Lógica Principal)

```javascript
// EN EL FRONTEND (para mostrar, el backend calcula la verdad)

function calcularSaldoDisponible(jar) {
  // 1. Calcular monto asignado
  const montoAsignado = jar.type === 'fixed' 
    ? jar.fixed_amount                    // Si es fijo, usa fixed_amount
    : (ingresoDelMes * jar.percent) / 100 // Si es %, calcula % del ingreso

  // 2. Sumar gastos (obtenidos del backend)
  const gastado = jar.totalGastado // Viene del endpoint

  // 3. Sumar ajuste manual
  const ajuste = jar.adjustment // Campo en la tabla

  // 4. Fórmula final
  return montoAsignado - gastado + ajuste
}
```

---

## APIs y Endpoints

### Endpoint 1: Obtener Saldo Disponible

**GET** `/api/users/{userId}/jars/{jarId}/balance`

**Respuesta:**
```json
{
  "jar_id": 1,
  "name": "Diversión",
  "type": "fixed",
  "refresh_mode": "reset",
  "allocated_amount": 500.00,
  "spent_amount": 125.50,
  "adjustment": 50.00,
  "available_balance": 424.50,
  "breakdown": {
    "allocated": 500.00,
    "spent": 125.50,
    "adjustment": 50.00
  }
}
```

**Qué mostrar en el frontend:**
```
┌─────────────────────────┐
│  DIVERSIÓN              │
├─────────────────────────┤
│ Asignado:    $500.00    │
│ Gastado:    -$125.50    │
│ Ajuste:      +$50.00    │
├─────────────────────────┤
│ DISPONIBLE:  $424.50    │
└─────────────────────────┘
```

---

### Endpoint 2: Crear/Aplicar Ajuste

**POST** `/api/users/{userId}/jars/{jarId}/adjust`

**Request:**
```json
{
  "amount": -50.00,
  "reason": "Compra de regalo de cumpleaños"
}
```

**Respuesta:**
```json
{
  "id": 42,
  "jar_id": 1,
  "amount": 50.00,
  "type": "decrement",
  "reason": "Compra de regalo de cumpleaños",
  "previous_available": 424.50,
  "new_available": 374.50,
  "adjustment_date": "2025-12-14",
  "created_at": "2025-12-14T07:15:30Z"
}
```

**Validaciones en Frontend:**
```javascript
// Antes de enviar el request
function validarAjuste(jar, monto) {
  // 1. Monto no puede ser $0
  if (monto === 0) {
    mostrarError("El monto debe ser mayor a $0")
    return false
  }

  // 2. Si es decremento, validar que no quede negativo
  if (monto < 0 && (jar.available_balance + monto) < 0) {
    mostrarError(`No hay saldo suficiente. Disponible: $${jar.available_balance}`)
    return false
  }

  return true
}
```

---

### Endpoint 3: Obtener Historial de Ajustes

**GET** `/api/users/{userId}/jars/{jarId}/adjustments?from=2025-12-01&to=2025-12-31`

**Respuesta:**
```json
{
  "data": [
    {
      "id": 40,
      "amount": 100.00,
      "type": "increment",
      "reason": "Ahorro extra",
      "previous_available": 400.00,
      "new_available": 500.00,
      "adjustment_date": "2025-12-10",
      "created_at": "2025-12-10T10:30:00Z"
    },
    {
      "id": 42,
      "amount": 50.00,
      "type": "decrement",
      "reason": "Compra de regalo",
      "previous_available": 424.50,
      "new_available": 374.50,
      "adjustment_date": "2025-12-14",
      "created_at": "2025-12-14T07:15:30Z"
    }
  ],
  "total": 2
}
```

**Qué mostrar en el frontend:**
```
┌──────────────────────────────────────────────┐
│  HISTORIAL DE AJUSTES - DICIEMBRE 2025      │
├──────────────────────────────────────────────┤
│ 10 Dic │ ➕ $100.00 │ Ahorro extra          │
│        │           | $400.00 → $500.00      │
├────────┼───────────┼──────────────────────┤
│ 14 Dic │ ➖ $50.00  │ Compra de regalo     │
│        │           | $424.50 → $374.50     │
└──────────────────────────────────────────────┘
```

---

### Endpoint 4: Reiniciar Ajuste para Nuevo Período

**POST** `/api/users/{userId}/jars/{jarId}/reset-adjustment`

**Cuándo usar:**
- Solo aplica si `refresh_mode === 'reset'`
- Se ejecuta automáticamente al cambiar de mes (o manualmente por el usuario)
- Limpia el campo `adjustment` a 0

**Respuesta:**
```json
{
  "success": true,
  "message": "Ajuste reiniciado para nuevo período",
  "jar_id": 1,
  "new_adjustment": 0,
  "new_available_balance": 500.00
}
```

---

## Ejemplos Prácticos

### Caso 1: Cantaro FIXED con RESET

**Escenario:**
- Nombre: "Diversión"
- Tipo: Fixed ($300/mes)
- Modo: Reset (cada mes se reinicia)

**Antes (Sistema Viejo):**
```
Tabla jar_period_balances:
┌──────────┬──────────────┬─────────────────┐
│ jar_id   │ period       │ available_amount│
├──────────┼──────────────┼─────────────────┤
│ 1        │ 2025-12-01   │ 300.00          │
└──────────┴──────────────┴─────────────────┘

Proceso:
1. Pre-generar saldo cada mes (cron job)
2. Cuando gasto, actualizar table
3. Sin historial detallado
4. Difícil de sincronizar
```

**Después (Sistema Nuevo):**
```
Tabla jars:
┌────┬──────────┬──────┬──────────┬──────────────┬────────────┐
│ id │ name     │ type │ fixed_am │ adjustment   │ refresh_mo │
├────┼──────────┼──────┼──────────┼──────────────┼────────────┤
│ 1  │ Diversión│ fixed│ 300.00   │ 0.00         │ reset      │
└────┴──────────┴──────┴──────────┴──────────────┴────────────┘

Tabla jar_adjustments (historial):
┌────┬─────────┬────────┬──────────┬────────────┬─────────────────────┐
│ id │ jar_id  │ amount │ type     │ reason     │ adjustment_date     │
├────┼─────────┼────────┼──────────┼────────────┼─────────────────────┤
│ 1  │ 1       │ 50.00  │ decrement│ Cine       │ 2025-12-14         │
│ 2  │ 1       │ 30.00  │ decrement│ Comida     │ 2025-12-14         │
└────┴─────────┴────────┴──────────┴────────────┴─────────────────────┘

Cálculo en tiempo real:
Saldo = 300 - (50 + 30) + 0 = $220

Ventajas:
✓ Historial detallado de cada cambio
✓ Fácil de auditar (quién, qué, cuándo, por qué)
✓ Sin scheduler necesario
✓ Cálculo en tiempo real
```

---

### Caso 2: Cantaro PERCENT con ACCUMULATIVE

**Escenario:**
- Nombre: "Ahorro"
- Tipo: Percent (20% del ingreso)
- Modo: Accumulative (suma mes a mes)

**Antes:**
```
Tabla jar_period_balances (pre-generada):
┌──────────┬──────────────┬─────────────────┐
│ jar_id   │ period       │ available_amount│
├──────────┼──────────────┼─────────────────┤
│ 2        │ 2025-12-01   │ 1000.00         │ (20% de $5000)
│ 2        │ 2025-01-01   │ 1800.00         │ (generado)
└──────────┴──────────────┴─────────────────┘

Problema:
- ¿Cómo se calcula el 20% si el ingreso cambia?
- ¿Y si agrego ingresos a mitad de mes?
- Hay que regenerar todo el período
```

**Después:**
```
Tabla jars:
┌────┬──────┬─────────┬─────────┬────────────┬──────────────┐
│ id │ name │ type    │ percent │ adjustment │ refresh_mode │
├────┼──────┼─────────┼─────────┼────────────┼──────────────┤
│ 2  │Ahorro│ percent │ 20.00   │ 0.00       │ accumulative │
└────┴──────┴─────────┴─────────┴────────────┴──────────────┘

Diciembre:
- Ingresos: $5,000
- Asignado (20%): $1,000
- Gastado: $200
- Saldo dic: $1,000 - $200 = $800

Enero:
- Ingresos: $6,000
- Asignado (20%): $1,200
- Saldo anterior: $800 (acumulado)
- Total disponible: $1,200 + $800 = $2,000

Ventajas:
✓ Cálculo automático basado en ingreso real
✓ Ajustes dinámicos si cambia el ingreso
✓ Historial de cambios por mes
```

---

### Caso 3: Sincronizar Saldos Anteriores (Diciembre)

**Tu caso real:**
```
Tienes saldos de diciembre en el sistema anterior:
- Necesidades: $15,000 (50% del ingreso)
- Diversión: $3,500 (fijo)
- Ahorro: $8,200 (20% del ingreso)
- Emergencias: $2,000 (fijo)
Total: $28,700
```

**Cómo sincronizar en el nuevo sistema:**

```javascript
// Frontend: Llamar a crear los cantaros y aplicar ajustes

const jarrosACrear = [
  {
    name: "Necesidades",
    type: "percent",
    percent: 50,
    refresh_mode: "accumulative",
    initialAdjustment: 15000
  },
  {
    name: "Diversión",
    type: "fixed",
    fixed_amount: 3500,
    refresh_mode: "reset",
    initialAdjustment: 0
  },
  {
    name: "Ahorro",
    type: "percent",
    percent: 20,
    refresh_mode: "accumulative",
    initialAdjustment: 8200
  },
  {
    name: "Emergencias",
    type: "fixed",
    fixed_amount: 2000,
    refresh_mode: "accumulative",
    initialAdjustment: 2000
  }
]

// Crear cada jarro
for (const jar of jarrosACrear) {
  // 1. POST /jars (crear jarro)
  const nuevoJar = await crearJarro(jar)
  
  // 2. POST /adjust (aplicar saldo inicial)
  if (jar.initialAdjustment > 0) {
    await ajustarBalance(nuevoJar.id, jar.initialAdjustment, 
      "Saldo inicial sincronizado de diciembre")
  }
}
```

---

## Estados Visuales

### Tarjeta de Cantaro (Card Component)

```
┌────────────────────────────────────────────────────┐
│                                                    │
│  DIVERSIÓN                               ✏️ Editar │
│  Fixed - Reset                                    │
│                                                    │
│  ┌─────────────────────────────────────────────┐  │
│  │  Saldo Disponible: $424.50                  │  │
│  └─────────────────────────────────────────────┘  │
│                                                    │
│  Detalles:                                        │
│  • Asignado:    $500.00                          │
│  • Gastado:    -$125.50                          │
│  • Ajuste:      +$50.00                          │
│                                                    │
│  Buttons:                                         │
│  [+ Agregar]  [- Retirar]  [📋 Historial]       │
│                                                    │
└────────────────────────────────────────────────────┘
```

### Modal: Crear/Editar Ajuste

```
┌────────────────────────────────────────────┐
│  Ajustar Balance - Diversión               │
├────────────────────────────────────────────┤
│                                            │
│  Saldo Actual: $424.50                     │
│                                            │
│  Tipo de Ajuste:                           │
│  ◉ Agregar dinero                          │
│  ○ Retirar dinero                          │
│                                            │
│  Monto: [_____________] $                  │
│                                            │
│  Razón (opcional):                         │
│  [_____________________________]            │
│                                            │
│  Nuevo Saldo: $474.50 (si agrega $50)     │
│                                            │
│  [Cancelar]  [Guardar Ajuste]             │
│                                            │
└────────────────────────────────────────────┘
```

### Historial de Cambios (Timeline)

```
DICIEMBRE 2025
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

10 Dic | 10:30
  ➕ Agregó $100.00
  "Ahorro extra"
  $400.00 → $500.00

14 Dic | 07:15
  ➖ Retiró $50.00
  "Compra de regalo de cumpleaños"
  $424.50 → $374.50

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total Movimientos: 2
Neto: +$50.00
```

---

## Manejo de Errores

### Errores Comunes y Soluciones

#### 1. **Saldo Insuficiente**
```javascript
if (monto < 0 && Math.abs(monto) > jarActual.available_balance) {
  mostrarError({
    titulo: "Saldo Insuficiente",
    mensaje: `Solo tienes $${jarActual.available_balance} disponibles`,
    sugerencia: "Reduce el monto o agrega dinero al cantaro"
  })
}
```

**Visualización:**
```
❌ Error: Saldo Insuficiente

No puedes retirar $200.00 de este cantaro.
Saldo disponible: $150.00

¿Quieres:
□ Ajustar el monto a $150.00
□ Agregar dinero primero
□ Cancelar
```

---

#### 2. **Monto Inválido**
```javascript
if (!monto || monto === 0) {
  mostrarError("El monto debe ser mayor a $0")
}

if (monto < 0.01) {
  mostrarError("Monto mínimo: $0.01")
}
```

---

#### 3. **Cantaro No Encontrado**
```javascript
try {
  const jar = await obtenerJarro(jarId)
} catch (error) {
  if (error.status === 404) {
    mostrarError("Este cantaro no existe o fue eliminado")
    redirigir("/jars")
  }
}
```

---

## Guía de Implementación Paso a Paso

### Frontend: Componente de Cantaro

#### Step 1: Obtener datos del cantaro

```javascript
// JarDetail.vue o JarDetail.tsx

export default {
  data() {
    return {
      jar: null,
      balance: null,
      adjustments: [],
      loading: true,
      error: null
    }
  },
  
  async mounted() {
    try {
      // 1. Obtener cantaro y balance
      const { data: balanceData } = await axios.get(
        `/api/users/${this.userId}/jars/${this.jarId}/balance`,
        { headers: { 'Authorization': `Bearer ${this.token}` } }
      )
      
      this.balance = balanceData
      this.jar = balanceData.jar_data
      
      // 2. Obtener historial
      const { data: historyData } = await axios.get(
        `/api/users/${this.userId}/jars/${this.jarId}/adjustments`,
        { headers: { 'Authorization': `Bearer ${this.token}` } }
      )
      
      this.adjustments = historyData.data
      
    } catch (err) {
      this.error = err.response?.data?.message || "Error al cargar cantaro"
    } finally {
      this.loading = false
    }
  }
}
```

---

#### Step 2: Mostrar balance con detalles

```javascript
// Template
<template>
  <div v-if="!loading" class="jar-detail">
    
    <!-- Encabezado -->
    <h1>{{ jar.name }}</h1>
    <p class="badge">{{ jar.type }} • {{ jar.refresh_mode }}</p>
    
    <!-- Balance Principal -->
    <div class="balance-card">
      <div class="amount">{{ formatCurrency(balance.available_balance) }}</div>
      <div class="label">Saldo Disponible</div>
    </div>
    
    <!-- Breakdown -->
    <div class="breakdown">
      <div class="item">
        <span>Asignado:</span>
        <span>{{ formatCurrency(balance.allocated_amount) }}</span>
      </div>
      <div class="item negative">
        <span>Gastado:</span>
        <span>-{{ formatCurrency(balance.spent_amount) }}</span>
      </div>
      <div class="item" :class="{ positive: balance.adjustment > 0 }">
        <span>Ajuste:</span>
        <span>{{ balance.adjustment > 0 ? '+' : '' }}{{ formatCurrency(balance.adjustment) }}</span>
      </div>
    </div>
    
    <!-- Botones de Acción -->
    <div class="actions">
      <button @click="abrirAjuste('increment')" class="btn-add">
        ➕ Agregar Dinero
      </button>
      <button @click="abrirAjuste('decrement')" class="btn-remove">
        ➖ Retirar Dinero
      </button>
      <button @click="verHistorial()" class="btn-history">
        📋 Ver Historial
      </button>
    </div>
    
  </div>
</template>
```

---

#### Step 3: Modal de Ajuste

```javascript
// AdjustmentModal.vue

export default {
  props: {
    jar: Object,
    balance: Object,
    tipo: String // 'increment' o 'decrement'
  },
  
  data() {
    return {
      monto: null,
      razon: '',
      guardando: false,
      error: null
    }
  },
  
  computed: {
    nuevoSaldo() {
      if (!this.monto) return this.balance.available_balance
      const ajuste = this.tipo === 'increment' ? this.monto : -this.monto
      return this.balance.available_balance + ajuste
    },
    
    puedeGuardar() {
      // Validar
      if (!this.monto || this.monto <= 0) return false
      if (this.tipo === 'decrement' && this.monto > this.balance.available_balance) return false
      return true
    }
  },
  
  methods: {
    async guardarAjuste() {
      if (!this.puedeGuardar) return
      
      this.guardando = true
      this.error = null
      
      try {
        // Enviar ajuste
        const amount = this.tipo === 'increment' ? this.monto : -this.monto
        
        const { data } = await axios.post(
          `/api/users/${this.userId}/jars/${this.jar.id}/adjust`,
          {
            amount,
            reason: this.razon || null
          },
          { headers: { 'Authorization': `Bearer ${this.token}` } }
        )
        
        // Éxito
        this.$emit('ajuste-completado', data)
        this.$emit('cerrar')
        
      } catch (err) {
        this.error = err.response?.data?.message || "Error al guardar"
      } finally {
        this.guardando = false
      }
    },
    
    cerrar() {
      this.$emit('cerrar')
    }
  }
}
```

---

#### Step 4: Historial de Ajustes

```javascript
// AdjustmentHistory.vue

export default {
  props: {
    adjustments: Array
  },
  
  methods: {
    formatFecha(fecha) {
      return new Date(fecha).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'short'
      })
    },
    
    formatTipo(tipo) {
      return tipo === 'increment' ? '➕' : '➖'
    },
    
    tieneRazon(adjustment) {
      return adjustment.reason && adjustment.reason.trim() !== ''
    }
  }
}
```

```html
<template>
  <div class="adjustment-history">
    
    <h3>Historial de Ajustes</h3>
    
    <div v-if="!adjustments.length" class="empty">
      No hay ajustes registrados
    </div>
    
    <div v-else class="timeline">
      <div v-for="adj in adjustments" :key="adj.id" class="entry">
        
        <div class="date">
          {{ formatFecha(adj.adjustment_date) }}
        </div>
        
        <div class="content">
          <div class="header">
            <span class="icon">{{ formatTipo(adj.type) }}</span>
            <span class="amount" :class="adj.type">
              {{ adj.type === 'increment' ? '+' : '-' }}
              ${{ adj.amount.toFixed(2) }}
            </span>
          </div>
          
          <div v-if="tieneRazon(adj)" class="reason">
            {{ adj.reason }}
          </div>
          
          <div class="details">
            ${{ adj.previous_available.toFixed(2) }} 
            → 
            ${{ adj.new_available.toFixed(2) }}
          </div>
        </div>
      </div>
    </div>
    
  </div>
</template>
```

---

### Resumen de Flujo Completo

```
USUARIO EN FRONTEND
    ↓
[Ver Cantaro "Diversión"]
    ↓
API: GET /jars/{id}/balance
Backend: Calcula = $300 - $125.50 + $50 = $424.50
    ↓
[Mostrar Saldo: $424.50 con breakdown]
    ↓
[Usuario hace clic en "Retirar $50"]
    ↓
[Modal abre con validaciones]
    • Monto: $50 ✓
    • Saldo suficiente: $424.50 > $50 ✓
    • Nuevo saldo: $374.50
    ↓
[Usuario confirma y agrega razón: "Regalo de cumpleaños"]
    ↓
API: POST /jars/{id}/adjust
  body: { amount: -50, reason: "Regalo de cumpleaños" }
    ↓
Backend: 
  1. Calcula saldo anterior: $424.50
  2. Actualiza jar.adjustment (-= 50)
  3. Crea registro en jar_adjustments
  4. Calcula nuevo saldo: $374.50
  5. Retorna confirmación
    ↓
[Frontend actualiza lista de adjustments]
[Muestra toast: "Ajuste guardado"]
[Actualiza balance a $374.50]
```

---

## Decisiones de Diseño

### ¿Por Qué Este Cambio?

| Aspecto | ANTES | DESPUÉS | Ventaja |
|---------|-------|---------|---------|
| Cálculo | Pre-generado | Tiempo real | Flexibilidad |
| Historial | Minimal | Completo (quién, qué, cuándo, por qué) | Auditoría |
| Scheduler | Necesario (cron) | Ninguno | Simplicidad |
| Sincronización | Compleja | Simple (POST /adjust) | Facilidad |
| Actualización | Overnight | Instantánea | Experiencia |

---

## Tips para el Equipo Frontend

1. **Caching**: Cachea el balance durante 30 segundos para no hacer requests constantemente
2. **Optimismo**: Actualiza UI antes de confirmar con backend
3. **Validación**: Valida ANTES de enviar (frontend + backend)
4. **Feedback**: Muestra loading states durante requests
5. **Error Handling**: Siempre maneja casos de error gracefully
6. **Formatting**: Usa formatCurrency() para montos consistentemente
7. **Accessibility**: Asegura que los números sean accesibles para lectores de pantalla

---

## Conclusión

El nuevo sistema es más simple, flexible y auditable. El frontend solo necesita:

✅ Llamar a 4 endpoints  
✅ Validar inputs  
✅ Mostrar datos con clarity  
✅ Manejar errores gracefully  

**¡Listo para implementar!** 🚀
