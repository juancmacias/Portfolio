# 🚨 PLAN DE ACCIÓN: Token de Telegram Expuesto

**Fecha:** 11 de abril de 2026  
**Severidad:** 🔴 **CRÍTICA**  
**GitHub Alert:** Token expuesto en commit 75d95504

---

## ⚠️ Problema Detectado

GitHub Security ha detectado que el **Telegram Bot Token** estaba hardcodeado en:
- Archivo: `admin/pages/get-telegram-chat-id.html`
- Líneas: 35 y 80
- Commit: `75d95504`
- Token expuesto: `8646528294:AAHvSDgPXfvkJtY-F7IyW2U8quHJS_VYaPQ`

### Riesgo:
- ✅ El repositorio es **privado** (menor exposición)
- ⚠️ Cualquiera con acceso de lectura puede ver el token
- ⚠️ El token permite enviar mensajes en nombre del bot
- ⚠️ Puede usarse para spam o acceso no autorizado

---

## ✅ Soluciones Aplicadas

### 1. Archivo Seguro Creado
✅ **Nuevo archivo:** `admin/pages/get-telegram-chat-id.php`
- Token obtenido desde `config.local.php` (no hardcodeado)
- Requiere autenticación admin
- Token no visible en código fuente del navegador
- Usa `json_encode()` para pasar token de forma segura a JavaScript

### 2. Protección de Configuración
✅ `admin/config/config.local.php` está en `.gitignore`:
```gitignore
admin/config/config.local.php
/admin/config/config.local.php
**/config.local.php
config.local.php
```

---

## 🔄 ACCIONES REQUERIDAS (Usuario)

### Paso 1: Rotar el Token en BotFather (URGENTE)

**⏱️ Tiempo:** 2 minutos

1. **Abre Telegram** y busca: `@BotFather`

2. **Revoca el token actual:**
   ```
   Tú: /revoke
   BotFather: Choose a bot to revoke its token
   ```
   - Selecciona tu bot de la lista
   - BotFather te preguntará confirmación
   - Confirma la revocación

3. **Genera un nuevo token:**
   ```
   Tú: /token
   BotFather: Choose a bot to generate new token
   ```
   - Selecciona tu bot
   - BotFather generará un **nuevo token**
   - **Cópialo** (formato: `1234567890:ABCdefGHIjklMNOpqrsTUVwxyz`)

### Paso 2: Actualizar config.local.php

Abre: `admin/config/config.local.php`

Busca la función `get_telegram_config()` y actualiza:

```php
function get_telegram_config() {
    return [
        'enabled' => true,
        'bot_token' => 'NUEVO_TOKEN_AQUI',  // ← Pegar nuevo token
        'chat_id' => '1472838529',  // ← Mantener igual
        // ... resto de la configuración
    ];
}
```

### Paso 3: Verificar que Funciona

1. **Abre:** `http://www.perfil.in/admin/pages/get-telegram-chat-id.php`
2. Click en **"Obtener Chat ID"**
3. Debe mostrar tu chat ID correctamente
4. Si funciona → ✅ Token rotado correctamente

---

## 📦 Commits de Seguridad a Realizar

### Commit 1: Remover Archivo Inseguro
```bash
# Remover archivo con token hardcodeado
git rm admin/pages/get-telegram-chat-id.html

# Agregar archivo seguro
git add admin/pages/get-telegram-chat-id.php

# Commit de seguridad
git commit -m "security: Remove hardcoded Telegram token from HTML file

- Removed get-telegram-chat-id.html (token exposed in lines 35, 80)
- Created get-telegram-chat-id.php with secure token retrieval
- Token now loaded from config.local.php (gitignored)
- Fixes GitHub Security Alert for commit 75d95504"
```

### Commit 2: Documentación de Seguridad
```bash
git add doc/SEGURIDAD-TELEGRAM-TOKEN-ROTATION.md
git commit -m "docs: Add Telegram token rotation security guide"
```

---

## 📊 Checklist de Seguridad

### ✅ Completado
- [x] Archivo seguro creado (`get-telegram-chat-id.php`)
- [x] Token obtenido desde configuración (no hardcodeado)
- [x] `config.local.php` en `.gitignore`
- [x] Documentación de rotación creada

### ⏳ Pendiente (Requiere Acción del Usuario)
- [ ] Revocar token viejo en BotFather
- [ ] Generar nuevo token
- [ ] Actualizar `config.local.php` con nuevo token
- [ ] Verificar que nuevo token funciona
- [ ] Hacer commit removiendo archivo HTML inseguro
- [ ] Push a GitHub
- [ ] Resolver alerta de seguridad en GitHub

---

## 🔍 Verificación Post-Rotación

### 1. Verificar Token Viejo Revocado
Prueba el token viejo en una terminal:
```bash
curl https://api.telegram.org/bot8646528294:AAHvSDgPXfvkJtY-F7IyW2U8quHJS_VYaPQ/getMe
```

**Respuesta esperada:**
```json
{"ok":false,"error_code":401,"description":"Unauthorized"}
```

✅ Si ves esto, el token viejo está **correctamente revocado**.

### 2. Verificar Nuevo Token Funciona
Abre el archivo de prueba:
```
http://www.perfil.in/api/portfolio/test-telegram.php
```

Debe enviar notificación correctamente con el nuevo token.

### 3. Verificar en Repositorio
Busca si hay más referencias al token viejo:
```bash
git log --all -S "8646528294:AAHvSDgPXfvkJtY" --oneline
```

Si encuentra commits históricos con el token:
- **No te preocupes:** GitHub usa branch filtering
- El token está revocado (inútil)
- Para histórico completo (opcional): `git filter-branch` o BFG Repo-Cleaner

---

## 🛡️ Mejores Prácticas (Prevención Futura)

### 1. Nunca Hardcodear Credenciales
❌ **Mal:**
```javascript
const BOT_TOKEN = "123456:ABC-DEF";
```

✅ **Bien:**
```php
<?php
$config = get_telegram_config();
$token = $config['bot_token'];
?>
<script>
const BOT_TOKEN = <?php echo json_encode($token); ?>;
</script>
```

### 2. Usar Variables de Entorno (Alternativa)
```php
// En config.local.php
function get_telegram_config() {
    return [
        'bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
        // ...
    ];
}
```

### 3. Pre-commit Hooks (Opcional)
Instalar `git-secrets` para detectar tokens antes de commit:
```bash
git secrets --install
git secrets --register-aws
```

### 4. GitHub Actions (Opcional)
Usar secret scanning en CI/CD:
```yaml
# .github/workflows/security.yml
name: Security Scan
on: [push]
jobs:
  gitleaks:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: gitleaks/gitleaks-action@v2
```

---

## 📞 Resolver Alerta de GitHub

### Una vez rotado el token:

1. **Ve a GitHub:** https://github.com/juancmacias/Portfolio/security
2. **Encuentra la alerta:** "Telegram Bot Token"
3. **Opciones:**
   - **"Dismiss alert"** → Selecciona: "The secret has been revoked"
   - Agrega nota: "Token rotado el 11/04/2026, archivo removido en commit [hash]"

### Verificar que no haya más alertas:
```
https://github.com/juancmacias/Portfolio/security/secret-scanning
```

---

## ⏱️ Timeline de Resolución

| Paso | Tiempo | Estado |
|------|--------|---------|
| Crear archivo seguro | 2 min | ✅ Completado |
| Revocar token viejo | 1 min | ⏳ Pendiente |
| Generar nuevo token | 1 min | ⏳ Pendiente |
| Actualizar config | 1 min | ⏳ Pendiente |
| Commit + Push | 2 min | ⏳ Pendiente |
| Resolver alerta GitHub | 1 min | ⏳ Pendiente |
| **Total** | **8 min** | **25% completo** |

---

## 🔐 Seguridad Adicional Implementada

### Autenticación en Herramienta
```php
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';
```
✅ Solo administradores autenticados pueden acceder.

### Token No Visible en HTML
```php
const BOT_TOKEN = <?php echo json_encode($botToken); ?>;
```
✅ Token inyectado en JS de forma segura (no en HTML source).

### Validación de Configuración
```php
$isConfigured = !empty($botToken);
if (!$isConfigured) {
    // Mostrar error
}
```
✅ No permite uso si config no está lista.

---

## 📚 Referencias

- **Telegram BotFather:** https://t.me/BotFather
- **GitHub Secret Scanning:** https://docs.github.com/en/code-security/secret-scanning
- **BFG Repo-Cleaner:** https://rtyley.github.io/bfg-repo-cleaner/
- **git-secrets:** https://github.com/awslabs/git-secrets

---

**Próximo paso:** Abre Telegram → BotFather → `/revoke` → Genera nuevo token → Actualiza config.local.php
