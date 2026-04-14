<?php
/**
 * Dashboard - Panel Administrativo
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/config.local.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$auth = new AdminAuth();

// Verificar autenticación
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getUser();
$db = Database::getInstance();

// Obtener estadísticas básicas
try {
    $stats = [
        'articles_total' => $db->fetchOne("SELECT COUNT(*) as count FROM articles")['count'] ?? 0,
        'articles_published' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'published'")['count'] ?? 0,
        'articles_draft' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE status = 'draft'")['count'] ?? 0,
        'projects_total' => $db->fetchOne("SELECT COUNT(*) as count FROM projects")['count'] ?? 0,
        'projects_active' => $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE status = 'active'")['count'] ?? 0
    ];
    
    // Estadísticas RAG
    $ragStats = [
        'prompts_total' => 0,
        'documents_total' => 0,
        'conversations_total' => 0
    ];
    
    try {
        $ragStats['prompts_total'] = $db->fetchOne("SELECT COUNT(*) as count FROM chat_prompts")['count'] ?? 0;
        $ragStats['documents_total'] = $db->fetchOne("SELECT COUNT(*) as count FROM reference_documents WHERE status = 'active'")['count'] ?? 0;
        $ragStats['conversations_total'] = $db->fetchOne("SELECT COUNT(DISTINCT session_id) as count FROM enhanced_conversations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'] ?? 0;
    } catch (Exception $e) {
        // Las tablas RAG no existen aún
    }
    
    // Estadísticas de Contacto
    $contactStats = [
        'contact_total' => 0,
        'contact_new' => 0
    ];
    
    try {
        $contactStats['contact_total'] = $db->fetchOne("SELECT COUNT(*) as count FROM contact_submissions")['count'] ?? 0;
        $contactStats['contact_new'] = $db->fetchOne("SELECT COUNT(*) as count FROM contact_submissions WHERE status = 'new'")['count'] ?? 0;
    } catch (Exception $e) {
        // La tabla contact_submissions no existe
    }
    
    $stats = array_merge($stats, $ragStats, $contactStats);
    
} catch (Exception $e) {
    $stats = [
        'articles_total' => 0, 
        'articles_published' => 0, 
        'articles_draft' => 0,
        'projects_total' => 0,
        'projects_active' => 0,
        'prompts_total' => 0,
        'documents_total' => 0,
        'conversations_total' => 0,
        'contact_total' => 0,
        'contact_new' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Administrativo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: white; padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card h3 { font-size: 2rem; color: #667eea; margin-bottom: 10px; }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .action-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .action-card h3 { margin-bottom: 15px; color: #333; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; margin: 5px; transition: background 0.3s; }
        .btn:hover { background: #5a6fd8; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Dashboard</h1>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?></span>
            <a href="logout.php" class="btn btn-danger">🚪 Salir</a>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['articles_total']; ?></h3>
                <p>Total Artículos</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['articles_published']; ?></h3>
                <p>Publicados</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['articles_draft']; ?></h3>
                <p>Borradores</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['projects_total']; ?></h3>
                <p>Total Proyectos</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['projects_active']; ?></h3>
                <p>Proyectos Activos</p>
            </div>
            <div class="stat-card" style="border-left: 4px solid #667eea;">
                <h3><?php echo $stats['prompts_total']; ?></h3>
                <p>💬 Prompts RAG</p>
            </div>
            <div class="stat-card" style="border-left: 4px solid #28a745;">
                <h3><?php echo $stats['documents_total']; ?></h3>
                <p>📁 Documentos</p>
            </div>
            <div class="stat-card" style="border-left: 4px solid #17a2b8;">
                <h3><?php echo $stats['conversations_total']; ?></h3>
                <p>🗨️ Conversaciones (30d)</p>
            </div>
            <div class="stat-card" style="border-left: 4px solid #ff6b6b;">
                <h3><?php echo $stats['contact_total']; ?></h3>
                <p>📬 Mensajes Contacto</p>
            </div>
            <?php if ($stats['contact_new'] > 0): ?>
            <div class="stat-card" style="border-left: 4px solid #ffa502; background: #fff3e0;">
                <h3 style="color: #ffa502;"><?php echo $stats['contact_new']; ?></h3>
                <p style="color: #ff9000; font-weight: bold;">🔔 Nuevos sin leer</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Acciones Rápidas -->
        <div class="actions-grid">
            <div class="action-card">
                <h3>📝 Gestión de Artículos</h3>
                <a href="articles.php" class="btn">📋 Ver Todos los Artículos</a>
                <a href="article-create.php" class="btn btn-success">➕ Nuevo Artículo</a>
                <a href="article-create.php?ai=1" class="btn btn-info">🤖 Generar con IA</a>
            </div>

            <div class="action-card">
                <h3>🚀 Gestión de Proyectos</h3>
                <a href="projects.php" class="btn">📋 Ver Todos los Proyectos</a>
                <a href="project-create.php" class="btn btn-success">➕ Nuevo Proyecto</a>
                <a href="../sql/migrate_projects.php" class="btn btn-info">📊 Migrar Datos JSON</a>
            </div>

            <div class="action-card">
                <h3>⚙️ Configuración</h3>
                <a href="settings.php" class="btn">🔧 Configuración General</a>
                <a href="settings.php#ai" class="btn btn-info">🤖 Configurar IA</a>
                <a href="google-calendar.php" class="btn btn-info">📅 Google Calendar</a>
            </div>

            <div class="action-card">
                <h3>🔍 Herramientas SEO</h3>
                <a href="sitemap-manager.php" class="btn">🗺️ Generador de Sitemap</a>
                <a href="sitemap-manager.php?action=info" class="btn btn-info">📊 Estado del Sitemap</a>
                <a href="gsc-inspector.php" class="btn btn-success">🔎 Inspector GSC</a>
                <a href="test-search-engine-notification.php" class="btn btn-info">🧪 Test Notificaciones</a>
            </div>

            <div class="action-card">
                <h3>🤖 Sistema RAG Conversacional</h3>
                <a href="rag/dashboard.php" class="btn">🎛️ Centro de Control RAG</a>
                <a href="rag/prompts.php" class="btn btn-success">💬 Gestión de Prompts</a>
                <a href="rag/documents.php" class="btn btn-info">📁 Subida de Documentos</a>
            </div>

            <div class="action-card" style="border-left: 4px solid #667eea;">
                <h3>📬 Formularios de Contacto</h3>
                <a href="contact-submissions.php" class="btn">📋 Ver Todos los Mensajes</a>
                <?php if ($stats['contact_new'] > 0): ?>
                <a href="contact-submissions.php?status=new" class="btn btn-danger" style="background: #ffa502;">
                    🔔 Nuevos (<?php echo $stats['contact_new']; ?>)
                </a>
                <?php endif; ?>
                <a href="contact-submissions.php?status=replied" class="btn btn-success">✉️ Respondidos</a>
            </div>

        </div>
    </div>
</body>
</html>