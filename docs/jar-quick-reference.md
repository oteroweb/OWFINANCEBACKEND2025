# 📚 Sistema de Jarros - Referencia Rápida

## 🚀 Inicio Rápido (5 minutos)

### 1. Ejecutar Migración
```bash
php artisan migrate
```

### 2. Ver Saldo de un Jarro
```bash
GET /api/v1/users/1/jars/1/balance
```

**Respuesta:**
```json
{
  "allocated_amount": 500.00,
  "spent_amount": 150.00,
  "adjustment": 0.00,
  "available_balance": 350.00
}
```

### 3. Ajustar Saldo (Sincronizar Sistema Anterior)
```bash
POST /api/v1/users/1/jars/1/adjust
{
  "amount": 15000,
  "reason": "Saldo inicial sincronizado"
}
```

---

## 🎯 Fórmula Principal

```
Available = (Allocated - Spent) + Adjustment
```

| Componente | Tipo | Ejemplo |
|-----------|------|---------|
| **Allocated** | Fijo o % | $500 o (10% de $2000 = $200) |
| **Spent** | Suma de gastos | $150 (del mes actual) |
| **Adjustment** | Manual | +$100 o -$50 |

---

## 🔌 API Endpoints

### GET Saldo
```bash
GET /api/v1/users/{userId}/jars/{jarId}/balance?date=2025-01-15
```

### POST Ajustar
```bash
POST /api/v1/users/{userId}/jars/{jarId}/adjust
{
  "amount": 100,              # Positivo o negativo
  "reason": "Sincronización",
  "date": "2025-01-15"        # Opcional
}
```

### GET Historial de Ajustes
```bash
GET /api/v1/users/{userId}/jars/{jarId}/adjustments?from=2025-01-01&to=2025-01-31
```

### POST Resetear Ajuste (para nuevo período)
```bash
POST /api/v1/users/{userId}/jars/{jarId}/reset-adjustment
```

---

## 📊 Tipos de Jarro

### Fixed (Fijo)
```json
{
  "type": "fixed",
  "fixed_amount": 500.00
}
```
Asigna **$500 cada mes**

### Percent (Porcentaje)
```json
{
  "type": "percent",
  "percent": 20
}
```
Asigna **20% de los ingresos del mes**

---

## 🔄 Modo de Refresco

### Reset
```json
{
  "refresh_mode": "reset"
}
```
**Cada mes comienza de cero.**
```
Enero:   Disponible $350
Febrero: Disponible $500 (se olvida el $350)
```

### Accumulative
```json
{
  "refresh_mode": "accumulative"
}
```
**Los saldos se mantienen.**
```
Enero:   Disponible $350
Febrero: Disponible $350 + $500 = $850
```

---

## 💡 Casos de Uso

### Sincronización Sistema Anterior (Diciembre 2024)

Tenías en otro sistema:
```
Necesidades: $15,000
Diversión:   $3,500
Ahorro:      $8,200
Emergencias: $2,000
```

**Solución:**
```bash
# Ajustar cada jarro
POST /api/v1/users/1/jars/1/adjust
{ "amount": 15000, "reason": "Saldo inicial" }

POST /api/v1/users/1/jars/2/adjust
{ "amount": 3500, "reason": "Saldo inicial" }

POST /api/v1/users/1/jars/3/adjust
{ "amount": 8200, "reason": "Saldo inicial" }

POST /api/v1/users/1/jars/4/adjust
{ "amount": 2000, "reason": "Saldo inicial" }
```

### Corregir Gasto Mal Asignado

Gastaste $100 que no debía estar en este jarro:

```bash
POST /api/v1/users/1/jars/1/adjust
{ "amount": -100, "reason": "Corrección: gasto fue en otro jarro" }
```

### Ver Historial de Cambios

```bash
GET /api/v1/users/1/jars/1/adjustments

Respuesta:
[
  {
    "amount": 100.00,
    "type": "decrement",
    "reason": "Corrección: gasto fue en otro jarro",
    "date": "2025-01-15",
    "adjusted_by": "José Luis"
  },
  {
    "amount": 15000.00,
    "type": "increment",
    "reason": "Saldo inicial",
    "date": "2025-01-01",
    "adjusted_by": "Sistema"
  }
]
```

---

## ✅ Lo más importante

> El sistema **calcula automáticamente** el saldo disponible cada vez que lo pides, basándose en:
> 1. El monto asignado (fijo o %)
> 2. Los gastos del mes
> 3. Los ajustes manuales

> **No necesitas pre-generar períodos, ni scheduler automático, ni tablas complejas.**
> 
> Es simple: `Available = (Allocated - Spent) + Adjustment`

---

## 🛠️ Checklist Implementación

- [x] Migración de base de datos
- [x] Modelo JarAdjustment
- [x] Servicio JarBalanceService
- [x] Controlador JarBalanceController
- [x] Rutas API
- [x] Documentación completa

**Pendiente:**
- [ ] Ejecutar: `php artisan migrate`
- [ ] Probar endpoints
- [ ] Sincronizar saldos anteriores (si aplica)

---

## 📖 Documentación Completa

- `docs/jar-balance-system.md` - Documentación detallada con ejemplos
- `docs/jar-balance-visual.md` - Guía visual con diagramas
