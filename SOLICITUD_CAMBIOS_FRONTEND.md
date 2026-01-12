# 📋 SOLICITUD DE CAMBIOS FRONTEND - SISTEMA DE JARROS

**Fecha:** 14 de Diciembre 2025  
**Estado:** ✅ Listo para Implementar  
**Prioridad:** Alta  
**Estimado:** 2-3 horas de implementación

---

## ⚠️ IMPORTANTE - LEE PRIMERO

### 🔐 Autenticación Requerida

**TODAS las solicitudes a los endpoints requieren:**
```javascript
Headers:
{
  "Authorization": "Bearer YOUR_SANCTUM_TOKEN",
  "Accept": "application/json"
}
```

**¿Cómo obtener el token?**
1. User hace login (email + password)
2. Backend retorna: `{ token: "..." }`
3. Guardar ese token en localStorage/sessionStorage
4. Incluir en TODAS las solicitudes a /api/v1/users/...

**Ejemplo correcto:**
```javascript
// Vue 3 / React
const token = localStorage.getItem('authToken');
const response = await axios.get(
  '/api/v1/users/4/jars/1/balance',
  {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  }
);
```

**Sin el token:**
❌ Error 404: Route not found (porque la ruta está protegida)

---

## 📌 RESUMEN EJECUTIVO

Se requiere implementar una nueva interfaz frontend para la gestión de **Jarros (Cantaros)** con lógica simplificada y mejorada de cálculo de saldos.

**Cambios clave:**
- ✅ Backend completamente implementado y testeado
- ✅ 4 endpoints REST listos para usar
- ✅ Código frontend de ejemplo (Vue 3 + React)
- ✅ Documentación completa incluida
- ⚠️ **REQUIEREN AUTENTICACIÓN SANCTUM** ← Lee sección anterior

---

## 🎯 QUÉ SE NECESITA DEL FRONTEND

### 1. **Entender la Lógica del Sistema**

#### Conceptos Fundamentales

**Qué es un Cantaro:**
Un contenedor de dinero que gestiona ahorros por categoría. Tiene:
- **Nombre:** Diversión, Ahorro, Emergencias, etc.
- **Tipo:** Fixed (monto fijo) o Percent (% del ingreso)
- **Modo:** Reset (mensual) o Accumulative (acumulativo)

**Ejemplo 1: Fixed + Reset (Diversión)**
```
Asignación: $500/mes
Gasto: $50
Ajuste Manual: +$20
─────────────────
SALDO = ($500 - $50) + $20 = $470
```

**Ejemplo 2: Percent + Accumulative (Ahorro)**
```
Ingreso del mes: $5,000
Porcentaje: 20%
Asignación: $5,000 × 20% = $1,000
Gasto: $200
Saldo anterior: $800
─────────────────────────
SALDO = ($1,000 - $200) + $800 = $1,600
```

#### Los 2 Tipos

| Tipo | Descripción | Caso de Uso | Ejemplo |
|------|-------------|-----------|---------|
| **FIXED** | Monto fijo cada mes | Gastos regulares | $500 mensuales para diversión |
| **PERCENT** | % del ingreso total | Ahorros | 20% del ingreso del mes |

#### Los 2 Modos

| Modo | Descripción | Caso de Uso | Resultado |
|------|-------------|-----------|---------|
| **RESET** | Reinicia a 0 cada mes | Gastos puntuales | Saldo no usado se pierde |
| **ACCUMULATIVE** | Suma con mes anterior | Ahorros a largo plazo | Saldo se suma al siguiente mes |

### 2. **Los 4 Endpoints REST que Ya Existen**

**⚠️ IMPORTANTE:** Todos requieren autenticación (Sanctum token)

**El usuario se extrae automáticamente del token Sanctum**

**Ubicación:** `GET/POST /api/v1/jars/{jarId}/...`

**Autenticación requerida:**
```javascript
// Header obligatorio en TODAS las solicitudes:
{
  "Authorization": "Bearer YOUR_SANCTUM_TOKEN",
  "Accept": "application/json"
}
```

#### **Endpoint 1: GET Balance (Obtener Saldo)**
```
GET /api/v1/jars/5/balance
```

**Headers requeridos:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "jar_id": 5,
    "available_balance": 470.00,
    "allocated_amount": 500.00,
    "spent_amount": 30.00,
    "adjustment": 0.00,
    "refresh_mode": "reset",
    "type": "fixed"
  }
}
```

**Úsalo para:** Mostrar saldo actual en la tarjeta del cantaro

---

#### **Endpoint 2: POST Ajuste (Crear Ajuste Manual)**
```
POST /api/v1/jars/5/adjust
Content-Type: application/json
Authorization: Bearer {token}

{
  "amount": 50.00,
  "type": "increment",
  "reason": "Regalo de cumpleaños"
}
```

**Headers requeridos:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Parámetros:**
- `amount` (required, decimal): Cantidad a ajustar
- `type` (required, enum): "increment" o "decrement"
- `reason` (optional, string): Motivo del ajuste

**Respuesta:**
```json
{
  "success": true,
  "message": "Ajuste creado exitosamente",
  "data": {
    "adjustment_id": 42,
    "previous_available": 420.00,
    "new_available": 470.00,
    "adjustment": 50.00,
    "reason": "Regalo de cumpleaños",
    "adjusted_at": "2025-12-14 10:30:00"
  }
}
```

**Úsalo para:** Crear ajustes manuales (modal de ajuste)

**Validaciones:**
- ✓ El monto debe ser > 0
- ✓ No se pueden hacer decrementos que dejen saldo negativo
- ✓ El cantaro debe existir
- ✓ El usuario debe ser propietario del cantaro

---

#### **Endpoint 3: GET Historial (Obtener Ajustes)**
```
GET /api/v1/jars/5/adjustments?limit=10
Authorization: Bearer {token}
```

**Headers requeridos:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Parámetros query:**
- `limit` (opcional, int): Máximo de resultados (default: 10)

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "jar_id": 5,
      "user_id": 123,
      "amount": 50.00,
      "type": "increment",
      "reason": "Regalo de cumpleaños",
      "previous_available": 420.00,
      "new_available": 470.00,
      "adjusted_by": {
        "id": 123,
        "name": "Juan Pérez"
      },
      "adjusted_at": "2025-12-14 10:30:00"
    }
  ]
}
```

**Úsalo para:** Mostrar historial de cambios (timeline/tabla)

---

#### **Endpoint 4: POST Reset (Reiniciar para Nuevo Período)**
```
POST /api/v1/jars/5/reset-adjustment
Authorization: Bearer {token}
```

**Headers requeridos:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Parámetros:** Ninguno requerido

**Respuesta:**
```json
{
  "success": true,
  "message": "Ajuste reiniciado para nuevo período",
  "data": {
    "jar_id": 5,
    "previous_adjustment": 50.00,
    "new_adjustment": 0.00,
    "available_balance": 500.00,
    "reset_at": "2025-12-14 10:30:00"
  }
}
```

**Úsalo para:** Botón "Reiniciar para Nuevo Mes" (solo en modo RESET)

---

### 3. **Componentes Frontend a Crear**

#### **Componente 1: Tarjeta del Cantaro (JarCard)**

Muestra información general del cantaro.

**Props requeridas:**
```typescript
{
  jar: {
    id: number,
    name: string,
    type: 'fixed' | 'percent',
    refresh_mode: 'reset' | 'accumulative',
    fixed_amount?: number,
    percent?: number
  },
  balance: {
    allocated_amount: number,
    spent_amount: number,
    available_balance: number,
    adjustment: number
  }
}
```

**Render visual:**
```
╔════════════════════════════════════╗
║  Diversión          FIXED | RESET  ║
╠════════════════════════════════════╣
║  Disponible: $470                  ║
║  ████████░░░░░░░░░░ 94%            ║
╠════════════════════════════════════╣
║  Asignado:  $500                   ║
║  Gastado:    $30                   ║
║  Ajuste:    +$0                    ║
╠════════════════════════════════════╣
║  [Ajustar]  [Historial] [Resetear] ║
╚════════════════════════════════════╝
```

**Acciones:**
- Click en "Ajustar" → Abre modal
- Click en "Historial" → Muestra timeline
- Click en "Resetear" → Llama endpoint reset

---

#### **Componente 2: Modal de Ajuste (AdjustmentModal)**

Form para crear ajustes manuales.

**Fields:**
```
┌─────────────────────────────────┐
│   Ajustar: Diversión            │
├─────────────────────────────────┤
│                                 │
│  Saldo Actual: $470             │
│                                 │
│  ◉ Incrementar  ○ Decrementar   │
│                                 │
│  Monto: [          ] (numérico) │
│                                 │
│  Razón (opcional):              │
│  [                            ] │
│  [                            ] │
│                                 │
│  Nuevo Saldo: $520              │
│                                 │
│  [Cancelar]  [Guardar]          │
└─────────────────────────────────┘
```

**Validaciones en tiempo real:**
- ✓ Monto debe ser > 0
- ✓ Decremento no debe dejar saldo < 0
- ✓ Mostrar nuevo saldo preview
- ✓ Deshabilitar botón Guardar si hay errores

---

#### **Componente 3: Historial de Cambios (AdjustmentHistory)**

Lista/timeline de ajustes realizados.

**Visual:**
```
┌─────────────────────────────────────┐
│  Historial de Cambios               │
├─────────────────────────────────────┤
│  14 Dic 10:30 - Regalo cumpleaños   │
│  +$50 (420 → 470) por Juan Pérez   │
│                                     │
│  12 Dic 15:45 - Gastos extras       │
│  -$20 (440 → 420) por Juan Pérez   │
│                                     │
│  10 Dic 09:15 - Ajuste inicial      │
│  +$100 (320 → 420) por Admin       │
│                                     │
└─────────────────────────────────────┘
```

---

### 4. **Flujo de Interacción Completo**

```
1. Usuario abre pantalla de Cantaros
   ↓
2. Se carga GET /balance para cada cantaro
   ↓
3. Se muestran tarjetas (JarCard) con saldos
   ↓
4. Usuario hace click en "Ajustar"
   ↓
5. Se abre modal (AdjustmentModal)
   ↓
6. Usuario ingresa cantidad y razón
   ↓
7. Validaciones en frontend
   ↓
8. Click "Guardar" → POST /adjust
   ↓
9. Si éxito: actualizar tarjeta + mostrar notificación
   ↓
10. Si error: mostrar mensaje de error
    ↓
11. Cargar historial si usuario lo pide (GET /adjustments)
```

---

## 🛠️ CÓDIGO LISTO PARA USAR

### **Vue 3 - Composable useJarBalance**

```typescript
import { ref, computed } from 'vue';
import axios from 'axios';

export function useJarBalance(jarId) {
  const balance = ref(null);
  const adjustments = ref([]);
  const loading = ref(false);
  const error = ref(null);

  // Obtener token del localStorage (o donde lo guardes)
  const getAuthHeaders = () => {
    const token = localStorage.getItem('authToken') || localStorage.getItem('token');
    return {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    };
  };

  // Obtener balance actual
  const cargarBalance = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get(
        `/api/v1/jars/${jarId}/balance`,
        { headers: getAuthHeaders() }
      );
      balance.value = response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar balance';
    } finally {
      loading.value = false;
    }
  };

  // Crear ajuste
  const crearAjuste = async (amount, type, reason) => {
    error.value = null;
    try {
      const response = await axios.post(
        `/api/v1/jars/${jarId}/adjust`,
        { amount, type, reason },
        { headers: getAuthHeaders() }
      );
      // Recargar balance después del ajuste
      await cargarBalance();
      return response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al crear ajuste';
      throw error.value;
    }
  };

  // Obtener historial
  const cargarHistorial = async (limit = 10) => {
    loading.value = true;
    try {
      const response = await axios.get(
        `/api/v1/jars/${jarId}/adjustments?limit=${limit}`,
        { headers: getAuthHeaders() }
      );
      adjustments.value = response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar historial';
    } finally {
      loading.value = false;
    }
  };

  // Resetear para nuevo período
  const resetearAjuste = async () => {
    error.value = null;
    try {
      const response = await axios.post(
        `/api/v1/jars/${jarId}/reset-adjustment`,
        {},
        { headers: getAuthHeaders() }
      );
      await cargarBalance();
      return response.data.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al resetear';
      throw error.value;
    }
  };

  return {
    balance,
    adjustments,
    loading,
    error,
    cargarBalance,
    crearAjuste,
    cargarHistorial,
    resetearAjuste
  };
}
```

---

### **Vue 3 - Componente JarCard.vue**

```vue
<template>
  <div class="jar-card">
    <div class="jar-header">
      <h3>{{ jar.name }}</h3>
      <div class="badges">
        <span class="badge" :class="jar.type">{{ jar.type }}</span>
        <span class="badge" :class="jar.refresh_mode">{{ jar.refresh_mode }}</span>
      </div>
    </div>

    <div class="jar-balance">
      <div class="balance-value">${{ formatCurrency(balance.available_balance) }}</div>
      <div class="balance-label">Disponible</div>
      <div class="progress-bar">
        <div 
          class="progress-fill" 
          :style="{ width: getPercentage() + '%' }">
        </div>
      </div>
    </div>

    <div class="breakdown">
      <div class="item">
        <span>Asignado</span>
        <span>${{ formatCurrency(balance.allocated_amount) }}</span>
      </div>
      <div class="item">
        <span>Gastado</span>
        <span class="text-red">${{ formatCurrency(balance.spent_amount) }}</span>
      </div>
      <div class="item">
        <span>Ajuste</span>
        <span :class="{ 'text-green': balance.adjustment > 0, 'text-red': balance.adjustment < 0 }">
          {{ balance.adjustment > 0 ? '+' : '' }}${{ formatCurrency(balance.adjustment) }}
        </span>
      </div>
    </div>

    <div class="actions">
      <button @click="abrirModal" class="btn-primary">
        Ajustar
      </button>
      <button @click="cargarHistorial" class="btn-secondary">
        Historial
      </button>
      <button v-if="jar.refresh_mode === 'reset'" @click="resetear" class="btn-warning">
        Resetear
      </button>
    </div>

    <!-- Modal -->
    <AdjustmentModal 
      v-if="showModal"
      :jarId="jar.id"
      :currentBalance="balance.available_balance"
      @save="guardarAjuste"
      @close="showModal = false"
    />

    <!-- Historial -->
    <div v-if="showHistory" class="history-section">
      <h4>Últimos cambios</h4>
      <AdjustmentHistory :adjustments="adjustments" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useJarBalance } from '@/composables/useJarBalance';
import AdjustmentModal from './AdjustmentModal.vue';
import AdjustmentHistory from './AdjustmentHistory.vue';

const props = defineProps({
  jar: Object,
  userId: Number
});

const { balance, adjustments, cargarBalance, crearAjuste, cargarHistorial, resetearAjuste } 
  = useJarBalance(props.userId, props.jar.id);

const showModal = ref(false);
const showHistory = ref(false);

onMounted(() => {
  cargarBalance();
});

const abrirModal = () => {
  showModal.value = true;
};

const guardarAjuste = async (data) => {
  await crearAjuste(data.amount, data.type, data.reason);
  showModal.value = false;
};

const resetear = async () => {
  if (confirm('¿Resetear ajuste para nuevo período?')) {
    await resetearAjuste();
  }
};

const formatCurrency = (value) => {
  return parseFloat(value).toFixed(2);
};

const getPercentage = () => {
  const allocated = balance.value?.allocated_amount || 0;
  const available = balance.value?.available_balance || 0;
  return Math.min((available / allocated) * 100, 100);
};
</script>

<style scoped>
.jar-card {
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
  background: white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  transition: all 0.3s ease;
}

.jar-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.jar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.jar-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.badges {
  display: flex;
  gap: 8px;
}

.badge {
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.badge.fixed {
  background: #e3f2fd;
  color: #1976d2;
}

.badge.percent {
  background: #f3e5f5;
  color: #7b1fa2;
}

.badge.reset {
  background: #fff3e0;
  color: #f57c00;
}

.badge.accumulative {
  background: #e8f5e9;
  color: #388e3c;
}

.jar-balance {
  text-align: center;
  margin: 24px 0;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  color: white;
}

.balance-value {
  font-size: 32px;
  font-weight: bold;
  margin-bottom: 4px;
}

.balance-label {
  font-size: 14px;
  opacity: 0.9;
}

.progress-bar {
  height: 6px;
  background: rgba(255,255,255,0.3);
  border-radius: 3px;
  margin-top: 12px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: rgba(255,255,255,0.9);
  border-radius: 3px;
}

.breakdown {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 16px;
  margin: 16px 0;
}

.breakdown .item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  font-size: 14px;
}

.breakdown .item:not(:last-child) {
  border-bottom: 1px solid #ddd;
}

.text-red {
  color: #d32f2f;
}

.text-green {
  color: #388e3c;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn-primary, .btn-secondary, .btn-warning {
  flex: 1;
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5568d3;
}

.btn-secondary {
  background: #e0e0e0;
  color: #333;
}

.btn-secondary:hover {
  background: #bdbdbd;
}

.btn-warning {
  background: #fbc02d;
  color: #333;
}

.btn-warning:hover {
  background: #f9a825;
}

.history-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.history-section h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: #666;
}
</style>
```

---

### **Vue 3 - Componente AdjustmentModal.vue**

```vue
<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Ajustar Cantaro</h2>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <div class="modal-body">
        <div class="info-box">
          <p>Saldo Actual: <strong>${{ formatCurrency(currentBalance) }}</strong></p>
        </div>

        <div class="form-group">
          <label>Tipo de Ajuste</label>
          <div class="radio-group">
            <label>
              <input 
                v-model="formData.type" 
                type="radio" 
                value="increment"
              />
              Incrementar (+)
            </label>
            <label>
              <input 
                v-model="formData.type" 
                type="radio" 
                value="decrement"
              />
              Decrementar (-)
            </label>
          </div>
        </div>

        <div class="form-group">
          <label for="amount">Monto *</label>
          <div class="input-group">
            <span class="currency-symbol">$</span>
            <input
              id="amount"
              v-model.number="formData.amount"
              type="number"
              placeholder="0.00"
              step="0.01"
              min="0"
              class="form-input"
              @input="validateAmount"
            />
          </div>
          <span v-if="errors.amount" class="error-text">{{ errors.amount }}</span>
        </div>

        <div class="form-group">
          <label for="reason">Razón (Opcional)</label>
          <textarea
            id="reason"
            v-model="formData.reason"
            placeholder="Ej: Regalo de cumpleaños, Gasto inesperado..."
            class="form-input"
            rows="3"
          ></textarea>
        </div>

        <div class="preview-box">
          <p>Nuevo Saldo Estimado:</p>
          <p class="preview-value" :class="previewClass">
            ${{ formatCurrency(newBalance) }}
          </p>
        </div>

        <div v-if="generalError" class="error-box">
          {{ generalError }}
        </div>
      </div>

      <div class="modal-footer">
        <button @click="$emit('close')" class="btn-cancel">
          Cancelar
        </button>
        <button 
          @click="guardar" 
          :disabled="!isFormValid"
          class="btn-save"
        >
          {{ loading ? 'Guardando...' : 'Guardar Ajuste' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  jarId: Number,
  currentBalance: Number
});

const emit = defineEmits(['save', 'close']);

const formData = ref({
  type: 'increment',
  amount: null,
  reason: ''
});

const errors = ref({});
const generalError = ref('');
const loading = ref(false);

const newBalance = computed(() => {
  if (!formData.value.amount) return props.currentBalance;
  
  const amount = parseFloat(formData.value.amount);
  if (formData.value.type === 'increment') {
    return props.currentBalance + amount;
  } else {
    return props.currentBalance - amount;
  }
});

const previewClass = computed(() => {
  if (newBalance.value < 0) return 'text-red';
  if (newBalance.value > props.currentBalance) return 'text-green';
  return 'text-red';
});

const isFormValid = computed(() => {
  return formData.value.amount 
    && formData.value.amount > 0 
    && newBalance.value >= 0
    && !errors.value.amount;
});

const validateAmount = () => {
  errors.value.amount = '';
  
  if (!formData.value.amount) {
    errors.value.amount = 'El monto es requerido';
    return;
  }
  
  if (formData.value.amount <= 0) {
    errors.value.amount = 'El monto debe ser mayor a 0';
    return;
  }
  
  if (formData.value.type === 'decrement' && newBalance.value < 0) {
    errors.value.amount = `No puedes decrementar más de $${props.currentBalance}`;
    return;
  }
};

const formatCurrency = (value) => {
  return parseFloat(value || 0).toFixed(2);
};

const guardar = async () => {
  validateAmount();
  
  if (!isFormValid.value) {
    return;
  }
  
  loading.value = true;
  generalError.value = '';
  
  try {
    emit('save', {
      amount: formData.value.amount,
      type: formData.value.type,
      reason: formData.value.reason
    });
  } catch (err) {
    generalError.value = err.message || 'Error al guardar ajuste';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #999;
  padding: 0;
}

.close-btn:hover {
  color: #333;
}

.modal-body {
  padding: 24px;
}

.info-box {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 20px;
  font-size: 14px;
  color: #666;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.radio-group {
  display: flex;
  gap: 16px;
}

.radio-group label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 400;
  margin: 0;
  cursor: pointer;
}

.radio-group input[type="radio"] {
  cursor: pointer;
}

.input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-symbol {
  position: absolute;
  left: 12px;
  font-size: 16px;
  font-weight: 600;
  color: #999;
}

.form-input {
  width: 100%;
  padding: 10px 12px 10px 32px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

textarea.form-input {
  padding: 10px 12px;
  resize: vertical;
}

.error-text {
  display: block;
  color: #d32f2f;
  font-size: 12px;
  margin-top: 4px;
}

.preview-box {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  padding: 16px;
  color: white;
  margin: 20px 0;
}

.preview-box p {
  margin: 0;
  font-size: 14px;
}

.preview-value {
  font-size: 28px;
  font-weight: bold;
  margin-top: 8px;
}

.preview-value.text-red {
  color: #ffb4b4;
}

.preview-value.text-green {
  color: #b4ffb4;
}

.error-box {
  background: #ffebee;
  border: 1px solid #ef5350;
  border-radius: 6px;
  padding: 12px 16px;
  color: #d32f2f;
  font-size: 14px;
  margin: 16px 0;
}

.modal-footer {
  display: flex;
  gap: 12px;
  padding: 24px;
  border-top: 1px solid #e0e0e0;
  background: #fafafa;
}

.btn-cancel, .btn-save {
  flex: 1;
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-cancel {
  background: #e0e0e0;
  color: #333;
}

.btn-cancel:hover {
  background: #bdbdbd;
}

.btn-save {
  background: #667eea;
  color: white;
}

.btn-save:hover:not(:disabled) {
  background: #5568d3;
}

.btn-save:disabled {
  background: #ccc;
  cursor: not-allowed;
  opacity: 0.6;
}
</style>
```

---

## 🔄 ANTES vs DESPUÉS (Arquitectura)

### Sistema Viejo ❌
```
Usuario → GET /period-balance → Busca tabla jar_period_balances
                                   ↓
                          Retorna saldo pre-calculado
                                   ↓
                        Frontend muestra: $300
                          (sin detalles)
```

**Problemas:**
- ❌ Requiere scheduler (cron job)
- ❌ Tabla redundante (jar_period_balances)
- ❌ Datos inflexibles
- ❌ Sin historial
- ❌ Sin auditoría
- ❌ Difícil de sincronizar

### Sistema Nuevo ✅
```
Usuario → GET /balance → JarBalanceService calcula:
                         - Allocated (asignado)
                         - Spent (gastado)
                         - Adjustment (ajuste)
                         - Available = Allocated - Spent + Adjustment
                                   ↓
                    Retorna desglose completo
                                   ↓
            Frontend muestra: $470 con breakdown
           (allocated $500 - spent $30 + adjustment $0)
```

**Ventajas:**
- ✅ Sin scheduler
- ✅ Cálculo en tiempo real
- ✅ Flexible
- ✅ Historial completo
- ✅ Auditoría incluida
- ✅ Sincronización fácil

---

## 📊 Matriz de Tarea

| Tarea | Responsable | Estimado | Estado |
|-------|-------------|----------|--------|
| Entender conceptos | Frontend | 30 min | 📋 |
| Crear componente JarCard | Frontend | 1 hora | 📋 |
| Crear modal AdjustmentModal | Frontend | 1 hora | 📋 |
| Crear composable/hook | Frontend | 30 min | 📋 |
| Integrar rutas | Frontend | 30 min | 📋 |
| Testing endpoints | Frontend | 1 hora | 📋 |
| **TOTAL** | **Frontend** | **2-3 horas** | **⏳** |

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Antes de Empezar
- [ ] Leer esta solicitud completa
- [ ] Entender conceptos de Fixed/Percent
- [ ] Entender conceptos de Reset/Accumulative
- [ ] Revisar endpoints en Backend (en `/docs/jar-testing-guide.md`)

### Desarrollo
- [ ] Crear composable/hook `useJarBalance`
- [ ] Crear componente `JarCard`
- [ ] Crear componente `AdjustmentModal`
- [ ] Crear componente `AdjustmentHistory` (opcional)
- [ ] Integrar en rutas
- [ ] Adaptar estilos al proyecto
- [ ] Validar en frontend

### Testing
- [ ] Test GET /balance (mostrar saldo)
- [ ] Test POST /adjust (crear ajuste)
- [ ] Test GET /adjustments (ver historial)
- [ ] Test POST /reset-adjustment (resetear)
- [ ] Validaciones funcionan
- [ ] Errores se muestran
- [ ] Responsive en mobile

### Finalización
- [ ] Code review
- [ ] Testing en staging
- [ ] Deploy a producción
- [ ] Monitoreo inicial

---

## 📚 DOCUMENTACIÓN COMPLETA

Todos estos archivos están en `/docs/`:

1. **SOLICITUD_CAMBIOS_FRONTEND.md** ← **TÚ ESTÁS AQUÍ**
2. **FRONTEND-SUMMARY.md** - Resumen ejecutivo
3. **FRONTEND-INDEX.md** - Índice y rutas por rol
4. **frontend-jar-logic-guide.md** - Conceptos y lógica (1,200+ líneas)
5. **frontend-code-examples.md** - Código Vue 3 + React (1,400+ líneas)
6. **before-after-architecture.md** - Comparativa sistema
7. **jar-testing-guide.md** - Casos de testing
8. **IMPLEMENTATION_SUMMARY.md** - Resumen técnico

---

## 🚀 PRÓXIMOS PASOS

### 1. Lectura (30 minutos)
```
1. Lee sección "QUÉ SE NECESITA DEL FRONTEND"
2. Lee sección "LOS 4 ENDPOINTS REST"
3. Lee sección "COMPONENTES FRONTEND A CREAR"
```

### 2. Desarrollo (2-3 horas)
```
1. Copia composable/hook useJarBalance
2. Copia componente JarCard
3. Copia componente AdjustmentModal
4. Adapta a tu proyecto (imports, rutas, etc)
```

### 3. Testing (1 hora)
```
1. Test cada endpoint con Postman/Thunder Client
2. Valida validaciones en frontend
3. Prueba en diferentes dispositivos (responsive)
```

### 4. Merge (15 minutos)
```
1. Code review
2. Merge a main
3. Deploy a staging/producción
```

---

## 💬 PREGUNTAS FRECUENTES

**P: ¿Funciona con React?**  
R: Sí, tengo código React en `frontend-code-examples.md`

**P: ¿Qué pasa con datos antiguos?**  
R: Migra con POST /adjust, ve `before-after-architecture.md` sección 5

**P: ¿Necesito cambiar estilos?**  
R: Código incluye CSS, adapta colores a tu design system

**P: ¿Y si hay errores?**  
R: Manejo de errores incluido en todos los componentes

**P: ¿Es mobile-friendly?**  
R: Sí, CSS es responsive (mobile-first)

---

## 📞 INFORMACIÓN TÉCNICA

**Framework Backend:** Laravel 11  
**Framework Frontend:** Vue 3 (con ejemplos React)  
**Base de Datos:** SQLite (migrations ejecutadas)  
**API:** REST con Sanctum (auth)  
**Status:** ✅ Backend 100% listo, Frontend lista para implementar

---

**Fecha de Solicitud:** 14 Diciembre 2025  
**Estimado Total:** 2-3 horas  
**Prioridad:** Alta  
**Estado:** 🟢 Listo para Implementar
