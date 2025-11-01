<?php
/**
 * Configuración de Base de Datos - Sistema Adaptativo
 * Detecta automáticamente PDO o MySQLi y usa el disponible
 * 
 * @package Portfolio
 * @author Juan Carlos Macías
 * @version 1.0.0
 */

// Evitar acceso directo
if (!defined('ADMIN_ACCESS')) {
    die('Acceso directo no permitido');
}

// Cargar configuración local
$config_file = __DIR__ . '/config.local.php';
if (!file_exists($config_file)) {
    throw new Exception('No se encontró config.local.php');
}
require_once $config_file;

/**
 * Clase Database adaptativa que usa PDO o MySQLi automáticamente
 */
class Database {
    private static $instance = null;
    private $connection;
    private $driver; // 'pdo' o 'mysqli'
    private $host;
    private $username;
    private $password;
    private $database;

    private function __construct() {
        $this->initializeConfig();
        $this->detectDriver();
        $this->connect();
    }

    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializar configuración
     */
    private function initializeConfig() {
        $config = get_db_config();
        $this->host = $config['host'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->database = $config['database'];
    }

    /**
     * Detectar qué driver usar
     */
    private function detectDriver() {
        if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
            $this->driver = 'pdo';
        } elseif (extension_loaded('mysqli')) {
            $this->driver = 'mysqli';
        } else {
            throw new Exception("Error crítico: No hay extensiones MySQL disponibles (PDO ni MySQLi)");
        }
    }

    /**
     * Conectar usando el driver disponible
     */
    private function connect() {
        if ($this->driver === 'pdo') {
            $this->connectPDO();
        } else {
            $this->connectMySQLi();
        }
    }

    /**
     * Conexión PDO
     */
    private function connectPDO() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ];
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("PDO Connection Error: " . $e->getMessage());
            throw new Exception("Error de conexión PDO: " . $e->getMessage());
        }
    }

    /**
     * Conexión MySQLi
     */
    private function connectMySQLi() {
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->connection->connect_error) {
                throw new Exception("Error MySQLi: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
            $this->connection->query("SET time_zone = '+01:00'");
        } catch (Exception $e) {
            error_log("MySQLi Connection Error: " . $e->getMessage());
            throw new Exception("Error de conexión MySQLi: " . $e->getMessage());
        }
    }

    /**
     * Ejecutar consulta (compatible con ambos drivers)
     */
    public function query($sql, $params = []) {
        if ($this->driver === 'pdo') {
            return $this->queryPDO($sql, $params);
        } else {
            return $this->queryMySQLi($sql, $params);
        }
    }

    /**
     * Query PDO
     */
    private function queryPDO($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("PDO Query Error: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
            throw new Exception("Error PDO: " . $e->getMessage());
        }
    }

    /**
     * Query MySQLi
     */
    private function queryMySQLi($sql, $params = []) {
        try {
            if (empty($params)) {
                $result = $this->connection->query($sql);
                if ($result === false) {
                    throw new Exception("Error MySQLi: " . $this->connection->error);
                }
                return $result;
            } else {
                $stmt = $this->connection->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Error preparando MySQLi: " . $this->connection->error);
                }
                
                if (!empty($params)) {
                    $types = $this->getMySQLiTypes($params);
                    $stmt->bind_param($types, ...$params);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Error ejecutando MySQLi: " . $stmt->error);
                }
                
                if (stripos(trim($sql), 'SELECT') === 0) {
                    return $stmt->get_result();
                }
                
                return $stmt->affected_rows;
            }
        } catch (Exception $e) {
            error_log("MySQLi Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }

    /**
     * Obtener tipos para MySQLi
     */
    private function getMySQLiTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }

    /**
     * Fetch one (compatible)
     */
    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        
        if ($this->driver === 'pdo') {
            return $result->fetch();
        } else {
            if ($result instanceof mysqli_result) {
                return $result->fetch_assoc();
            }
            return null;
        }
    }

    /**
     * Fetch all (compatible)
     */
    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        
        if ($this->driver === 'pdo') {
            return $result->fetchAll();
        } else {
            $rows = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            return $rows;
        }
    }

    /**
     * Last Insert ID (compatible)
     */
    public function lastInsertId() {
        if ($this->driver === 'pdo') {
            return $this->connection->lastInsertId();
        } else {
            return $this->connection->insert_id;
        }
    }

    /**
     * Insert y devolver ID
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->lastInsertId();
    }

    /**
     * Test de conexión
     */
    public function testConnection() {
        try {
            $result = $this->fetchOne("SELECT 1 as test");
            return isset($result['test']) && $result['test'] == 1;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtener información del driver
     */
    public function getDriverInfo() {
        $info = ['driver' => $this->driver];
        
        if ($this->driver === 'pdo') {
            $info['version'] = $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
        } else {
            $info['version'] = $this->connection->server_info;
        }
        
        return $info;
    }

    /**
     * Verificar si estamos en ambiente de desarrollo
     */
    private function isDevelopment() {
        return in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) || 
               isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development';
    }

    /**
     * Verificar conexión a la base de datos
     */
    public function isConnected() {
        return $this->testConnection();
    }

    /**
     * Obtener estadísticas de la base de datos (para dashboard)
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Verificar si existe la tabla articles
            if ($this->driver === 'pdo') {
                $tables = $this->fetchAll("SHOW TABLES LIKE 'articles'");
            } else {
                $result = $this->connection->query("SHOW TABLES LIKE 'articles'");
                $tables = [];
                if ($result && $result->num_rows > 0) {
                    $tables[] = $result->fetch_array();
                }
            }
            
            if (empty($tables)) {
                // Si no existe la tabla articles, devolver estadísticas vacías
                return [
                    'articles' => [
                        'total' => 0,
                        'published' => 0,
                        'draft' => 0,
                        'ai_generated' => 0
                    ],
                    'admin_users' => $this->getAdminUsersCount()
                ];
            }
            
            // Contar artículos por estado
            $articles = $this->fetchAll("SELECT status, COUNT(*) as count FROM articles GROUP BY status");
            foreach ($articles as $row) {
                $stats['articles'][$row['status']] = $row['count'];
            }
            
            // Total de artículos
            $total = $this->fetchOne("SELECT COUNT(*) as total FROM articles");
            $stats['articles']['total'] = $total['total'] ?? 0;
            
            // Artículos con IA
            $ai_generated = $this->fetchOne("SELECT COUNT(*) as total FROM articles WHERE ai_generated = 1");
            $stats['articles']['ai_generated'] = $ai_generated['total'] ?? 0;
            
            // Usuarios admin
            $stats['admin_users'] = $this->getAdminUsersCount();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Stats Error: " . $e->getMessage());
            return [
                'articles' => ['total' => 0, 'published' => 0, 'draft' => 0, 'ai_generated' => 0],
                'admin_users' => 0
            ];
        }
    }

    /**
     * Contar usuarios admin
     */
    private function getAdminUsersCount() {
        try {
            $result = $this->fetchOne("SELECT COUNT(*) as total FROM admin_users WHERE active = 1");
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener el número de filas afectadas por la última operación
     */
    public function rowsAffected() {
        if ($this->driver === 'pdo') {
            // PDO no mantiene un estado global de filas afectadas
            // Se debe usar el resultado de la última operación execute()
            return 0; // Por seguridad, retornamos 0
        } else {
            return mysqli_affected_rows($this->connection);
        }
    }

    /**
     * Obtener conexión directa (para compatibilidad)
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Evitar clonación del singleton
     */
    private function __clone() {}

    /**
     * Evitar deserialización del singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Función helper para obtener la instancia de la base de datos
 */
function db() {
    return Database::getInstance();
}

/**
 * Función helper para obtener la conexión directamente (mantiene compatibilidad)
 */
function pdo() {
    return Database::getInstance()->getConnection();
}
?>