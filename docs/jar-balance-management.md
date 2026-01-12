# 🏺 Sistema de Gestión de Jarros Financieros

## Descripción General

Este documento describe la estrategia para gestionar saldos disponibles en jarros financieros con dos enfoques:
- **Acumulativo**: El saldo se suma mes a mes (p.ej., Ahorro)
- **Reset Mensual**: El saldo se reinicia cada mes (p.ej., Diversión)

**Fórmula Simple:**
```
Saldo Disponible = (Monto Asignado - Gastos) + Ajustes Manuales
```

---

## 📊 Estructura de Datos

### Tabla `jars` (nuevas columnas)

```sql
-- Ajuste manual al saldo disponible
adjustment DECIMAL(12,2) DEFAULT 0

-- Modo de refresh del jarro
refresh_mode ENUM('reset', 'accumulative') DEFAULT 'reset'
```

### Tabla `jar_adjustments` (nueva)

Registra el historial de ajustes manuales para auditoría:

```sql
id BIGINT PRIMARY KEY
jar_id BIGINT              -- Referencia al jarro
user_id BIGINT             -- Quién hizo el ajuste
amount DECIMAL(12,2)       -- Monto del ajuste
type ENUM('increment', 'decrement')  -- Tipo
reason TEXT                -- Por qué se hizo
previous_available DECIMAL -- Antes del ajuste
new_available DECIMAL      -- Después del ajuste
adjustment_date DATE       -- Fecha del ajuste
created_at TIMESTAMP
updated_at TIMESTAMP

INDEX (jar_id, adjustment_date)
INDEX (user_id, created_at)
```

---

## 🎯 Modos de Funcionamiento

### OPCIÓN 1: Acumulativo ✅ (Recomendado para Ahorros)

**Cada mes**, el saldo anterior se suma al nuevo monto asignado.

**Ejemplo**:
```
Diciembre:  Saldo: $500
Enero:      Nuevo: $500 → Total: $500 + $500 = $1,000
Febrero:    Nuevo: $500 → Total: $1,000 + $500 = $1,500
```

**Ventaja**: Ves el crecimiento acumulativo
**Desventaja**: Requiere control manual si quieres limitar

---

### OPCIÓN 2: Reset Mensual 🔄 (Para Gastos Mensuales)

**Cada mes**, el saldo se reinicia ignorando meses anteriores.

**Ejemplo**:
```
Diciembre:  Saldo: $300 (no gastaste todo)
Enero 1°:   Reset → Saldo: $500 (empieza de cero, $300 anterior se olvida)
Febrero 1°: Reset → Saldo: $500 (de nuevo de cero)
```

**Ventaja**: Control simple y predecible
**Desventaja**: Pierdes dinero no gastado

---

## 🚀 Cómo Usar

### 1. Configurar un Jarro (Acumulativo para Ahorros)

```bash
POST /api/v1/users/1/jars
{
  "name": "Ahorro",
  "type": "percent",
  "percent": 20,
  "refresh_mode": "accumulative"
}
```

---

### 2. Configurar un Jarro (Reset para Gastos Mensuales)

```bash
POST /api/v1/users/1/jars
{
  "name": "Diversión",
  "type": "fixed",
  "fixed_amount": 500,
  "refresh_mode": "reset"
}
```

---

### 3. Obtener Saldo Actual

```bash
GET /api/v1/users/1/jars/1/balance

Respuesta:
{
  "allocated_amount": 500.00,      # Monto asignado (fijo o %)
  "spent_amount": 150.00,          # Gastos del mes
  "adjustment": 100.00,            # Ajuste manual
  "available_balance": 450.00      # = 500 - 150 + 100
}
```

---

### 4. Ajustar Manualmente el Saldo (Sincronizar con otro sistema)

```bash
POST /api/v1/users/1/jars/1/adjust
{
  "amount": 15000,
  "reason": "Sincronización inicial desde sistema anterior",
  "date": "2024-12-01"
}
```

**El saldo ahora será:**
```
available_balance = 500 - 150 + 15100 = 15,450
```

---

### 5. Ver Historial de Ajustes

```bash
GET /api/v1/users/1/jars/1/adjustments

Respuesta:
[
  {
    "amount": 15000.00,
    "type": "increment",
    "reason": "Sincronización inicial desde sistema anterior",
    "date": "2024-12-01",
    "adjusted_by": "José Luis"
  }
]
```

---

### 6. Resetear Ajustes para Siguiente Período

Si el jarro está en modo **reset**, ejecuta esto al cambiar de mes:

```bash
POST /api/v1/users/1/jars/1/reset-adjustment
```

Esto limpia los ajustes, pero el historial persiste en `jar_adjustments`.

---

## ⏰ Cálculo Automático

**NO hay scheduler.** El sistema calcula el saldo **en tiempo real** cuando lo solicitas:

```bash
GET /api/v1/users/1/jars/1/balance
```

**Proceso:**
1. Obtiene monto asignado (fijo o % de ingresos)
2. Suma gastos del mes actual
3. Suma ajuste manual
4. Devuelve: `allocated - spent + adjustment`

---

## 💡 Caso de Uso: Sincronización Diciembre 2024

Tu sistema anterior tenía:
```
Necesidades: $15,000
Diversión:   $3,500
Ahorro:      $8,200
Emergencias: $2,000
Total:       $28,700
```

**Solución (5 minutos):**

```bash
# 1. Crear jarros
POST /api/v1/users/1/jars { "name": "Necesidades", "type": "percent", "percent": 50 }
POST /api/v1/users/1/jars { "name": "Diversión", "type": "fixed", "fixed_amount": 500 }
POST /api/v1/users/1/jars { "name": "Ahorro", "type": "percent", "percent": 20 }
POST /api/v1/users/1/jars { "name": "Emergencias", "type": "fixed", "fixed_amount": 1000 }

# 2. Sincronizar saldos
POST /api/v1/users/1/jars/1/adjust { "amount": 15000, "reason": "Saldo inicial" }
POST /api/v1/users/1/jars/2/adjust { "amount": 3500, "reason": "Saldo inicial" }
POST /api/v1/users/1/jars/3/adjust { "amount": 8200, "reason": "Saldo inicial" }
POST /api/v1/users/1/jars/4/adjust { "amount": 2000, "reason": "Saldo inicial" }

# 3. Listo! Sistema nuevo con $28,700 sincronizados
```

---

## ✅ Lo Más Importante

1. **Sin tablas complejas** - Solo 2 nuevas columnas en `jars`
2. **Sin scheduler automático** - Cálculo en tiempo real
3. **Sin pre-generación de períodos** - Se calcula al solicitar
4. **Auditoría completa** - Historial en `jar_adjustments`
5. **Ambos modos soportados** - Reset y acumulativo
6. **Flexible** - Ajustes manuales cuando necesites

---

## 📖 Documentación Adicional

- `docs/jar-balance-system.md` - Guía técnica completa
- `docs/jar-balance-visual.md` - Resumen visual
- `docs/jar-quick-reference.md` - Referencia rápida
- `docs/jar-testing-guide.md` - Casos de prueba

**Ejemplo**:
- Noviembre: Jarro Ahorro asignado $1,000, gastaste $200 → Disponible: $800
- Diciembre: Se suma $1,000 nuevamente → Disponible: $800 + $1,000 = $1,800
- Enero: Se suma $1,000 nuevamente → Disponible: $1,800 + $1,000 = $2,800

**Ventaja**: Ves el crecimiento del dinero
**Desventaja**: El dinero se acumula indefinidamente

---

### OPCIÓN 2: Reset Mensual 🔄

**Cada mes**, el jarro se reinicia con el monto configurado, ignorando meses anteriores.

**Ejemplo**:
- Noviembre: Jarro Diversión asignado $500, gastaste $200 → Disponible: $300
- Diciembre: Se resetea a $500 (el $300 anterior se ignora) → Disponible: $500
- Enero: Se resetea a $500 → Disponible: $500

**Ventaja**: Control simple y predecible
**Desventaja**: Pierdes dinero no gastado

---

## 🚀 Cómo Usar

### 1. Configurar un Jarro

Crear un jarro con modo acumulativo:
```bash
POST /api/v1/jars
{
  "name": "Ahorro",
  "type": "percent",
  "percent": 20,
  "base_scope": "all_income",
  "refresh_mode": "accumulative",  # NUEVA opción
  "refresh_day": 1                  # Refrescar el 1 de cada mes
}
```

O crear uno con reset mensual:
```bash
POST /api/v1/jars
{
  "name": "Diversión",
  "type": "fixed",
  "fixed_amount": 500,
  "refresh_mode": "reset",
  "refresh_day": 1
}
```

---

### 2. Obtener Saldo Actual de un Jarro

```bash
GET /api/v1/users/:userId/jars/:jarId/balance
```

**Respuesta**:
```json
{
  "status": "OK",
  "code": 200,
  "data": {
    "jar_id": 1,
    "jar_name": "Ahorro",
    "type": "percent",
    "refresh_mode": "accumulative",
    "allocated_amount": 2000.00,    # Lo asignado este mes
    "spent_amount": 450.75,         # Lo gastado
    "available_amount": 1549.25,    # Lo disponible
    "period_start": "2024-12-01",
    "period_end": "2024-12-31"
  }
}
```

---

### 3. Ajustar Manualmente el Saldo (Sincronizar con otro sistema)

Ideal para comenzar diciembre con saldos del sistema anterior:

```bash
POST /api/v1/users/:userId/jars/:jarId/force-balance
{
  "available_amount": 15000.50,
  "date": "2024-12-01",
  "reason": "Sincronización inicial del mes con sistema anterior"
}
```

**Respuesta**:
```json
{
  "status": "OK",
  "code": 200,
  "message": "Jar balance adjusted successfully",
  "data": {
    "jar_id": 1,
    "previous_available": 1549.25,
    "new_available": 15000.50,
    "reason": "Sincronización inicial del mes con sistema anterior",
    "updated_at": "2024-12-13T14:30:00Z"
  }
}
```

---

### 4. Refrescar Manualmente un Jarro

Si necesitas refrescar antes de la fecha automática:

```bash
POST /api/v1/users/:userId/jars/:jarId/refresh
```

**Respuesta**:
```json
{
  "status": "OK",
  "code": 200,
  "message": "Jar refreshed successfully",
  "data": {
    "jar_id": 1,
    "jar_name": "Ahorro",
    "refresh_mode": "accumulative",
    "allocated_amount": 2000.00,
    "available_amount": 3549.25,  # Saldo anterior + allocated
    "period": "2024-12-01"
  }
}
```

---

### 5. Refrescar Todos los Jarros de un Usuario

```bash
POST /api/v1/users/:userId/jars/refresh-all
```

**Respuesta**:
```json
{
  "status": "OK",
  "code": 200,
  "message": "All jars refreshed successfully",
  "data": {
    "refreshed_count": 5,
    "jars": [
      {
        "jar_id": 1,
        "jar_name": "Ahorro",
        "refresh_mode": "accumulative",
        "allocated_amount": 2000.00,
        "available_amount": 3549.25,
        "period": "2024-12-01"
      },
      ...
    ]
  }
}
```

---

## ⏰ Automatización

### Comando Artisan Manual

Puedes ejecutar el refresh manualmente en cualquier momento:

```bash
# Refrescar todos los usuarios
php artisan jars:refresh-balances

# Refrescar un usuario específico
php artisan jars:refresh-balances --user-id=5

# Refrescar para una fecha específica
php artisan jars:refresh-balances --date=2024-12-01

# Forzar refresh incluso si no es el día configurado
php artisan jars:refresh-balances --force
```

### Scheduler Automático

El comando se ejecuta **automáticamente todos los días a las 00:05 AM** gracias al scheduler en `app/Console/Kernel.php`.

Para usar el scheduler en producción:
```bash
# Ejecutar el scheduler en segundo plano
php artisan schedule:work

# O en cron job (añadir a tu crontab)
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## 💡 Caso de Uso: Sincronizar con Sistema Anterior (Diciembre 2024)

Tienes un sistema anterior con saldos de jarros que quieres sincronizar.

### Paso 1: Crear los Jarros Configurados

```bash
POST /api/v1/jars
{
  "name": "Necesidades",
  "type": "percent",
  "percent": 50,
  "refresh_mode": "accumulative"
}
```

### Paso 2: Ajustar el Saldo al Inicio del Mes

```bash
POST /api/v1/users/1/jars/1/force-balance
{
  "available_amount": 8500.00,
  "date": "2024-12-01",
  "reason": "Saldo inicial sincronizado desde sistema anterior"
}
```

### Paso 3: Registrar Gastos Normalmente

Las transacciones se restan automáticamente del saldo.

### Paso 4: Ver el Saldo en Tiempo Real

```bash
GET /api/v1/users/1/jars/1/balance
```

Verás el saldo disponible actualizado automáticamente conforme se registran gastos.

---

## 🔧 Implementación Técnica

### Flujo de Datos

```
┌─────────────────────────────────────┐
│  Jarro Configurado (name, type)     │
│  - type: 'fixed' | 'percent'        │
│  - refresh_mode: 'accumulative'...  │
└─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────┐
│  Scheduler Diario (00:05)           │
│  → RefreshJarBalances Command       │
└─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────┐
│  JarBalanceService                  │
│  → refreshJar() / refreshUserJars() │
└─────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────┐
│  jar_period_balances (Histórico)    │
│  - allocated_amount (calculado)     │
│  - spent_amount (del histórico)     │
│  - available_amount (allocated...)  │
└─────────────────────────────────────┘
```

### Cálculo del Saldo Disponible

**Para jarro tipo `percent`:**
```
allocated_amount = (ingresos_del_mes) * (percent / 100)
```

**Para jarro tipo `fixed`:**
```
allocated_amount = fixed_amount
```

**Saldo disponible:**
```
available_amount = allocated_amount - spent_amount

# Acumulativo:
available_amount = (saldo_anterior) + allocated_amount - spent_amount

# Reset:
available_amount = allocated_amount - spent_amount
```

---

## 📝 Notas Importantes

1. **El gasto se calcula automáticamente** desde `item_transactions` para el período
2. **El refresh se ejecuta diariamente** a menos que lo hagas manualmente
3. **Puedes forzar cualquier saldo** en cualquier momento con `/force-balance`
4. **El histórico se mantiene** en `jar_period_balances` para auditoría
5. **No hay límite de saldos negativos** - el sistema permite gastar más de lo disponible

---

## 🐛 Troubleshooting

### "El scheduler no se ejecuta automáticamente"
- Asegúrate de que tu servidor de producción ejecuta el cron job
- En desarrollo, usa `php artisan schedule:work`

### "El saldo no se actualiza después de crear una transacción"
- Las transacciones se restan automáticamente del saldo en la siguiente ejecución del scheduler
- O llama manualmente a `POST /api/v1/users/:userId/jars/:jarId/refresh`

### "Quiero cambiar el modo de un jarro"
```bash
PUT /api/v1/users/1/jars/1
{
  "refresh_mode": "reset"
}
```

---

## 🚀 Próximos Pasos

1. **Ejecutar la migración**:
   ```bash
   php artisan migrate
   ```

2. **Actualizar Jarros Existentes** (Opción):
   ```bash
   # Asignar modo por defecto a jarros existentes
   UPDATE jars SET refresh_mode = 'accumulative' WHERE refresh_mode IS NULL;
   ```

3. **Probar los Endpoints**:
   - Crear un jarro de prueba
   - Ajustar su saldo con `/force-balance`
   - Crear algunas transacciones
   - Verificar con `/balance`

4. **Activar el Scheduler** en producción
   - Agregar cron job
   - O usar `php artisan schedule:work` en desarrollo

---

## 📞 Preguntas Frecuentes

**¿Puedo tener jarros con diferente modo?**
Sí, algunos acumulativos y otros reset, sin problemas.

**¿Se pierden los datos históricos si cambio de modo?**
No, el histórico se mantiene en `jar_period_balances`.

**¿Puedo ver cuánto gasté en un período específico?**
Sí, consulta `jar_period_balances`:
```sql
SELECT * FROM jar_period_balances WHERE jar_id = 1 AND period_start = '2024-12-01';
```

**¿Qué pasa si tengo gastos sin asignar a ningún jarro?**
No afectan el cálculo de jarros. Solo se cuentan los gastos en `item_transactions` con `jar_id` asignado.
