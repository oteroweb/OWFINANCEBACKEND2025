# 🧪 EJEMPLOS DE PETICIONES - Testing API

**Fecha:** 25 Diciembre 2025  
**Herramientas:** Postman, cURL, Axios

---

## 🔧 CONFIGURACIÓN INICIAL

### Variables de Entorno
```
BASE_URL=http://localhost:8000/api/v1
TOKEN=tu_bearer_token_aqui
```

### Headers Comunes
```
Authorization: Bearer {{TOKEN}}
Content-Type: application/json
Accept: application/json
```

---

## 1️⃣ GET /jars/income-summary

### Request cURL
```bash
curl -X GET "{{BASE_URL}}/jars/income-summary?month=2025-01" \
  -H "Authorization: Bearer {{TOKEN}}" \
  -H "Accept: application/json"
```

### Request Axios
```javascript
const response = await axios.get('/api/v1/jars/income-summary', {
  params: { month: '2025-01' },
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
```

### Response Exitosa (200)
```json
{
  "success": true,
  "data": {
    "expected_income": 5000.00,
    "calculated_income": 4200.00,
    "difference": -800.00,
    "difference_percentage": -16.00,
    "month": "2025-01",
    "breakdown": {
      "by_category": [
        {
          "category_id": 1,
          "category_name": "Salario",
          "amount": 4000.00
        },
        {
          "category_id": 2,
          "category_name": "Freelance",
          "amount": 200.00
        }
      ]
    }
  }
}
```

### Response sin monthly_income configurado
```json
{
  "success": true,
  "data": {
    "expected_income": 0.00,
    "calculated_income": 4200.00,
    "difference": 4200.00,
    "difference_percentage": 0,
    "month": "2025-01",
    "breakdown": {
      "by_category": [...]
    }
  }
}
```

---

## 2️⃣ POST /jars/{id}/adjust

### Request cURL
```bash
curl -X POST "{{BASE_URL}}/jars/5/adjust" \
  -H "Authorization: Bearer {{TOKEN}}" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 160.00,
    "description": "Compensar diferencia de ingreso"
  }'
```

### Request Axios
```javascript
const response = await axios.post('/api/v1/jars/5/adjust', {
  amount: 160.00,
  description: 'Compensar diferencia de ingreso'
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
```

### Response Exitosa (200)
```json
{
  "success": true,
  "message": "Ajuste aplicado correctamente",
  "data": {
    "jar_id": 5,
    "jar_name": "Ahorro",
    "adjustment": 160.00,
    "previous_adjustment": 0.00,
    "balance": {
      "asignado": 840.00,
      "gastado": 300.00,
      "ajuste": 160.00,
      "balance": 700.00,
      "porcentaje_utilizado": 35.71
    },
    "adjustment_record_id": 123
  }
}
```

### Response Error - Balance Negativo (400)
```json
{
  "success": false,
  "message": "The adjustment would result in a negative balance",
  "details": {
    "current_balance": 100.00,
    "requested_adjustment": -150.00,
    "would_result_in": -50.00
  }
}
```

### Response Error - Jar no encontrado (404)
```json
{
  "success": false,
  "message": "Jar not found or does not belong to you"
}
```

### Response Error - Validación (422)
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "amount": [
      "The amount field is required."
    ]
  }
}
```

---

## 3️⃣ POST /jars/{id}/adjust/reset

### Request cURL
```bash
curl -X POST "{{BASE_URL}}/jars/5/adjust/reset" \
  -H "Authorization: Bearer {{TOKEN}}" \
  -H "Accept: application/json"
```

### Request Axios
```javascript
const response = await axios.post('/api/v1/jars/5/adjust/reset', {}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
```

### Response Exitosa (200)
```json
{
  "success": true,
  "message": "Ajuste reseteado correctamente",
  "data": {
    "jar_id": 5,
    "jar_name": "Ahorro",
    "adjustment": 0,
    "previous_adjustment": 160.00,
    "balance": {
      "asignado": 840.00,
      "gastado": 300.00,
      "ajuste": 0,
      "balance": 540.00
    }
  }
}
```

---

## 4️⃣ PUT /user/profile (Actualizado)

### Request cURL
```bash
curl -X PUT "{{BASE_URL}}/user/profile" \
  -H "Authorization: Bearer {{TOKEN}}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "José Luis",
    "email": "jose@example.com",
    "monthly_income": 5000.00,
    "currency_id": 1
  }'
```

### Request Axios
```javascript
const response = await axios.put('/api/v1/user/profile', {
  name: 'José Luis',
  email: 'jose@example.com',
  monthly_income: 5000.00,
  currency_id: 1
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
```

### Response Exitosa (200)
```json
{
  "success": true,
  "message": "Perfil actualizado correctamente",
  "data": {
    "id": 1,
    "name": "José Luis",
    "email": "jose@example.com",
    "monthly_income": 5000.00,
    "currency_id": 1,
    "currency": {
      "id": 1,
      "name": "Dólar",
      "symbol": "$",
      "code": "USD"
    }
  }
}
```

---

## 5️⃣ POST /jars (Actualizado)

### Request cURL - Cántaro con TODAS las categorías de ingreso
```bash
curl -X POST "{{BASE_URL}}/jars" \
  -H "Authorization: Bearer {{TOKEN}}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ahorro General",
    "type": "percent",
    "percent": 20,
    "base_scope": "all_income",
    "color": "#00FF00",
    "categories": [10, 11, 12]
  }'
```

### Request cURL - Cántaro con categorías ESPECÍFICAS de ingreso
```bash
curl -X POST "{{BASE_URL}}/jars" \
  -H "Authorization: Bearer {{TOKEN}}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ahorro Freelance",
    "type": "percent",
    "percent": 30,
    "base_scope": "categories",
    "base_categories": [5, 7],
    "color": "#FF00FF",
    "categories": [15, 16]
  }'
```

### Request Axios
```javascript
const response = await axios.post('/api/v1/jars', {
  name: 'Ahorro Freelance',
  type: 'percent',
  percent: 30,
  base_scope: 'categories',
  base_categories: [5, 7],  // IDs de categorías de ingreso (ej: Freelance, Bonos)
  categories: [15, 16],      // IDs de categorías de gasto (dónde se gastará)
  color: '#FF00FF'
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
```

### Response Exitosa (200)
```json
{
  "status": "OK",
  "code": 200,
  "message": "Jar saved correctly",
  "data": {
    "id": 10,
    "name": "Ahorro Freelance",
    "type": "percent",
    "percent": 30.00,
    "base_scope": "categories",
    "color": "#FF00FF",
    "user_id": 1,
    "categories": [
      {
        "id": 15,
        "name": "Entretenimiento",
        "pivot": {
          "jar_id": 10,
          "category_id": 15
        }
      },
      {
        "id": 16,
        "name": "Viajes",
        "pivot": {
          "jar_id": 10,
          "category_id": 16
        }
      }
    ],
    "baseCategories": [
      {
        "id": 5,
        "name": "Freelance",
        "pivot": {
          "jar_id": 10,
          "category_id": 5
        }
      },
      {
        "id": 7,
        "name": "Bonos",
        "pivot": {
          "jar_id": 10,
          "category_id": 7
        }
      }
    ]
  }
}
```

### Response Error - Sin base_categories cuando base_scope = 'categories' (422)
```json
{
  "status": "FAILED",
  "code": 422,
  "message": "You must select at least one income category when base_scope is categories"
}
```

---

## 6️⃣ PUT /jars/{id} (Actualizado)

### Request cURL
```bash
curl -X PUT "{{BASE_URL}}/jars/10" \
  -H "Authorization: Bearer {{TOKEN}}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ahorro Actualizado",
    "percent": 25,
    "base_scope": "all_income",
    "base_categories": []
  }'
```

### Request Axios
```javascript
const response = await axios.put('/api/v1/jars/10', {
  name: 'Ahorro Actualizado',
  percent: 25,
  base_scope: 'all_income',
  base_categories: []  // Limpiar categorías base
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
```

### Response Exitosa (200)
```json
{
  "status": "OK",
  "code": 200,
  "message": "Jar updated",
  "data": {
    "id": 10,
    "name": "Ahorro Actualizado",
    "type": "percent",
    "percent": 25.00,
    "base_scope": "all_income",
    "categories": [...],
    "baseCategories": []
  }
}
```

---

## 🧪 CASOS DE PRUEBA RECOMENDADOS

### Caso 1: Usuario sin monthly_income
```bash
# 1. No configurar monthly_income
# 2. GET /jars/income-summary
# Resultado esperado: expected_income = 0
```

### Caso 2: Ajuste positivo
```bash
# 1. POST /jars/5/adjust con amount: 100
# 2. Verificar que balance aumente
# 3. POST /jars/5/adjust con amount: 50
# 4. Verificar que adjustment = 150 (acumulativo)
```

### Caso 3: Ajuste negativo (válido)
```bash
# 1. Cántaro con balance = 500
# 2. POST /jars/5/adjust con amount: -200
# 3. Verificar que balance = 300
```

### Caso 4: Ajuste negativo (inválido)
```bash
# 1. Cántaro con balance = 100
# 2. POST /jars/5/adjust con amount: -200
# 3. Esperar error 400 (balance negativo)
```

### Caso 5: base_scope con categorías específicas
```bash
# 1. Crear cántaro con base_scope: "categories"
# 2. base_categories: [5, 7] (Freelance, Bonos)
# 3. Crear transacción de ingreso en categoría 5
# 4. GET /jars/income-summary
# 5. Verificar que solo cuenta ese ingreso
```

### Caso 6: base_scope con todas las categorías
```bash
# 1. Crear cántaro con base_scope: "all_income"
# 2. Crear transacciones en varias categorías de ingreso
# 3. Verificar que suma TODAS
```

---

## 📊 FLUJO COMPLETO DE USO

### Paso 1: Configurar ingreso mensual
```bash
PUT /user/profile
{
  "monthly_income": 5000.00
}
```

### Paso 2: Crear cántaros
```bash
# Cántaro 1: 50% de todos los ingresos
POST /jars
{
  "name": "Ahorro",
  "type": "percent",
  "percent": 50,
  "base_scope": "all_income"
}

# Cántaro 2: 30% solo de ingresos de Freelance
POST /jars
{
  "name": "Inversión",
  "type": "percent",
  "percent": 30,
  "base_scope": "categories",
  "base_categories": [5]  # Freelance
}
```

### Paso 3: Ver resumen
```bash
GET /jars/income-summary
# expected_income: 5000
# calculated_income: 4200 (si solo ganaste 4200)
# difference: -800
```

### Paso 4: Ajustar cántaro por diferencia
```bash
# Distribuir los -800 proporcionalmente
POST /jars/1/adjust
{
  "amount": -400,
  "description": "Ajuste por ingreso menor"
}

POST /jars/2/adjust
{
  "amount": -400,
  "description": "Ajuste por ingreso menor"
}
```

---

## 🐛 TROUBLESHOOTING

### Error: "Unauthenticated"
**Solución:** Verificar que el token Bearer esté en el header

### Error: "Jar not found"
**Solución:** Verificar que el ID del jar sea correcto y pertenezca al usuario autenticado

### Error: "Total percent exceeds 100%"
**Solución:** La suma de todos los cántaros de tipo `percent` no puede superar 100%

### Error: "The adjustment would result in negative balance"
**Solución:** No puedes restar más dinero del que hay disponible en el cántaro

---

## 📁 COLECCIÓN POSTMAN

Importa esta colección en Postman:

```json
{
  "info": {
    "name": "OWFinance - Jars System",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Get Income Summary",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{BASE_URL}}/jars/income-summary?month=2025-01",
          "host": ["{{BASE_URL}}"],
          "path": ["jars", "income-summary"],
          "query": [{"key": "month", "value": "2025-01"}]
        }
      }
    },
    {
      "name": "Adjust Jar",
      "request": {
        "method": "POST",
        "header": [{"key": "Content-Type", "value": "application/json"}],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"amount\": 160.00,\n  \"description\": \"Compensación\"\n}"
        },
        "url": {
          "raw": "{{BASE_URL}}/jars/:id/adjust",
          "host": ["{{BASE_URL}}"],
          "path": ["jars", ":id", "adjust"],
          "variable": [{"key": "id", "value": "5"}]
        }
      }
    }
  ]
}
```

---

**¡Todo listo para testing!** 🧪✅
