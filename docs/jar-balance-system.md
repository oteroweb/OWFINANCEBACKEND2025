# Sistema de Saldo de Jarros (Cantaros) - Documentación Completa

## 🎯 Resumen Ejecutivo

El sistema de saldo de jarros calcula automáticamente el dinero disponible en cada jarro basándose en:
1. **Tipo de jarro** (fijo o porcentaje)
2. **Gastos** registrados en el período
3. **Ajustes manuales** (sincronización de sistemas anteriores, correcciones)
4. **Modo de refresco** (reset mensual o acumulativo)

**Fórmula Simple:**
```
Saldo Disponible = (Monto Asignado - Gastos) + Ajustes Manuales
```

---

## 📊 Conceptos Clave

### Tipo de Jarro

#### 1. **Fijo (Fixed)**
- Asigna el **mismo monto cada mes**
- Ejemplo: Jarro de Emergencias con $500 fijos

```
Enero:   $500 - $50 = $450 disponible
Febrero: $500 - $120 = $380 disponible
Marzo:   $500 - $30 = $470 disponible
```

#### 2. **Porcentaje (Percent)**
- Asigna un **% de los ingresos del mes**
- Ejemplo: Jarro Diversión con 10% de ingresos

```
Enero:   Ingresos: $1000 → 10% = $100
         Gastos: $60
         Disponible: $40

Febrero: Ingresos: $1200 → 10% = $120
         Gastos: $140
         Disponible: -$20 (EN ROJO)

Marzo:   Ingresos: $900 → 10% = $90
         Gastos: $30
         Disponible: $60
```

---

### Modo de Refresco

#### 1. **RESET (Mensual)**
Cada mes se **olvida el saldo anterior** y comienza de cero

```
ENERO (Jarro Fijo $500, RESET):
- Monto: $500
- Gastos: $420
- Disponible: $80

FEBRERO 1° (Se resetea):
- Monto: Se vuelve a $500 (fresco)
- Gastos (feb): $0
- Disponible: $500 ← El saldo de $80 se perdió
```

#### 2. **ACCUMULATIVE (Acumulativo)**
El saldo de meses anteriores **se suma** al nuevo período

```
ENERO (Jarro Fijo $500, ACUMULATIVO):
- Monto: $500
- Gastos: $420
- Disponible: $80

FEBRERO (Se acumula):
- Monto anterior: $80
- Nuevo monto: $500
- Total: $580
- Gastos (feb): $0
- Disponible: $580 ← Se mantiene el saldo anterior
```

---

### Ajustes Manuales

Un **ajuste manual** es un cambio al saldo **que no es gasto**. Se usa para:
- ✅ Sincronizar con sistema anterior
- ✅ Corregir errores
- ✅ Ajustar presupuestos en medio del mes
- ✅ Transferencias entre jarros

**Características:**
- Se registra en historial (auditoría completa)
- Puede ser positivo (incremento) o negativo (decremento)
- Aplica a partir de la fecha especificada

**Comportamiento:**
- **RESET:** El ajuste se olvida cuando llega nuevo período
- **ACUMULATIVO:** El ajuste se mantiene en el siguiente período

---

## 💻 Implementación de Base de Datos

### Cambios en tabla `jars`

```sql
ALTER TABLE jars ADD COLUMN adjustment DECIMAL(12,2) DEFAULT 0;
ALTER TABLE jars ADD COLUMN refresh_mode VARCHAR(20) DEFAULT 'reset';
```

### Nueva tabla `jar_adjustments`

```sql
CREATE TABLE jar_adjustments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    jar_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,           -- Cantidad del ajuste
    type ENUM('increment', 'decrement'),     -- Tipo
    reason TEXT,                              -- Por qué
    previous_available DECIMAL(12,2),        -- Antes del ajuste
    new_available DECIMAL(12,2),             -- Después del ajuste
    adjustment_date DATE NOT NULL,           -- Fecha del ajuste
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (jar_id) REFERENCES jars(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX (jar_id, adjustment_date),
    INDEX (user_id, created_at)
);
```

---

## 🔌 API Endpoints

### 1. GET Saldo Actual de un Jarro

**Endpoint:**
```
GET /api/v1/users/{userId}/jars/{jarId}/balance
```

**Query Parameters:**
- `date` (opcional): Fecha específica (formato: YYYY-MM-DD). Defecto: hoy

**Ejemplo:**
```bash
GET /api/v1/users/1/jars/5/balance?date=2025-01-15
```

**Respuesta (200 OK):**
```json
{
  "status": "OK",
  "code": 200,
  "data": {
    "jar_id": 5,
    "jar_name": "Diversión",
    "type": "fixed",
    "refresh_mode": "reset",
    "allocated_amount": 500.00,
    "spent_amount": 150.50,
    "adjustment": 0.00,
    "available_balance": 349.50,
    "period": {
      "month": "January 2025",
      "start": "2025-01-01",
      "end": "2025-01-31"
    }
  }
}
```

---

### 2. POST Ajustar Saldo Manual

**Endpoint:**
```
POST /api/v1/users/{userId}/jars/{jarId}/adjust
```

**Body:**
```json
{
  "amount": 150.50,
  "reason": "Sincronización desde sistema anterior",
  "date": "2025-01-15"
}
```

**Parámetros:**
- `amount` (requerido): Número positivo o negativo
  - Positivo: Incrementa el saldo
  - Negativo: Disminuye el saldo
- `reason` (opcional): Explicación del ajuste
- `date` (opcional): Fecha del ajuste (defecto: hoy)

**Ejemplo: Reducir saldo en $100**
```bash
POST /api/v1/users/1/jars/5/adjust
{
  "amount": -100,
  "reason": "Ajuste por gasto no registrado"
}
```

**Respuesta (200 OK):**
```json
{
  "status": "OK",
  "code": 200,
  "message": "Balance adjusted successfully",
  "data": {
    "jar_id": 5,
    "jar_name": "Diversión",
    "adjustment": {
      "id": 42,
      "amount": 100.00,
      "type": "decrement",
      "reason": "Ajuste por gasto no registrado",
      "previous_available": 349.50,
      "new_available": 249.50,
      "date": "2025-01-15"
    }
  }
}
```

---

### 3. GET Historial de Ajustes

**Endpoint:**
```
GET /api/v1/users/{userId}/jars/{jarId}/adjustments
```

**Query Parameters:**
- `from` (opcional): Fecha inicial (YYYY-MM-DD)
- `to` (opcional): Fecha final (YYYY-MM-DD)

**Ejemplo:**
```bash
GET /api/v1/users/1/jars/5/adjustments?from=2025-01-01&to=2025-01-31
```

**Respuesta (200 OK):**
```json
{
  "status": "OK",
  "code": 200,
  "data": [
    {
      "id": 42,
      "amount": 100.00,
      "type": "decrement",
      "reason": "Ajuste por gasto no registrado",
      "previous_available": 349.50,
      "new_available": 249.50,
      "date": "2025-01-15",
      "created_at": "2025-01-15 14:30:00",
      "adjusted_by": "José Luis"
    },
    {
      "id": 41,
      "amount": 500.00,
      "type": "increment",
      "reason": "Sincronización desde sistema anterior",
      "previous_available": -150.50,
      "new_available": 349.50,
      "date": "2025-01-01",
      "created_at": "2025-01-01 10:00:00",
      "adjusted_by": "Sistema"
    }
  ]
}
```

---

### 4. POST Resetear Ajustes para Siguiente Período

**Endpoint:**
```
POST /api/v1/users/{userId}/jars/{jarId}/reset-adjustment
```

**Uso:** Cuando llega fin de mes y modo es `reset`, ejecuta esto para limpiar ajustes

**Respuesta (200 OK):**
```json
{
  "status": "OK",
  "code": 200,
  "message": "Adjustment reset for next period",
  "data": {
    "jar_id": 5,
    "adjustment": 0.00,
    "refresh_mode": "reset"
  }
}
```

---

## 📋 Ejemplos de Casos Reales

### Caso 1: Sincronización Sistema Anterior (Diciembre 2024)

Tu sistema anterior tenía estos saldos para diciembre:

```
Jarro              Saldo
─────────────────────────
Necesidades       $15,000
Diversión         $3,500
Ahorro            $8,200
Emergencias       $2,000
```

**Proceso:**

1. **Crear jarros** (si no existen)
```bash
POST /api/v1/users/1/jars
{
  "name": "Necesidades",
  "type": "percent",
  "percent": 50,
  "refresh_mode": "accumulative"
}
```

2. **Ajustar cada jarro a saldo anterior**
```bash
POST /api/v1/users/1/jars/1/adjust
{
  "amount": 15000,
  "reason": "Saldo inicial sincronizado desde sistema anterior",
  "date": "2024-12-01"
}

POST /api/v1/users/1/jars/2/adjust
{
  "amount": 3500,
  "reason": "Saldo inicial sincronizado desde sistema anterior",
  "date": "2024-12-01"
}

POST /api/v1/users/1/jars/3/adjust
{
  "amount": 8200,
  "reason": "Saldo inicial sincronizado desde sistema anterior",
  "date": "2024-12-01"
}

POST /api/v1/users/1/jars/4/adjust
{
  "amount": 2000,
  "reason": "Saldo inicial sincronizado desde sistema anterior",
  "date": "2024-12-01"
}
```

3. **Verificar saldos**
```bash
GET /api/v1/users/1/jars/1/balance
# Respuesta muestra available_balance: 15000.00
```

---

### Caso 2: Jarro Fijo (Reset mensual)

**Configuración:**
- Jarro: Mantenimiento ($300 fijos, RESET)
- Categoría: Reparaciones

**Enero:**
```
GET /api/v1/users/1/jars/10/balance?date=2025-01-15

Respuesta:
- allocated_amount: 300.00    (monto fijo)
- spent_amount: 180.00        (gastos registrados)
- adjustment: 0.00
- available_balance: 120.00   (300 - 180 + 0)
```

**Enero 25:** Gasto adicional no esperado de $150 en reparación
```
POST /api/v1/users/1/jars/10/adjust
{
  "amount": -150,
  "reason": "Reparación de emergencia"
}

Ahora:
- available_balance: -30.00 (en rojo)
```

**Febrero 1°:** Se resetea
```
GET /api/v1/users/1/jars/10/balance?date=2025-02-01

Respuesta:
- allocated_amount: 300.00   (vuelve a 300)
- spent_amount: 0.00         (febrero es nuevo mes)
- adjustment: 0.00           (ajuste de enero se olvida)
- available_balance: 300.00  (empieza de cero)
```

---

### Caso 3: Jarro Porcentaje (Acumulativo)

**Configuración:**
- Jarro: Ahorro ($0 fijos, 20% de ingresos, ACUMULATIVO)

**Enero:**
```
Ingresos: $2000
Porcentaje: 20% = $400
Gastos en categorías de ahorro: $100
Adjustment: +$500 (deposito adicional)

GET /api/v1/users/1/jars/11/balance?date=2025-01-15

Respuesta:
- allocated_amount: 400.00
- spent_amount: 100.00
- adjustment: 500.00
- available_balance: 800.00   (400 - 100 + 500)
```

**Febrero (Acumulativo):**
```
Ingresos: $2500
Porcentaje: 20% = $500
Saldo anterior: $800

GET /api/v1/users/1/jars/11/balance?date=2025-02-15

Respuesta:
- allocated_amount: 500.00
- spent_amount: 50.00
- adjustment: 500.00         (se mantiene el ajuste anterior)
- available_balance: 1250.00 (500 - 50 + 500 + 300 acumulado)
                                         ↑ El 300 viene del saldo anterior
```

---

## 🔄 Flujo de Transacciones

Cuando registras una transacción (gasto), se actualiza automáticamente:

```
POST /api/v1/transactions
{
  "name": "Compra de abarrotes",
  "amount": 75.50,
  "items": [
    {
      "name": "Comida",
      "amount": 75.50,
      "category_id": 3,  // Categoría vinculada a Jarro Necesidades
      "jar_id": 1
    }
  ]
}
```

**Efecto:**
```
Antes:
GET /api/v1/users/1/jars/1/balance
- available_balance: 450.00

Después:
GET /api/v1/users/1/jars/1/balance
- available_balance: 374.50  (450 - 75.50)
```

---

## 📝 Casos de Uso de Ajustes

### ✅ Cuándo HACER un ajuste

1. **Sincronización de sistemas**
   - Migrar de contabilidad manual
   - Migrar de otra aplicación

2. **Correcciones**
   - Gasto registrado incorrectamente
   - Transacción duplicada
   - Error de monto

3. **Ajustes presupuestarios**
   - Transferencia entre jarros
   - Bonificación o devolución
   - Cambio de política a mitad de mes

### ❌ Cuándo NO hacer un ajuste

- ❌ Registrar un gasto regular (usa endpoint de transacciones)
- ❌ Cambiar el tipo de jarro (actualiza configuración)
- ❌ Cambiar el porcentaje (actualiza configuración)

---

## 🛠️ Configuración del Jarro

```json
{
  "id": 1,
  "name": "Necesidades",
  "type": "percent",           // "fixed" o "percent"
  "percent": 50,               // Si type = "percent"
  "fixed_amount": null,        // Si type = "fixed"
  "refresh_mode": "accumulative",  // "reset" o "accumulative"
  "adjustment": 0.00,          // Ajuste actual
  "categories": [3, 5, 7]      // Categorías vinculadas
}
```

---

## 🎓 Preguntas Frecuentes

### ¿El ajuste afecta los gastos históricos?
**No.** El ajuste es separado de los gastos. Los gastos se calculan aparte.

### ¿Puedo ajustar un jarro del mes pasado?
**Sí.** Usa `date` en el POST para especificar cuándo aplica el ajuste.

### ¿Se pierde el historial cuando reset?
**No.** El historial (`jar_adjustments`) siempre queda guardado. Solo el ajuste actual se resetea.

### ¿Qué pasa si gasto más que lo disponible?
El saldo se pone en **rojo (negativo)**. El sistema permite, pero es una alerta de que gastaste más de lo asignado.

### ¿Cómo sé cuáles son mis ingresos del mes?
Los ingresos se calculan de las transacciones marcadas como `type='income'` en tu cuenta.

### ¿Puedo tener ajustes diferentes cada mes?
**Sí.** Cada ajuste tiene su propia `adjustment_date`. En RESET, se olvidan. En ACUMULATIVO, se mantienen.

---

## 📊 Fórmula Técnica Completa

```
available_balance = allocated_amount - spent_amount + adjustment

Donde:

allocated_amount = 
  - Si type="fixed": fixed_amount
  - Si type="percent": (ingresos_del_mes * percent / 100)

spent_amount = 
  - Suma de todos los gastos del mes en las categorías del jarro

adjustment = 
  - Suma de ajustes manuales registrados
  - En RESET: se limpia a 0 cada período
  - En ACUMULATIVO: se mantiene y suma a períodos posteriores
```

---

## 🚀 Próximos Pasos

1. **Ejecutar migración**
   ```bash
   php artisan migrate
   ```

2. **Crear tus jarros** si no existen
   ```bash
   POST /api/v1/users/1/jars
   ```

3. **Sincronizar saldos anteriores**
   ```bash
   POST /api/v1/users/1/jars/{jarId}/adjust
   ```

4. **Empezar a registrar gastos normalmente**
   - Los saldos se actualizan automáticamente

5. **Monitorear cada mes**
   - GET /api/v1/users/1/jars/{jarId}/balance
   - Ver disponible vs. gastado
