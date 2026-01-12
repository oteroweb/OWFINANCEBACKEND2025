# 🎯 RESUMEN PARA EL FRONTEND - Nuevos Endpoints Disponibles

**Fecha:** 25 Diciembre 2025  
**Backend:** OWFINANCEBackend2025  
**Estado:** ✅ LISTO PARA CONSUMIR

---

## 📡 NUEVOS ENDPOINTS

### 1️⃣ GET /api/v1/jars/income-summary

**URL:** `https://tu-api.com/api/v1/jars/income-summary`

**Propósito:** Obtener resumen de ingresos del mes (esperado vs real)

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (todos opcionales):**
```javascript
{
  month: '2025-01',      // Formato: YYYY-MM
  year: 2025,            // Año específico
  date: '2025-01-15'     // Fecha específica
}
```

**Ejemplo de uso (Axios):**
```javascript
const response = await axios.get('/api/v1/jars/income-summary', {
  params: { month: '2025-01' }
})

console.log(response.data)
// {
//   success: true,
//   data: {
//     expected_income: 5000.00,
//     calculated_income: 4200.00,
//     difference: -800.00,
//     difference_percentage: -16.00,
//     month: "2025-01",
//     breakdown: {
//       by_category: [
//         { category_id: 1, category_name: "Salario", amount: 4000.00 },
//         { category_id: 2, category_name: "Freelance", amount: 200.00 }
//       ]
//     }
//   }
// }
```

**¿Cuándo usar?:**
- Al cargar la página `/user/jars` (panel superior)
- Al cambiar de mes en el selector
- Después de crear/editar una transacción de ingreso

---

### 2️⃣ POST /api/v1/jars/{id}/adjust

**URL:** `https://tu-api.com/api/v1/jars/{id}/adjust`

**Propósito:** Aplicar ajuste manual al balance de un cántaro

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```javascript
{
  amount: 160.00,                               // Positivo = agregar, Negativo = restar
  description: "Compensar diferencia de ingreso" // Opcional
}
```

**Ejemplo de uso (Axios):**
```javascript
const response = await axios.post(`/api/v1/jars/${jarId}/adjust`, {
  amount: 160.00,
  description: 'Compensación manual'
})

console.log(response.data)
// {
//   success: true,
//   message: "Ajuste aplicado correctamente",
//   data: {
//     jar_id: 5,
//     jar_name: "Ahorro",
//     adjustment: 160.00,
//     previous_adjustment: 0.00,
//     balance: {
//       asignado: 840.00,
//       gastado: 300.00,
//       ajuste: 160.00,
//       balance: 700.00,
//       porcentaje_utilizado: 35.71
//     },
//     adjustment_record_id: 123
//   }
// }
```

**¿Cuándo usar?:**
- Cuando el usuario hace click en "Ajustar" en un cántaro
- Para compensar diferencias de ingreso
- Para redistribuir dinero entre cántaros

**Validaciones del backend:**
- ❌ Si el ajuste resulta en balance negativo → Error 400
- ❌ Si el cántaro no pertenece al usuario → Error 404

---

### 3️⃣ POST /api/v1/jars/{id}/adjust/reset

**URL:** `https://tu-api.com/api/v1/jars/{id}/adjust/reset`

**Propósito:** Resetear el ajuste manual del cántaro a 0

**Headers:**
```
Authorization: Bearer {token}
```

**Body:** (No requiere)

**Ejemplo de uso (Axios):**
```javascript
const response = await axios.post(`/api/v1/jars/${jarId}/adjust/reset`)

console.log(response.data)
// {
//   success: true,
//   message: "Ajuste reseteado correctamente",
//   data: {
//     jar_id: 5,
//     jar_name: "Ahorro",
//     adjustment: 0,
//     previous_adjustment: 160.00,
//     balance: { ... }
//   }
// }
```

**¿Cuándo usar?:**
- Botón "Resetear ajuste" en cada cántaro
- Al inicio de mes (si el usuario quiere limpiar ajustes)

---

## 🔄 ENDPOINTS ACTUALIZADOS

### 4️⃣ PUT /api/v1/user/profile (ACTUALIZADO)

**Cambio:** Ahora acepta el campo `monthly_income`

**Body (nuevo campo):**
```javascript
{
  name: "José Luis",
  email: "jose@example.com",
  monthly_income: 5000.00,  // ← NUEVO
  currency_id: 1
}
```

**Ejemplo de uso:**
```javascript
const response = await axios.put('/api/v1/user/profile', {
  ...formData,
  monthly_income: parseFloat(monthlyIncome)
})
```

**¿Cuándo usar?:**
- Cuando el usuario guarda su ingreso mensual en `/user/config`

---

### 5️⃣ POST /api/v1/jars (ACTUALIZADO)

**Cambio:** Ahora acepta `base_categories`

**Body (nuevos campos):**
```javascript
{
  name: "Ahorro Freelance",
  type: "percent",
  percent: 30,
  base_scope: "categories",        // ← NUEVO: 'all_income' | 'categories'
  base_categories: [5, 7],         // ← NUEVO: array de IDs de categorías
  categories: [10, 11, 12],        // Existente: categorías de gasto
  color: "#00FF00"
}
```

**Validación importante:**
```javascript
// Si base_scope = 'categories', base_categories NO puede estar vacío
if (baseScope === 'categories' && (!baseCategories || baseCategories.length === 0)) {
  // El backend devolverá error 422
  // Mensaje: "Debes seleccionar al menos una categoría de ingreso"
}
```

**¿Cuándo usar?:**
- Al crear un cántaro en `/user/jars`
- Mostrar selector de categorías solo si `base_scope === 'categories'`

---

### 6️⃣ PUT /api/v1/jars/{id} (ACTUALIZADO)

**Cambio:** Ahora acepta `base_categories`

**Body (igual que POST):**
```javascript
{
  name: "Ahorro General",
  percent: 25,
  base_scope: "all_income",
  base_categories: []  // Vacío si base_scope = 'all_income'
}
```

---

## 🎨 INTEGRACIÓN CON EL FRONTEND

### Componente: MonthlyIncomePanel.vue

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const incomeSummary = ref(null)

const fetchIncomeSummary = async () => {
  try {
    const response = await $fetch('/api/v1/jars/income-summary')
    incomeSummary.value = response.data
  } catch (error) {
    console.error('Error fetching income summary:', error)
  }
}

onMounted(() => {
  fetchIncomeSummary()
})
</script>

<template>
  <div v-if="incomeSummary">
    <div class="stat">
      <div class="stat-title">Ingreso Mensual</div>
      <div class="stat-value">${{ incomeSummary.expected_income }}</div>
    </div>
    <div class="stat">
      <div class="stat-title">Total Calculado</div>
      <div class="stat-value">${{ incomeSummary.calculated_income }}</div>
    </div>
    <div class="stat">
      <div class="stat-title">Diferencia</div>
      <div class="stat-value" :class="{
        'text-success': incomeSummary.difference >= 0,
        'text-error': incomeSummary.difference < 0
      }">
        ${{ incomeSummary.difference }}
      </div>
    </div>
  </div>
</template>
```

---

### Componente: JarCard.vue

```vue
<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  jar: Jar
  incomeSummary: any
}>()

const suggestedAmount = computed(() => {
  if (props.jar.type === 'percent') {
    const income = props.jar.base_scope === 'categories'
      ? calculateCategoryIncome()  // Calcular según categorías base
      : props.incomeSummary.calculated_income
    
    return income * (props.jar.percent / 100)
  }
  return props.jar.fixed_amount
})

const adjustJar = async (amount: number, description: string) => {
  try {
    const response = await $fetch(`/api/v1/jars/${props.jar.id}/adjust`, {
      method: 'POST',
      body: {
        amount,
        description
      }
    })
    
    // Actualizar el jar con los nuevos datos
    emit('jar-updated', response.data)
  } catch (error) {
    console.error('Error adjusting jar:', error)
  }
}

const resetAdjustment = async () => {
  try {
    const response = await $fetch(`/api/v1/jars/${props.jar.id}/adjust/reset`, {
      method: 'POST'
    })
    
    emit('jar-updated', response.data)
  } catch (error) {
    console.error('Error resetting adjustment:', error)
  }
}
</script>

<template>
  <div class="jar-card">
    <h3>{{ jar.name }}</h3>
    
    <div v-if="jar.type === 'percent'" class="suggestion">
      💡 Sugerido: ${{ suggestedAmount.toFixed(2) }}
    </div>
    
    <div class="balance">
      <div>Asignado: ${{ jar.balance.asignado }}</div>
      <div>Gastado: ${{ jar.balance.gastado }}</div>
      <div>Ajuste: ${{ jar.balance.ajuste }}</div>
      <div>Disponible: ${{ jar.balance.balance }}</div>
    </div>
    
    <button @click="showAdjustModal = true">Ajustar</button>
    <button @click="resetAdjustment">Resetear</button>
  </div>
</template>
```

---

## 🔐 AUTENTICACIÓN

Todos los endpoints requieren:

```javascript
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
}
```

El token se obtiene del `authStore` en Nuxt:

```javascript
const authStore = useAuthStore()
const token = authStore.token
```

---

## ⚠️ MANEJO DE ERRORES

### Error 400 - Validación
```javascript
{
  success: false,
  message: "Incorrect Params",
  errors: {
    amount: ["The amount field is required."]
  }
}
```

### Error 404 - No encontrado
```javascript
{
  success: false,
  message: "Jar not found or does not belong to you"
}
```

### Error 422 - Regla de negocio
```javascript
{
  success: false,
  message: "The adjustment would result in a negative balance",
  details: {
    current_balance: 100.00,
    requested_adjustment: -150.00,
    would_result_in: -50.00
  }
}
```

---

## 📋 CHECKLIST DE INTEGRACIÓN

### Para MonthlyIncomePanel
- [ ] Llamar a `GET /jars/income-summary` al montar el componente
- [ ] Mostrar expected_income, calculated_income, difference
- [ ] Aplicar colores según diferencia (positivo = verde, negativo = rojo)
- [ ] Actualizar al cambiar de mes

### Para JarCard
- [ ] Calcular sugerencia según tipo (percent vs fixed)
- [ ] Mostrar sugerencia solo si hay monthly_income configurado
- [ ] Botón "Ajustar" que llama a `POST /jars/{id}/adjust`
- [ ] Botón "Resetear" que llama a `POST /jars/{id}/adjust/reset`
- [ ] Actualizar balance después de ajustar

### Para Configuración de Usuario
- [ ] Campo de input para monthly_income
- [ ] Guardar en `PUT /user/profile`
- [ ] Validar que sea numérico >= 0

### Para Crear/Editar Jar
- [ ] Agregar campo `base_scope` (radio buttons: all_income | categories)
- [ ] Mostrar selector de categorías solo si base_scope = 'categories'
- [ ] Validar que haya categorías si base_scope = 'categories'
- [ ] Enviar `base_categories` en POST/PUT

---

## 🎉 ¡LISTO PARA USAR!

Todos los endpoints están:
- ✅ Implementados
- ✅ Probados (rutas registradas)
- ✅ Documentados
- ✅ Con validaciones
- ✅ Con manejo de errores

**El backend está esperando las llamadas del frontend.** 🚀

---

**Contacto:** Si tienes dudas sobre algún endpoint, revisa:
- `IMPLEMENTATION_COMPLETE.md` (detalle técnico completo)
- `BACKEND_SPECIFICATIONS.md` (especificaciones originales)
