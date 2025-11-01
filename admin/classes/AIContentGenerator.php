<?php
/**
 * Gestor de IA para Generación de Contenido
 * Maneja múltiples proveedores: Groq, HuggingFace, OpenAI
 */

// Incluir dependencias
require_once __DIR__ . '/AIProviderInterface.php';
require_once __DIR__ . '/AIProviders.php';

class AIContentGenerator {
    private $db;
    private $providers = [];
    private $defaultProvider = 'groq';
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeProviders();
    }
    
    /**
     * Inicializar proveedores de IA
     */
    private function initializeProviders() {
        $this->providers = [
            'groq' => new GroqProvider(),
            'huggingface' => new HuggingFaceProvider(),
            'openai' => new OpenAIProvider()
        ];
    }
    
    /**
     * Generar contenido usando el proveedor especificado o por defecto
     */
    public function generateContent($prompt, $type = 'article', $provider = null, $options = []) {
        $provider = $provider ?: $this->defaultProvider;
        
        if (!isset($this->providers[$provider])) {
            throw new Exception("Proveedor de IA no válido: {$provider}");
        }
        
        $startTime = microtime(true);
        
        try {
            // Obtener el prompt optimizado según el tipo
            $optimizedPrompt = $this->getOptimizedPrompt($prompt, $type);
            
            // Generar contenido con el proveedor
            $result = $this->providers[$provider]->generate($optimizedPrompt, $options);
            
            // Limpiar el contenido generado
            $result['content'] = $this->cleanGeneratedContent($result['content'], $type);
            
            $executionTime = round((microtime(true) - $startTime) * 1000);
            
            // Registrar en logs
            $this->logAIUsage([
                'provider' => $provider,
                'model' => $result['model'] ?? 'unknown',
                'prompt' => $optimizedPrompt,
                'response' => $result['content'],
                'tokens_used' => $result['tokens_used'] ?? 0,
                'cost_estimated' => $result['cost_estimated'] ?? 0,
                'execution_time_ms' => $executionTime,
                'status' => 'success'
            ]);
            
            return [
                'success' => true,
                'content' => $result['content'],
                'provider' => $provider,
                'model' => $result['model'] ?? 'unknown',
                'tokens_used' => $result['tokens_used'] ?? 0,
                'cost_estimated' => $result['cost_estimated'] ?? 0,
                'execution_time' => $executionTime
            ];
            
        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000);
            
            // Registrar error en logs
            $this->logAIUsage([
                'provider' => $provider,
                'model' => 'unknown',
                'prompt' => $optimizedPrompt ?? $prompt,
                'response' => null,
                'tokens_used' => 0,
                'cost_estimated' => 0,
                'execution_time_ms' => $executionTime,
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $provider,
                'execution_time' => $executionTime
            ];
        }
    }
    
    /**
     * Generar título de artículo
     */
    public function generateTitle($topic, $keywords = '', $tone = 'professional') {
        $prompt = "Genera ÚNICAMENTE un título atractivo y optimizado para SEO sobre: {$topic}";
        
        if ($keywords) {
            $prompt .= "\nIncluir palabras clave: {$keywords}";
        }
        
        $prompt .= "\nTono: {$tone}";
        $prompt .= "\n\nIMPORTANTE: Responde SOLO con el título, máximo 60 caracteres, sin comillas, sin explicaciones, sin preámbulos.";
        
        return $this->generateContent($prompt, 'title');
    }
    
    /**
     * Generar contenido completo de artículo
     */
    public function generateArticle($title, $keywords = '', $wordCount = 800, $tone = 'professional') {
        $prompt = "Escribe un artículo completo sobre: {$title}\n\n";
        $prompt .= "Especificaciones:\n";
        $prompt .= "- Aproximadamente {$wordCount} palabras\n";
        $prompt .= "- Tono: {$tone}\n";
        $prompt .= "- Estructura: Introducción, desarrollo con subtítulos, conclusión\n";
        $prompt .= "- Formato: Markdown con títulos H2 y H3\n";
        $prompt .= "- SEO optimizado\n";
        
        if ($keywords) {
            $prompt .= "- Incluir naturalmente estas palabras clave: {$keywords}\n";
        }
        
        $prompt .= "\nIMPORTANTE: Responde ÚNICAMENTE con el contenido del artículo en Markdown. Sin introducción, sin explicaciones, sin 'Aquí tienes el artículo' o similares.";
        
        return $this->generateContent($prompt, 'article', null, ['max_tokens' => 2000]);
    }
    
    /**
     * Generar extracto de artículo
     */
    public function generateExcerpt($content, $maxLength = 150) {
        $prompt = "Basándote en este artículo, crea un extracto conciso:\n\n{$content}\n\n";
        $prompt .= "Requisitos:\n";
        $prompt .= "- Máximo {$maxLength} caracteres\n";
        $prompt .= "- Capte la esencia del artículo\n";
        $prompt .= "- Sea atractivo para el lector\n";
        $prompt .= "\nIMPORTANTE: Responde ÚNICAMENTE con el extracto, sin comillas, sin 'Aquí tienes el extracto' o explicaciones similares.";
        
        return $this->generateContent($prompt, 'excerpt');
    }
    
    /**
     * Generar meta description para SEO
     */
    public function generateMetaDescription($content, $keywords = '') {
        $prompt = "Basándote en este contenido, crea una meta description para SEO:\n\n{$content}\n\n";
        $prompt .= "Requisitos:\n";
        $prompt .= "- Entre 140-160 caracteres\n";
        $prompt .= "- Incluya call-to-action sutil\n";
        $prompt .= "- SEO optimizada\n";
        
        if ($keywords) {
            $prompt .= "- Incluir naturalmente: {$keywords}\n";
        }
        
        $prompt .= "\nIMPORTANTE: Responde ÚNICAMENTE con la meta description, sin comillas, sin 'Aquí te dejo una meta descripción' o explicaciones similares.";
        
        return $this->generateContent($prompt, 'meta_description');
    }
    
    /**
     * Generar tags/etiquetas
     */
    public function generateTags($content, $maxTags = 8) {
        $prompt = "Analiza este contenido y extrae {$maxTags} palabras clave relevantes:\n\n{$content}\n\n";
        $prompt .= "FORMATO REQUERIDO: palabra1,palabra2,palabra3,palabra4\n";
        $prompt .= "REGLAS ESTRICTAS:\n";
        $prompt .= "- Solo palabras separadas por comas\n";
        $prompt .= "- Sin espacios después de las comas\n";
        $prompt .= "- En minúsculas\n";
        $prompt .= "- Sin números, sin símbolos especiales\n";
        $prompt .= "- Máximo {$maxTags} palabras\n";
        $prompt .= "\nIMPORTANTE: Tu respuesta debe ser ÚNICAMENTE las palabras separadas por comas. Ejemplo válido: react,javascript,frontend,desarrollo\nNo incluyas explicaciones, títulos, ni texto adicional.";
        
        return $this->generateContent($prompt, 'tags');
    }
    
    /**
     * Obtener prompts optimizados según el tipo de contenido
     */
    private function getOptimizedPrompt($prompt, $type) {
        $baseInstructions = [
            'title' => "Eres un experto en copywriting y SEO. ",
            'article' => "Eres un escritor técnico especializado en tecnología y desarrollo web. ",
            'excerpt' => "Eres un experto en marketing de contenidos. ",
            'meta_description' => "Eres un especialista en SEO y marketing digital. ",
            'tags' => "Eres un experto en taxonomía y categorización de contenido. "
        ];
        
        $instruction = $baseInstructions[$type] ?? "Eres un asistente especializado en generación de contenido. ";
        
        return $instruction . $prompt;
    }
    
    /**
     * Limpiar contenido generado eliminando preámbulos y explicaciones
     */
    private function cleanGeneratedContent($content, $type) {
        $content = trim($content);
        
        // Patrones comunes de preámbulos a eliminar
        $patterns = [
            '/^(Aquí te dejo|Aquí tienes|Te proporciono|A continuación|Basándome en|Según el contenido)/i',
            '/^(.*meta descripción.*optimizada.*SEO.*:)/i',
            '/^(.*título.*para.*artículo.*:)/i',
            '/^(.*extracto.*para.*artículo.*:)/i',
            '/^(.*tags.*para.*contenido.*:)/i',
            '/^(.*etiquetas.*relevantes.*:)/i',
            '/^(.*palabras clave.*:)/i',
            '/^(Título:|Meta descripción:|Extracto:|Tags:|Etiquetas:|Palabras clave:)/i',
            '/^(.*)\s*:\s*$/m', // Líneas que terminan solo en dos puntos
            '/^(Después de analizar.*)/i',
            '/^(Las siguientes.*)/i',
            '/^(He aquí.*)/i',
        ];
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
            $content = trim($content);
        }
        
        // Limpiar comillas al inicio y final
        $content = trim($content, '"\'');
        
        // Para tags, asegurar formato correcto
        if ($type === 'tags') {
            // Eliminar cualquier texto explicativo común
            $tagPatterns = [
                '/^(.*palabras clave.*:)/i',
                '/^(.*tags.*:)/i',
                '/^(.*etiquetas.*:)/i',
                '/^(.*categorías.*:)/i',
                '/^(.*palabras.*relevantes.*:)/i',
                '/^(Basándome en el contenido.*)/i',
                '/^(Después de analizar.*)/i',
                '/^(Las palabras clave.*)/i',
                '/^(Estas son las.*)/i',
                '/\n.*$/', // Eliminar cualquier línea adicional después de la primera
            ];
            
            foreach ($tagPatterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
                $content = trim($content);
            }
            
            // Extraer solo la primera línea si hay múltiples líneas
            $lines = explode("\n", $content);
            $content = trim($lines[0]);
            
            // Limpiar caracteres no deseados y normalizar
            $content = preg_replace('/[^\w,áéíóúñü\s-]/', '', $content);
            $content = preg_replace('/,\s+/', ',', $content);
            $content = preg_replace('/\s*,\s*/', ',', $content);
            $content = strtolower($content);
            
            // Eliminar palabras vacías y duplicados
            $tags = array_filter(array_unique(explode(',', $content)));
            $content = implode(',', array_slice($tags, 0, 8)); // Máximo 8 tags
        }
        
        // Eliminar líneas vacías al inicio y final
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Registrar uso de IA en la base de datos
     */
    private function logAIUsage($data) {
        try {
            $sql = "
                INSERT INTO ai_logs (
                    ai_provider, ai_model, prompt_text, response_text,
                    tokens_used, cost_estimated, execution_time_ms,
                    status, error_message
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $this->db->query($sql, [
                $data['provider'],
                $data['model'],
                $data['prompt'],
                $data['response'],
                $data['tokens_used'],
                $data['cost_estimated'],
                $data['execution_time_ms'],
                $data['status'],
                $data['error_message'] ?? null
            ]);
            
        } catch (Exception $e) {
            error_log("Error logging AI usage: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de uso de IA
     */
    public function getUsageStats($timeframe = '30 days') {
        try {
            $sql = "
                SELECT 
                    ai_provider,
                    COUNT(*) as total_requests,
                    SUM(tokens_used) as total_tokens,
                    SUM(cost_estimated) as total_cost,
                    AVG(execution_time_ms) as avg_execution_time,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_requests,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed_requests
                FROM ai_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$timeframe})
                GROUP BY ai_provider
                ORDER BY total_requests DESC
            ";
            
            return $this->db->fetchAll($sql);
            
        } catch (Exception $e) {
            error_log("Error getting AI usage stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener proveedores disponibles
     */
    public function getAvailableProviders() {
        $providers = [];
        foreach ($this->providers as $name => $provider) {
            $providers[$name] = [
                'name' => $name,
                'display_name' => $provider->getDisplayName(),
                'models' => $provider->getAvailableModels(),
                'cost_per_token' => $provider->getCostPerToken(),
                'max_tokens' => $provider->getMaxTokens(),
                'available' => $provider->isAvailable()
            ];
        }
        return $providers;
    }
    
    /**
     * Establecer proveedor por defecto
     */
    public function setDefaultProvider($provider) {
        if (!isset($this->providers[$provider])) {
            throw new Exception("Proveedor no válido: {$provider}");
        }
        $this->defaultProvider = $provider;
    }
    
    /**
     * Limpiar logs antiguos
     */
    public function cleanOldLogs($daysToKeep = 90) {
        try {
            $sql = "DELETE FROM ai_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $result = $this->db->query($sql, [$daysToKeep]);
            return $this->db->rowsAffected();
        } catch (Exception $e) {
            error_log("Error cleaning old AI logs: " . $e->getMessage());
            return 0;
        }
    }
}
?>