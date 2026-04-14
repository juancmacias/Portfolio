# 🗓️ Sistema de Agendado de Citas - Requerimientos Simplificados

**Fecha**: 13 de abril de 2026  
**Versión**: 2.0 (Revisada)  
**Autor**: Análisis basado en requerimientos específicos del usuario

---

## 📋 Requerimientos REALES del Usuario

### Lo que se necesita:

1. **Página standalone `/agendar`** (no en el chat principal del portfolio)
2. **Chat conversacional específico** para el proceso de agendado (LLM guía el proceso)
3. **Leer calendario personal** y mostrar **3 próximos slots libres**
4. **Duración fija**: 30 minutos por reunión
5. **Proceso guiado**:
   ```
   Bot: "¡Hola! Tengo estos 3 horarios disponibles:"
   → Mostrar 3 slots
   
   Usuario: Selecciona uno
   
   Bot: "Perfecto. ¿Cuál es tu nombre?"
   Usuario: "Juan Pérez"
   
   Bot: "¿Y tu email para enviarte la invitación?"
   Usuario: "juan@example.com"
   
   Bot: "✅ ¡Listo! He agendado la reunión y te he enviado 
        la invitación a juan@example.com"
   ```

6. **Crear evento** en el calendario personal del dueño (Juan Carlos)
7. **Enviar invitación automática** al visitante vía Google Calendar (campo `attendees`)

---

## 🎯 Lo que NO se necesita (simplificación)

❌ Detección de intenciones en el chat principal del portfolio  
❌ Sistema complejo de múltiples acciones  
❌ Integración en ChatModal.jsx existente  
❌ Logging excesivo tipo Google Sheets  
❌ Mostrar 5+ slots con configuración dinámica  

✅ Solo necesitamos: **Página dedicada + Chat simple + 3 slots + Recoger datos + Crear evento**

---

## 🏗️ Arquitectura Simplificada

### Componentes necesarios:

```
1. Frontend:
   /agendar ruta en React
   ├── SchedulingChatPage.jsx (layout de la página)
   ├── SchedulingChatBox.jsx (chat específico)
   └── useSchedulingChat.js (hook dedicado)

2. Backend:
   api/portfolio/
   ├── calendar-availability.php (YA EXISTE) ✅
   ├── calendar-create-event.php (YA EXISTE) ✅
   └── scheduling-chat.php (NUEVO - chat LLM específico)

3. Base de datos:
   └── scheduling_sessions (tabla para mantener estado)
```

---

## 🔄 Flujo de Conversación Detallado

### Estado 1: Inicio → Mostrar Slots

**Backend** (`scheduling-chat.php`):
```php
// Al iniciar sesión de scheduling
$slots = getNext3AvailableSlots(); // Llamar calendar-availability.php
$sessionId = uniqid('sched_');

// Guardar en BD
saveSchedulingSession($sessionId, [
    'state' => 'awaiting_slot_selection',
    'slots' => $slots,
    'user_data' => []
]);

// Respuesta
return [
    'message' => "¡Hola! Estos son mis próximos 3 horarios disponibles para una reunión de 30 minutos:",
    'slots' => $slots,
    'state' => 'awaiting_slot_selection'
];
```

**Frontend** renderiza:
```
🤖 Bot:
¡Hola! Estos son mis próximos 3 horarios disponibles 
para una reunión de 30 minutos:

┌────────────────────────────────┐
│ 1️⃣ Lunes 15 Abril - 10:00    │ [Seleccionar]
├────────────────────────────────┤
│ 2️⃣ Lunes 15 Abril - 16:00    │ [Seleccionar]
├────────────────────────────────┤
│ 3️⃣ Martes 16 Abril - 11:00   │ [Seleccionar]
└────────────────────────────────┘

Escribe el número (1, 2 o 3) o haz clic en "Seleccionar"
```

---

### Estado 2: Slot Seleccionado → Pedir Nombre

**Usuario** escribe: "1" o "el primero" o clic

**Backend** detecta:
```php
$sessionState = getSchedulingSession($sessionId);

if ($sessionState['state'] === 'awaiting_slot_selection') {
    // Detectar selección (regex simple)
    $slotIndex = detectSlotNumber($userMessage); // 0, 1, o 2
    
    if ($slotIndex !== null) {
        updateSchedulingSession($sessionId, [
            'state' => 'awaiting_name',
            'selected_slot_index' => $slotIndex
        ]);
        
        return [
            'message' => "Perfecto, he reservado el horario: {$slots[$slotIndex]['formatted']}. ¿Cuál es tu nombre completo?",
            'state' => 'awaiting_name'
        ];
    }
}
```

**Frontend** renderiza:
```
🧑 Usuario:
El 1

🤖 Bot:
Perfecto, he reservado el horario: Lunes 15 Abril 10:00-10:30
¿Cuál es tu nombre completo?
```

---

### Estado 3: Nombre Recibido → Pedir Email

**Usuario** escribe: "Juan Pérez García"

**Backend**:
```php
if ($sessionState['state'] === 'awaiting_name') {
    $name = trim($userMessage);
    
    // Validación básica
    if (strlen($name) < 3) {
        return [
            'message' => "Por favor, indícame tu nombre completo.",
            'state' => 'awaiting_name'
        ];
    }
    
    updateSchedulingSession($sessionId, [
        'state' => 'awaiting_email',
        'user_data' => ['name' => $name]
    ]);
    
    return [
        'message' => "Encantado, {$name}. ¿Cuál es tu email para enviarte la invitación del calendario?",
        'state' => 'awaiting_email'
    ];
}
```

**Frontend**:
```
🧑 Usuario:
Juan Pérez García

🤖 Bot:
Encantado, Juan Pérez García. ¿Cuál es tu email 
para enviarte la invitación del calendario?
```

---

### Estado 4: Email Recibido → Crear Evento y Confirmar

**Usuario** escribe: "juan.perez@example.com"

**Backend**:
```php
if ($sessionState['state'] === 'awaiting_email') {
    $email = trim($userMessage);
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'message' => "El email no parece válido. Por favor, indícame un email correcto.",
            'state' => 'awaiting_email'
        ];
    }
    
    // Obtener datos de sesión
    $slot = $sessionState['slots'][$sessionState['selected_slot_index']];
    $name = $sessionState['user_data']['name'];
    
    // CREAR EVENTO en Google Calendar con invitación
    $eventResult = createEventWithInvitation(
        $slot['start'],
        $slot['end'],
        "Reunión con {$name}",
        "Reunión agendada a través del portfolio",
        [$email] // Attendees - Google Calendar enviará invitación automática
    );
    
    if ($eventResult['success']) {
        // Marcar sesión como completada
        updateSchedulingSession($sessionId, [
            'state' => 'completed',
            'user_data' => ['name' => $name, 'email' => $email],
            'event_id' => $eventResult['event_id']
        ]);
        
        return [
            'message' => "✅ ¡Perfecto! He agendado la reunión para el {$slot['formatted']}.\n\n" .
                        "📧 Te he enviado una invitación de calendario a {$email}.\n\n" .
                        "Recibirás un email de Google Calendar con los detalles y podrás agregarlo a tu calendario.\n\n" .
                        "¡Nos vemos pronto!",
            'state' => 'completed',
            'event_link' => $eventResult['html_link']
        ];
    } else {
        return [
            'message' => "❌ Lo siento, hubo un error al crear la reunión. Por favor, contacta directamente a través de email.",
            'state' => 'error'
        ];
    }
}
```

**Frontend**:
```
🧑 Usuario:
juan.perez@example.com

🤖 Bot:
✅ ¡Perfecto! He agendado la reunión para el 
   Lunes 15 Abril 10:00-10:30.

📧 Te he enviado una invitación de calendario a 
   juan.perez@example.com.

Recibirás un email de Google Calendar con los detalles 
y podrás agregarlo a tu calendario.

¡Nos vemos pronto!

[Botón: Agendar otra reunión]
```

---

## 💾 Base de Datos

### Tabla: `scheduling_sessions`

```sql
CREATE TABLE IF NOT EXISTS scheduling_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) UNIQUE NOT NULL,
    
    -- Estado de la conversación
    state ENUM(
        'awaiting_slot_selection',
        'awaiting_name',
        'awaiting_email',
        'completed',
        'error',
        'abandoned'
    ) DEFAULT 'awaiting_slot_selection',
    
    -- Datos de slots disponibles
    available_slots JSON,
    selected_slot_index INT NULL,
    
    -- Datos del usuario
    user_name VARCHAR(255) NULL,
    user_email VARCHAR(255) NULL,
    
    -- Evento creado
    google_event_id VARCHAR(255) NULL,
    google_event_link TEXT NULL,
    
    -- Metadata
    user_ip VARCHAR(45),
    user_agent TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_session_id (session_id),
    INDEX idx_state (state),
    INDEX idx_created_at (created_at)
);
```

---

## 🔧 Implementación Backend

### 1. `api/portfolio/scheduling-chat.php`

```php
<?php
/**
 * Chat conversacional específico para agendado de citas
 * Flujo: Slots → Nombre → Email → Crear evento
 */

require_once __DIR__ . '/../../admin/config/database.php';
require_once __DIR__ . '/../../admin/classes/AIContentGenerator.php';
require_once __DIR__ . '/calendar-lib.php';

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(false, null, 'Método no permitido', 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    $userMessage = trim($input['message'] ?? '');
    $sessionId = $input['session_id'] ?? null;
    $action = $input['action'] ?? 'message'; // message | start | reset
    
    $db = Database::getInstance();
    
    // ACCIÓN: Iniciar nueva sesión
    if ($action === 'start' || !$sessionId) {
        $result = startSchedulingSession($db);
        respondJson(true, $result);
    }
    
    // ACCIÓN: Resetear sesión
    if ($action === 'reset') {
        $result = startSchedulingSession($db);
        respondJson(true, $result);
    }
    
    // ACCIÓN: Mensaje en conversación existente
    if (empty($userMessage)) {
        throw new Exception('Mensaje vacío');
    }
    
    // Obtener estado de sesión
    $session = getSchedulingSession($db, $sessionId);
    if (!$session) {
        throw new Exception('Sesión no encontrada');
    }
    
    // Procesar según estado
    $result = processUserMessage($db, $session, $userMessage);
    
    respondJson(true, $result);
    
} catch (Exception $e) {
    respondJson(false, null, $e->getMessage(), 500);
}

// ============= FUNCIONES =============

function respondJson($success, $data = null, $error = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

function startSchedulingSession($db) {
    $sessionId = uniqid('sched_', true);
    
    // Obtener próximos 3 slots disponibles
    $slots = getNext3Slots();
    
    if (empty($slots)) {
        throw new Exception('No hay horarios disponibles en este momento');
    }
    
    // Crear sesión en BD
    $db->execute(
        "INSERT INTO scheduling_sessions (session_id, state, available_slots, user_ip, user_agent) 
         VALUES (?, ?, ?, ?, ?)",
        [
            $sessionId,
            'awaiting_slot_selection',
            json_encode($slots),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]
    );
    
    return [
        'session_id' => $sessionId,
        'message' => "¡Hola! 👋\n\nEstos son mis próximos 3 horarios disponibles para una reunión de 30 minutos:",
        'slots' => $slots,
        'state' => 'awaiting_slot_selection'
    ];
}

function getNext3Slots() {
    // Llamar a calendar-availability.php internamente
    $start = date('c');
    $end = date('c', strtotime('+14 days')); // Próximos 14 días
    
    // Hacer request a calendar-availability
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'start' => $start,
                'end' => $end,
                'duration_minutes' => 30,
                'timezone' => 'Europe/Madrid'
            ])
        ]
    ]);
    
    $response = file_get_contents('http://localhost/api/portfolio/calendar-availability.php', false, $context);
    $data = json_decode($response, true);
    
    if (!$data || !$data['success']) {
        return [];
    }
    
    // Retornar solo los 3 primeros slots
    $slots = array_slice($data['data']['slots'] ?? [], 0, 3);
    
    // Formatear para visualización
    foreach ($slots as &$slot) {
        $start = new DateTime($slot['start']);
        $slot['formatted'] = $start->format('l j \d\e F - H:i') . ' (30 min)';
    }
    
    return $slots;
}

function getSchedulingSession($db, $sessionId) {
    $session = $db->fetchOne(
        "SELECT * FROM scheduling_sessions WHERE session_id = ?",
        [$sessionId]
    );
    
    if ($session) {
        $session['available_slots'] = json_decode($session['available_slots'], true);
    }
    
    return $session;
}

function processUserMessage($db, $session, $userMessage) {
    $state = $session['state'];
    
    switch ($state) {
        case 'awaiting_slot_selection':
            return handleSlotSelection($db, $session, $userMessage);
            
        case 'awaiting_name':
            return handleNameInput($db, $session, $userMessage);
            
        case 'awaiting_email':
            return handleEmailInput($db, $session, $userMessage);
            
        default:
            return [
                'message' => 'Esta sesión ya ha finalizado. Por favor, inicia una nueva.',
                'state' => $session['state']
            ];
    }
}

function handleSlotSelection($db, $session, $userMessage) {
    $slots = $session['available_slots'];
    
    // Detectar número (1, 2, 3)
    $slotIndex = detectSlotNumber($userMessage, count($slots));
    
    if ($slotIndex === null) {
        return [
            'message' => "Por favor, selecciona un horario escribiendo 1, 2 o 3.",
            'slots' => $slots,
            'state' => 'awaiting_slot_selection'
        ];
    }
    
    // Actualizar sesión
    $db->execute(
        "UPDATE scheduling_sessions SET state = ?, selected_slot_index = ? WHERE session_id = ?",
        ['awaiting_name', $slotIndex, $session['session_id']]
    );
    
    $selectedSlot = $slots[$slotIndex];
    
    return [
        'message' => "Perfecto, he reservado el horario:\n\n📅 {$selectedSlot['formatted']}\n\n¿Cuál es tu nombre completo?",
        'selected_slot' => $selectedSlot,
        'state' => 'awaiting_name'
    ];
}

function detectSlotNumber($message, $maxSlots) {
    $message = strtolower(trim($message));
    
    // Buscar número (1, 2, 3)
    if (preg_match('/(?:opción|slot|número|el)?\s*(\d+)/', $message, $matches)) {
        $num = (int)$matches[1];
        if ($num >= 1 && $num <= $maxSlots) {
            return $num - 1; // Index 0-based
        }
    }
    
    // Buscar palabras
    if (strpos($message, 'primer') !== false || $message === '1') return 0;
    if (strpos($message, 'segund') !== false || $message === '2') return 1;
    if (strpos($message, 'tercer') !== false || $message === '3') return 2;
    
    return null;
}

function handleNameInput($db, $session, $userMessage) {
    $name = trim($userMessage);
    
    // Validación
    if (strlen($name) < 3) {
        return [
            'message' => "Por favor, indícame tu nombre completo (mínimo 3 caracteres).",
            'state' => 'awaiting_name'
        ];
    }
    
    // Actualizar sesión
    $db->execute(
        "UPDATE scheduling_sessions SET state = ?, user_name = ? WHERE session_id = ?",
        ['awaiting_email', $name, $session['session_id']]
    );
    
    return [
        'message' => "Encantado, {$name}. 😊\n\n¿Cuál es tu email para enviarte la invitación del calendario?",
        'state' => 'awaiting_email'
    ];
}

function handleEmailInput($db, $session, $userMessage) {
    $email = trim($userMessage);
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'message' => "El email no parece válido. Por favor, indícame un email correcto (ej: tumail@ejemplo.com).",
            'state' => 'awaiting_email'
        ];
    }
    
    // Obtener datos
    $slot = $session['available_slots'][$session['selected_slot_index']];
    $name = $session['user_name'];
    
    // CREAR EVENTO con invitación
    try {
        $eventResult = createEventWithAttendee(
            $slot['start'],
            $slot['end'],
            "Reunión con {$name}",
            "Reunión agendada a través del portfolio.\n\nAsistente: {$name}\nEmail: {$email}",
            $email,
            $name
        );
        
        if ($eventResult['success']) {
            // Actualizar sesión como completada
            $db->execute(
                "UPDATE scheduling_sessions 
                 SET state = ?, user_email = ?, google_event_id = ?, google_event_link = ?, completed_at = NOW() 
                 WHERE session_id = ?",
                [
                    'completed',
                    $email,
                    $eventResult['event_id'],
                    $eventResult['html_link'],
                    $session['session_id']
                ]
            );
            
            return [
                'message' => "✅ ¡Perfecto! He agendado la reunión.\n\n" .
                            "📅 **{$slot['formatted']}**\n\n" .
                            "📧 Te he enviado una invitación de calendario a **{$email}**.\n\n" .
                            "Recibirás un email de Google Calendar con los detalles y podrás agregarlo a tu calendario.\n\n" .
                            "¡Nos vemos pronto! 👋",
                'state' => 'completed',
                'event_link' => $eventResult['html_link']
            ];
        } else {
            throw new Exception($eventResult['error'] ?? 'Error desconocido');
        }
        
    } catch (Exception $e) {
        return [
            'message' => "❌ Lo siento, hubo un error al crear la reunión:\n\n{$e->getMessage()}\n\n" .
                        "Por favor, contacta directamente a través de email: juancmaciassalvador@gmail.com",
            'state' => 'error'
        ];
    }
}

function createEventWithAttendee($start, $end, $title, $description, $attendeeEmail, $attendeeName) {
    [$cfg, $accessToken] = calendar_get_valid_access_token();
    $calendarId = $cfg['calendar_id'] ?? 'primary';
    
    $eventData = [
        'summary' => $title,
        'description' => $description,
        'start' => [
            'dateTime' => $start,
            'timeZone' => 'Europe/Madrid'
        ],
        'end' => [
            'dateTime' => $end,
            'timeZone' => 'Europe/Madrid'
        ],
        'attendees' => [
            [
                'email' => $attendeeEmail,
                'displayName' => $attendeeName,
                'responseStatus' => 'needsAction'
            ]
        ],
        'reminders' => [
            'useDefault' => false,
            'overrides' => [
                ['method' => 'email', 'minutes' => 24 * 60], // 1 día antes
                ['method' => 'popup', 'minutes' => 30]
            ]
        ],
        // Enviar notificaciones automáticamente
        'sendUpdates' => 'all'
    ];
    
    $result = calendar_google_api_request(
        'POST',
        "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events",
        $accessToken,
        $eventData
    );
    
    if (isset($result['id'])) {
        return [
            'success' => true,
            'event_id' => $result['id'],
            'html_link' => $result['htmlLink'] ?? null
        ];
    } else {
        return [
            'success' => false,
            'error' => $result['error']['message'] ?? 'Error creando evento'
        ];
    }
}
```

---

## 🎨 Implementación Frontend

### 1. Hook: `useSchedulingChat.js`

```javascript
import { useState, useCallback } from 'react';

const useSchedulingChat = () => {
    const [messages, setMessages] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [sessionId, setSessionId] = useState(null);
    const [currentState, setCurrentState] = useState(null);
    const [availableSlots, setAvailableSlots] = useState([]);
    const [error, setError] = useState(null);
    
    const API_ENDPOINT = '/api/portfolio/scheduling-chat.php';
    
    // Iniciar sesión de scheduling
    const startSession = useCallback(async () => {
        setIsLoading(true);
        setError(null);
        
        try {
            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'start' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                setSessionId(data.data.session_id);
                setCurrentState(data.data.state);
                setAvailableSlots(data.data.slots || []);
                
                // Agregar mensaje del bot
                setMessages([{
                    id: Date.now(),
                    text: data.data.message,
                    sender: 'bot',
                    slots: data.data.slots,
                    timestamp: new Date().toISOString()
                }]);
            } else {
                setError(data.error || 'Error iniciando sesión');
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setIsLoading(false);
        }
    }, []);
    
    // Enviar mensaje
    const sendMessage = useCallback(async (userMessage) => {
        if (!userMessage.trim() || isLoading || !sessionId) return;
        
        setIsLoading(true);
        setError(null);
        
        // Agregar mensaje del usuario
        const userMsg = {
            id: Date.now(),
            text: userMessage,
            sender: 'user',
            timestamp: new Date().toISOString()
        };
        setMessages(prev => [...prev, userMsg]);
        
        try {
            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'message',
                    session_id: sessionId,
                    message: userMessage
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                setCurrentState(data.data.state);
                
                // Agregar respuesta del bot
                const botMsg = {
                    id: Date.now() + 1,
                    text: data.data.message,
                    sender: 'bot',
                    slots: data.data.slots,
                    selectedSlot: data.data.selected_slot,
                    eventLink: data.data.event_link,
                    state: data.data.state,
                    timestamp: new Date().toISOString()
                };
                setMessages(prev => [...prev, botMsg]);
            } else {
                setError(data.error || 'Error procesando mensaje');
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setIsLoading(false);
        }
    }, [sessionId, isLoading]);
    
    // Seleccionar slot mediante botón
    const selectSlot = useCallback((slotIndex) => {
        sendMessage(`${slotIndex + 1}`);
    }, [sendMessage]);
    
    // Reiniciar sesión
    const reset = useCallback(() => {
        setMessages([]);
        setSessionId(null);
        setCurrentState(null);
        setAvailableSlots([]);
        setError(null);
        startSession();
    }, [startSession]);
    
    return {
        messages,
        isLoading,
        error,
        sessionId,
        currentState,
        availableSlots,
        startSession,
        sendMessage,
        selectSlot,
        reset
    };
};

export default useSchedulingChat;
```

### 2. Componente de Página: `SchedulingPage.jsx`

```jsx
import React, { useEffect } from 'react';
import { Container, Row, Col, Card, Button, Form, InputGroup, Spinner } from 'react-bootstrap';
import useSchedulingChat from '../../hooks/useSchedulingChat';
import './SchedulingPage.css';

const SchedulingPage = () => {
    const {
        messages,
        isLoading,
        error,
        currentState,
        startSession,
        sendMessage,
        selectSlot,
        reset
    } = useSchedulingChat();
    
    const [inputValue, setInputValue] = React.useState('');
    
    // Iniciar sesión al montar
    useEffect(() => {
        startSession();
    }, [startSession]);
    
    const handleSubmit = (e) => {
        e.preventDefault();
        if (inputValue.trim()) {
            sendMessage(inputValue);
            setInputValue('');
        }
    };
    
    const formatDateTime = (isoString) => {
        const date = new Date(isoString);
        return date.toLocaleString('es-ES', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    
    return (
        <section className="scheduling-page">
            <Container>
                <Row className="justify-content-center">
                    <Col md={10} lg={8}>
                        <Card className="scheduling-card">
                            <Card.Header className="text-center">
                                <h2>📅 Agendar una Reunión</h2>
                                <p className="mb-0">Duración: 30 minutos</p>
                            </Card.Header>
                            
                            <Card.Body>
                                {/* Chat de mensajes */}
                                <div className="messages-container">
                                    {messages.map(msg => (
                                        <div key={msg.id} className={`message ${msg.sender}-message`}>
                                            <div className="message-content">
                                                <div className="message-text">
                                                    {msg.text}
                                                </div>
                                                
                                                {/* Renderizar slots si existen */}
                                                {msg.slots && msg.slots.length > 0 && (
                                                    <div className="slots-container">
                                                        {msg.slots.map((slot, index) => (
                                                            <div key={index} className="slot-item">
                                                                <div className="slot-info">
                                                                    <span className="slot-number">{index + 1}</span>
                                                                    <span className="slot-date">
                                                                        {formatDateTime(slot.start)}
                                                                    </span>
                                                                </div>
                                                                <Button
                                                                    variant="success"
                                                                    size="sm"
                                                                    onClick={() => selectSlot(index)}
                                                                    disabled={isLoading}
                                                                >
                                                                    Seleccionar
                                                                </Button>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                                
                                                {/* Slot seleccionado */}
                                                {msg.selectedSlot && (
                                                    <div className="alert alert-info mt-2">
                                                        <strong>Horario seleccionado:</strong><br/>
                                                        {msg.selectedSlot.formatted}
                                                    </div>
                                                )}
                                                
                                                {/* Estado completado */}
                                                {msg.state === 'completed' && (
                                                    <div className="mt-3">
                                                        <Button
                                                            variant="primary"
                                                            onClick={reset}
                                                        >
                                                            Agendar otra reunión
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                    
                                    {/* Loading indicator */}
                                    {isLoading && (
                                        <div className="message bot-message">
                                            <Spinner animation="border" size="sm" /> Procesando...
                                        </div>
                                    )}
                                    
                                    {/* Error */}
                                    {error && (
                                        <div className="alert alert-danger">
                                            {error}
                                        </div>
                                    )}
                                </div>
                                
                                {/* Input de mensaje (ocultar si está completado) */}
                                {currentState !== 'completed' && (
                                    <Form onSubmit={handleSubmit} className="mt-3">
                                        <InputGroup>
                                            <Form.Control
                                                type="text"
                                                placeholder="Escribe tu respuesta..."
                                                value={inputValue}
                                                onChange={(e) => setInputValue(e.target.value)}
                                                disabled={isLoading}
                                            />
                                            <Button
                                                variant="primary"
                                                type="submit"
                                                disabled={isLoading || !inputValue.trim()}
                                            >
                                                Enviar
                                            </Button>
                                        </InputGroup>
                                    </Form>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Container>
        </section>
    );
};

export default SchedulingPage;
```

### 3. Ruta en `App.js`

```jsx
import SchedulingPage from './components/Scheduling/SchedulingPage';

// Dentro de <Routes>
<Route path="/agendar" element={<SchedulingPage />} />
```

### 4. Enlace en Navbar

```jsx
<Nav.Link as={Link} to="/agendar">
    📅 Agendar Cita
</Nav.Link>
```

---

## ✅ Checklist de Implementación

### Backend:
- [ ] Crear tabla `scheduling_sessions` en BD
- [ ] Implementar `api/portfolio/scheduling-chat.php`
- [ ] Añadir función `createEventWithAttendee()` en `calendar-lib.php`
- [ ] Testing de flujo completo con Postman

### Frontend:
- [ ] Crear hook `useSchedulingChat.js`
- [ ] Crear componente `SchedulingPage.jsx`
- [ ] Añadir estilos CSS
- [ ] Agregar ruta `/agendar` en `App.js`
- [ ] Enlace en Navbar

### Testing:
- [ ] Flujo completo: Inicio → Slot → Nombre → Email → Confirmación
- [ ] Verificar recepción de email de Google Calendar
- [ ] Casos edge: Sin disponibilidad, email inválido, OAuth no configurado

---

## 🎯 Diferencias con Propuesta Anterior

| Aspecto | Propuesta Anterior | Nueva Propuesta |
|---------|-------------------|-----------------|
| Ubicación | Chat principal portfolio | Página dedicada `/agendar` |
| Slots mostrados | 5 configurables | 3 fijos |
| Duración | Variable | 30 min fijos |
| Detección intenciones | Compleja (múltiples) | Simple (solo scheduling) |
| Recopilación datos | No especificada | Nombre + Email |
| Invitación | No | Sí, vía Google Calendar |
| Complejidad | Alta | Media |

---

## ⏱️ Tiempo Estimado

**Total: 4-6 horas**

- Backend (scheduling-chat.php): 2-3 horas
- Frontend (hook + componente): 1.5-2 horas
- Testing E2E: 0.5-1 hora

---

¿Procedo con la implementación de esta versión simplificada y enfocada?
