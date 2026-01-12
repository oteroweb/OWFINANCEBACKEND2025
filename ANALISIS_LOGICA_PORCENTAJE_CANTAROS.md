# 📊 ANÁLISIS: LÓGICA DE PORCENTAJE EN CANTAROS

**Fecha:** 17 Diciembre 2025  
**Estado:** ✅ Analizado y Documentado  
**Problema:** Las categorías de ingresos no se asignan a los cantaros al calcular porcentaje

---

## 🔄 FLUJO ACTUAL DE CÁLCULO DE PORCENTAJE

### 1️⃣ **CREACIÓN DEL CANTARO**

```
POST /api/v1/jars
{
  "name": "Ahorro",
  "type": "percent",
  "percent": 20,  ← Usuario ingresa manualmente el 20%
  "base_scope": "all_income"
}
```

**En `JarController@save()`:**
```php
$payload['percent'] = $payload['percent'] ?? 0;  // Toma el valor ingresado (20)
$payload['type'] = 'percent';
$payload['base_scope'] = 'all_income';  // IMPORTANTE: Define el alcance
```

**Validación:**
- ✅ No puede exceder 100% (suma de todos los porcentajes del usuario)
- ✅ Debe estar entre 0 y 100
- ✅ Se valida en `JarController@save()` con `sumPercentForUser()`

---

### 2️⃣ **CÁLCULO DE BALANCE (Cuando se solicita)**

**En `JarBalanceService@getAvailableBalance()`:**

```php
public function getAvailableBalance(Jar $jar, ?Carbon $date = null): float
{
    // 1. Calcula cantidad asignada (según tipo)
    $allocatedAmount = $this->calculateAllocatedAmount($jar, $date);
    
    // 2. Calcula cantidad gastada
    $spentAmount = $this->calculateSpentAmount($jar, $date);
    
    // 3. Obtiene ajuste manual
    $adjustment = $jar->adjustment ?? 0;
    
    // 4. Fórmula: (asignado - gastado) + ajuste
    return $allocatedAmount - $spentAmount + $adjustment;
}
```

---

## 🔢 DETALLES DE CADA PASO

### **PASO 1: Calcular Cantidad Asignada (allocated_amount)**

```php
private function calculateAllocatedAmount(Jar $jar, Carbon $date): float
{
    if ($jar->type === 'percent') {
        // Obtiene TODOS los ingresos del usuario en el mes
        $income = $this->calculateUserIncome($jar->user_id, $date);
        
        // Aplica el porcentaje del cantaro
        return $income * ($jar->percent / 100);
    }
}
```

**Ejemplo:**
```
Usuario José:
├─ Ingreso enero: $5,000
├─ Cantaro "Ahorro" con 20%
└─ Cantidad Asignada = $5,000 × (20 / 100) = $1,000
```

### **PASO 2: Calcular Ingresos Totales (calculateUserIncome)**

```php
private function calculateUserIncome(int $userId, Carbon $date): float
{
    $startOfMonth = $date->clone()->startOfMonth();
    $endOfMonth = $date->clone()->endOfMonth();

    return (float) ItemTransaction::where('user_id', $userId)
        ->where('type', 'income')  ← Busca TODAS las transacciones de tipo 'income'
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
        ->sum('amount');
}
```

**Problema aquí:** ⚠️
- ✅ Busca transacciones de tipo `'income'`
- ❌ NO filtra por categoría de ingreso
- ❌ Suma TODOS los ingresos sin discriminar por categoría

---

### **PASO 3: Calcular Gasto (calculateSpentAmount)**

```php
private function calculateSpentAmount(Jar $jar, Carbon $date): float
{
    $startOfMonth = $date->clone()->startOfMonth();
    $endOfMonth = $date->clone()->endOfMonth();

    $query = ItemTransaction::whereBetween('created_at', [$startOfMonth, $endOfMonth]);

    // Si el cantaro tiene categorías asignadas, suma gastos de esas categorías
    if ($jar->categories()->exists()) {
        $query->whereIn('category_id', $jar->categories()->pluck('id'));
    } else {
        // Si no tiene categorías, busca por jar_id directo
        $query->where('jar_id', $jar->id);
    }

    return (float) $query->sum('amount');
}
```

**Ejemplo:**
```
Cantaro "Ahorro" tiene categorías asignadas:
├─ Ahorro Vivienda (category_id: 5)
└─ Ahorro Auto (category_id: 8)

Gastos en enero:
├─ $200 en Ahorro Vivienda → ✅ Suma
├─ $300 en Ahorro Auto → ✅ Suma
└─ $100 en Entretenimiento → ❌ No suma

Total Gasto = $500
```

---

## 🎯 EL PROBLEMA REAL

### **Problema Identificado**

Las categorías de **INGRESOS** no se están asignando a los cantaros cuando se calcula el porcentaje.

### **Escenario Actual (Incorrecto)**

```
Usuario José:
├─ Ingresos en enero:
│  ├─ Salario: $4,000 (categoría: Salario)
│  ├─ Freelance: $1,000 (categoría: Freelance)
│  └─ Total GENERAL = $5,000
│
├─ Cantaro "Ahorro" (20%)
│  ├─ Asignado = $5,000 × 20% = $1,000 ← De TODOS los ingresos
│  ├─ Categorías asignadas: Ahorro Vivienda, Ahorro Auto
│  ├─ Gasto: $300 (en esas categorías)
│  └─ Disponible = ($1,000 - $300) + 0 = $700
│
└─ Cantaro "Inversión" (30%)
   ├─ Asignado = $5,000 × 30% = $1,500 ← De TODOS los ingresos también
   ├─ Categorías asignadas: Fondos, Acciones
   ├─ Gasto: $500
   └─ Disponible = ($1,500 - $500) + 0 = $1,000
```

**Problema:** 
- Ambos cantaros usan el MISMO ingreso base ($5,000)
- No hay forma de decir "El Ahorro es del 20% de Salarios y el 10% de Freelance"
- El porcentaje es GENÉRICO para todos los ingresos

---

### **Escenario Deseado (Si hubiera discriminación por categoría)**

```
Usuario José:
├─ Ingresos en enero:
│  ├─ Salario: $4,000 (categoría: Salario)
│  ├─ Freelance: $1,000 (categoría: Freelance)
│
├─ Cantaro "Ahorro" (20% de Salario solamente)
│  ├─ Asignado = $4,000 × 20% = $800 ← Solo de Salario
│  ├─ Gasto: $300
│  └─ Disponible = $500
│
└─ Cantaro "Inversión" (50% de Freelance solamente)
   ├─ Asignado = $1,000 × 50% = $500 ← Solo de Freelance
   ├─ Gasto: $100
   └─ Disponible = $400
```

---

## 🔑 DETALLE CRÍTICO: COMPORTAMIENTO POR DEFECTO

**"Si NO pongo ingresos en los cantaros, ¿qué toma?"**

### Respuesta: Toma TODOS los valores positivos de INGRESOS

```
Opción A (Actual - Recomendado):
├─ Si NO se asignan categorías de ingreso:
│  └─ Toma TODOS los ItemTransactions con type = 'income'
│  
Opción B (Alternativa):
├─ Si NO se asignan categorías de ingreso:
│  └─ Toma TODOS los valores POSITIVOS de cualquier categoría
│  └─ (Porque los ingresos son números positivos)
```

**En la práctica:**

```
Cantaro "Ahorro" - 20%:
├─ base_scope: no asignado O "all_income"
├─ base_categories: vacío (sin categorías de ingreso)
│
└─ Resultado: Suma TODOS los ItemTransactions donde type = 'income'
   ├─ Salario: $4,000 ✅
   ├─ Freelance: $1,000 ✅
   ├─ Bonus: $500 ✅
   └─ Ingresos Totales = $5,500
   └─ Allocated = $5,500 × 20% = $1,100
```

### Diferencia clave:

| Escenario | Comportamiento |
|-----------|----------------|
| **Sin categorías de ingreso asignadas** | Suma TODOS los ingresos (defecto inteligente) |
| **Con categorías de ingreso asignadas** | Suma SOLO los de esas categorías (filtrado) |
| **Sin especificar base_scope** | Se considera como `all_income` (suma TODOS) |

### Flujo lógico recomendado:

```php
if ($jar->base_scope === 'categories') {
    // El usuario QUISO filtrar por categorías específicas
    if ($jar->baseCategories()->exists()) {
        // Hay categorías: usar solo esas
        $income = sumaIngresosDeCategorias($jar->baseCategories());
    } else {
        // No hay categorías pero base_scope = 'categories'
        // CASO AMBIGUO: opciones:
        // A) Lanzar excepción/error
        // B) Retornar 0 (no hay ingresos válidos)
        // C) Default a all_income (fallback seguro)
        // RECOMENDADO: Opción C (fallback a all_income)
    }
} else {
    // base_scope = 'all_income' o no está definido
    // Comportamiento por defecto: suma TODOS los ingresos
    $income = sumaAllIngresos();
}
```

---

## 📋 TABLA ACTUAL DE CAMPOS EN JARS

| Campo | Tipo | Propósito | Valor |
|-------|------|----------|-------|
| `id` | int | Identificador | Auto |
| `name` | string | Nombre del cantaro | "Ahorro" |
| `user_id` | int | Propietario | 1 |
| `type` | enum | Fixed o Percent | "percent" |
| `fixed_amount` | decimal | Para tipo Fixed | 500.00 |
| `percent` | decimal | Para tipo Percent | 20.5 |
| `base_scope` | enum | ⚠️ NO USADO | "all_income" |
| `refresh_mode` | enum | Reset o Accumulative | "reset" |
| `adjustment` | decimal | Ajuste manual | 50.00 |
| `active` | boolean | Está activo | true |
| `sort_order` | int | Orden de visualización | 1 |
| `color` | string | Color en UI | "#FF5733" |
| `date` | date | Fecha creación | 2025-01-15 |

**Campo problemático:**
- `base_scope` = "all_income" | "categories"
  - ✅ Está definido en el modelo
  - ❌ NO se utiliza en `calculateUserIncome()`
  - ❌ NO hay relación entre cantaros y categorías de ingresos

---

## 🔗 RELACIONES ACTUALES

```
Jar
├─ belongsToMany → Category (tabla: jar_category)
│  └─ Categorías de GASTO (dónde se gastó)
│
├─ baseCategories() 
│  └─ NO SE IMPLEMENTÓ
│
└─ NO HAY relación explícita con categorías de INGRESO
```

---

## 💡 SOLUCIONES POSIBLES

### **OPCIÓN 1: Implementar base_scope correctamente (RECOMENDADO)**

**Lógica (IMPORTANTE - Nuevo detalle):**

```
Si NO se asignan categorías de ingreso al cantaro:
├─ Tomar TODOS los ingresos (del tipo 'income' en ItemTransactions)
│  O
├─ Tomar TODOS los valores positivos de categorías de tipo 'income'
│
Si SE ASIGNAN categorías de ingreso (base_scope = 'categories'):
└─ Tomar SOLO los ingresos de esas categorías específicas
```

**Cambio en `calculateUserIncome()`:**

```php
private function calculateUserIncome(int $userId, Jar $jar, Carbon $date): float
{
    $startOfMonth = $date->clone()->startOfMonth();
    $endOfMonth = $date->clone()->endOfMonth();

    $query = ItemTransaction::where('user_id', $userId)
        ->where('type', 'income')
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

    // Si base_scope es 'categories' Y tiene categorías asignadas
    if ($jar->base_scope === 'categories' && $jar->baseCategories()->exists()) {
        // Filtrar SOLO por categorías base asignadas al cantaro
        $query->whereIn('category_id', $jar->baseCategories()->pluck('id'));
    } else if ($jar->base_scope === 'all_income') {
        // Tomar TODOS los ingresos (comportamiento por defecto)
        // No se agrega filtro adicional
    }
    // Si ni siquiera se asigna base_scope, también toma TODOS

    return (float) $query->sum('amount');
}
```

**Comportamiento:**

| base_scope | base_categories asignadas | Resultado |
|------------|---------------------------|-----------|
| `all_income` | (no importa) | Suma TODOS los ingresos |
| `categories` | ✅ SÍ | Suma SOLO ingresos de esas categorías |
| `categories` | ❌ NO | ⚠️ Ambiguo - opción: suma TODOS o suma 0 |
| null/sin asignar | (no importa) | Suma TODOS los ingresos |

**Recomendación:**
- Cuando `base_scope = 'categories'` pero NO hay categorías asignadas → Suma 0 (o lanza error)
- Cuando `base_scope = 'all_income'` → Suma TODOS los ingresos sin importar categoría
- Cuando no está asignado → Default a `all_income` (suma TODOS)

**Cambios necesarios:**
1. Agregar parámetro `Jar $jar` a `calculateUserIncome()`
2. Pasar el cantaro desde `calculateAllocatedAmount()`
3. Implementar lógica IF/ELSE para base_scope
4. Permitir asignar categorías de ingreso al crear/editar cantaros
5. En JarController: validar que si `base_scope = 'categories'` debe haber categorías

**Ventajas:**
- ✅ Solución elegante
- ✅ Reutiliza `base_scope` que ya existe
- ✅ Compatible con la estructura actual
- ✅ Flexible: por defecto toma TODOS, pero permite filtrar si se especifica
- ✅ Comportamiento intuitivo: "Si no digo nada, toma todo"

---

### **OPCIÓN 2: Nuevo campo base_categories_id**

```sql
ALTER TABLE jars ADD COLUMN base_categories_id INT DEFAULT NULL;
```

Pero esto es redundante con Option 1.

---

### **OPCIÓN 3: Tabla pivot jar_income_categories**

```
jar_income_categories
├─ jar_id (FK)
├─ category_id (FK → categorías de ingreso)
└─ created_at
```

Pero Option 1 con `baseCategories()` ya lo permite.

---

## 🔧 CAMBIOS DE CÓDIGO NECESARIOS

### **Cambio 1: Modelo Jar**
✅ Ya tiene el método `baseCategories()` definido

### **Cambio 2: JarBalanceService**

```diff
- private function calculateUserIncome(int $userId, Carbon $date): float
+ private function calculateUserIncome(int $userId, Jar $jar, Carbon $date): float
  {
      $startOfMonth = $date->clone()->startOfMonth();
      $endOfMonth = $date->clone()->endOfMonth();

      $query = ItemTransaction::where('user_id', $userId)
          ->where('type', 'income')
          ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
+     
+     // Si base_scope es 'categories', filtrar por categorías base
+     if ($jar->base_scope === 'categories' && $jar->baseCategories()->exists()) {
+         $query->whereIn('category_id', $jar->baseCategories()->pluck('id'));
+     }

      return (float) $query->sum('amount');
  }

  private function calculateAllocatedAmount(Jar $jar, Carbon $date): float
  {
      if ($jar->type === 'percent') {
-         $income = $this->calculateUserIncome($jar->user_id, $date);
+         $income = $this->calculateUserIncome($jar->user_id, $jar, $date);
          return $income * ($jar->percent / 100);
      }
  }
```

### **Cambio 3: JarController**

Para permitir asignar categorías base al crear/actualizar:

```diff
public function save(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:100',
        'type' => 'nullable|in:fixed,percent',
        'percent' => 'nullable|numeric|min:0|max:100',
        'base_scope' => 'nullable|in:all_income,categories',
+       'base_categories' => 'nullable|array',
+       'base_categories.*' => 'integer|exists:categories,id',
        // ...
    ]);
    
    // Al guardar:
    $jar = $this->jarRepo->store($payload);
    
+   // Si especificó categorías base, asignarlas
+   if ($request->filled('base_categories')) {
+       $jar->baseCategories()->sync($request->input('base_categories'));
+   }
}
```

---

## 📌 RESUMEN

| Aspecto | Estado |
|--------|--------|
| **Cómo se ingresa porcentaje** | Manualmente por usuario (campo en payload) |
| **Cómo se valida** | No puede exceder 100% en suma total |
| **Cómo se calcula ingreso base** | Suma TODOS los ingresos del usuario/mes |
| **Categorías de ingreso usadas** | ❌ NO (problema identificado) |
| **Categorías de gasto usadas** | ✅ SÍ (asignadas al cantaro) |
| **Campo base_scope existe** | ✅ SÍ (pero no implementado) |
| **Método baseCategories() existe** | ✅ SÍ (pero no conectado a balance) |
| **Solución recomendada** | Implementar `base_scope` en `calculateUserIncome()` |

---

## 🎯 COMPORTAMIENTO ESPERADO (Definitivo)

**Cuando NO hay categorías de ingreso asignadas:**
```
Cantaro con 20% de ingresos
├─ base_scope: 'all_income' o sin asignar
├─ base_categories: (vacío)
└─ Resultado: Toma TODOS los ingresos registrados
   └─ Allocated = Suma de todos los ItemTransactions(type='income') × 20%
```

**Cuando SÍ hay categorías de ingreso asignadas:**
```
Cantaro con 30% de ingresos (solo Freelance)
├─ base_scope: 'categories'
├─ base_categories: [Freelance]
└─ Resultado: Toma SOLO ingresos de Freelance
   └─ Allocated = Suma de ingresos Freelance × 30%
```

**Tabla de decisión:**

| base_scope | Categorías asignadas | Acción |
|-----------|--------|--------|
| `null` | N/A | ➡️ Default a 'all_income': suma TODOS |
| `'all_income'` | ✅ sí | ➡️ Ignora las categorías, suma TODOS |
| `'all_income'` | ❌ no | ➡️ Suma TODOS (normal) |
| `'categories'` | ✅ sí | ➡️ Suma SOLO esas categorías |
| `'categories'` | ❌ no | ➡️ ⚠️ Fallback a 'all_income' (suma TODOS) |

---

## 🎯 PRÓXIMOS PASOS

1. **Confirmar comportamiento deseado** con el usuario
2. **Implementar cambios** en JarBalanceService y JarController
3. **Crear migrations** para categorías base si no existen
4. **Actualizar tests** con casos de base_scope = 'categories'
5. **Documentar en API** el parámetro base_categories

---

**Documento creado:** 17 Diciembre 2025  
**Versión:** 1.0 - Análisis Inicial
