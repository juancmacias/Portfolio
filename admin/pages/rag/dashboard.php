<?php
/**
 * Dashboard RAG - Centro de Control Integrado
 * Panel unificado para gestión completa del sistema conversacional
 * 
 * @package PortfolioRAG
 * @author Juan Carlos Macías
 * @version 1.0
 */

// Verificar acceso admin
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../../config/config.local.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

$auth = new AdminAuth();

// Verificar autenticación
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = $auth->getUser();

class RAGDashboard {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener estadísticas generales del sistema
     */
    public function getSystemStats() {
        try {
            $stats = [];
            
            // Estadísticas de prompts
            $promptStats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as used
                FROM chat_prompts
            ");
            $stats['prompts'] = $promptStats ?: ['total' => 0, 'active' => 0, 'used' => 0];
            
            // Estadísticas de documentos
            $documentStats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(file_size) as total_size,
                    AVG(file_size) as avg_size
                FROM reference_documents
                WHERE is_active = 1
            ");
            $stats['documents'] = $documentStats ?: ['total' => 0, 'total_size' => 0, 'avg_size' => 0];
            
            // Estadísticas de chunks
            $chunkStats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    AVG(CHAR_LENGTH(chunk_text)) as avg_words,
                    SUM(CHAR_LENGTH(chunk_text)) as total_words
                FROM document_chunks dc
                JOIN reference_documents rd ON dc.document_id = rd.id
                WHERE rd.is_active = 1
            ");
            $stats['chunks'] = $chunkStats ?: ['total' => 0, 'avg_words' => 0, 'total_words' => 0];
            
            // Estadísticas de embeddings
            $embeddingStats = $this->db->fetchOne("
                SELECT COUNT(*) as total FROM simple_embeddings
            ");
            $stats['embeddings'] = $embeddingStats ?: ['total' => 0];
            
            // Estadísticas de conversaciones
            $conversationStats = $this->db->fetchOne("
                SELECT 
                    COUNT(DISTINCT session_id) as sessions,
                    COUNT(*) as total_messages,
                    AVG(CHAR_LENGTH(user_message)) as avg_message_length
                FROM enhanced_conversations
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stats['conversations'] = $conversationStats ?: ['sessions' => 0, 'total_messages' => 0, 'avg_message_length' => 0];
            
            // Configuración del sistema
            $configStats = $this->db->fetchAll("SELECT config_key, config_value FROM chat_configuration WHERE is_active = 1");
            $stats['config'] = [];
            foreach ($configStats as $config) {
                $stats['config'][$config['config_key']] = $config['config_value'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas RAG: " . $e->getMessage());
            return $this->getEmptyStats();
        }
    }
    
    /**
     * Estadísticas vacías por defecto
     */
    private function getEmptyStats() {
        return [
            'prompts' => ['total' => 0, 'active' => 0, 'used' => 0],
            'documents' => ['total' => 0, 'total_size' => 0, 'avg_size' => 0],
            'chunks' => ['total' => 0, 'avg_words' => 0, 'total_words' => 0],
            'embeddings' => ['total' => 0],
            'conversations' => ['sessions' => 0, 'total_messages' => 0, 'avg_message_length' => 0],
            'config' => []
        ];
    }
    
    /**
     * Obtener actividad reciente
     */
    public function getRecentActivity() {
        try {
            $activities = [];
            
            // Últimos documentos subidos
            $documents = $this->db->fetchAll("
                SELECT title, upload_date, file_size 
                FROM reference_documents 
                ORDER BY upload_date DESC 
                LIMIT 5
            ");
            foreach ($documents as $doc) {
                $activities[] = [
                    'type' => 'document',
                    'title' => "Documento subido: " . $doc['title'],
                    'timestamp' => $doc['upload_date'],
                    'details' => round(($doc['file_size'] ?? 0) / 1024, 1) . ' KB'
                ];
            }
            
            // Últimas conversaciones
            $conversations = $this->db->fetchAll("
                SELECT session_id, user_message, timestamp
                FROM enhanced_conversations 
                ORDER BY timestamp DESC 
                LIMIT 5
            ");
            foreach ($conversations as $conv) {
                $activities[] = [
                    'type' => 'conversation',
                    'title' => "Chat: " . substr($conv['user_message'], 0, 50) . '...',
                    'timestamp' => $conv['timestamp'],
                    'details' => 'Sesión: ' . substr($conv['session_id'], 0, 8)
                ];
            }
            
            // Ordenar por timestamp
            usort($activities, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            
            return array_slice($activities, 0, 10);
            
        } catch (Exception $e) {
            error_log("Error obteniendo actividad: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar estado del sistema
     */
    public function getSystemHealth() {
        $health = [
            'database' => false,
            'files' => false,
            'config' => false,
            'overall' => 'critical'
        ];
        
        try {
            // Test de base de datos
            $health['database'] = $this->db->testConnection();
            
            // Test de archivos/directorios
            $uploadsDir = __DIR__ . '/../../../uploads/documents/';
            $health['files'] = is_dir($uploadsDir) && is_writable($uploadsDir);
            
            // Test de configuración
            $configCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM chat_configuration WHERE is_active = 1");
            $health['config'] = ($configCount['count'] ?? 0) > 0;
            
            // Estado general
            $healthyComponents = array_filter($health, function($v, $k) {
                return $k !== 'overall' && $v === true;
            }, ARRAY_FILTER_USE_BOTH);
            
            $healthPercentage = count($healthyComponents) / 3;
            
            if ($healthPercentage >= 1) {
                $health['overall'] = 'excellent';
            } elseif ($healthPercentage >= 0.67) {
                $health['overall'] = 'good';
            } elseif ($healthPercentage >= 0.33) {
                $health['overall'] = 'warning';
            } else {
                $health['overall'] = 'critical';
            }
            
        } catch (Exception $e) {
            error_log("Error verificando salud del sistema: " . $e->getMessage());
        }
        
        return $health;
    }
}

// Inicializar dashboard
$dashboard = new RAGDashboard();
$stats = $dashboard->getSystemStats();
$activity = $dashboard->getRecentActivity();
$health = $dashboard->getSystemHealth();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎛️ Dashboard RAG - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .breadcrumb {
            color: #666;
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .health-indicator {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .health-excellent { background: #d4edda; color: #155724; }
        .health-good { background: #d1ecf1; color: #0c5460; }
        .health-warning { background: #fff3cd; color: #856404; }
        .health-critical { background: #f8d7da; color: #721c24; }
        
        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-detail {
            font-size: 0.8rem;
            color: #888;
            margin-top: 10px;
        }
        
        .activity-panel {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .activity-panel h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .activity-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            width: 40px;
            text-align: center;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .activity-time {
            color: #666;
            font-size: 0.8rem;
        }
        
        .activity-details {
            color: #888;
            font-size: 0.8rem;
            margin-top: 3px;
        }
        
        .navigation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .nav-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .nav-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .nav-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .nav-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .nav-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .nav-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .navigation-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Modal de Ayuda */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: none;
            border-radius: 8px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            padding: 0 10px;
        }
        
        .close:hover {
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .variable-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .variable-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .variable-item {
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .variable-name {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #e83e8c;
            background: #f8f9fa;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .variable-description {
            margin-top: 8px;
            color: #666;
            line-height: 1.5;
        }
        
        .example-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .example-code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            line-height: 1.4;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        
        .btn-help {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            margin-left: 10px;
        }
        
        .btn-help:hover {
            background: #138496;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>🎛️ Dashboard RAG</h1>
            <div class="breadcrumb">
                <a href="../dashboard.php">📊 Dashboard Principal</a> / Sistema RAG
            </div>
        </div>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Salir</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Estado del Sistema -->
        <div class="health-indicator health-<?php echo $health['overall']; ?>">
            Estado del Sistema RAG: <?php echo ucfirst($health['overall']); ?>
        </div>
        
        <!-- Main Grid -->
        <div class="main-grid">
            <!-- Estadísticas -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">💬</div>
                        <div class="stat-number"><?php echo $stats['prompts']['total']; ?></div>
                        <div class="stat-label">Prompts</div>
                        <div class="stat-detail">
                            <?php echo $stats['prompts']['active']; ?> activos · 
                            <?php echo $stats['prompts']['used']; ?> usados
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📁</div>
                        <div class="stat-number"><?php echo $stats['documents']['total']; ?></div>
                        <div class="stat-label">Documentos</div>
                        <div class="stat-detail">
                            <?php echo round(($stats['documents']['total_size'] ?? 0) / 1024 / 1024, 1); ?> MB total
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🧩</div>
                        <div class="stat-number"><?php echo $stats['chunks']['total']; ?></div>
                        <div class="stat-label">Chunks</div>
                        <div class="stat-detail">
                            ~<?php echo round($stats['chunks']['avg_words'] ?? 0); ?> palabras promedio
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🔍</div>
                        <div class="stat-number"><?php echo $stats['embeddings']['total']; ?></div>
                        <div class="stat-label">Embeddings</div>
                        <div class="stat-detail">Vectores semánticos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🗨️</div>
                        <div class="stat-number"><?php echo $stats['conversations']['sessions']; ?></div>
                        <div class="stat-label">Sesiones</div>
                        <div class="stat-detail">
                            <?php echo $stats['conversations']['total_messages']; ?> mensajes (30 días)
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actividad Reciente -->
            <div class="activity-panel">
                <h3>📈 Actividad Reciente</h3>
                
                <?php if (empty($activity)): ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📭</div>
                        <p>No hay actividad reciente</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($activity as $item): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php echo $item['type'] === 'document' ? '📄' : '💬'; ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="activity-time">
                                    <?php echo date('d/m/Y H:i', strtotime($item['timestamp'])); ?>
                                </div>
                                <div class="activity-details"><?php echo htmlspecialchars($item['details']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Navegación -->
        <div class="navigation-grid">
            <a href="prompts.php" class="nav-card">
                <span class="nav-icon">💬</span>
                <div class="nav-title">Gestión de Prompts</div>
                <div class="nav-description">
                    Crear, editar y gestionar prompts personalizados para diferentes contextos de conversación
                </div>
            </a>
            
            <a href="documents.php" class="nav-card">
                <span class="nav-icon">📁</span>
                <div class="nav-title">Subida de Documentos</div>
                <div class="nav-description">
                    Cargar PDFs, extraer texto y generar embeddings para alimentar el sistema RAG
                </div>
            </a>
            
            <a href="logs-viewer.php" class="nav-card">
                <span class="nav-icon">📋</span>
                <div class="nav-title">Logs del Chat</div>
                <div class="nav-description">
                    Ver prompts enviados, contexto RAG y respuestas del modelo en tiempo real
                </div>
            </a>
            
            <a href="../dashboard.php" class="nav-card">
                <span class="nav-icon">🏠</span>
                <div class="nav-title">Dashboard Principal</div>
                <div class="nav-description">
                    Volver al panel administrativo principal del portfolio
                </div>
            </a>
        </div>
    </div>
    
    <script>
        // Auto-refresh cada 5 minutos
        setTimeout(() => {
            location.reload();
        }, 5 * 60 * 1000);
        
        console.log('🎛️ Dashboard RAG inicializado');
        console.log('📊 Estadísticas:', <?php echo json_encode($stats); ?>);
        console.log('🏥 Estado:', <?php echo json_encode($health); ?>);
    </script>
</body>
</html>