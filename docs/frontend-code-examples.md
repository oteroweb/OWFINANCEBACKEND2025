# Frontend: Ejemplos de Código (Vue 3 & React)

## Vue 3 - Composición Completa

### 1. Composable: useJarBalance

```javascript
// composables/useJarBalance.js

import { ref, computed } from 'vue'
import axios from 'axios'

export function useJarBalance(userId, jarId, token) {
  const jar = ref(null)
  const balance = ref(null)
  const adjustments = ref([])
  const loading = ref(false)
  const error = ref(null)

  // Funciones
  const cargarBalance = async () => {
    loading.value = true
    error.value = null
    try {
      const { data } = await axios.get(
        `/api/users/${userId}/jars/${jarId}/balance`,
        { headers: { Authorization: `Bearer ${token}` } }
      )
      balance.value = data
      jar.value = data.jar_data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar'
    } finally {
      loading.value = false
    }
  }

  const cargarHistorial = async (desde = null, hasta = null) => {
    try {
      const params = new URLSearchParams()
      if (desde) params.append('from', desde)
      if (hasta) params.append('to', hasta)

      const { data } = await axios.get(
        `/api/users/${userId}/jars/${jarId}/adjustments?${params}`,
        { headers: { Authorization: `Bearer ${token}` } }
      )
      adjustments.value = data.data
    } catch (err) {
      error.value = 'Error al cargar historial'
    }
  }

  const crearAjuste = async (amount, reason = null) => {
    try {
      const { data } = await axios.post(
        `/api/users/${userId}/jars/${jarId}/adjust`,
        { amount, reason },
        { headers: { Authorization: `Bearer ${token}` } }
      )

      // Actualizar balance optimistamente
      balance.value.available_balance = data.new_available
      balance.value.adjustment = data.type === 'increment'
        ? balance.value.adjustment + data.amount
        : balance.value.adjustment - data.amount

      // Agregar al historial
      adjustments.value.unshift(data)

      return data
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al crear ajuste'
      throw err
    }
  }

  const resetearAjuste = async () => {
    try {
      const { data } = await axios.post(
        `/api/users/${userId}/jars/${jarId}/reset-adjustment`,
        {},
        { headers: { Authorization: `Bearer ${token}` } }
      )

      balance.value.adjustment = 0
      balance.value.available_balance = data.new_available_balance

      return data
    } catch (err) {
      error.value = 'Error al resetear ajuste'
      throw err
    }
  }

  // Computed
  const esAhorroLargo = computed(() =>
    jar.value?.type === 'percent' && jar.value?.refresh_mode === 'accumulative'
  )

  const esGastoMensual = computed(() =>
    jar.value?.type === 'fixed' && jar.value?.refresh_mode === 'reset'
  )

  const puedeRetirar = computed(() => (monto) => {
    if (!balance.value) return false
    return monto > 0 && monto <= balance.value.available_balance
  })

  return {
    // State
    jar,
    balance,
    adjustments,
    loading,
    error,

    // Methods
    cargarBalance,
    cargarHistorial,
    crearAjuste,
    resetearAjuste,

    // Computed
    esAhorroLargo,
    esGastoMensual,
    puedeRetirar
  }
}
```

---

### 2. Componente: JarCard.vue

```vue
<template>
  <div class="jar-card" :class="{ 'is-percent': jar?.type === 'percent' }">

    <!-- Header -->
    <div class="jar-header">
      <div>
        <h2>{{ jar?.name }}</h2>
        <span class="badge">
          {{ jar?.type === 'fixed' ? '💵 Fijo' : '📊 %Ingreso' }}
        </span>
        <span class="badge" :class="jar?.refresh_mode">
          {{ jar?.refresh_mode === 'reset' ? '🔄 Reset' : '📈 Acumulativo' }}
        </span>
      </div>
      <button @click="$emit('editar')" class="btn-edit">⚙️</button>
    </div>

    <!-- Balance Principal -->
    <div class="balance-display">
      <div class="amount">{{ formatCurrency(balance?.available_balance ?? 0) }}</div>
      <div class="label">Saldo Disponible</div>
    </div>

    <!-- Breakdown -->
    <div class="breakdown">
      <div class="breakdown-item">
        <span>Asignado</span>
        <span class="value">{{ formatCurrency(balance?.allocated_amount ?? 0) }}</span>
      </div>
      <div class="breakdown-item negative">
        <span>Gastado</span>
        <span class="value">-{{ formatCurrency(balance?.spent_amount ?? 0) }}</span>
      </div>
      <div class="breakdown-item" :class="{ positive: (balance?.adjustment ?? 0) > 0 }">
        <span>Ajuste Manual</span>
        <span class="value">
          {{ (balance?.adjustment ?? 0) > 0 ? '+' : '' }}
          {{ formatCurrency(balance?.adjustment ?? 0) }}
        </span>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-container">
      <div class="progress-bar">
        <div
          class="progress-fill"
          :style="{ width: percentualUso + '%' }"
        ></div>
      </div>
      <span class="progress-text">{{ percentualUso }}% utilizado</span>
    </div>

    <!-- Acciones -->
    <div class="actions">
      <button
        @click="$emit('ajuste', 'increment')"
        class="btn btn-add"
      >
        ➕ Agregar
      </button>
      <button
        @click="$emit('ajuste', 'decrement')"
        class="btn btn-remove"
        :disabled="!balance?.available_balance"
      >
        ➖ Retirar
      </button>
      <button
        @click="$emit('historial')"
        class="btn btn-history"
      >
        📋 Historial
      </button>
    </div>

    <!-- Status Message -->
    <div v-if="statusMessage" :class="['status', statusMessage.type]">
      {{ statusMessage.text }}
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  jar: Object,
  balance: Object,
  loading: Boolean,
  error: String
})

const emit = defineEmits(['editar', 'ajuste', 'historial'])

// Funciones
const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 2
  }).format(value)
}

// Computed
const percentualUso = computed(() => {
  if (!props.balance?.allocated_amount) return 0
  const uso = props.balance.allocated_amount - props.balance.available_balance
  return Math.round((uso / props.balance.allocated_amount) * 100)
})

const statusMessage = computed(() => {
  if (props.error) {
    return { type: 'error', text: props.error }
  }
  if (props.balance?.available_balance === 0) {
    return { type: 'warning', text: 'Saldo agotado' }
  }
  if (props.balance?.available_balance < (props.balance?.allocated_amount * 0.1)) {
    return { type: 'warning', text: 'Saldo bajo' }
  }
  return null
})
</script>

<style scoped>
.jar-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
  transition: all 0.3s ease;
}

.jar-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.jar-card.is-percent {
  border-left: 4px solid #3b82f6;
}

.jar-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 20px;
}

.jar-header h2 {
  margin: 0;
  font-size: 1.5rem;
  color: #1f2937;
}

.badge {
  display: inline-block;
  background: #f0f4f8;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  margin-right: 8px;
  margin-top: 8px;
  color: #4b5563;
}

.badge.accumulative {
  background: #dbeafe;
  color: #1e40af;
}

.badge.reset {
  background: #fef3c7;
  color: #92400e;
}

.btn-edit {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  opacity: 0.6;
  transition: opacity 0.2s;
}

.btn-edit:hover {
  opacity: 1;
}

/* Balance Display */
.balance-display {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 30px;
  border-radius: 8px;
  margin-bottom: 20px;
  text-align: center;
}

.amount {
  font-size: 2.5rem;
  font-weight: bold;
  margin-bottom: 8px;
}

.label {
  font-size: 0.875rem;
  opacity: 0.9;
}

/* Breakdown */
.breakdown {
  background: #f9fafb;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.875rem;
}

.breakdown-item:last-child {
  border-bottom: none;
}

.breakdown-item.negative .value {
  color: #dc2626;
}

.breakdown-item.positive .value {
  color: #16a34a;
}

/* Progress Bar */
.progress-container {
  margin-bottom: 20px;
}

.progress-bar {
  background: #e5e7eb;
  height: 8px;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  background: linear-gradient(90deg, #667eea, #764ba2);
  height: 100%;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.75rem;
  color: #6b7280;
}

/* Acciones */
.actions {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 10px;
  margin-bottom: 15px;
}

.btn {
  padding: 10px;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-add {
  background: #dcfce7;
  color: #166534;
}

.btn-add:hover {
  background: #bbf7d0;
}

.btn-remove {
  background: #fee2e2;
  color: #991b1b;
}

.btn-remove:hover:not(:disabled) {
  background: #fecaca;
}

.btn-remove:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-history {
  background: #e0e7ff;
  color: #3730a3;
}

.btn-history:hover {
  background: #c7d2fe;
}

/* Status Messages */
.status {
  padding: 12px;
  border-radius: 6px;
  font-size: 0.875rem;
  text-align: center;
}

.status.error {
  background: #fee2e2;
  color: #991b1b;
}

.status.warning {
  background: #fef3c7;
  color: #92400e;
}

.status.success {
  background: #dcfce7;
  color: #166534;
}
</style>
```

---

### 3. Componente: AdjustmentModal.vue

```vue
<template>
  <div v-if="mostrar" class="modal-overlay" @click.self="cerrar">
    <div class="modal-content">

      <div class="modal-header">
        <h3>Ajustar Balance - {{ jar?.name }}</h3>
        <button @click="cerrar" class="btn-close">✕</button>
      </div>

      <div class="modal-body">

        <!-- Info Actual -->
        <div class="info-box">
          <span>Saldo Actual:</span>
          <span class="amount">{{ formatCurrency(balance?.available_balance ?? 0) }}</span>
        </div>

        <!-- Tipo de Ajuste -->
        <div class="form-group">
          <label>Tipo de Ajuste</label>
          <div class="radio-group">
            <label class="radio-label">
              <input
                v-model="tipoAjuste"
                type="radio"
                value="increment"
              />
              <span>➕ Agregar Dinero</span>
            </label>
            <label class="radio-label">
              <input
                v-model="tipoAjuste"
                type="radio"
                value="decrement"
              />
              <span>➖ Retirar Dinero</span>
            </label>
          </div>
        </div>

        <!-- Monto -->
        <div class="form-group">
          <label for="monto">Monto</label>
          <div class="input-with-currency">
            <span class="currency">$</span>
            <input
              id="monto"
              v-model.number="monto"
              type="number"
              placeholder="0.00"
              step="0.01"
              min="0"
              @input="validar"
            />
          </div>
          <span v-if="errorMonto" class="error">{{ errorMonto }}</span>
        </div>

        <!-- Razón (Opcional) -->
        <div class="form-group">
          <label for="razon">Razón (Opcional)</label>
          <textarea
            id="razon"
            v-model="razon"
            placeholder="Ej: Compra de regalo, Ahorro extra, etc."
            rows="3"
          ></textarea>
        </div>

        <!-- Nuevo Saldo Simulado -->
        <div class="info-box success" v-if="nuevoSaldo !== null">
          <span>Nuevo Saldo:</span>
          <span class="amount">{{ formatCurrency(nuevoSaldo) }}</span>
        </div>

        <!-- Errores -->
        <div v-if="error" class="alert error">
          {{ error }}
        </div>

      </div>

      <div class="modal-footer">
        <button @click="cerrar" class="btn btn-secondary">Cancelar</button>
        <button
          @click="guardar"
          class="btn btn-primary"
          :disabled="!puedeGuardar || guardando"
        >
          <span v-if="!guardando">Guardar Ajuste</span>
          <span v-else>Guardando...</span>
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  mostrar: Boolean,
  jar: Object,
  balance: Object,
  token: String,
  userId: Number,
  jarId: Number
})

const emit = defineEmits(['cerrar', 'ajuste-guardado'])

// Data
const tipoAjuste = ref('increment')
const monto = ref(null)
const razon = ref('')
const guardando = ref(false)
const error = ref(null)
const errorMonto = ref(null)

// Funciones
const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 2
  }).format(value)
}

const validar = () => {
  errorMonto.value = null

  if (!monto.value || monto.value <= 0) {
    errorMonto.value = 'El monto debe ser mayor a $0'
    return
  }

  if (tipoAjuste.value === 'decrement' && monto.value > (props.balance?.available_balance ?? 0)) {
    errorMonto.value = `Monto máximo disponible: ${formatCurrency(props.balance?.available_balance ?? 0)}`
    return
  }
}

const guardar = async () => {
  validar()
  if (errorMonto.value) return

  guardando.value = true
  error.value = null

  try {
    const amount = tipoAjuste.value === 'increment' ? monto.value : -monto.value

    const response = await fetch(
      `/api/users/${props.userId}/jars/${props.jarId}/adjust`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${props.token}`
        },
        body: JSON.stringify({
          amount,
          reason: razon.value || null
        })
      }
    )

    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.message || 'Error al guardar')
    }

    const data = await response.json()
    emit('ajuste-guardado', data)
    cerrar()

  } catch (err) {
    error.value = err.message
  } finally {
    guardando.value = false
  }
}

const cerrar = () => {
  monto.value = null
  razon.value = ''
  error.value = null
  errorMonto.value = null
  emit('cerrar')
}

// Computed
const nuevoSaldo = computed(() => {
  if (!monto.value || !props.balance) return null
  const ajuste = tipoAjuste.value === 'increment' ? monto.value : -monto.value
  return props.balance.available_balance + ajuste
})

const puedeGuardar = computed(() => {
  if (!monto.value || monto.value <= 0) return false
  if (tipoAjuste.value === 'decrement' && monto.value > (props.balance?.available_balance ?? 0)) return false
  return true
})
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
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
}

.modal-body {
  padding: 20px;
}

.info-box {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 0.875rem;
}

.info-box.success {
  background: #dcfce7;
  color: #166534;
}

.amount {
  font-weight: bold;
  font-size: 1.125rem;
}

/* Forms */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.radio-group {
  display: flex;
  gap: 15px;
}

.radio-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 0.875rem;
}

.radio-label input {
  margin-right: 8px;
  cursor: pointer;
}

.input-with-currency {
  position: relative;
  display: flex;
  align-items: center;
}

.currency {
  position: absolute;
  left: 12px;
  font-weight: bold;
  color: #6b7280;
}

input[type="number"] {
  width: 100%;
  padding: 10px 10px 10px 32px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
}

input[type="number"]:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  font-family: inherit;
}

textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.error {
  color: #dc2626;
  font-size: 0.75rem;
  display: block;
  margin-top: 4px;
}

.alert {
  padding: 12px;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-bottom: 15px;
}

.alert.error {
  background: #fee2e2;
  color: #991b1b;
}

/* Footer */
.modal-footer {
  display: flex;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid #e5e7eb;
  justify-content: flex-end;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5568d3;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: #e5e7eb;
  color: #374151;
}

.btn-secondary:hover {
  background: #d1d5db;
}
</style>
```

---

## React - Hooks & Componentes Funcionales

### 1. Hook: useJarBalance

```javascript
// hooks/useJarBalance.js

import { useState, useEffect, useCallback } from 'react'
import axios from 'axios'

export const useJarBalance = (userId, jarId, token) => {
  const [jar, setJar] = useState(null)
  const [balance, setBalance] = useState(null)
  const [adjustments, setAdjustments] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  const cargarBalance = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const { data } = await axios.get(
        `/api/users/${userId}/jars/${jarId}/balance`,
        { headers: { Authorization: `Bearer ${token}` } }
      )
      setBalance(data)
      setJar(data.jar_data)
    } catch (err) {
      setError(err.response?.data?.message || 'Error al cargar')
    } finally {
      setLoading(false)
    }
  }, [userId, jarId, token])

  const cargarHistorial = useCallback(async (desde = null, hasta = null) => {
    try {
      const params = new URLSearchParams()
      if (desde) params.append('from', desde)
      if (hasta) params.append('to', hasta)

      const { data } = await axios.get(
        `/api/users/${userId}/jars/${jarId}/adjustments?${params}`,
        { headers: { Authorization: `Bearer ${token}` } }
      )
      setAdjustments(data.data)
    } catch (err) {
      setError('Error al cargar historial')
    }
  }, [userId, jarId, token])

  const crearAjuste = useCallback(async (amount, reason = null) => {
    try {
      const { data } = await axios.post(
        `/api/users/${userId}/jars/${jarId}/adjust`,
        { amount, reason },
        { headers: { Authorization: `Bearer ${token}` } }
      )

      // Actualizar optimistamente
      setBalance(prev => ({
        ...prev,
        available_balance: data.new_available,
        adjustment: data.type === 'increment'
          ? prev.adjustment + data.amount
          : prev.adjustment - data.amount
      }))

      setAdjustments(prev => [data, ...prev])

      return data
    } catch (err) {
      setError(err.response?.data?.message || 'Error al crear ajuste')
      throw err
    }
  }, [userId, jarId, token])

  const resetearAjuste = useCallback(async () => {
    try {
      const { data } = await axios.post(
        `/api/users/${userId}/jars/${jarId}/reset-adjustment`,
        {},
        { headers: { Authorization: `Bearer ${token}` } }
      )

      setBalance(prev => ({
        ...prev,
        adjustment: 0,
        available_balance: data.new_available_balance
      }))

      return data
    } catch (err) {
      setError('Error al resetear ajuste')
      throw err
    }
  }, [userId, jarId, token])

  useEffect(() => {
    cargarBalance()
    cargarHistorial()
  }, [cargarBalance, cargarHistorial])

  return {
    jar,
    balance,
    adjustments,
    loading,
    error,
    cargarBalance,
    cargarHistorial,
    crearAjuste,
    resetearAjuste
  }
}
```

---

### 2. Componente: JarCard.jsx

```jsx
// components/JarCard.jsx

import React from 'react'
import './JarCard.css'

export const JarCard = ({ jar, balance, loading, error, onEdit, onAdjust, onHistory }) => {

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-ES', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 2
    }).format(value ?? 0)
  }

  const calculateUsage = () => {
    if (!balance?.allocated_amount) return 0
    const used = balance.allocated_amount - balance.available_balance
    return Math.round((used / balance.allocated_amount) * 100)
  }

  const percentualUso = calculateUsage()

  const getStatusMessage = () => {
    if (error) return { type: 'error', text: error }
    if (balance?.available_balance === 0) return { type: 'warning', text: 'Saldo agotado' }
    if (balance?.available_balance < (balance?.allocated_amount * 0.1)) {
      return { type: 'warning', text: 'Saldo bajo' }
    }
    return null
  }

  const status = getStatusMessage()

  if (loading) {
    return <div className="jar-card loading">Cargando...</div>
  }

  return (
    <div className={`jar-card ${jar?.type === 'percent' ? 'is-percent' : ''}`}>

      {/* Header */}
      <div className="jar-header">
        <div>
          <h2>{jar?.name}</h2>
          <span className="badge">
            {jar?.type === 'fixed' ? '💵 Fijo' : '📊 %Ingreso'}
          </span>
          <span className={`badge ${jar?.refresh_mode}`}>
            {jar?.refresh_mode === 'reset' ? '🔄 Reset' : '📈 Acumulativo'}
          </span>
        </div>
        <button onClick={onEdit} className="btn-edit">⚙️</button>
      </div>

      {/* Balance Principal */}
      <div className="balance-display">
        <div className="amount">
          {formatCurrency(balance?.available_balance ?? 0)}
        </div>
        <div className="label">Saldo Disponible</div>
      </div>

      {/* Breakdown */}
      <div className="breakdown">
        <div className="breakdown-item">
          <span>Asignado</span>
          <span className="value">
            {formatCurrency(balance?.allocated_amount ?? 0)}
          </span>
        </div>
        <div className="breakdown-item negative">
          <span>Gastado</span>
          <span className="value">
            -{formatCurrency(balance?.spent_amount ?? 0)}
          </span>
        </div>
        <div className={`breakdown-item ${(balance?.adjustment ?? 0) > 0 ? 'positive' : ''}`}>
          <span>Ajuste Manual</span>
          <span className="value">
            {(balance?.adjustment ?? 0) > 0 ? '+' : ''}
            {formatCurrency(balance?.adjustment ?? 0)}
          </span>
        </div>
      </div>

      {/* Progress Bar */}
      <div className="progress-container">
        <div className="progress-bar">
          <div className="progress-fill" style={{ width: `${percentualUso}%` }} />
        </div>
        <span className="progress-text">{percentualUso}% utilizado</span>
      </div>

      {/* Acciones */}
      <div className="actions">
        <button onClick={() => onAdjust('increment')} className="btn btn-add">
          ➕ Agregar
        </button>
        <button
          onClick={() => onAdjust('decrement')}
          className="btn btn-remove"
          disabled={!balance?.available_balance}
        >
          ➖ Retirar
        </button>
        <button onClick={onHistory} className="btn btn-history">
          📋 Historial
        </button>
      </div>

      {/* Status Message */}
      {status && (
        <div className={`status ${status.type}`}>
          {status.text}
        </div>
      )}

    </div>
  )
}
```

---

### 3. Componente: AdjustmentModal.jsx

```jsx
// components/AdjustmentModal.jsx

import React, { useState, useEffect } from 'react'
import './AdjustmentModal.css'

export const AdjustmentModal = ({
  isOpen,
  jar,
  balance,
  onClose,
  onSave,
  token,
  userId,
  jarId
}) => {

  const [tipoAjuste, setTipoAjuste] = useState('increment')
  const [monto, setMonto] = useState('')
  const [razon, setRazon] = useState('')
  const [guardando, setGuardando] = useState(false)
  const [error, setError] = useState(null)
  const [errorMonto, setErrorMonto] = useState(null)

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-ES', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 2
    }).format(value ?? 0)
  }

  const validar = () => {
    setErrorMonto(null)

    if (!monto || monto <= 0) {
      setErrorMonto('El monto debe ser mayor a $0')
      return false
    }

    if (tipoAjuste === 'decrement' && monto > (balance?.available_balance ?? 0)) {
      setErrorMonto(
        `Monto máximo disponible: ${formatCurrency(balance?.available_balance ?? 0)}`
      )
      return false
    }

    return true
  }

  const handleGuardar = async (e) => {
    e.preventDefault()

    if (!validar()) return

    setGuardando(true)
    setError(null)

    try {
      const amount = tipoAjuste === 'increment' ? parseFloat(monto) : -parseFloat(monto)

      const response = await fetch(
        `/api/users/${userId}/jars/${jarId}/adjust`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({
            amount,
            reason: razon || null
          })
        }
      )

      if (!response.ok) {
        const data = await response.json()
        throw new Error(data.message || 'Error al guardar')
      }

      const data = await response.json()
      onSave(data)
      handleCerrar()

    } catch (err) {
      setError(err.message)
    } finally {
      setGuardando(false)
    }
  }

  const handleCerrar = () => {
    setMonto('')
    setRazon('')
    setError(null)
    setErrorMonto(null)
    onClose()
  }

  const nuevoSaldo = monto
    ? balance.available_balance + (tipoAjuste === 'increment' ? parseFloat(monto) : -parseFloat(monto))
    : null

  const puedeGuardar = monto && parseFloat(monto) > 0 &&
    !(tipoAjuste === 'decrement' && parseFloat(monto) > balance?.available_balance)

  if (!isOpen) return null

  return (
    <div className="modal-overlay" onClick={handleCerrar}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>

        <div className="modal-header">
          <h3>Ajustar Balance - {jar?.name}</h3>
          <button onClick={handleCerrar} className="btn-close">✕</button>
        </div>

        <form onSubmit={handleGuardar}>

          <div className="modal-body">

            {/* Info Actual */}
            <div className="info-box">
              <span>Saldo Actual:</span>
              <span className="amount">{formatCurrency(balance?.available_balance ?? 0)}</span>
            </div>

            {/* Tipo de Ajuste */}
            <div className="form-group">
              <label>Tipo de Ajuste</label>
              <div className="radio-group">
                <label className="radio-label">
                  <input
                    type="radio"
                    value="increment"
                    checked={tipoAjuste === 'increment'}
                    onChange={(e) => setTipoAjuste(e.target.value)}
                  />
                  <span>➕ Agregar Dinero</span>
                </label>
                <label className="radio-label">
                  <input
                    type="radio"
                    value="decrement"
                    checked={tipoAjuste === 'decrement'}
                    onChange={(e) => setTipoAjuste(e.target.value)}
                  />
                  <span>➖ Retirar Dinero</span>
                </label>
              </div>
            </div>

            {/* Monto */}
            <div className="form-group">
              <label htmlFor="monto">Monto</label>
              <div className="input-with-currency">
                <span className="currency">$</span>
                <input
                  id="monto"
                  type="number"
                  placeholder="0.00"
                  step="0.01"
                  min="0"
                  value={monto}
                  onChange={(e) => setMonto(e.target.value)}
                />
              </div>
              {errorMonto && <span className="error">{errorMonto}</span>}
            </div>

            {/* Razón */}
            <div className="form-group">
              <label htmlFor="razon">Razón (Opcional)</label>
              <textarea
                id="razon"
                placeholder="Ej: Compra de regalo, Ahorro extra, etc."
                rows="3"
                value={razon}
                onChange={(e) => setRazon(e.target.value)}
              />
            </div>

            {/* Nuevo Saldo */}
            {nuevoSaldo !== null && (
              <div className="info-box success">
                <span>Nuevo Saldo:</span>
                <span className="amount">{formatCurrency(nuevoSaldo)}</span>
              </div>
            )}

            {/* Errores */}
            {error && <div className="alert error">{error}</div>}

          </div>

          <div className="modal-footer">
            <button type="button" onClick={handleCerrar} className="btn btn-secondary">
              Cancelar
            </button>
            <button
              type="submit"
              className="btn btn-primary"
              disabled={!puedeGuardar || guardando}
            >
              {guardando ? 'Guardando...' : 'Guardar Ajuste'}
            </button>
          </div>

        </form>

      </div>
    </div>
  )
}
```

---

## Conclusión

Con estos componentes y hooks, tu frontend está listo para:

✅ Mostrar balances en tiempo real
✅ Crear y gestionar ajustes
✅ Ver historial completo
✅ Validar datos antes de enviar
✅ Manejar errores gracefully

**¡Implementación lista!** 🚀
