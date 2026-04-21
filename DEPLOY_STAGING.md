# Deploy Staging — OWFINANCE2026 Checklist

## Pre-requisitos (Manual — José Luis)

- [ ] Elegir proveedor AI (ver tabla abajo)
- [ ] Obtener API key del proveedor elegido
- [ ] Copiar API key al .env del servidor

### Comparativa de proveedores AI

| Proveedor | Extracción | Asesor | Costo/mes* | Free tier |
|-----------|-----------|--------|-----------|-----------|
| **Groq** (recomendado arranque) | llama-3.3-70b | llama-3.3-70b | ~$0 | Sí, generoso |
| **Gemini Flash 2.0** | gemini-2.0-flash | gemini-2.0-flash | ~$0.05 | Sí |
| **Anthropic Claude** (mejor calidad ES) | claude-haiku-4-5 | claude-sonnet-4-5 | ~$0.60 | No ($5 mín) |
| **OpenAI** | gpt-4o-mini | gpt-4o-mini | ~$0.20 | No |

*estimado para 100 transacciones/mes por usuario activo

### Cómo obtener cada key
- **Groq** (gratis): https://console.groq.com → API Keys
- **Gemini** (gratis hasta límites): https://aistudio.google.com → Get API Key
- **Anthropic**: https://console.anthropic.com → API Keys (recargar $5 mín)
- **OpenAI**: https://platform.openai.com → API Keys

---

## Backend Deploy

### 1. Setup servidor (primera vez)
- [ ] PHP 8.3+, Composer, Redis, MySQL/PostgreSQL
- [ ] FrankenPHP o PHP-FPM + Nginx
- [ ] SSL activo en appfinanzas.blockshift.website

### 2. Deploy código
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Variables .env en servidor
```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
REDIS_URL=redis://localhost

# Elegir proveedor (groq = gratis, anthropic = mejor calidad)
AI_EXTRACTION_PROVIDER=groq
AI_ADVISOR_PROVIDER=groq

# Solo la key del proveedor elegido:
GROQ_API_KEY=gsk_...
# ANTHROPIC_API_KEY=sk-ant-...
# GEMINI_API_KEY=AI...
# OPENAI_API_KEY=sk-...
```

### 4. Migraciones (SOLO PRIMERA VEZ o tras pull con nuevas migraciones)
```bash
php artisan migrate --force
```

Migraciones AI incluidas:
- `ai_user_settings` — configuración IA por usuario
- `ai_extractions` — historial extracciones voz/OCR/auto
- `ai_conversations` — conversaciones con el asesor
- `ai_conversation_messages` — mensajes de cada conversación
- `ai_usage_log` — log de uso y costos

### 5. Seeders
```bash
php artisan db:seed --class=AiUserSettingsSeeder
```

---

## Frontend Deploy

### 1. Build
```bash
cd OWFinanceFrontend2025
npm install
npm run build
```

### 2. Verificar .env.production
```env
VITE_API_URL=https://appfinanzas.blockshift.website/api/v1
```

### 3. Deploy archivos
- Copiar `dist/spa/` al directorio público del servidor
- Nginx: rewrite rule para SPA (todas las rutas → index.html)

```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

---

## Smoke Tests (manual post-deploy)

### Core
- [ ] Login funciona
- [ ] Crear gasto manualmente (TransactionCreateDialog)
- [ ] Crear ingreso manualmente

### Features AI
- [ ] QuickActionSheet abre correctamente
- [ ] **Voz**: botón micrófono → solicita permiso → transcript aparece → IA extrae → confirmación
- [ ] **Escanear**: abre cámara → foto de ticket → IA extrae datos → confirmación
- [ ] **Auto IA**: escribir "gasté 100 en comida" → IA extrae → confirmación
- [ ] **Asesor IA**: navegar a /user/asesor → enviar mensaje → respuesta hace streaming

### Layouts
- [ ] LITE mobile: bottom nav y header funcionan
- [ ] PRO desktop: sidebar visible, colapsa al click
- [ ] PRO mobile: sidebar oculto, toggle funciona

### Rate Limiting
- [ ] POST /api/v1/ai/chat: 11 requests/min → 429 en el 11vo
- [ ] POST /api/v1/ai/extract-transaction: 21 requests/min → 429

---

## Cambiar proveedor AI (sin downtime)

```bash
# En servidor, editar .env:
AI_EXTRACTION_PROVIDER=gemini
GEMINI_API_KEY=AI...

# Limpiar caché de config:
php artisan config:cache

# Probar:
curl -X POST https://appfinanzas.blockshift.website/api/v1/ai/extract-transaction \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"source":"auto","input":"gasté 50 pesos en el super"}'
```
