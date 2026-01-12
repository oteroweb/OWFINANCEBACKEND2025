# 🐛 BUG FIX - SQL Ambiguity in Income Summary

**Fecha:** 25 Diciembre 2025  
**Severidad:** Alta  
**Estado:** ✅ RESUELTO

---

## 📋 DESCRIPCIÓN DEL BUG

### Problema
Error SQL de ambigüedad al ejecutar `GET /api/v1/jars/income-summary`:

```sql
-- ERROR ORIGINAL
WHERE "user_id" = 4
-- Ambiguo porque tanto item_transactions como categories tienen user_id
```

### Causa Raíz
En el endpoint `/jars/income-summary`, la consulta hace un JOIN entre las tablas `item_transactions` y `categories`. Ambas tablas tienen un campo `user_id`, lo que causa ambigüedad en la cláusula WHERE.

---

## ✅ SOLUCIÓN IMPLEMENTADA

### Archivos Corregidos

#### 1. JarIncomeController.php
**Ubicación:** `app/Http/Controllers/Api/JarIncomeController.php`

**ANTES:**
```php
$calculatedIncome = (float) ItemTransaction::where('user_id', $user->id)
    ->whereBetween('date', [$startOfMonth, $endOfMonth])
    ->sum('amount');

$breakdown = ItemTransaction::where('user_id', $user->id)
    ->whereBetween('date', [$startOfMonth, $endOfMonth])
    ->join('categories', 'item_transactions.category_id', '=', 'categories.id')
    ->sum('amount');
```

**DESPUÉS:**
```php
$calculatedIncome = (float) ItemTransaction::where('item_transactions.user_id', $user->id)
    ->whereBetween('item_transactions.date', [$startOfMonth, $endOfMonth])
    ->sum('item_transactions.amount');

$breakdown = ItemTransaction::where('item_transactions.user_id', $user->id)
    ->whereBetween('item_transactions.date', [$startOfMonth, $endOfMonth])
    ->join('categories', 'item_transactions.category_id', '=', 'categories.id')
    ->sum('item_transactions.amount');
```

#### 2. JarBalanceService.php
**Ubicación:** `app/Services/JarBalanceService.php`

**Método: `calculateUserIncome()`**

**ANTES:**
```php
$query = ItemTransaction::where('user_id', $jar->user_id)
    ->whereBetween('date', [$startOfMonth, $endOfMonth])
    ->whereIn('category_id', $baseCategoryIds)
    ->sum('amount');
```

**DESPUÉS:**
```php
$query = ItemTransaction::where('item_transactions.user_id', $jar->user_id)
    ->whereBetween('item_transactions.date', [$startOfMonth, $endOfMonth])
    ->whereIn('item_transactions.category_id', $baseCategoryIds)
    ->sum('item_transactions.amount');
```

**Método: `calculateSpentAmount()`**

**ANTES:**
```php
$query = ItemTransaction::whereBetween('created_at', [$startOfMonth, $endOfMonth])
    ->whereIn('category_id', $jar->categories()->pluck('id'))
    ->where('jar_id', $jar->id)
    ->sum('amount');
```

**DESPUÉS:**
```php
$query = ItemTransaction::whereBetween('item_transactions.created_at', [$startOfMonth, $endOfMonth])
    ->whereIn('item_transactions.category_id', $jar->categories()->pluck('id'))
    ->where('item_transactions.jar_id', $jar->id)
    ->sum('item_transactions.amount');
```

---

## 🔍 CAMBIOS ESPECÍFICOS

### SQL Generado ANTES (Con error)
```sql
SELECT SUM("amount") 
FROM "item_transactions"
LEFT JOIN "categories" ON "item_transactions"."category_id" = "categories"."id"
WHERE "user_id" = 4  -- ❌ AMBIGUO
  AND "date" BETWEEN '2025-01-01' AND '2025-01-31'
```

### SQL Generado DESPUÉS (Correcto)
```sql
SELECT SUM("item_transactions"."amount") 
FROM "item_transactions"
LEFT JOIN "categories" ON "item_transactions"."category_id" = "categories"."id"
WHERE "item_transactions"."user_id" = 4  -- ✅ EXPLÍCITO
  AND "item_transactions"."date" BETWEEN '2025-01-01' AND '2025-01-31'
```

---

## 🧪 TESTING

### Test Manual
```bash
# Endpoint corregido
curl -X GET "http://localhost:8000/api/v1/jars/income-summary" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Debe retornar 200 OK sin errores SQL
```

### Casos Probados
- ✅ Usuario con transacciones de ingreso
- ✅ Usuario sin transacciones
- ✅ Filtrado por mes específico
- ✅ Breakdown por categoría

---

## 📝 LECCIONES APRENDIDAS

### Best Practice: Siempre Especificar Tablas
Cuando se usan JOINs, **siempre** especificar el nombre de la tabla en:
- Cláusulas WHERE: `table.column`
- Cláusulas SELECT: `table.column`
- Cláusulas ORDER BY: `table.column`
- Funciones agregadas: `SUM(table.column)`

### Ejemplo Correcto
```php
// ✅ CORRECTO - Tabla explícita
ItemTransaction::where('item_transactions.user_id', $userId)
    ->join('categories', 'item_transactions.category_id', '=', 'categories.id')
    ->whereBetween('item_transactions.date', [$start, $end])
    ->sum('item_transactions.amount');

// ❌ INCORRECTO - Ambiguo con JOINs
ItemTransaction::where('user_id', $userId)
    ->join('categories', 'item_transactions.category_id', '=', 'categories.id')
    ->sum('amount');
```

---

## ✅ CHECKLIST DE VERIFICACIÓN

- [x] Corregido `JarIncomeController::getIncomeSummary()`
- [x] Corregido `JarBalanceService::calculateUserIncome()`
- [x] Corregido `JarBalanceService::calculateSpentAmount()`
- [x] Cachés limpiadas
- [x] Rutas regeneradas
- [x] Documentación actualizada

---

## 🚀 ESTADO FINAL

✅ **BUG RESUELTO** - El endpoint `/jars/income-summary` ahora funciona correctamente sin errores de ambigüedad SQL.

### Endpoints Afectados (Todos Corregidos)
- `GET /api/v1/jars/income-summary` ✅
- Cálculos internos de `JarBalanceService` ✅

---

**Fecha de resolución:** 25 Diciembre 2025  
**Tiempo de resolución:** ~15 minutos  
**Impacto:** Alto (afectaba funcionalidad principal)
