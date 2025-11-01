# � **Plan de Seguridad - Sistema Integrado de Artículos con IA**

## **Resumen Ejecutivo**

Este documento establece las medidas de seguridad para implementar un **sistema integrado de gestión de artículos con IA**, desarrollado completamente en PHP y utilizando sesiones tradicionales. El enfoque prioriza la **simplicidad, seguridad práctica** y **aprovechamiento de la infraestructura existente** del portfolio.

---

## 🔒 **Evaluación de Riesgos de Seguridad**

### **Matriz de Riesgos para Sistema Integrado**

| Amenaza | Probabilidad | Impacto | Riesgo | Mitigación |
|---------|-------------|---------|---------|------------|
| Inyección SQL | Media | Alto | 🔴 Alto | Prepared statements, validación input |
| XSS | Alta | Medio | 🟡 Medio | htmlspecialchars(), CSP headers |
| CSRF | Media | Medio | 🟡 Medio | Tokens CSRF en formularios |
| Brute Force | Alta | Medio | 🟡 Medio | Rate limiting, intentos limitados |
| Session Hijacking | Media | Alto | 🟡 Medio | HTTPS, regeneración session ID |
| Acceso directo admin | Alta | Alto | 🔴 Alto | Verificación sesión, redirects |
| Exposición de API Keys | Baja | Alto | � Medio | Variables entorno, archivos config |
| File Upload | Media | Alto | 🟡 Medio | Validación tipo, ubicación segura |

---

## 🔐 **Implementación de Seguridad Integrada**

### **1. Autenticación con Sesiones PHP**

#### **Sistema de Sesiones Seguras**
```php
<?php
// backend/porfolio/includes/auth.php
class AdminAuth {
    private $maxFailedAttempts = 5;
    private $lockoutDuration = 900;        // 15 minutos
    private $sessionTimeout = 3600;       // 1 hora
    
    public function __construct() {
        $this->startSecureSession();
    }
    
    private function startSecureSession() {
        // Configuración segura de sesiones
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } else if (time() - $_SESSION['last_regeneration'] > 300) { // 5 min
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    public function login($username, $password) {
        // Verificar rate limiting
        if ($this->isAccountLocked($username)) {
            return ['success' => false, 'message' => 'Cuenta bloqueada temporalmente'];
        }
        
        // Validar credenciales
        $user = $this->validateCredentials($username, $password);
        if (!$user) {
            $this->recordFailedAttempt($username);
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }
        
        // Crear sesión segura
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        
        // Limpiar intentos fallidos
        $this->clearFailedAttempts($username);
        
        return ['success' => true, 'message' => 'Login exitoso'];
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            return false;
        }
        
        // Verificar timeout de sesión
        if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        // Verificar IP (opcional, puede ser problemático con proxies)
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            $this->logout();
            return false;
        }
        
        // Actualizar última actividad
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function logout() {
        // Destruir sesión completamente
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    private function validateCredentials($username, $password) {
        // Consulta preparada para evitar SQL injection
        $stmt = $this->db->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ? AND active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                return $user;
            }
        }
        return false;
    }
    
    private function isAccountLocked($username) {
        $stmt = $this->db->prepare("SELECT locked_until FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            return $user['locked_until'] && strtotime($user['locked_until']) > time();
        }
        return false;
    }
    
    private function recordFailedAttempt($username) {
        $stmt = $this->db->prepare("UPDATE admin_users SET failed_attempts = failed_attempts + 1 WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        // Bloquear si excede intentos máximos
        $stmt = $this->db->prepare("SELECT failed_attempts FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc() && $user['failed_attempts'] >= $this->maxFailedAttempts) {
            $lockUntil = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
            $stmt = $this->db->prepare("UPDATE admin_users SET locked_until = ? WHERE username = ?");
            $stmt->bind_param("ss", $lockUntil, $username);
            $stmt->execute();
        }
    }
}
```

### **2. Protección del Panel Administrativo**

#### **Verificación de Sesión en Cada Página**
```php
<?php
// backend/porfolio/config/security.php
function requireAdminLogin() {
    $auth = new AdminAuth();
    
    if (!$auth->isLoggedIn()) {
        // Registrar intento de acceso no autorizado
        error_log("Unauthorized access attempt to admin panel from IP: " . $_SERVER['REMOTE_ADDR']);
        
        // Redireccionar al login
        header('Location: /backend/porfolio/admin/login.php');
        exit();
    }
}

// Proteger todas las páginas administrativas
requireAdminLogin();
?>
```

#### **Protección CSRF en Formularios**
```php
<?php
class CSRFProtection {
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function getHiddenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
```

### **3. Validación y Sanitización de Datos**

#### **Validador Integrado**
```php
<?php
// backend/porfolio/includes/validator.php
class DataValidator {
    
    public static function validateArticleData($data) {
        $errors = [];
        
        // Validar título
        if (empty($data['title'])) {
            $errors[] = "El título es obligatorio";
        } elseif (strlen($data['title']) > 255) {
            $errors[] = "El título no puede exceder 255 caracteres";
        }
        
        // Validar contenido
        if (empty($data['content'])) {
            $errors[] = "El contenido es obligatorio";
        }
        
        // Validar slug
        if (!empty($data['slug'])) {
            if (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
                $errors[] = "El slug solo puede contener letras minúsculas, números y guiones";
            }
        }
        
        // Validar estado
        $allowedStatus = ['draft', 'published', 'archived'];
        if (!in_array($data['status'], $allowedStatus)) {
            $errors[] = "Estado inválido";
        }
        
        return $errors;
    }
    
    public static function sanitizeArticleData($data) {
        $sanitized = [];
        
        // Sanitizar campos básicos
        $sanitized['title'] = htmlspecialchars(trim($data['title']), ENT_QUOTES, 'UTF-8');
        $sanitized['excerpt'] = htmlspecialchars(trim($data['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8');
        $sanitized['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $data['slug'] ?? ''));
        $sanitized['status'] = in_array($data['status'], ['draft', 'published', 'archived']) ? $data['status'] : 'draft';
        
        // Para el contenido, permitir HTML básico pero sanitizar
        $sanitized['content'] = self::sanitizeHTML($data['content']);
        
        // Tags como string simple
        $sanitized['tags'] = htmlspecialchars(trim($data['tags'] ?? ''), ENT_QUOTES, 'UTF-8');
        
        return $sanitized;
    }
    
    private static function sanitizeHTML($html) {
        // Lista blanca de etiquetas HTML permitidas
        $allowedTags = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><code><pre>';
        
        return strip_tags($html, $allowedTags);
    }
}
```

### **4. Seguridad de Base de Datos**

#### **Conexión Segura y Consultas Preparadas**
```php
<?php
// backend/porfolio/config/database.php
class SecureDatabase {
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $host = $_SERVER['DB_HOST'] ?? 'localhost';
        $dbname = $_SERVER['DB_NAME'] ?? 'portfolio_db';
        $username = $_SERVER['DB_USER'] ?? 'portfolio_user';
        $password = $_SERVER['DB_PASS'] ?? '';
        
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Usar prepared statements reales
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Ajustar según SSL config
            ];
            
            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    public function insertArticle($data) {
        $sql = "INSERT INTO articles (title, slug, content, excerpt, status, author, ai_generated, ai_model, ai_prompt, tags, meta_description, reading_time, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['excerpt'],
            $data['status'],
            $data['author'],
            $data['ai_generated'] ? 1 : 0,
            $data['ai_model'],
            $data['ai_prompt'],
            $data['tags'],
            $data['meta_description'],
            $data['reading_time']
        ]);
    }
    
    public function getArticles($status = 'published', $limit = 10, $offset = 0) {
        $sql = "SELECT id, title, slug, excerpt, author, published_at, reading_time, tags, featured_image, views_count, ai_generated 
                FROM articles 
                WHERE status = ? 
                ORDER BY published_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$status, $limit, $offset]);
        return $stmt->fetchAll();
    }
            
            if (!empty($value)) {
                // Type validation
                if (!$this->validateType($value, $rules['type'])) {
                    $errors[$field][] = "Invalid type";
                    continue;
                }
                
                // Length validation
                if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                    $errors[$field][] = "Minimum length: " . $rules['min_length'];
                }
                
                if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                    $errors[$field][] = "Maximum length: " . $rules['max_length'];
                }
                
                // Pattern validation
                if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                    $errors[$field][] = "Invalid format";
                }
                
                // Sanitize based on type
                $sanitized[$field] = $this->sanitize($value, $rules);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized
        ];
    }
    
    private function sanitize($value, $rules) {
        switch ($rules['type']) {
            case 'string':
                $value = trim($value);
                if (isset($rules['allowed_tags'])) {
                    // Permitir solo tags específicos
                    $allowedTags = '<' . implode('><', $rules['allowed_tags']) . '>';
                    $value = strip_tags($value, $allowedTags);
                } else {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
                break;
                
            case 'email':
                $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                break;
                
            case 'url':
                $value = filter_var($value, FILTER_SANITIZE_URL);
                break;
                
            case 'int':
                $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                break;
        }
        
        return $value;
    }
}
```

### **3. Rate Limiting Avanzado**

#### **Sistema de Rate Limiting Multi-nivel**
```php
<?php
class AdvancedRateLimiter extends RateLimiter {
    private $limits = [
        'api_public' => ['requests' => 100, 'window' => 3600],      // 100/hora
        'api_auth' => ['requests' => 1000, 'window' => 3600],       // 1000/hora
        'ai_generation' => ['requests' => 10, 'window' => 3600],    // 10/hora
        'login_attempts' => ['requests' => 5, 'window' => 900],     // 5/15min
        'password_reset' => ['requests' => 3, 'window' => 3600]     // 3/hora
    ];
    
    public function checkAdvancedLimit($endpoint, $identifier, $userRole = null) {
        // Diferentes límites por rol
        $limitKey = $this->getLimitKey($endpoint, $userRole);
        $limit = $this->limits[$limitKey] ?? $this->limits['api_public'];
        
        $key = "{$limitKey}:{$identifier}";
        
        if (!$this->checkLimit($key, $limit['requests'], $limit['window'])) {
            $this->logRateLimitExceeded($endpoint, $identifier, $limit);
            return false;
        }
        
        return true;
    }
    
    private function getLimitKey($endpoint, $userRole) {
        if (strpos($endpoint, '/auth/login') !== false) {
            return 'login_attempts';
        }
        
        if (strpos($endpoint, '/ai/') !== false) {
            return 'ai_generation';
        }
        
        if ($userRole === 'admin' || $userRole === 'editor') {
            return 'api_auth';
        }
        
        return 'api_public';
    }
    
    private function logRateLimitExceeded($endpoint, $identifier, $limit) {
        error_log(sprintf(
            "Rate limit exceeded: endpoint=%s, identifier=%s, limit=%d/%ds",
            $endpoint,
            $identifier,
            $limit['requests'],
            $limit['window']
        ));
    }
}
```

### **4. Logging y Monitoreo de Seguridad**

#### **Sistema de Logs de Seguridad**
```php
<?php
class SecurityLogger {
    private $logPath;
    
    public function __construct() {
        $this->logPath = __DIR__ . '/../logs/security.log';
    }
    
    public function logSecurityEvent($eventType, $data = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $data['user_id'] ?? null,
            'data' => $data,
            'severity' => $this->getSeverity($eventType)
        ];
        
        $this->writeLog($logEntry);
        
        // Enviar alertas para eventos críticos
        if ($logEntry['severity'] === 'CRITICAL') {
            $this->sendAlert($logEntry);
        }
    }
    
    private function getSeverity($eventType) {
        $severities = [
            'LOGIN_SUCCESS' => 'INFO',
            'LOGIN_FAILED' => 'WARNING',
            'ACCOUNT_LOCKED' => 'CRITICAL',
            'TOKEN_VALIDATION_FAILED' => 'WARNING',
            'RATE_LIMIT_EXCEEDED' => 'WARNING',
            'SQL_INJECTION_ATTEMPT' => 'CRITICAL',
            'XSS_ATTEMPT' => 'HIGH',
            'UNAUTHORIZED_ACCESS' => 'HIGH',
            'AI_GENERATION_FAILED' => 'WARNING',
            'AI_GENERATION_SUCCESS' => 'INFO'
        ];
        
        return $severities[$eventType] ?? 'INFO';
    }
    
    private function writeLog($logEntry) {
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($this->logPath, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    private function sendAlert($logEntry) {
        // Implementar notificaciones (email, Slack, etc.)
        $subject = "Security Alert: " . $logEntry['event_type'];
        $message = "Security event detected:\n\n" . json_encode($logEntry, JSON_PRETTY_PRINT);
        
        // mail('admin@juancarlosmacias.es', $subject, $message);
        
        // Log crítico también en syslog
        syslog(LOG_CRIT, "Portfolio Security Alert: " . $logEntry['event_type']);
    }
}
```

---

## 🛡️ **Headers de Seguridad**

### **Configuración .htaccess Segura**
```apache
# Headers de seguridad
<IfModule mod_headers.c>
    # Prevenir clickjacking
    Header always set X-Frame-Options "DENY"
    
    # Prevenir MIME sniffing
    Header always set X-Content-Type-Options "nosniff"
    
    # XSS Protection
    Header always set X-XSS-Protection "1; mode=block"
    
    # HSTS (HTTP Strict Transport Security)
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    
    # Content Security Policy
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://api.groq.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https://api.groq.com https://api-inference.huggingface.co; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'"
    
    # Referrer Policy
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Permissions Policy
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=(), fullscreen=(self)"
    
    # Remove server information
    Header always unset Server
    Header always unset X-Powered-By
</IfModule>

# Proteger archivos sensibles
<FilesMatch "\.(env|log|sql|md)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Proteger directorios
RedirectMatch 404 /\.git
RedirectMatch 404 /\.env
RedirectMatch 404 /config/
RedirectMatch 404 /logs/
```

---

## 🔐 **Procedimientos de Seguridad**

### **1. Procedimiento de Respuesta a Incidentes**

#### **Clasificación de Incidentes**
- **CRÍTICO**: Acceso no autorizado, exfiltración de datos, defacement
- **ALTO**: Múltiples intentos de acceso, vulnerabilidades críticas
- **MEDIO**: Rate limiting excedido, errores de autenticación
- **BAJO**: Logs de actividad normal, métricas de rendimiento

#### **Proceso de Respuesta**
```
1. DETECCIÓN (0-5 min)
   ↓
2. CLASIFICACIÓN (5-10 min)
   ↓
3. CONTENCIÓN (10-30 min)
   ↓
4. ERRADICACIÓN (30 min - 2 horas)
   ↓
5. RECUPERACIÓN (2-24 horas)
   ↓
6. LECCIONES APRENDIDAS (1-7 días)
```

### **2. Procedimiento de Backup y Recuperación**

#### **Script de Backup Automatizado**
```bash
#!/bin/bash
# backup.sh - Backup automático seguro

BACKUP_DIR="/secure/backups/portfolio"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="portfolio_db"
APP_DIR="/var/www/portfolio"

# Crear directorio de backup
mkdir -p "$BACKUP_DIR/$DATE"

# Backup de base de datos (encriptado)
mysqldump --single-transaction --routines --triggers "$DB_NAME" | \
    gzip | \
    gpg --symmetric --cipher-algo AES256 --compress-algo 1 --output "$BACKUP_DIR/$DATE/database.sql.gz.gpg"

# Backup de archivos de aplicación
tar -czf "$BACKUP_DIR/$DATE/application.tar.gz" \
    --exclude='logs' \
    --exclude='cache' \
    --exclude='node_modules' \
    "$APP_DIR"

# Backup de logs (últimos 7 días)
find "$APP_DIR/backend/logs" -name "*.log" -mtime -7 | \
    tar -czf "$BACKUP_DIR/$DATE/logs.tar.gz" -T -

# Verificar integridad
sha256sum "$BACKUP_DIR/$DATE"/* > "$BACKUP_DIR/$DATE/checksums.sha256"

# Limpiar backups antiguos (retener 30 días)
find "$BACKUP_DIR" -type d -mtime +30 -exec rm -rf {} \;

# Log del backup
echo "$(date): Backup completed successfully - $DATE" >> /var/log/portfolio_backup.log
```

### **3. Procedimiento de Actualizaciones de Seguridad**

#### **Checklist de Actualización**
```markdown
## Pre-Actualización
- [ ] Crear backup completo
- [ ] Verificar integridad del backup
- [ ] Notificar a usuarios de mantenimiento
- [ ] Preparar entorno de rollback

## Durante la Actualización
- [ ] Activar modo de mantenimiento
- [ ] Aplicar parches de seguridad
- [ ] Actualizar dependencias
- [ ] Ejecutar tests de seguridad
- [ ] Verificar logs de errores

## Post-Actualización
- [ ] Verificar funcionalidad crítica
- [ ] Revisar logs de seguridad
- [ ] Monitorear métricas de rendimiento
- [ ] Desactivar modo de mantenimiento
- [ ] Documentar cambios aplicados
```

---

## 📊 **Monitoreo y Alertas**

### **Script de Monitoreo de Seguridad**
```bash
#!/bin/bash
# security_monitor.sh - Monitoreo continuo de seguridad

LOG_FILE="/var/log/portfolio/security.log"
ALERT_EMAIL="admin@juancarlosmacias.es"

# Verificar intentos de login fallidos (últimos 15 min)
failed_logins=$(grep "LOGIN_FAILED" "$LOG_FILE" | grep "$(date -d '15 minutes ago' '+%Y-%m-%d %H:%M')" | wc -l)

if [ "$failed_logins" -gt 10 ]; then
    echo "ALERT: $failed_logins failed login attempts in last 15 minutes" | \
        mail -s "Security Alert: Multiple Failed Logins" "$ALERT_EMAIL"
fi

# Verificar rate limiting excedido
rate_limit_exceeded=$(grep "RATE_LIMIT_EXCEEDED" "$LOG_FILE" | grep "$(date '+%Y-%m-%d %H')" | wc -l)

if [ "$rate_limit_exceeded" -gt 50 ]; then
    echo "ALERT: Rate limiting exceeded $rate_limit_exceeded times this hour" | \
        mail -s "Security Alert: Rate Limiting" "$ALERT_EMAIL"
fi

# Verificar espacio en disco
disk_usage=$(df /var/www/portfolio | awk 'NR==2 {print $5}' | sed 's/%//')

if [ "$disk_usage" -gt 90 ]; then
    echo "ALERT: Disk usage is at ${disk_usage}%" | \
        mail -s "System Alert: High Disk Usage" "$ALERT_EMAIL"
fi

# Verificar logs de errores críticos
critical_errors=$(grep "CRITICAL" "$LOG_FILE" | grep "$(date '+%Y-%m-%d')" | wc -l)

if [ "$critical_errors" -gt 0 ]; then
    echo "ALERT: $critical_errors critical security events today" | \
        mail -s "CRITICAL Security Alert" "$ALERT_EMAIL"
fi
```

---

## 🔒 **Configuración de Entorno Seguro**

### **Variables de Entorno (.env)**
```bash
# Base de datos
DB_HOST=localhost
DB_NAME=portfolio_secure_db
DB_USER=portfolio_user
DB_PASS=SecureRandomPassword123!@#

# JWT
JWT_SECRET=ultra_secure_jwt_secret_key_min_256_bits_long
JWT_ACCESS_EXPIRY=900
JWT_REFRESH_EXPIRY=604800

# API Keys (encriptadas en producción)
GROQ_API_KEY=encrypted:gsk_xxxxxxxxxxxxxxxxxxxxxxxx
HUGGINGFACE_API_KEY=encrypted:hf_xxxxxxxxxxxxxxxxxxxxxxxx

# Seguridad
ENCRYPTION_KEY=32_byte_encryption_key_for_aes_256
SALT_ROUNDS=12
MAX_LOGIN_ATTEMPTS=5
LOCKOUT_DURATION=900

# Rate Limiting
RATE_LIMIT_PUBLIC=100
RATE_LIMIT_AUTH=1000
RATE_LIMIT_AI=10

# Logs
LOG_LEVEL=INFO
LOG_RETENTION_DAYS=90
SECURITY_LOG_RETENTION_DAYS=365

# Backup
BACKUP_ENCRYPTION_KEY=gpg_backup_encryption_passphrase
BACKUP_RETENTION_DAYS=30
BACKUP_SCHEDULE="0 2 * * *"

# Alertas
ALERT_EMAIL=admin@juancarlosmacias.es
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/xxx
```

---

## ✅ **Checklist de Seguridad de Producción**

### **Pre-Deploy Security Checklist**

#### **Configuración del Servidor**
- [ ] HTTPS configurado con certificado válido
- [ ] Firewall configurado (solo puertos 80, 443, 22)
- [ ] SSH configurado con keys, no passwords
- [ ] Usuario de aplicación sin privilegios sudo
- [ ] PHP configurado en modo seguro
- [ ] Base de datos con usuario limitado
- [ ] Logs configurados y rotados

#### **Aplicación**
- [ ] Variables de entorno configuradas
- [ ] Secrets encriptados
- [ ] Headers de seguridad configurados
- [ ] Rate limiting implementado
- [ ] Validación de input en todos los endpoints
- [ ] Logs de seguridad funcionando
- [ ] Backups automáticos configurados

#### **Monitoreo**
- [ ] Alertas de seguridad configuradas
- [ ] Monitoreo de logs configurado
- [ ] Métricas de rendimiento activas
- [ ] Notificaciones de errores configuradas
- [ ] Plan de respuesta a incidentes documentado

---

*Plan de seguridad generado el 27 de octubre de 2025*
*Próxima revisión: 30 días*