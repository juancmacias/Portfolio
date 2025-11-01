<?php
/**
 * Proveedores de IA para generación de contenido
 */

// Incluir la interfaz
require_once __DIR__ . '/AIProviderInterface.php';

/**
 * Proveedor Groq para generación de contenido con IA
 */
class GroqProvider implements AIProviderInterface {
    private $apiKey;
    private $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private $models = [
        'llama-3.1-8b-instant' => ['name' => 'Llama 3.1 8B Instant', 'max_tokens' => 8192, 'cost_per_1k' => 0.00005],
        'llama-3.1-70b-versatile' => ['name' => 'Llama 3.1 70B Versatile', 'max_tokens' => 8192, 'cost_per_1k' => 0.00059],
        'llama-3.2-3b-preview' => ['name' => 'Llama 3.2 3B Preview', 'max_tokens' => 8192, 'cost_per_1k' => 0.00006],
        'mixtral-8x7b-32768' => ['name' => 'Mixtral 8x7B', 'max_tokens' => 32768, 'cost_per_1k' => 0.00024],
        'gemma2-9b-it' => ['name' => 'Gemma 2 9B', 'max_tokens' => 8192, 'cost_per_1k' => 0.00002]
    ];
    private $defaultModel = 'llama-3.1-8b-instant';
    
    public function __construct() {
        $this->apiKey = $this->getApiKey();
    }
    
    /**
     * Obtener API key desde configuración
     */
    private function getApiKey() {
        // Primero intentar desde variables de entorno
        if (getenv('GROQ_API_KEY')) {
            return getenv('GROQ_API_KEY');
        }
        
        // Intentar desde archivo de configuración local
        try {
            $configFile = __DIR__ . '/../config/config.local.php';
            if (file_exists($configFile)) {
                require_once $configFile;
                $aiConfig = get_ai_config();
                if (!empty($aiConfig['api_keys']['groq'])) {
                    return $aiConfig['api_keys']['groq'];
                }
            }
        } catch (Exception $e) {
            error_log("Error loading Groq API key from config: " . $e->getMessage());
        }
        
        // Luego desde configuración de base de datos
        try {
            $db = Database::getInstance();
            $config = $db->fetchOne("SELECT config_value FROM system_config WHERE config_key = 'groq_api_key'");
            if ($config && $config['config_value']) {
                return $config['config_value'];
            }
        } catch (Exception $e) {
            error_log("Error loading Groq API key from DB: " . $e->getMessage());
        }
        
        // Valor por defecto (debe configurarse)
        return '';
    }
    
    /**
     * Generar contenido usando Groq
     */
    public function generate($prompt, $options = []) {
        if (empty($this->apiKey)) {
            throw new Exception('API key de Groq no configurada');
        }
        
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => false
        ];
        
        $response = $this->makeRequest($data);
        
        if (!$response) {
            throw new Exception('No se recibió respuesta de Groq');
        }
        
        $content = $response['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $response['usage']['total_tokens'] ?? 0;
        $costEstimated = $this->calculateCost($tokensUsed, $model);
        
        return [
            'content' => trim($content),
            'model' => $model,
            'tokens_used' => $tokensUsed,
            'cost_estimated' => $costEstimated
        ];
    }
    
    /**
     * Realizar petición HTTP a la API de Groq
     */
    private function makeRequest($data) {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Portfolio-Admin/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error cURL: {$error}");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? "HTTP Error {$httpCode}";
            throw new Exception("Error Groq API: {$errorMsg}");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decodificando respuesta JSON de Groq');
        }
        
        return $decoded;
    }
    
    /**
     * Calcular costo estimado
     */
    private function calculateCost($tokens, $model) {
        if (!isset($this->models[$model])) {
            return 0;
        }
        
        $costPer1k = $this->models[$model]['cost_per_1k'];
        return ($tokens / 1000) * $costPer1k;
    }
    
    public function getDisplayName() {
        return 'Groq';
    }
    
    public function getAvailableModels() {
        return $this->models;
    }
    
    public function getCostPerToken() {
        return $this->models[$this->defaultModel]['cost_per_1k'] / 1000;
    }
    
    public function getMaxTokens() {
        return $this->models[$this->defaultModel]['max_tokens'];
    }
    
    public function isAvailable() {
        return !empty($this->apiKey) && function_exists('curl_init');
    }
}

/**
 * Proveedor HuggingFace para generación de contenido
 */
class HuggingFaceProvider implements AIProviderInterface {
    private $apiKey;
    private $baseUrl = 'https://api-inference.huggingface.co/models/';
    private $models = [
        'microsoft/DialoGPT-large' => ['name' => 'DialoGPT Large', 'max_tokens' => 1024, 'cost_per_1k' => 0.0001],
        'facebook/blenderbot-400M-distill' => ['name' => 'BlenderBot 400M', 'max_tokens' => 512, 'cost_per_1k' => 0.00005],
        'google/flan-t5-large' => ['name' => 'Flan-T5 Large', 'max_tokens' => 512, 'cost_per_1k' => 0.00008]
    ];
    private $defaultModel = 'microsoft/DialoGPT-large';
    
    public function __construct() {
        $this->apiKey = $this->getApiKey();
    }
    
    private function getApiKey() {
        if (getenv('HUGGINGFACE_API_KEY')) {
            return getenv('HUGGINGFACE_API_KEY');
        }
        
        // Intentar desde archivo de configuración local
        try {
            $configFile = __DIR__ . '/../config/config.local.php';
            if (file_exists($configFile)) {
                require_once $configFile;
                $aiConfig = get_ai_config();
                if (!empty($aiConfig['api_keys']['huggingface'])) {
                    return $aiConfig['api_keys']['huggingface'];
                }
            }
        } catch (Exception $e) {
            error_log("Error loading HuggingFace API key from config: " . $e->getMessage());
        }
        
        try {
            $db = Database::getInstance();
            $config = $db->fetchOne("SELECT config_value FROM system_config WHERE config_key = 'huggingface_api_key'");
            if ($config && $config['config_value']) {
                return $config['config_value'];
            }
        } catch (Exception $e) {
            error_log("Error loading HuggingFace API key: " . $e->getMessage());
        }
        
        return '';
    }
    
    public function generate($prompt, $options = []) {
        if (empty($this->apiKey)) {
            throw new Exception('API key de HuggingFace no configurada');
        }
        
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 200;
        
        $data = [
            'inputs' => $prompt,
            'parameters' => [
                'max_new_tokens' => $maxTokens,
                'temperature' => $options['temperature'] ?? 0.7,
                'return_full_text' => false
            ]
        ];
        
        $response = $this->makeRequest($model, $data);
        
        if (!$response || empty($response[0]['generated_text'])) {
            throw new Exception('No se recibió respuesta válida de HuggingFace');
        }
        
        $content = $response[0]['generated_text'];
        $tokensUsed = str_word_count($content) * 1.3; // Estimación aproximada
        $costEstimated = $this->calculateCost($tokensUsed, $model);
        
        return [
            'content' => trim($content),
            'model' => $model,
            'tokens_used' => (int)$tokensUsed,
            'cost_estimated' => $costEstimated
        ];
    }
    
    private function makeRequest($model, $data) {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $model,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error cURL: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Error HuggingFace API: HTTP {$httpCode}");
        }
        
        return json_decode($response, true);
    }
    
    private function calculateCost($tokens, $model) {
        if (!isset($this->models[$model])) {
            return 0;
        }
        
        $costPer1k = $this->models[$model]['cost_per_1k'];
        return ($tokens / 1000) * $costPer1k;
    }
    
    public function getDisplayName() {
        return 'HuggingFace';
    }
    
    public function getAvailableModels() {
        return $this->models;
    }
    
    public function getCostPerToken() {
        return $this->models[$this->defaultModel]['cost_per_1k'] / 1000;
    }
    
    public function getMaxTokens() {
        return $this->models[$this->defaultModel]['max_tokens'];
    }
    
    public function isAvailable() {
        return !empty($this->apiKey) && function_exists('curl_init');
    }
}

/**
 * Proveedor OpenAI para generación de contenido
 */
class OpenAIProvider implements AIProviderInterface {
    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1/chat/completions';
    private $models = [
        'gpt-3.5-turbo' => ['name' => 'GPT-3.5 Turbo', 'max_tokens' => 4096, 'cost_per_1k' => 0.001],
        'gpt-3.5-turbo-16k' => ['name' => 'GPT-3.5 Turbo 16K', 'max_tokens' => 16384, 'cost_per_1k' => 0.003],
        'gpt-4' => ['name' => 'GPT-4', 'max_tokens' => 8192, 'cost_per_1k' => 0.03],
        'gpt-4-turbo' => ['name' => 'GPT-4 Turbo', 'max_tokens' => 128000, 'cost_per_1k' => 0.01]
    ];
    private $defaultModel = 'gpt-3.5-turbo';
    
    public function __construct() {
        $this->apiKey = $this->getApiKey();
    }
    
    private function getApiKey() {
        if (getenv('OPENAI_API_KEY')) {
            return getenv('OPENAI_API_KEY');
        }
        
        // Intentar desde archivo de configuración local
        try {
            $configFile = __DIR__ . '/../config/config.local.php';
            if (file_exists($configFile)) {
                require_once $configFile;
                $aiConfig = get_ai_config();
                if (!empty($aiConfig['api_keys']['openai'])) {
                    return $aiConfig['api_keys']['openai'];
                }
            }
        } catch (Exception $e) {
            error_log("Error loading OpenAI API key from config: " . $e->getMessage());
        }
        
        try {
            $db = Database::getInstance();
            $config = $db->fetchOne("SELECT config_value FROM system_config WHERE config_key = 'openai_api_key'");
            if ($config && $config['config_value']) {
                return $config['config_value'];
            }
        } catch (Exception $e) {
            error_log("Error loading OpenAI API key: " . $e->getMessage());
        }
        
        return '';
    }
    
    public function generate($prompt, $options = []) {
        if (empty($this->apiKey)) {
            throw new Exception('API key de OpenAI no configurada');
        }
        
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature
        ];
        
        $response = $this->makeRequest($data);
        
        if (!$response) {
            throw new Exception('No se recibió respuesta de OpenAI');
        }
        
        $content = $response['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $response['usage']['total_tokens'] ?? 0;
        $costEstimated = $this->calculateCost($tokensUsed, $model);
        
        return [
            'content' => trim($content),
            'model' => $model,
            'tokens_used' => $tokensUsed,
            'cost_estimated' => $costEstimated
        ];
    }
    
    private function makeRequest($data) {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error cURL: {$error}");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? "HTTP Error {$httpCode}";
            throw new Exception("Error OpenAI API: {$errorMsg}");
        }
        
        return json_decode($response, true);
    }
    
    private function calculateCost($tokens, $model) {
        if (!isset($this->models[$model])) {
            return 0;
        }
        
        $costPer1k = $this->models[$model]['cost_per_1k'];
        return ($tokens / 1000) * $costPer1k;
    }
    
    public function getDisplayName() {
        return 'OpenAI';
    }
    
    public function getAvailableModels() {
        return $this->models;
    }
    
    public function getCostPerToken() {
        return $this->models[$this->defaultModel]['cost_per_1k'] / 1000;
    }
    
    public function getMaxTokens() {
        return $this->models[$this->defaultModel]['max_tokens'];
    }
    
    public function isAvailable() {
        return !empty($this->apiKey) && function_exists('curl_init');
    }
}
?>