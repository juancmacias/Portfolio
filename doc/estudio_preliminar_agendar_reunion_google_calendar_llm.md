# Estudio preliminar: “Agendar una reunión” + agente con Google Calendar (LLM)

Fecha: 27/03/2026  
Repositorio: Portfolio (PHP + React)

## 1) Objetivo
Sustituir en la barra superior el enlace **“Políticas de Privacidad”** por **“Agendar una reunión”**.

Al pulsarlo, cargar un layout/página que permita:
1) Consultar el calendario de Google (tu calendario) para **mostrar huecos libres**.
2) Elegir un horario.
3) **Crear un evento** en Google Calendar.
4) (Opcional) Hacerlo de forma conversacional usando los LLM ya integrados (Groq / OpenAI/GitHub Models / Hugging Face).

## 2) Estado actual (lo que ya existe)
- Frontend React (React Router) con navbar en `frontend/src/components/Navbar.js`.
- Ruta de políticas en `/politics` (`frontend/src/App.js`).
- Backend PHP con API pública en `api/portfolio/`.
- Infraestructura LLM ya operativa para chat RAG: `api/portfolio/chat-rag.php` + `admin/classes/AIContentGenerator.php` y prompts/RAG.

Esto es importante porque **ya hay un “canal” estable frontend→API PHP→LLM**. Para calendar, lo ideal es seguir el mismo patrón: frontend llama a endpoints PHP, y el backend habla con Google.

## 3) ¿Es difícil?
No es “muy difícil”, pero **sí tiene complejidad real** por:
- **OAuth 2.0** de Google (consent, tokens, refresh tokens).
- Seguridad: evitar exponer secretos en el navegador.
- Diseño de flujo: selección de fecha, zona horaria, duración, buffers, etc.

Aun así, es un caso muy estándar y se puede implementar por fases.

## 4) Opciones de implementación (de menor a mayor esfuerzo)

### Opción A — Enlace directo a Calendly / Google Appointment Schedules (recomendación si quieres algo inmediato)
- Cambias el link a “Agendar una reunión” y apuntas a un enlace de Calendly o al sistema de “Appointment schedules” de Google.
- Pros: casi cero código, robusto, sin OAuth propio.
- Contras: no hay “agente” ni control fino con LLM.

**Tiempo estimado**: 10–30 min.

### Opción B — Página propia + integración Google Calendar (sin LLM)
- Layout/página “Agendar reunión” con UI simple:
  - Selector de rango de fechas (p. ej. próximos 7/14 días).
  - Duración (30/45/60 min).
  - Lista de huecos y botón “Reservar”.
- Backend PHP:
  - Endpoint para obtener “free/busy”.
  - Endpoint para crear evento.
- Pros: control total, UX consistente con tu portfolio.
- Contras: requiere OAuth y gestionar tokens.

**Tiempo estimado**: 1–3 días (dependiendo de OAuth y despliegue).

### Opción C — Agente conversacional con “tools” (LLM) que consulta huecos y crea eventos
- El LLM actúa como “orquestador”:
  - Pregunta duración, fecha preferida, franja horaria, email del asistente, etc.
  - Llama herramientas (funciones) del backend:
    - `getAvailability(...)`
    - `createEvent(...)`
- Importante: el LLM **no debe** hablar con Google directamente; debe invocar endpoints internos.
- Pros: experiencia “asistente” moderna.
- Contras: más piezas (estado conversacional, validación, confirmaciones).

**Tiempo estimado**: 3–7 días (MVP), más si se busca UX impecable.

## 5) Arquitectura recomendada (segura y simple)

### 5.1 Componentes
- **Frontend (React)**
  - Nueva ruta: `/agendar` (o `/meeting`).
  - Vista con dos modos:
    - “Rápido” (UI directa de slots)
    - “Asistente” (chat)
- **API PHP (pública, `api/portfolio/`)**
  - `calendar-auth-start.php`: inicia OAuth (redirect a Google)
  - `calendar-auth-callback.php`: recibe `code`, intercambia por tokens
  - `calendar-availability.php`: obtiene huecos (free/busy)
  - `calendar-create-event.php`: crea evento
  - (Opcional) `calendar-agent.php`: endpoint de “agent loop” que llama al LLM y ejecuta tools
- **Storage**
  - Guardar tokens OAuth de forma segura:
    - O bien en tabla `system_config`/nueva tabla `oauth_tokens`.
    - O bien en archivo fuera de webroot (menos recomendable en hosting compartido).

### 5.2 Autenticación Google (elección)
- Para “tu calendario personal” lo normal es:
  - **OAuth 2.0 con cuenta Google** (consentimiento) y refresh token.
- Service Account solo funciona “bien” si:
  - Es un Google Workspace con delegación de dominio, o
  - Se comparte un calendario específico con la service account (posible, pero hay matices).

**Recomendación**: OAuth 2.0 clásico con refresh token almacenado en servidor.

### 5.3 Obtención de huecos libres
Hay dos enfoques:
- **FreeBusy API**: dado un rango, devuelve ocupación.
- **Events + reglas propias**: list events y calculas huecos.

**Recomendación**: empezar con FreeBusy (más simple), luego añadir reglas:
- Horario laboral (p. ej. 10–14 y 16–19)
- Buffer antes/después (p. ej. 10 min)
- Excluir festivos

### 5.4 Creación de evento
- Crear evento con:
  - `summary`: “Reunión con {nombre}”
  - `start/end`
  - `attendees` (si quieres invitar)
  - `conferenceData` (si quieres Google Meet; requiere parámetro adicional)

## 6) Diseño del “agente” (si usamos LLM)

### 6.1 Patrón recomendado: LLM + Tools
- El chat existente (`chat-rag.php`) es RAG para portfolio.
- Para agenda conviene separar:
  - Un “system prompt” de scheduling.
  - Tools estrictas con schema (validación):
    - `get_availability({ start, end, durationMinutes, timezone })`
    - `create_event({ start, end, timezone, title, attendeeEmail?, notes? })`

### 6.2 Guardrails necesarios
- Confirmación explícita antes de crear evento.
- Validar zona horaria.
- No permitir que el LLM inyecte datos a Google sin validar.
- Logging de acciones (auditoría) sin guardar datos sensibles en logs.

### 6.3 Estado conversacional
- Guardar “draft booking” (preferencias) por `session_id`.
- Reutilizar `enhanced_conversations` o crear una tabla ligera `calendar_sessions`.

## 7) Privacidad y cumplimiento
Cambiar el enlace “Políticas de Privacidad” por “Agendar” puede ser delicado: normalmente la política debe seguir accesible.

**Recomendación**:
- Mantener la política en el footer o en otra sección (por ejemplo en `Footer`) o dentro de “Términos”.

Datos sensibles:
- Emails/ nombres de asistentes.
- Detalles de eventos.
- Tokens OAuth.

Medidas mínimas:
- Tokens en servidor, nunca en frontend.
- Variables/secretos en `admin/config/config.local.php` o variables de entorno.
- Endpoints con rate limiting básico y validación de inputs.

## 8) Plan por fases (MVP realista)

### Fase 0 (UI mínima)
- Cambiar enlace del navbar a “Agendar una reunión”.
- Crear ruta `/agendar` con un layout básico explicando el proceso.

### Fase 1 (Google Calendar sin LLM)
- Implementar OAuth en backend.
- Implementar disponibilidad + creación de evento.
- UI con lista de slots.

### Fase 2 (Agente LLM)
- Nuevo endpoint tipo `calendar-agent.php`:
  - Llama al LLM.
  - Detecta intención.
  - Ejecuta tools del calendario.
- UI de chat dentro de la página `/agendar`.

### Fase 3 (Calidad)
- Google Meet auto.
- Cancelación/reprogramación.
- Gestión de buffers/horarios/festivos.

## 9) Estimación de esfuerzo
- **Opción A**: 0.5h.
- **Opción B (MVP)**: 1–3 días.
- **Opción C (MVP agente)**: +2–5 días sobre la Opción B.

Depende mucho de:
- Si ya tienes credenciales Google Cloud creadas.
- Dónde se despliega (hosting compartido vs VPS) y si permite HTTPS estable (OAuth lo necesita).

## 10) Riesgos y decisiones pendientes (para cerrar el alcance)
1) ¿Debe seguir visible “Políticas de privacidad”? (recomendado que sí)
2) ¿Reuniones de 30/60 min? ¿horario laboral fijo?
3) ¿Se invita a la otra persona por email, o solo bloqueas tu calendario?
4) ¿Zona horaria: Europe/Madrid?
5) ¿Quieres Google Meet automático?

## 11) Recomendación final
- Si quieres valor rápido: **Opción A**.
- Si quieres control y “agente” real: **Opción B → C por fases**.

Si me confirmas las decisiones del punto 10, puedo pasar del estudio a implementación con cambios mínimos (navbar + nueva ruta + endpoints PHP + OAuth + disponibilidad + creación de eventos).
