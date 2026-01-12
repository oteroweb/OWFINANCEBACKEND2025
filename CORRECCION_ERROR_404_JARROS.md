# 🔧 CORRECCIÓN RÁPIDA - ERROR 404 EN ENDPOINTS DE JARROS

**Estado:** Error 404 al solicitar `/api/v1/users/4/jars/1/balance`  
**Causa:** Falta header de autenticación  
**Solución:** Agregar token Sanctum en todas las solicitudes  
**Tiempo:** 5 minutos para implementar

---

## ❌ PROBLEMA

Frontend recibe error 404 al solicitar:
```
GET /api/v1/users/4/jars/1/balance
```

**Error Message:**
```
"The route api/v1/users/4/jars/1/balance could not be found."
```

---

## ✅ CAUSA

Las rutas están protegidas con middleware `auth:sanctum`. El frontend **no está enviando el token de autenticación**.

**IMPORTANTE:** El usuario NO debe enviar su ID. Sanctum lo extrae automáticamente del token.

---

## 🔧 SOLUCIÓN

### Ruta Correcta (sin userId)
```
GET /api/v1/jars/1/balance
```

**Nota:** Solo se pasa el `jarId`, el usuario se obtiene del token.

### Paso 1: Agregar Headers Obligatorios

**TODAS las solicitudes a `/api/v1/jars/...` deben incluir:**
```javascript
{
  "Authorization": "Bearer YOUR_SANCTUM_TOKEN",
  "Accept": "application/json"
}
```

### Paso 2: Implementar en tu código

#### Vue 3 (Axios)
```javascript
// ANTES (❌ No funciona)
axios.get('/api/v1/users/4/jars/1/balance')

// DESPUÉS (✅ Funciona)
const token = localStorage.getItem('authToken');
axios.get('/api/v1/jars/1/balance', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
```

#### React (Fetch)
```javascript
// ANTES (❌ No funciona)
fetch('/api/v1/users/4/jars/1/balance')

// DESPUÉS (✅ Funciona)
const token = localStorage.getItem('authToken');
fetch('/api/v1/jars/1/balance', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
```

#### React (Axios)
```javascript
// DESPUÉS (✅ Funciona)
const token = localStorage.getItem('authToken');
axios.get('/api/v1/jars/1/balance', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
```

---

## 📋 LOS 4 ENDPOINTS (CON RUTA CORRECTA)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/v1/jars/{jarId}/balance` | Obtener saldo |
| POST | `/api/v1/jars/{jarId}/adjust` | Crear ajuste |
| GET | `/api/v1/jars/{jarId}/adjustments` | Ver historial |
| POST | `/api/v1/jars/{jarId}/reset-adjustment` | Reiniciar período |

**⚠️ Todas requieren:** `Authorization: Bearer {token}`

**⚠️ NO incluir userId:** El usuario se obtiene del token automáticamente

---

## 🔐 ¿CÓMO OBTENER EL TOKEN?

### 1. User hace login
```bash
POST /api/v1/login
{
  "email": "user@example.com",
  "password": "password123"
}
```

### 2. Backend retorna token
```json
{
  "token": "1|abc123xyz789...",
  "user": { ... }
}
```

### 3. Guardar en localStorage
```javascript
localStorage.setItem('authToken', response.data.token);
```

### 4. Usar en todas las solicitudes
```javascript
const token = localStorage.getItem('authToken');
// Incluir en headers
```

---

## 📍 UBICACIÓN DE CAMBIOS EN CÓDIGO

### Si usas Axios Interceptor (RECOMENDADO)
```javascript
// src/api/axios.js (o similar)
import axios from 'axios';

const api = axios.create();

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('authToken');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
```

**Luego usar:**
```javascript
import api from '@/api/axios';
api.get('/api/v1/jars/1/balance')
```

### Si usas composable useJarBalance
```typescript
// composables/useJarBalance.ts
const getAuthHeaders = () => {
  const token = localStorage.getItem('authToken');
  return {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  };
};

// En cada función:
const response = await axios.get(url, { 
  headers: getAuthHeaders() 
});
```

---

## ✅ CHECKLIST DE CORRECCIÓN

- [ ] Agregué header `Authorization` con token
- [ ] Token se obtiene del localStorage (o donde lo guardes)
- [ ] Token incluye prefijo `Bearer `
- [ ] Las rutas usan `/api/v1/` (no solo `/api/`)
- [ ] Todos los 4 endpoints incluyen el header
- [ ] Probé con Postman/Thunder Client antes de integrar
- [ ] Funciona en Firefox, Chrome, Safari

---

## 🧪 PRUEBA RÁPIDA CON POSTMAN

1. **Abrir Postman**
2. **URL:** `https://appfinanzas.blockshift.website/api/v1/jars/1/balance`
3. **Method:** GET
4. **Tab Headers:**
   - Key: `Authorization`
   - Value: `Bearer YOUR_TOKEN_HERE`
   - Key: `Accept`
   - Value: `application/json`
5. **Send**

**Resultado esperado:**
```json
{
  "success": true,
  "data": {
    "jar_id": 1,
    "available_balance": 470.00,
    "allocated_amount": 500.00,
    "spent_amount": 30.00,
    "adjustment": 0.00
  }
}
```

---

## 🆘 SI AÚN NO FUNCIONA

### Error 401 (Unauthorized)
- ❌ Token inválido o expirado
- ✅ Solución: Hacer login nuevamente, obtener token nuevo

### Error 403 (Forbidden)
- ❌ Usuario no es propietario del jarro
- ✅ Solución: Verificar userId y jarId son correctos

### Error 500 (Server Error)
- ❌ Error en el backend
- ✅ Solución: Revisar logs del servidor

### Error CORS
- ❌ Cross-origin issue
- ✅ Solución: Verificar frontend URL está en CORS whitelist del backend

---

## 📞 REFERENCIA COMPLETA

Documento completo actualizado: `SOLICITUD_CAMBIOS_FRONTEND.md`

Sección: "IMPORTANTE - LEE PRIMERO" → Autenticación Requerida

---

**Fecha:** 14 Diciembre 2025  
**Actualización:** Corrección de rutas con autenticación  
**Estado:** ✅ RESUELTO
