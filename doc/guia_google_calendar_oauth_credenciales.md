# Guía: crear credenciales OAuth para Google Calendar (Portfolio)

Esta guía configura Google OAuth 2.0 para que tu página **/agendar** pueda:
- Consultar huecos libres (FreeBusy)
- Crear un evento en Google Calendar

La implementación del proyecto usa estos endpoints:
- `/api/portfolio/calendar-auth-start.php`
- `/api/portfolio/calendar-auth-callback.php`

Y lee la configuración desde `admin/config/config.local.php` mediante:
- `get_google_calendar_config()`

---

## 0) Requisitos previos
- Un usuario Google con acceso al calendario que quieres usar.
- Acceso a **Google Cloud Console**.
- Tu sitio accesible por **HTTPS** si vas a autorizar en producción (Google suele exigirlo).

---

## 1) Crear un proyecto en Google Cloud
1. Entra en Google Cloud Console.
2. Selecciona el desplegable de proyecto (arriba) → **New Project**.
3. Nombre sugerido: `Portfolio-Calendar`.
4. Crea el proyecto y selecciónalo.

---

## 2) Activar la API de Google Calendar
1. Ve a **APIs & Services** → **Library**.
2. Busca **Google Calendar API**.
3. Pulsa **Enable**.

---

## 3) Configurar la pantalla de consentimiento (OAuth consent screen)
1. Ve a **APIs & Services** → **OAuth consent screen**.
2. Tipo:
   - Si es cuenta normal: **External**.
   - Si estás en Google Workspace y solo dentro del dominio: **Internal**.
3. Rellena lo mínimo:
   - App name: `Portfolio Agenda`
   - User support email
   - Developer contact information (email)
4. **Scopes**:
   - Para este MVP se usa acceso completo a Calendar:
     - `https://www.googleapis.com/auth/calendar`
   - (Más seguro a futuro) podrías reducir scope, pero con este MVP es el necesario.
5. Test users:
   - Añade tu email como usuario de prueba si el estado queda “Testing”.

Notas:
- Si la app está en “Testing”, Google limita a usuarios que estén en Test users.

---

## 4) Crear credenciales OAuth Client ID
1. Ve a **APIs & Services** → **Credentials**.
2. Click **Create credentials** → **OAuth client ID**.
3. Application type: **Web application**.
4. Name: `Portfolio Web OAuth`.

### 4.1 Authorized JavaScript origins
Añade el origen de tu web.
- En local (si pruebas con Apache en tu máquina):
  - `http://localhost`
  - o `http://localhost:80` (normalmente no hace falta)
- En producción:
  - `https://www.tu-dominio.com`

### 4.2 Authorized redirect URIs
Añade **exactamente** el callback de tu endpoint:
- Local:
  - `http://localhost/api/portfolio/calendar-auth-callback.php`
- Producción:
  - `https://www.tu-dominio.com/api/portfolio/calendar-auth-callback.php`

### 4.3 Ejemplos para tus dominios (evitar mismatches)
En OAuth, Google compara la URL de callback *literalmente*. Estos detalles importan:
- `http` vs `https`
- `www` vs sin `www`
- subdominios (`perfil.in` vs `juancarlosmacias.es`)

Ejemplos típicos (ajusta según tu instalación real):
- Producción (con `www`):
   - `https://www.juancarlosmacias.es/api/portfolio/calendar-auth-callback.php`
- Producción (sin `www`):
   - `https://juancarlosmacias.es/api/portfolio/calendar-auth-callback.php`
- Local:
   - `http://localhost/api/portfolio/calendar-auth-callback.php`

Recomendación práctica:
- Si tu web responde tanto con `www` como sin `www`, añade **ambas** redirect URIs.
- Si en algún entorno todavía sirves por `http`, añade ese `http` también (solo para local/dev).

5. Guarda.
6. Copia los valores:
   - **Client ID**
   - **Client secret**

---

## 5) Pegar la configuración en el proyecto
Tienes dos formas de configurar el proyecto:

### Opción A (recomendada para MVP): desde el Admin (BD)
1. Entra al panel admin.
2. Ve a **Configuración → Google Calendar**.
3. Activa Google Calendar y pega:
   - Client ID
   - Client Secret
   - Redirect URI
   - Calendar ID

Nota: en esta fase MVP, el Client Secret queda en texto plano en la tabla `system_config`.

### Opción B: desde `config.local.php`
Edita `admin/config/config.local.php` y añade (o completa) esta función:

```php
function get_google_calendar_config() {
    return [
        'enabled' => true,
        'client_id' => 'TU_CLIENT_ID',
        'client_secret' => 'TU_CLIENT_SECRET',
        'redirect_uri' => 'https://tu-dominio.com/api/portfolio/calendar-auth-callback.php',
        'calendar_id' => 'primary',
    ];
}
```

Notas:
- `calendar_id`:
  - `primary` usa tu calendario principal.
  - También puedes usar el ID de un calendario específico.

---

## 6) Probar el flujo OAuth
1. Abre en tu navegador:
   - `https://tu-dominio.com/api/portfolio/calendar-auth-start.php`
2. Pulsa “Continuar con Google”.
3. Autoriza el acceso.
4. Tras volver al callback deberías ver “Autorización completada”.

Si falla, revisa:
- Que el `redirect_uri` en Google Cloud coincide EXACTAMENTE con el que usas.
- Que `enabled` está a `true`.
- Que el servidor tiene cURL habilitado.

---

## 7) Probar disponibilidad y creación de evento
1. En el frontend, abre:
   - `https://tu-dominio.com/agendar`
2. Pulsa “Ver horarios disponibles”.
3. Pulsa “Reservar” en un hueco.

---

## 8) Problemas típicos y solución

### 8.1 redirect_uri_mismatch
Causa: el `redirect_uri` no coincide.
Solución:
- Copia/pega exactamente la URL del callback y añádela en “Authorized redirect URIs”.

### 8.2 App en modo Testing
Causa: no has publicado la app.
Solución:
- Añade tu email en **Test users**.

### 8.3 No se guarda el token
Causa: el backend guarda en `system_config` (key `google_calendar_oauth_token`).
Solución:
- Verifica que existe la tabla `system_config` y que el usuario de DB tiene permisos.
- Si no existe, habrá que añadir una migración/tabla (lo podemos hacer).

### 8.4 401 / token expirado
Causa: expiración normal.
Solución:
- El sistema refresca con `refresh_token` automáticamente.
- Si no hay `refresh_token`, fuerza re-consent (el flujo ya lo hace con `prompt=consent`).

---

## 9) Siguiente mejora recomendada
- Reducir scopes si se desea (seguridad).
- Añadir confirmación previa a crear evento (si se habilita modo agente LLM).
- Añadir Google Meet automático (conferenceData) si lo necesitas.
