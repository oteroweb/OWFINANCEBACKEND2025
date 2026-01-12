# 🎯 REFERENCIA RÁPIDA - SOLICITUD FRONTEND

**Documento principal:** `SOLICITUD_CAMBIOS_FRONTEND.md` (32 KB, 1,306 líneas)

---

## 📌 LOS 4 ENDPOINTS (COPIA/PEGA)

### 1️⃣ GET BALANCE
```bash
GET /api/users/{userId}/jars/{jarId}/balance
```
**Para:** Mostrar saldo actual  
**Respuesta:**
```json
{
  "success": true,
  "data": {
    "available_balance": 470.00,
    "allocated_amount": 500.00,
    "spent_amount": 30.00,
    "adjustment": 0.00,
    "refresh_mode": "reset",
    "type": "fixed"
  }
}
```

---

### 2️⃣ POST ADJUST
```bash
POST /api/users/{userId}/jars/{jarId}/adjust
Content-Type: application/json

{
  "amount": 50.00,
  "type": "increment",
  "reason": "Regalo"
}
```
**Para:** Crear ajuste manual  
**Parámetros:**
- `amount`: número > 0
- `type`: "increment" | "decrement"
- `reason`: string (opcional)

---

### 3️⃣ GET ADJUSTMENTS
```bash
GET /api/users/{userId}/jars/{jarId}/adjustments?limit=10
```
**Para:** Ver historial de cambios  
**Respuesta:** Array de ajustes con: id, amount, type, reason, adjusted_by, adjusted_at

---

### 4️⃣ POST RESET-ADJUSTMENT
```bash
POST /api/users/{userId}/jars/{jarId}/reset-adjustment
```
**Para:** Resetear para nuevo período (solo modo RESET)

---

## 🧩 LOS 3 COMPONENTES

### 1. JarCard (Tarjeta del Cantaro)
**Muestra:** Saldo disponible + breakdown  
**Acciones:** Ajustar | Historial | Resetear

```
╔═══════════════════════╗
║ Diversión (FIXED)     ║
║ Disponible: $470      ║
║ Asignado: $500        ║
║ Gastado: $30          ║
║ [Ajustar] [Historial] ║
╚═══════════════════════╝
```

### 2. AdjustmentModal (Modal de Ajuste)
**Campos:** Tipo (increment/decrement) + Monto + Razón  
**Validación:** Real-time + preview de nuevo saldo

### 3. AdjustmentHistory (Historial)
**Muestra:** Timeline de todos los cambios  
**Info:** Fecha, monto, quien, razón

---

## 🔑 CONCEPTOS CLAVE (TL;DR)

| Concepto | Significado | Ejemplo |
|----------|-----------|---------|
| **FIXED** | Monto fijo cada mes | $500/mes |
| **PERCENT** | % del ingreso | 20% de $5,000 = $1,000 |
| **RESET** | Reinicia cada mes | Dic saldo no se suma a Ene |
| **ACCUMULATIVE** | Se suma mes a mes | Dic saldo se suma a Ene |

---

## 💻 CÓDIGO (COPY/PASTE)

**Composable Vue 3:**
```typescript
const { balance, adjustments, cargarBalance, crearAjuste, cargarHistorial, resetearAjuste } 
  = useJarBalance(userId, jarId);
```

**Componente Vue 3:**
```vue
<JarCard :jar="jar" :userId="userId" />
<AdjustmentModal v-if="showModal" @save="guardarAjuste" />
```

**Hook React:**
```javascript
const { balance, adjustments, cargarBalance, crearAjuste } = useJarBalance(userId, jarId);
```

---

## ✅ CHECKLIST MÍNIMO

- [ ] Entender Fixed/Percent/Reset/Accumulative
- [ ] Copiar composable/hook
- [ ] Copiar JarCard + AdjustmentModal
- [ ] Conectar a los 4 endpoints
- [ ] Test: GET /balance
- [ ] Test: POST /adjust
- [ ] Test: GET /adjustments
- [ ] Deploy

---

## 📁 ARCHIVOS DISPONIBLES

**Principal:**  
📄 `SOLICITUD_CAMBIOS_FRONTEND.md` ← **LEER ESTO PRIMERO**

**Documentación:**  
📚 `/docs/FRONTEND-SUMMARY.md`  
📚 `/docs/FRONTEND-INDEX.md`  
📚 `/docs/frontend-jar-logic-guide.md`  
📚 `/docs/frontend-code-examples.md` (código Vue + React)  
📚 `/docs/before-after-architecture.md`  

---

## 🎓 GUÍA DE LECTURA (POR ROL)

**👨‍💼 Manager (20 min)**
→ Lee: `before-after-architecture.md` sección 6 (tabla comparativa)

**👨‍💻 Developer (3 horas)**
→ Lee: `SOLICITUD_CAMBIOS_FRONTEND.md` completo  
→ Copia código de `/docs/frontend-code-examples.md`  
→ Test endpoints

**🏗️ Tech Lead (1 hora)**
→ Lee: `before-after-architecture.md` completo  
→ Revisa patrones en `/docs/frontend-code-examples.md`

---

## 🚀 TIMELINE

| Fase | Tiempo | Qué hacer |
|------|--------|-----------|
| 1. Lectura | 30 min | Entender conceptos + endpoints |
| 2. Desarrollo | 2 horas | Copiar + adaptar código |
| 3. Testing | 1 hora | Validar endpoints + UI |
| **TOTAL** | **3.5 horas** | **Listo para producción** |

---

## 🔗 API ENDPOINTS RÁPIDO

```javascript
// Obtener balance
GET https://api.app.com/api/users/123/jars/5/balance

// Ajustar (incrementar $50)
POST https://api.app.com/api/users/123/jars/5/adjust
{
  "amount": 50,
  "type": "increment",
  "reason": "Bonus"
}

// Ver historial
GET https://api.app.com/api/users/123/jars/5/adjustments

// Resetear para nuevo mes
POST https://api.app.com/api/users/123/jars/5/reset-adjustment
```

---

## 🎨 DISEÑO DE COMPONENTE (ASCII)

```
┌─────────────────────────────────────┐
│  📦 Diversión                       │
│  Badge: FIXED | RESET               │
├─────────────────────────────────────┤
│                                     │
│     💰 Disponible: $470             │
│     ████████░░░░░░░░░░ 94%          │
│                                     │
├─────────────────────────────────────┤
│  Asignado:   $500                   │
│  Gastado:    -$30                   │
│  Ajuste:     +$0                    │
├─────────────────────────────────────┤
│  [Ajustar] [Historial] [Resetear]   │
└─────────────────────────────────────┘
```

---

**Última actualización:** 14 Dic 2025  
**Estado:** ✅ Listo para Solicitar al Frontend  
**Versión:** 1.0
