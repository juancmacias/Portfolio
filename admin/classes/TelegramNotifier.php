<?php
/**
 * TelegramNotifier
 * Envía notificaciones a Telegram cuando ocurren eventos importantes en el portfolio
 * 
 * Funcionalidades:
 * - Notificaciones de mensajes en Chat RAG
 * - Alertas de formularios de contacto
 * - Errores del sistema
 * - Rate limiting automático para evitar spam
 * - Logging de todas las notificaciones enviadas
 * 
 * @package Portfolio Admin
 * @author Juan Carlos Macías
 * @version 1.0.0
 */

class TelegramNotifier
{
    private $config;
    private $botToken;
    private $chatId;
    private $enabled;
    private $logger;
    private $rateLimitFile;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Cargar configuración
        $configFile = dirname(__DIR__) . '/config/config.local.php';
        if (file_exists($configFile)) {
            require_once $configFile;
            $this->config = get_telegram_config();
        } else {
            $this->config = ['enabled' => false];
        }
        
        $this->enabled = $this->config['enabled'] ?? false;
        $this->botToken = $this->config['bot_token'] ?? '';
        $this->chatId = $this->config['chat_id'] ?? '';
        
        // Archivo para rate limiting (crear directorio solo si es necesario)
        $logsDir = dirname(dirname(__DIR__)) . '/logs/telegram';
        $this->rateLimitFile = $logsDir . '/rate_limit.json';
        
        // Logger (carga lazy, solo si se necesita)
        if (file_exists(dirname(__DIR__) . '/classes/SearchEngineLogger.php')) {
            require_once dirname(__DIR__) . '/classes/SearchEngineLogger.php';
            $this->logger = new SearchEngineLogger('telegram');
        }
    }
    
    /**
     * Trunca una cadena UTF-8 de forma segura
     * Usa mb_substr si está disponible, si no usa substr
     * 
     * @param string $str Cadena a truncar
     * @param int $start Posición inicial
     * @param int $length Longitud máxima
     * @return string Cadena truncada
     */
    private function safeSubstr($str, $start, $length)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($str, $start, $length);
        }
        
        // Fallback: usar substr (puede cortar mal caracteres UTF-8 multibyte)
        $result = substr($str, $start, $length);
        
        // Si la cadena termina con un caracter multibyte truncado, limpiarlo
        if (strlen($result) > 0 && ord($result[strlen($result) - 1]) > 127) {
            // Remover último caracter potencialmente corrupto
            $result = substr($result, 0, -1);
        }
        
        return $result;
    }
    
    /**
     * Obtiene la longitud de una cadena UTF-8 de forma segura
     * Usa mb_strlen si está disponible, si no usa strlen
     * 
     * @param string $str Cadena a medir
     * @return int Longitud de la cadena
     */
    private function safeStrlen($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str);
        }
        
        // Fallback: usar strlen (cuenta bytes, no caracteres UTF-8)
        return strlen($str);
    }
    
    /**
     * Envía notificación de mensaje en Chat RAG
     * 
     * @param string $userMessage Mensaje del usuario
     * @param string $botResponse Respuesta del bot
     * @param array $metadata Metadata adicional (IP, timestamp, etc.)
     * @return bool Success
     */
    public function notifyChatMessage($userMessage, $botResponse = '', $metadata = [])
    {
        if (!$this->isEventEnabled('chat_messages')) {
            return false;
        }
        
        if ($this->isRateLimited('chat_message')) {
            if ($this->logger) {
                $this->logger->log('Chat message notification rate limited');
            }
            return false;
        }
        
        // Truncar mensajes largos (compatible sin mbstring)
        $userMessageShort = $this->safeSubstr($userMessage, 0, 200);
        $botResponseShort = $this->safeSubstr($botResponse, 0, 200);
        
        $ip = $metadata['ip'] ?? $this->getClientIp();
        $timestamp = $metadata['timestamp'] ?? date('Y-m-d H:i:s');
        
        $message = "💬 *Nuevo mensaje en Chat RAG*\n\n";
        $message .= "👤 *Usuario:* `{$ip}`\n";
        $message .= "🕐 *Hora:* {$timestamp}\n\n";
        $message .= "📝 *Mensaje:*\n_{$userMessageShort}_";
        
        if ($botResponse) {
            $message .= "\n\n🤖 *Respuesta:*\n_{$botResponseShort}_";
        }
        
        if ($this->safeStrlen($userMessage) > 200) {
            $message .= "\n\n_...mensaje truncado_";
        }
        
        return $this->sendMessage($message);
    }
    
    /**
     * Envía notificación de formulario de contacto
     * 
     * @param array $formData Datos del formulario
     * @return bool Success
     */
    public function notifyContactForm($formData)
    {
        if (!$this->isEventEnabled('contact_form')) {
            return false;
        }
        
        if ($this->isRateLimited('contact_form')) {
            return false;
        }
        
        $name = $formData['name'] ?? 'Anónimo';
        $email = $formData['email'] ?? 'No proporcionado';
        $subject = $formData['subject'] ?? 'Sin asunto';
        $messageText = $formData['message'] ?? '';
        
        $message = "📧 *Nuevo mensaje de contacto*\n\n";
        $message .= "👤 *Nombre:* {$name}\n";
        $message .= "📧 *Email:* `{$email}`\n";
        $message .= "📌 *Asunto:* {$subject}\n\n";
        $message .= "💬 *Mensaje:*\n_{$messageText}_";
        
        return $this->sendMessage($message);
    }
    
    /**
     * Envía notificación de error del sistema
     * 
     * @param string $errorMessage Mensaje de error
     * @param array $context Contexto del error
     * @return bool Success
     */
    public function notifyError($errorMessage, $context = [])
    {
        if (!$this->isEventEnabled('errors')) {
            return false;
        }
        
        if ($this->isRateLimited('error', 300)) { // Máximo 1 error cada 5 minutos
            return false;
        }
        
        $file = $context['file'] ?? 'Desconocido';
        $line = $context['line'] ?? '?';
        $url = $context['url'] ?? $_SERVER['REQUEST_URI'] ?? '';
        
        $message = "⚠️ *ERROR en Portfolio*\n\n";
        $message .= "🔴 *Error:* `{$errorMessage}`\n";
        $message .= "📁 *Archivo:* `{$file}:{$line}`\n";
        $message .= "🌐 *URL:* `{$url}`\n";
        $message .= "🕐 *Hora:* " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($message);
    }
    
    /**
     * Envía notificación de login en admin panel
     * 
     * @param string $username Usuario que inició sesión
     * @param bool $success Si el login fue exitoso
     * @return bool Success
     */
    public function notifyAdminLogin($username, $success = true)
    {
        if (!$this->isEventEnabled('admin_login')) {
            return false;
        }
        
        if ($this->isRateLimited('admin_login', 600)) { // Máximo 1 cada 10 minutos
            return false;
        }
        
        $ip = $this->getClientIp();
        $emoji = $success ? '✅' : '❌';
        $status = $success ? 'exitoso' : 'FALLIDO';
        
        $message = "{$emoji} *Login admin {$status}*\n\n";
        $message .= "👤 *Usuario:* `{$username}`\n";
        $message .= "🌐 *IP:* `{$ip}`\n";
        $message .= "🕐 *Hora:* " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($message);
    }
    
    /**
     * Envía notificación de vista de artículo (con rate limit agresivo)
     * 
     * @param string $articleTitle Título del artículo
     * @param string $articleSlug Slug del artículo
     * @return bool Success
     */
    public function notifyArticleView($articleTitle, $articleSlug)
    {
        if (!$this->isEventEnabled('article_views')) {
            return false;
        }
        
        // Rate limit muy estricto: solo 5 notificaciones de artículos por hora
        if ($this->isRateLimited('article_view', 720)) { // 12 minutos
            return false;
        }
        
        $message = "📖 *Nuevo artículo leído*\n\n";
        $message .= "📝 *Título:* {$articleTitle}\n";
        $message .= "🔗 *URL:* `/articles/{$articleSlug}`\n";
        $message .= "🕐 *Hora:* " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($message);
    }
    
    /**
     * Envía mensaje personalizado
     * 
     * @param string $message Mensaje a enviar (admite Markdown)
     * @return bool Success
     */
    public function sendMessage($message)
    {
        if (!$this->enabled || empty($this->botToken) || empty($this->chatId)) {
            if ($this->logger) {
                $this->logger->log('Telegram not configured or disabled');
            }
            return false;
        }
        
        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            
            $data = [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                if ($this->logger) {
                    $this->logger->success('Telegram notification sent successfully');
                }
                return true;
            } else {
                if ($this->logger) {
                    $this->logger->error("Telegram API error: HTTP {$httpCode} - {$response}");
                }
                return false;
            }
            
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Telegram notification failed: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Verifica si un evento está habilitado en la configuración
     * 
     * @param string $eventType Tipo de evento
     * @return bool
     */
    private function isEventEnabled($eventType)
    {
        return $this->enabled && ($this->config['events'][$eventType] ?? false);
    }
    
    /**
     * Verifica si estamos en rate limit
     * 
     * @param string $type Tipo de notificación
     * @param int $cooldown Tiempo de cooldown en segundos (default: de config)
     * @return bool True si está limitado
     */
    private function isRateLimited($type, $cooldown = null)
    {
        if (!($this->config['rate_limit']['enabled'] ?? true)) {
            return false;
        }
        
        if ($cooldown === null) {
            $cooldown = $this->config['rate_limit']['cooldown_seconds'] ?? 60;
        }
        
        // Cargar timestamps de últimas notificaciones
        $rateLimits = [];
        if (file_exists($this->rateLimitFile)) {
            $content = @file_get_contents($this->rateLimitFile);
            $rateLimits = json_decode($content, true) ?: [];
        }
        
        $now = time();
        $lastSent = $rateLimits[$type] ?? 0;
        
        if (($now - $lastSent) < $cooldown) {
            return true; // Rate limited
        }
        
        // Actualizar timestamp (crear directorio si es necesario)
        $rateLimits[$type] = $now;
        $dir = dirname($this->rateLimitFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($this->rateLimitFile, json_encode($rateLimits));
        
        return false;
    }
    
    /**
     * Obtiene la IP del cliente
     * 
     * @return string
     */
    private function getClientIp()
    {
        $ip = 'Unknown';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    /**
     * Test de conectividad con Telegram (para debugging)
     * 
     * @return array Resultado del test
     */
    public function testConnection()
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'error' => 'Telegram notifications are disabled in config'
            ];
        }
        
        if (empty($this->botToken)) {
            return [
                'success' => false,
                'error' => 'Bot token not configured'
            ];
        }
        
        if (empty($this->chatId)) {
            return [
                'success' => false,
                'error' => 'Chat ID not configured'
            ];
        }
        
        $testMessage = "🧪 *Test de conectividad*\n\nPortfolio Telegram Bot funcionando correctamente!\n\n🕐 " . date('Y-m-d H:i:s');
        
        if ($this->sendMessage($testMessage)) {
            return [
                'success' => true,
                'message' => 'Test notification sent successfully',
                'bot_token' => substr($this->botToken, 0, 10) . '...',
                'chat_id' => $this->chatId
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to send test message. Check logs for details.'
            ];
        }
    }
}
