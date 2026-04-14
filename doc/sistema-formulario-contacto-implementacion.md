# Sistema de Formulario de Contacto - Implementación Completa

## 📋 Resumen de Cambios

Se ha implementado un **sistema completo de formulario de contacto** como alternativa GDPR-compliant al sistema de agendamiento con LLM. Esta solución garantiza que los datos personales de los usuarios nunca se envíen a APIs externas de terceros.

## 🗂️ Archivos Creados

### 1. Backend - Base de Datos
**`database/migrations/006_create_contact_submissions.sql`**
- Tabla `contact_submissions` con workflow completo (new → read → replied → archived)
- 15 campos: id, name, email, phone, subject, message, status, user_ip, user_agent, referrer, admin_notes, replied_at, replied_by, created_at, updated_at
- 4 índices optimizados para consultas rápidas
- Configuración del sistema en `system_config`

### 2. Backend - API REST
**`api/portfolio/contact-submit.php`**
- Endpoint POST para recibir formularios
- Validación completa de campos (name ≥3, email válido, subject ≥5, message ≥10)
- Rate limiting: 10 minutos entre envíos por IP
- Sanitización HTML contra XSS
- Protección SQL Injection con queries parametrizadas
- Integración con TelegramNotifier para alertas en tiempo real
- Respuestas JSON estructuradas con timestamp

### 3. Backend - Panel de Administración
**`admin/pages/contact-submissions.php`**
- Dashboard con estadísticas (nuevos, leídos, respondidos, archivados)
- Filtros por estado con contador en tiempo real
- Lista paginada (20 registros por página)
- Vista detallada con toda la información del usuario
- Acciones: Marcar como leído, responder, archivar, eliminar
- Sistema de notas admin para seguimiento interno
- Botón directo de email para responder desde cliente de correo
- Auto-marcado como "leído" al abrir detalles

### 4. Frontend - Hook Personalizado
**`frontend/src/hooks/useContactForm.js`**
- Gestión de estado del formulario (formData, errors, isSubmitting, submitSuccess, submitError)
- Validación client-side con feedback inmediato
- Integración con API (detección automática localhost/producción)
- Manejo de errores con mensajes user-friendly
- Reset automático del formulario tras envío exitoso
- Scroll al top para mostrar mensajes de confirmación

### 5. Frontend - Página de Contacto
**`frontend/src/components/Contact/ContactPage.jsx`**
- Diseño de dos columnas responsive (información + formulario)
- Sección izquierda: Email, teléfono, ubicación, redes sociales
- Indicador de disponibilidad con animación pulsante
- Formulario con validación en tiempo real
- Campos: Nombre*, Email*, Teléfono, Asunto*, Mensaje*
- Mensajes de éxito/error con estilo Bootstrap Alert
- Loading state durante envío (spinner + texto "Enviando...")
- Nota de privacidad en el footer del formulario

### 6. Frontend - Estilos CSS
**`frontend/src/components/Contact/ContactPage.css`**
- Theme oscuro/claro con CSS variables
- Cards con efecto hover (box-shadow + transform)
- Animación pulse para indicador de disponibilidad
- Estilos personalizados para inputs (focus, disabled, invalid)
- Botón submit con gradiente y efecto hover
- Alertas personalizadas (success verde, error rojo)
- Responsive: tablets (768px) y móviles (576px)

### 7. Navegación - Actualización
**`frontend/src/components/Navbar.js`**
- Cambiado enlace de "/agendar" a "/contacto"
- Añadido icono AiOutlineMail
- Texto actualizado a "Contacto"

**`frontend/src/App.js`**
- Importado componente ContactPage
- Ruta actualizada: `/contacto` → `<ContactPage />`

### 8. Script de Migración Helper
**`admin/run-contact-migration.php`**
- Ejecuta automáticamente todos los statements de la migración
- Verifica creación de tabla y estructura
- Muestra configuración del sistema
- Enlace directo al panel admin tras ejecución
- **ELIMINAR después de usar**

## 🚀 Pasos para Activar el Sistema

### Paso 1: Ejecutar la Migración SQL

**Opción A: Navegador (recomendado)**
```
http://localhost/admin/run-contact-migration.php
```
- Abre en tu navegador
- Verificará la creación de la tabla y configuraciones
- **Elimina el archivo después de ejecutarlo**

**Opción B: CLI (MySQL)**
```bash
mysql -u tu_usuario -p portfolio_db < database/migrations/006_create_contact_submissions.sql
```

**Opción C: phpMyAdmin**
1. Selecciona tu base de datos
2. Ve a la pestaña "SQL"
3. Copia y pega el contenido de `006_create_contact_submissions.sql`
4. Ejecuta

### Paso 2: Verificar Configuración de Telegram (Opcional)

Si ya tienes configurado TelegramNotifier, recibirás notificaciones automáticamente. Si no:

1. Edita `admin/config/config.local.php`
2. Añade la función `get_telegram_config()`:
```php
function get_telegram_config() {
    return [
        'enabled' => true,
        'bot_token' => 'TU_BOT_TOKEN',
        'chat_id' => 'TU_CHAT_ID'
    ];
}
```

### Paso 3: Probar Frontend

**Desarrollo:**
```bash
cd frontend
npm install  # Si no lo has hecho
npm start    # Puerto 3000
```

Navega a: http://localhost:3000/contacto

**Producción:**
```bash
cd frontend
npm run build
```

Los archivos generados en `frontend/build/` están listos para subir al servidor.

### Paso 4: Probar Formulario

1. **Testing Local:**
   - Ve a http://localhost:3000/contacto
   - Rellena el formulario con datos de prueba
   - Envía

2. **Verificar Base de Datos:**
   - Abre phpMyAdmin
   - Tabla `contact_submissions`
   - Deberías ver un nuevo registro con status "new"

3. **Verificar Notificación Telegram:**
   - Si está configurado, recibirás un mensaje instantáneo
   - Formato: "📬 Nuevo Mensaje de Contacto #[ID]"

4. **Probar Panel Admin:**
   - Ve a http://localhost/admin/pages/contact-submissions.php
   - Deberías ver el envío en la lista
   - Verifica acciones: Ver detalles, marcar como leído, etc.

### Paso 5: Testing de Rate Limiting

1. Envía el formulario correctamente
2. **Inmediatamente**, intenta enviarlo de nuevo
3. Deberías recibir error: "Por favor, espera al menos 10 minutos entre envíos"
4. Esto protege contra spam

## 🧪 Testing Completo - Checklist

### ✅ Frontend
- [ ] Página `/contacto` carga correctamente
- [ ] Formulario responsive en móvil/tablet
- [ ] Validación muestra errores en rojo
- [ ] Email inválido rechazado (ej: "test@")
- [ ] Campos mínimos validados (nombre 3+, asunto 5+, mensaje 10+)
- [ ] Loading state durante envío (spinner visible)
- [ ] Mensaje de éxito aparece tras envío
- [ ] Formulario se resetea tras éxito
- [ ] Error de rate limiting muestra alerta roja

### ✅ Backend - API
- [ ] POST a `/api/portfolio/contact-submit.php` funciona
- [ ] Campos requeridos validados server-side
- [ ] Rate limiting bloquea segundo envío en 10 min
- [ ] Registro insertado en DB con metadata (IP, user_agent)
- [ ] Notificación Telegram enviada (si configurado)
- [ ] Respuesta JSON correcta (`success: true`)

### ✅ Backend - Admin Panel
- [ ] `/admin/pages/contact-submissions.php` carga
- [ ] Estadísticas actualizadas (nuevos, leídos, etc.)
- [ ] Filtros funcionan (all, new, read, replied, archived)
- [ ] Paginación funciona con más de 20 registros
- [ ] Vista detallada muestra toda la información
- [ ] Botón "Marcar como leído" funciona
- [ ] Botón "Responder por Email" abre cliente de correo
- [ ] Sistema de notas admin guarda correctamente
- [ ] Archivar cambia estado a "archived"
- [ ] Eliminar borra el registro (con confirmación)

### ✅ Seguridad
- [ ] SQL Injection: Intentar `'; DROP TABLE contact_submissions; --` en nombre
- [ ] XSS: Intentar `<script>alert('XSS')</script>` en mensaje
- [ ] Rate Limiting: 2 envíos seguidos bloqueados
- [ ] CORS: Frontend en otro puerto puede acceder a API

## 📊 Configuración del Sistema

Las siguientes configuraciones están disponibles en la tabla `system_config`:

| Key | Valor Default | Descripción |
|-----|---------------|-------------|
| `contact_form_enabled` | `1` | 1 = activado, 0 = desactivado |
| `contact_notification_enabled` | `1` | 1 = notificaciones Telegram ON |
| `contact_rate_limit_minutes` | `10` | Minutos entre envíos por IP |
| `contact_auto_reply_enabled` | `0` | Auto-respuesta por email (futuro) |

**Modificar configuración:**
```sql
UPDATE system_config 
SET config_value = '5' 
WHERE config_key = 'contact_rate_limit_minutes';
```

## 🔐 Cumplimiento GDPR

### ✅ Garantías de Privacidad

1. **Datos en servidor propio**: Ningún dato personal se envía a APIs externas (Groq, OpenAI, etc.)
2. **Almacenamiento controlado**: Base de datos MySQL en servidor propio con acceso restringido
3. **Eliminación bajo demanda**: Script de eliminación disponible en admin panel
4. **Transparencia**: Nota de privacidad visible en el formulario
5. **Seguridad**: HTTPS obligatorio en producción, conexión DB cifrada

### 📄 Aviso de Privacidad (añadir a política)

```
Los datos personales proporcionados a través del formulario de contacto (nombre, email, 
teléfono, mensaje) se almacenan en nuestra base de datos privada exclusivamente para:
- Responder a consultas y solicitudes
- Gestionar comunicaciones relacionadas con servicios ofrecidos

No se comparten con terceros ni se usan para marketing sin consentimiento explícito.
Puedes solicitar la eliminación de tus datos contactando a juancmaciassalvador@gmail.com
```

## 🐛 Troubleshooting

### Error: "Cannot find module 'react-icons/ai'"
```bash
cd frontend
npm install react-icons
```

### Error: "Database connection failed"
- Verifica `admin/config/config.local.php`
- Asegúrate que DB credentials son correctos
- Comprueba que MySQL esté ejecutándose

### Error: "Tabla contact_submissions no existe"
- Ejecuta la migración: `admin/run-contact-migration.php`

### Rate limiting no funciona
- Verifica que la IP se capture correctamente:
```sql
SELECT user_ip, created_at FROM contact_submissions ORDER BY created_at DESC LIMIT 5;
```
- Si todas las IPs son NULL, revisa `$_SERVER['REMOTE_ADDR']` en `contact-submit.php`

### Notificaciones Telegram no llegan
1. Verifica configuración en `config.local.php`
2. Comprueba que `TelegramNotifier` clase existe
3. Mira logs de error: `logs/chat/` o `error_log`
4. Test manual del bot:
```php
require_once 'admin/classes/TelegramNotifier.php';
$notifier = new TelegramNotifier();
$notifier->sendMessage("Test desde contact form");
```

### Frontend no conecta con backend
- Verifica detección de URL en `useContactForm.js` línea 71-73
- Comprueba CORS headers en `contact-submit.php`
- Inspecciona Network tab en DevTools:
  - Status: debe ser 200
  - Response: debe ser JSON válido

## 📈 Métricas y Analytics

El sistema registra automáticamente:
- **user_ip**: Para rate limiting y analytics de ubicación
- **user_agent**: Navegador y dispositivo del visitante
- **referrer**: URL desde donde llegó al formulario
- **created_at**: Timestamp del envío

**Query útil para estadísticas:**
```sql
-- Mensajes por día
SELECT DATE(created_at) as fecha, COUNT(*) as total 
FROM contact_submissions 
GROUP BY DATE(created_at) 
ORDER BY fecha DESC;

-- Tasa de respuesta
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as respondidos,
    ROUND(SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as tasa_respuesta
FROM contact_submissions;

-- Tiempo promedio de respuesta
SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, replied_at)) as horas_promedio
FROM contact_submissions 
WHERE replied_at IS NOT NULL;
```

## 🔄 Próximas Mejoras (Opcional)

### Fase 2 - Features Avanzados
- [ ] **Auto-Reply**: Email automático confirmando recepción
- [ ] **Adjuntos**: Permitir subir archivos (CV, portfolio)
- [ ] **Captcha**: reCAPTCHA v3 para protección avanzada
- [ ] **Email Admin**: Notificación por email además de Telegram
- [ ] **Templates**: Plantillas de respuesta rápida en admin panel
- [ ] **Analytics Dashboard**: Gráficos de mensajes por día/mes
- [ ] **Etiquetas**: Sistema de tags para categorizar consultas
- [ ] **Búsqueda Avanzada**: Filtros por fecha, keywords, estado

### Fase 3 - Integraciones
- [ ] **CRM Integration**: Exportar contactos a HubSpot/Pipedrive
- [ ] **Slack**: Alternativa a Telegram para notificaciones
- [ ] **Zapier**: Automatizaciones con herramientas externas
- [ ] **Calendar Booking**: Link directo para agendar reunión tras contacto inicial

## 📞 Contacto y Soporte

Si encuentras problemas durante la implementación:
- **Email**: juancmaciassalvador@gmail.com
- **GitHub Issues**: Abre un issue en el repositorio del proyecto
- **Telegram**: (si tienes el contacto personal)

---

## ✅ Estado Actual

**Backend**: ✅ 100% Completo
- Database schema ✅
- API endpoint ✅
- Admin panel ✅
- Telegram notifications ✅

**Frontend**: ✅ 100% Completo
- ContactPage component ✅
- useContactForm hook ✅
- CSS styling ✅
- Navbar link updated ✅
- App.js routing ✅

**Testing**: ⏳ Pendiente
- Ejecutar migración
- Probar formulario end-to-end
- Verificar panel admin
- Validar rate limiting

**Deployment**: ⏳ Pendiente
- Build frontend
- Subir archivos a servidor
- Ejecutar migración en producción

---

**Fecha de creación**: <?= date('Y-m-d H:i:s') ?>  
**Versión**: 1.0  
**Autor**: Juan Carlos Macías
