# Guía Completa: Configuración Google Calendar OAuth 2.0

**Fecha:** Abril 2026  
**Sistema:** Portfolio - Agendamiento de Reuniones  
**Tipo de Autenticación:** OAuth 2.0 (Acceso a Calendar Personal)

---

## 📋 Índice

1. [Requisitos Previos](#requisitos-previos)
2. [Configuración en Google Cloud Console](#configuración-en-google-cloud-console)
3. [Configuración en config.local.php](#configuración-en-configlocalphp)
4. [Autorización OAuth](#autorización-oauth)
5. [Verificación y Pruebas](#verificación-y-pruebas)
6. [Troubleshooting](#troubleshooting)
7. [Archivos Involucrados](#archivos-involucrados)

---

## 🔧 Requisitos Previos

✅ **Backend completo** (ya implementado):
- `api/portfolio/calendar-lib.php` - Funciones OAuth
- `api/portfolio/calendar-auth-start.php` - Inicio de autorización
- `api/portfolio/calendar-auth-callback.php` - Callback OAuth
- `api/portfolio/calendar-availability.php` - FreeBusy API
- `api/portfolio/calendar-create-event.php` - Crear eventos

✅ **Frontend completo** (ya implementado):
- `frontend/src/components/Scheduling/ScheduleMeeting.js` - UI de agendamiento

✅ **Script de verificación**:
- `api/portfolio/test-calendar-setup.php` - Diagnóstico de configuración

---

## 🌐 Configuración en Google Cloud Console

### Paso 1: Habilitar Google Calendar API

1. Abre: https://console.cloud.google.com/apis/library
2. Busca: **"Google Calendar API"**
3. Click en **"Habilitar"** (Enable)
4. Espera confirmación (aparecerá "API enabled")

### Paso 2: Configurar OAuth Consent Screen

**Primera vez solamente:**

1. Ve a: https://console.cloud.google.com/apis/credentials/consent
2. Selecciona: **External** (para uso personal)
3. Click **"CREATE"**

**Información de la App:**
- **App name:** `Portfolio Juan Carlos` (o cualquier nombre)
- **User support email:** Tu email de Google
- **App logo:** (opcional)
- **Application home page:** `https://www.juancarlosmacias.es/`
- **Application privacy policy:** (opcional, puede dejarse vacío)
- **Developer contact email:** Tu email

4. Click **"SAVE AND CONTINUE"**

**Scopes:**
- No es necesario agregar scopes aquí (el código ya los solicita)
- Click **"SAVE AND CONTINUE"**

**Test users:**
- Click **"+ ADD USERS"**
- Agrega tu email de Google (el que usará el Calendar)
- Click **"ADD"**
- Click **"SAVE AND CONTINUE"**

5. Revisa y click **"BACK TO DASHBOARD"**

### Paso 3: Crear OAuth Client ID

1. Ve a: https://console.cloud.google.com/apis/credentials
2. Click **"+ CREATE CREDENTIALS"**
3. Selecciona: **"OAuth client ID"**

**Configuración:**
- **Application type:** `Web application`
- **Name:** `Portfolio Calendar` (o cualquier nombre)

**Authorized redirect URIs:** (⚠️ **MUY IMPORTANTE**)
Click **"+ ADD URI"** dos veces para agregar:

```
http://www.perfil.in/api/portfolio/calendar-auth-callback.php
https://www.juancarlosmacias.es/api/portfolio/calendar-auth-callback.php
```

4. Click **"CREATE"**

### Paso 4: Copiar Credenciales

Aparecerá un popup con:

```
Client ID: xxxxx-xxxxxx.apps.googleusercontent.com
Client secret: GOCSPX-xxxxxxxxxxxxx
```

**💾 Guárdalos en lugar seguro** (puedes descargar el JSON)

---

## ⚙️ Configuración en config.local.php

### Editar Función get_google_calendar_config()

Abre: `admin/config/config.local.php`

Busca la función `get_google_calendar_config()` (aproximadamente línea 83) y actualiza:

```php
function get_google_calendar_config() {
    return [
        'enabled' => true,  // ✅ Cambiar a true
        'client_id' => 'TU_CLIENT_ID.apps.googleusercontent.com',  // ✅ Pegar Client ID
        'client_secret' => 'GOCSPX-xxxxxxxxxxxxx',  // ✅ Pegar Client Secret
        'redirect_uri' => 'http://www.perfil.in/api/portfolio/calendar-auth-callback.php',  // ✅ URI local (cambiar en producción)
        'calendar_id' => 'primary',  // ✅ 'primary' para tu calendar principal
    ];
}
```

### Configuración Local vs Producción

**Entorno local (localhost/perfil.in):**
```php
'redirect_uri' => 'http://www.perfil.in/api/portfolio/calendar-auth-callback.php',
```

**Entorno producción (www.juancarlosmacias.es):**
```php
'redirect_uri' => 'https://www.juancarlosmacias.es/api/portfolio/calendar-auth-callback.php',
```

💡 **Tip:** El código `calendar-lib.php` puede detectar automáticamente el entorno si configuras lógica de detección.

---

## 🔐 Autorización OAuth

### Paso 1: Verificar Configuración

Visita: `http://www.perfil.in/api/portfolio/test-calendar-setup.php`

Debe mostrar:
- ✅ Client ID configurado
- ✅ Client Secret configurado
- ✅ Redirect URI configurado
- ⚠️ No hay tokens guardados (esperado)

### Paso 2: Iniciar Autorización

1. Visita: `http://www.perfil.in/api/portfolio/calendar-auth-start.php`
2. Click en **"Continuar con Google"**
3. Serás redirigido a Google OAuth consent screen

### Paso 3: Autorizar en Google

En la pantalla de Google:

1. **Selecciona tu cuenta** de Google (la que tiene el Calendar)
2. Verás: "Portfolio Juan Carlos quiere acceder a tu Cuenta de Google"
3. **⚠️ Si aparece advertencia "App no verificada":**
   - Click en **"Configuración avanzada"**
   - Click en **"Ir a Portfolio Juan Carlos (no seguro)"**
   - Esto es normal para apps en modo test
4. **Acepta los permisos:**
   - Ver eventos en todos tus calendarios
   - Ver y editar eventos en todos tus calendarios
5. Click **"Continuar"** o **"Permitir"**

### Paso 4: Redirección Automática

Serás redirigido a:
```
http://www.perfil.in/api/portfolio/calendar-auth-callback.php?code=xxxxx&state=xxxxx
```

Si todo va bien, verás mensaje de éxito:
```
✅ Autorización completada correctamente
Tokens guardados en base de datos
```

### Paso 5: Verificar Tokens Guardados

Vuelve a visitar: `http://www.perfil.in/api/portfolio/test-calendar-setup.php`

Ahora debe mostrar:
- ✅ Access Token: Presente
- ✅ Refresh Token: Presente
- ⏱️ Expira en: XX minutos

---

## ✅ Verificación y Pruebas

### Test 1: Verificación de Setup

```bash
URL: http://www.perfil.in/api/portfolio/test-calendar-setup.php
Esperado:
  ✅ Configuración OAuth correcta
  ✅ Tokens encontrados en base de datos
  ✅ Access Token válido
```

### Test 2: Consultar Disponibilidad

Abre el frontend (React):
```bash
URL: http://www.perfil.in/agendar
```

**Pasos:**
1. Selecciona rango de fechas (ej: próximos 7 días)
2. Selecciona duración (ej: 30 minutos)
3. Click **"Ver horarios disponibles"**

**Resultado esperado:**
- Muestra horarios libres entre 10:00-14:00 y 16:00-19:00
- No muestra franjas ocupadas
- Buffer de 10 minutos entre reuniones

### Test 3: Reservar Reunión

1. Click en uno de los horarios disponibles
2. Confirma reserva
3. Debe aparecer mensaje: "✅ Reunión agendada correctamente"
4. **Verifica en tu Google Calendar:**
   - Abre: https://calendar.google.com/
   - El evento debe estar creado
   - Título: "Reunión con Juan Carlos Macias" (o configurado)
   - Duración: La seleccionada (30 min, 1h, etc)

### Test 4: Renovación Automática de Token

El **Access Token expira cada 1 hora**. El sistema debe renovarlo automáticamente usando el **Refresh Token**.

**Para probar:**
1. Espera 1 hora después de la autorización
2. Intenta reservar otra reunión
3. El sistema debe renovar el token automáticamente
4. La reserva debe funcionar sin reautorizar

**Verificar en logs:**
```bash
admin/error_log
# Buscar: "Token renovado automáticamente"
```

---

## 🐛 Troubleshooting

### Error: "redirect_uri_mismatch"

**Problema:** La URI de callback no coincide con la configurada en Google Console.

**Solución:**
1. Verifica en Google Console: https://console.cloud.google.com/apis/credentials
2. Edit OAuth Client ID
3. Asegúrate que la URI exacta esté autorizada:
   ```
   http://www.perfil.in/api/portfolio/calendar-auth-callback.php
   ```
4. ⚠️ **No debe haber espacios, mayúsculas incorrectas, o barras extra**

### Error: "invalid_client"

**Problema:** Client ID o Client Secret incorrecto.

**Solución:**
1. Verifica `config.local.php` → `get_google_calendar_config()`
2. Copia de nuevo desde Google Console
3. Asegúrate de copiar completo (sin espacios extra)

### Error: "access_denied"

**Problema:** Rechazaste los permisos o la app no tiene test users.

**Solución:**
1. Ve a OAuth Consent Screen
2. Agrega tu email en **Test users**
3. Reintenta la autorización

### Error: "No hay tokens guardados"

**Problema:** La autorización no se completó correctamente.

**Solución:**
1. Verifica que `calendar-auth-callback.php` tenga permisos de escritura
2. Verifica tabla `system_config` en MySQL:
   ```sql
   SELECT * FROM system_config WHERE config_key = 'google_calendar_tokens';
   ```
3. Reintenta la autorización

### Error: "Token expirado" después de varios días

**Problema:** El Refresh Token puede expirar si no se usa por 6 meses o si revocas permisos.

**Solución:**
1. Revocar acceso en: https://myaccount.google.com/permissions
2. Borrar tokens de BD:
   ```sql
   DELETE FROM system_config WHERE config_key = 'google_calendar_tokens';
   ```
3. Reautorizar desde `calendar-auth-start.php`

### Frontend no muestra horarios

**Verificar:**
1. Console del navegador (F12) → Network → Ver respuesta de `calendar-availability.php`
2. Si error 401/403: Token expirado o revocado → Reautorizar
3. Si error 500: Ver `admin/error_log` para detalles

### Horarios incorrectos (timezone)

**Problema:** Los horarios se muestran en zona horaria incorrecta.

**Solución:**
El sistema usa UTC. Verifica en `calendar-availability.php`:
```php
// Debería usar la zona horaria del servidor o del usuario
$timezone = 'Europe/Madrid';  // Ajustar según ubicación
```

---

## 📁 Archivos Involucrados

### Backend - API OAuth

| Archivo | Propósito |
|---------|-----------|
| `api/portfolio/calendar-lib.php` | Funciones OAuth: build_auth_url, exchange_code, refresh_token |
| `api/portfolio/calendar-auth-start.php` | Inicio del flujo OAuth (genera link de autorización) |
| `api/portfolio/calendar-auth-callback.php` | Callback OAuth (recibe code, intercambia por tokens) |
| `api/portfolio/calendar-availability.php` | FreeBusy API (consulta horarios libres) |
| `api/portfolio/calendar-create-event.php` | Crea eventos en Calendar |
| `api/portfolio/test-calendar-setup.php` | Script de diagnóstico (verificación de setup) |

### Backend - Configuración

| Archivo | Propósito |
|---------|-----------|
| `admin/config/config.local.php` | Configuración OAuth (Client ID, Secret, Redirect URI) |
| `admin/config/config.local.example.php` | Plantilla de configuración |

### Frontend - UI

| Archivo | Propósito |
|---------|-----------|
| `frontend/src/components/Scheduling/ScheduleMeeting.js` | Componente React de agendamiento |
| `frontend/src/Services/urls.js` | Endpoints API (calendar-availability, calendar-create-event) |

### Base de Datos

| Tabla | Campo | Propósito |
|-------|-------|-----------|
| `system_config` | `config_key = 'google_calendar_tokens'` | Almacena access_token y refresh_token |

**Estructura de tokens:**
```json
{
  "access_token": "ya29.xxxxx",
  "refresh_token": "1//xxxxx",
  "expires_in": 3600,
  "created_at": 1712345678
}
```

---

## 🔒 Seguridad

### Buenas Prácticas Implementadas

✅ **CSRF Protection:**
- Genera token `state` aleatorio en `calendar-auth-start.php`
- Valida `state` en callback para prevenir ataques CSRF

✅ **Tokens Seguros:**
- Tokens guardados en base de datos (no en archivos)
- Refresh token permite renovar sin reautorizar

✅ **HTTPS en Producción:**
- Redirect URI usa HTTPS en producción
- Previene intercepción de tokens

✅ **Scope Mínimo:**
- Solo solicita permisos de Calendar (no Drive, Gmail, etc)

### ⚠️ Consideraciones

- **config.local.php NO debe estar en Git** (ya en `.gitignore`)
- Client Secret es sensible - no compartir públicamente
- Tokens tienen permisos de lectura/escritura en tu Calendar
- App en modo "Testing" - máximo 100 usuarios test
- Para producción real: Publicar app (verificación de Google)

---

## 📊 Estados del Sistema

### Estado 1: Sin Configurar
```
Config: enabled = false
Tokens: No existen
Acción: Configurar credenciales OAuth
```

### Estado 2: Configurado pero No Autorizado
```
Config: Client ID + Secret configurados
Tokens: No existen
Acción: Visitar calendar-auth-start.php para autorizar
```

### Estado 3: Autorizado y Funcional
```
Config: Completo
Tokens: Access token + Refresh token en BD
Estado: ✅ Sistema operativo
```

### Estado 4: Token Expirado
```
Config: Completo
Tokens: Access token expirado, Refresh token válido
Acción: Sistema renueva automáticamente (sin intervención)
```

### Estado 5: Refresh Token Revocado
```
Config: Completo
Tokens: Refresh token inválido/revocado
Acción: Reautorizar desde calendar-auth-start.php
```

---

## 🚀 Despliegue en Producción

### Checklist Pre-Deploy

- [ ] Credenciales OAuth creadas en Google Console
- [ ] Redirect URI de producción agregada: `https://www.juancarlosmacias.es/api/portfolio/calendar-auth-callback.php`
- [ ] Test users incluye tu email de producción
- [ ] `config.local.php` con client_id y client_secret correctos
- [ ] `redirect_uri` apunta a URL de producción
- [ ] Autorización OAuth completada (tokens guardados)
- [ ] Test de disponibilidad funciona
- [ ] Test de creación de evento funciona
- [ ] Tabla `system_config` tiene tokens guardados

### Pasos de Deploy

1. **Actualizar config.local.php en servidor:**
   ```php
   'redirect_uri' => 'https://www.juancarlosmacias.es/api/portfolio/calendar-auth-callback.php',
   ```

2. **Subir archivos backend:**
   ```bash
   git pull origin main
   ```

3. **Reautorizar en producción:**
   - Visita: `https://www.juancarlosmacias.es/api/portfolio/calendar-auth-start.php`
   - Autoriza de nuevo (los tokens locales no sirven)

4. **Verificar:**
   - `https://www.juancarlosmacias.es/api/portfolio/test-calendar-setup.php`

5. **Build frontend:**
   ```bash
   cd frontend
   npm run build
   ```

6. **Desplegar build en servidor**

7. **Test end-to-end:**
   - Visita: `https://www.juancarlosmacias.es/agendar`
   - Reserva una reunión de prueba

---

## 📞 Soporte y Referencias

### Documentación Oficial Google

- **OAuth 2.0:** https://developers.google.com/identity/protocols/oauth2
- **Calendar API:** https://developers.google.com/calendar/api/v3/reference
- **FreeBusy:** https://developers.google.com/calendar/api/v3/reference/freebusy
- **Scopes:** https://developers.google.com/identity/protocols/oauth2/scopes#calendar

### Documentación del Proyecto

- `doc/guia_google_calendar_oauth_credenciales.md` - Guía original de credenciales
- `doc/estudio_preliminar_agendar_reunion_google_calendar_llm.md` - Análisis inicial
- `admin/pages/google-calendar.php` - Panel admin de Calendar (si existe)

### Logs

- **Backend:** `admin/error_log`
- **API:** `api/portfolio/error_log`
- **Frontend:** Console del navegador (F12)

---

## ✨ Próximos Pasos (Opcional)

### Mejoras Sugeridas

1. **Notificaciones por Email:**
   - Enviar confirmación al usuario después de reservar
   - Incluir link de calendar (.ics)

2. **Recordatorios:**
   - Email 24h antes
   - Email 1h antes
   - Implementar con Google Calendar API

3. **Cancelación de Reuniones:**
   - Endpoint para cancelar eventos
   - UI en frontend para gestionar reservas

4. **Múltiples Calendarios:**
   - Selector de calendario (personal, trabajo, etc)
   - Configurar varios calendar_id

5. **Analytics:**
   - Tracking de reservas
   - Horarios más solicitados
   - Tasa de conversión

6. **Verificación de App:**
   - Pasar de "Testing" a "In Production"
   - Proceso de verificación de Google
   - Quitar límite de 100 usuarios

---

**Documentación creada:** Abril 2026  
**Última actualización:** Abril 2026  
**Versión:** 1.0  
**Autor:** Sistema Portfolio - Juan Carlos Macías
