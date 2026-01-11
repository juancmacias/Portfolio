<?php
/**
 * API Endpoint para Chat RAG del Portfolio
 * Integra Groq LLM, RAG Engine y PromptManager
 * 
 * @package PortfolioRAG
 * @author Juan Carlos Macías
 * @version 1.0
 */

// Suprimir errores para devolver JSON limpio
error_reporting(0);
ini_set('display_errors', 0);

// Limpiar cualquier salida previa
ob_start();

// Headers CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Función para logging en archivo
function logChatEvent($level, $message, $data = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => $level,
        'message' => $message,
        'data' => $data
    ];
    error_log("[CHAT-RAG] " . json_encode($logData));
}

// Función para logging detallado en archivo .log
function logDetailedChat($sessionId, $userMessage, $ragContext, $fullPrompt, $botResponse, $metadata = []) {
    try {
        // Crear directorio de logs si no existe
        $logDir = __DIR__ . '/../../logs/chat';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Archivo de log diario
        $logFile = $logDir . '/chat_' . date('Y-m-d') . '.log';
        
        // Preparar contenido del log
        $logContent = str_repeat('=', 80) . "\n";
        $logContent .= "NUEVA CONVERSACIÓN\n";
        $logContent .= str_repeat('=', 80) . "\n";
        $logContent .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        $logContent .= "Session ID: " . $sessionId . "\n";
        $logContent .= str_repeat('-', 80) . "\n\n";
        
        // 1. Mensaje del usuario
        $logContent .= "1. MENSAJE DEL USUARIO:\n";
        $logContent .= str_repeat('-', 80) . "\n";
        $logContent .= $userMessage . "\n\n";
        
        // 2. Contexto RAG
        $logContent .= "2. CONTEXTO RAG RECUPERADO:\n";
        $logContent .= str_repeat('-', 80) . "\n";
        $logContent .= "Total de resultados: " . count($ragContext) . "\n\n";
        
        foreach ($ragContext as $idx => $result) {
            $logContent .= sprintf(
                "[%d] Fuente: %s | Tipo: %s | Relevancia: %.3f\n",
                $idx + 1,
                $result['source'] ?? 'unknown',
                $result['source_type'] ?? 'unknown',
                $result['relevance_score'] ?? 0
            );
            $logContent .= "Contenido: " . substr($result['content'] ?? '', 0, 200) . "...\n\n";
        }
        
        // 3. Información de prompts activos
        $logContent .= "3. PROMPTS ACTIVOS UTILIZADOS:\n";
        $logContent .= str_repeat('-', 80) . "\n";
        if (isset($metadata['active_prompts']) && !empty($metadata['active_prompts'])) {
            foreach ($metadata['active_prompts'] as $prompt) {
                $logContent .= "📋 Prompt: " . ($prompt['name'] ?? 'unknown') . "\n";
                $logContent .= "   ID: " . ($prompt['id'] ?? 'N/A') . "\n";
                $logContent .= "   Descripción: " . ($prompt['description'] ?? 'N/A') . "\n";
                $logContent .= "   Sistema: " . ($prompt['prompt_text'] ?? 'N/A') . "\n";
                $logContent .= "   Contexto: " . ($prompt['context_prompt'] ?? 'N/A') . "\n";
                $logContent .= "   Prioridad: " . ($prompt['priority'] ?? 'N/A') . "\n";
                $logContent .= "   Categoría: " . ($prompt['category'] ?? 'N/A') . "\n\n";
            }
        } else {
            $logContent .= "No hay información de prompts disponible\n\n";
        }
        
        // 4. Prompt completo enviado al modelo
        $logContent .= "4. PROMPT COMPLETO ENVIADO AL MODELO:\n";
        $logContent .= str_repeat('-', 80) . "\n";
        $logContent .= $fullPrompt . "\n\n";
        
        // 5. Respuesta del modelo
        $logContent .= "5. RESPUESTA DEL MODELO:\n";
        $logContent .= str_repeat('-', 80) . "\n";
        $logContent .= $botResponse . "\n\n";
        
        // 6. Metadata técnica
        $logContent .= "6. METADATA TÉCNICA:\n";
        $logContent .= str_repeat('-', 80) . "\n";
        $logContent .= "Proveedor LLM: " . ($metadata['llm_provider'] ?? 'unknown') . "\n";
        $logContent .= "Modelo: " . ($metadata['model'] ?? 'unknown') . "\n";
        $logContent .= "Tokens usados: " . ($metadata['tokens_used'] ?? 0) . "\n";
        $logContent .= "Tiempo de procesamiento: " . sprintf("%.3f", $metadata['processing_time'] ?? 0) . " segundos\n";
        $logContent .= "Longitud respuesta: " . strlen($botResponse) . " caracteres\n";
        $logContent .= "Temperatura: " . ($metadata['temperature'] ?? 'N/A') . "\n";
        $logContent .= "Max tokens: " . ($metadata['max_tokens'] ?? 'N/A') . "\n\n";
        
        $logContent .= str_repeat('=', 80) . "\n\n\n";
        
        // Escribir en el archivo de log
        file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
        
        return true;
    } catch (Exception $e) {
        error_log("Error escribiendo log detallado: " . $e->getMessage());
        return false;
    }
}

try {
    // IMPORTANTE: Definir ADMIN_ACCESS primero
    if (!defined('ADMIN_ACCESS')) {
        define('ADMIN_ACCESS', true);
    }
    
    // Cargar configuración de base de datos directamente
    if (file_exists('../../admin/config/database.php')) {
        require_once '../../admin/config/database.php';
    } else {
        throw new Exception('Archivo de configuración de base de datos no encontrado');
    }
    
    // Verificar que la clase Database esté disponible
    if (!class_exists('Database')) {
        throw new Exception('Clase Database no disponible');
    }
    
    // Cargar clases necesarias con verificación
    $requiredClasses = [
        '../../admin/classes/AIContentGenerator.php',
        '../../admin/classes/RAG/SemanticSearchEngine.php', 
        '../../admin/classes/RAG/PromptManager.php'
    ];
    
    foreach ($requiredClasses as $classFile) {
        if (file_exists($classFile)) {
            require_once $classFile;
        } else {
            throw new Exception("Archivo de clase requerido no encontrado: " . basename($classFile));
        }
    }
    
    // Verificar que las clases estén disponibles
    if (!class_exists('SimplifiedRAGEngine')) {
        throw new Exception('Clase SimplifiedRAGEngine no disponible');
    }
    if (!class_exists('PromptManager')) {
        throw new Exception('Clase PromptManager no disponible');
    }
    if (!class_exists('AIContentGenerator')) {
        throw new Exception('Clase AIContentGenerator no disponible');
    }
    
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar parámetros requeridos
    $userMessage = trim($input['message'] ?? '');
    $sessionId = $input['session_id'] ?? uniqid('chat_', true);
    $includeVoice = $input['include_voice'] ?? false;
    
    if (empty($userMessage)) {
        throw new Exception('Mensaje vacío');
    }
    
    logChatEvent('INFO', 'Nueva consulta RAG', [
        'session_id' => $sessionId,
        'message_length' => strlen($userMessage),
        'include_voice' => $includeVoice
    ]);
    
    // Inicializar componentes
    $db = Database::getInstance();
    $ragEngine = new SimplifiedRAGEngine();
    $promptManager = new PromptManager();
    $aiGenerator = new AIContentGenerator();
    
    // 1. Búsqueda semántica RAG
    logChatEvent('INFO', 'Iniciando búsqueda RAG');
    $ragResults = $ragEngine->searchRelevantContent($userMessage, [
        'max_results' => 8,
        'min_relevance' => 0.3,
        'sources' => ['portfolio', 'documents', 'projects']
    ]);
    
    logChatEvent('INFO', 'Búsqueda RAG completada', [
        'results_count' => count($ragResults),
        'max_score' => $ragResults[0]['relevance_score'] ?? 0
    ]);
    
    // 2. Obtener historial de conversación reciente
    $conversationHistory = [];
    try {
        $historyQuery = "
            SELECT user_message, bot_response 
            FROM enhanced_conversations 
            WHERE session_id = ? 
            ORDER BY created_at DESC 
            LIMIT 3
        ";
        $history = $db->fetchAll($historyQuery, [$sessionId]);
        $conversationHistory = array_reverse($history); // Orden cronológico
    } catch (Exception $e) {
        logChatEvent('WARNING', 'Error obteniendo historial', ['error' => $e->getMessage()]);
    }
    
    // 3. Obtener prompts activos para logging
    $activePrompts = $promptManager->getActivePrompts();
    
    // 4. Construir prompt completo con PromptManager
    logChatEvent('INFO', 'Construyendo prompt con contexto RAG');
    $fullPrompt = $promptManager->buildConversationPrompt(
        $userMessage,
        $ragResults,
        $conversationHistory
    );
    
    // 5. Determinar proveedor LLM (priorizar Groq)
    $llmProvider = 'groq'; // Siempre usar Groq para conversaciones
    $model = 'llama-3.1-8b-instant'; // Modelo rápido para chat
    
    // 6. Configurar parámetros de generación
    $generationOptions = [
        'model' => $model,
        'max_tokens' => 800,
        'temperature' => 0.4
    ];
    
    logChatEvent('INFO', 'Enviando a LLM', [
        'provider' => $llmProvider,
        'model' => $model,
        'prompt_length' => strlen($fullPrompt),
        'rag_context_items' => count($ragResults)
    ]);
    
    // 6. Generar respuesta con Groq (usando la firma correcta)
    $response = $aiGenerator->generateContent(
        $fullPrompt,           // prompt
        'conversation',        // type
        $llmProvider,          // provider
        $generationOptions     // options
    );
    
    if (!$response || !isset($response['content'])) {
        throw new Exception('Error generando respuesta del LLM');
    }
    
    $botResponse = trim($response['content']);
    
    logChatEvent('INFO', 'Respuesta LLM generada', [
        'response_length' => strlen($botResponse),
        'tokens_used' => $response['usage']['total_tokens'] ?? 0
    ]);
    
    // 6.5. Guardar log detallado en archivo .log
    $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    $processingTime = microtime(true) - $startTime;
    
    logDetailedChat(
        $sessionId,
        $userMessage,
        $ragResults,
        $fullPrompt,
        $botResponse,
        [
            'llm_provider' => $llmProvider,
            'model' => $model,
            'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            'processing_time' => $processingTime,
            'temperature' => $generationOptions['temperature'],
            'max_tokens' => $generationOptions['max_tokens'],
            'active_prompts' => $activePrompts
        ]
    );
    
    // 7. Guardar conversación en BD
    try {
        $saveQuery = "
            INSERT INTO enhanced_conversations 
            (session_id, user_message, bot_response, rag_context, llm_provider, tokens_used) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $ragContextJson = json_encode([
            'results_count' => count($ragResults),
            'top_sources' => array_slice($ragResults, 0, 3),
            'relevance_scores' => array_column($ragResults, 'relevance_score')
        ]);
        
        $db->query($saveQuery, [
            $sessionId,
            $userMessage,
            $botResponse,
            $ragContextJson,
            $llmProvider,
            $response['usage']['total_tokens'] ?? 0
        ]);
        
        logChatEvent('INFO', 'Conversación guardada en BD');
        
    } catch (Exception $e) {
        logChatEvent('ERROR', 'Error guardando conversación', ['error' => $e->getMessage()]);
    }
    
    // 9. Preparar respuesta final
    $apiResponse = [
        'success' => true,
        'data' => [
            'response' => $botResponse,
            'session_id' => $sessionId,
            'timestamp' => date('Y-m-d H:i:s'),
            'rag_context' => [
                'results_count' => count($ragResults),
                'sources_used' => array_unique(array_column($ragResults, 'source_type')),
                'relevance_range' => [
                    'min' => end($ragResults)['relevance_score'] ?? 0,
                    'max' => $ragResults[0]['relevance_score'] ?? 0
                ]
            ],
            'metadata' => [
                'llm_provider' => $llmProvider,
                'model' => $model,
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                'processing_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
            ]
        ]
    ];
    
    // 10. Respuesta con audio (si se solicita)
    if ($includeVoice) {
        // Nota: Se manejará en el frontend con Web Speech API
        $apiResponse['data']['voice_enabled'] = true;
        $apiResponse['data']['voice_text'] = $botResponse;
    }
    
    logChatEvent('INFO', 'Respuesta API completada', [
        'session_id' => $sessionId,
        'success' => true,
        'processing_time' => $apiResponse['data']['metadata']['processing_time']
    ]);
    
    // Limpiar buffer y responder
    ob_clean();
    http_response_code(200);
    echo json_encode($apiResponse, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
    
} catch (Exception $e) {
    logChatEvent('ERROR', 'Error en chat-rag API', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Limpiar buffer y responder error
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>