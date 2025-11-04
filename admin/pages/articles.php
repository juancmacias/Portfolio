<?php
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/ArticleManager.php';

// Verificar autenticación
$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$articleManager = new ArticleManager();

// Manejar acción de inicializar vistas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'init_views') {
    $initResult = $articleManager->initializeViews();
    $initMessage = $initResult['message'];
    $initSuccess = $initResult['success'];
}

// Procesar filtros
$filters = [];
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;

if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

if (!empty($_GET['ai_generated'])) {
    $filters['ai_generated'] = $_GET['ai_generated'];
}

if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Obtener artículos
try {
    $result = $articleManager->getArticles($filters, $page, $perPage);
    $articles = $result['articles'];
    $pagination = $result['pagination'];
} catch (Exception $e) {
    $error = "Error al cargar artículos: " . $e->getMessage();
    $articles = [];
    $pagination = null;
}

// Obtener estadísticas
try {
    $stats = $articleManager->getStats();
} catch (Exception $e) {
    $stats = null;
}

$pageTitle = "Gestión de Artículos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Admin Portfolio</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { color: #333; font-size: 28px; }
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Botones */
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary { background: #007cba; color: white; }
        .btn-primary:hover { background: #005a87; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #545b62; }
        .btn-outline { background: transparent; color: #007cba; border: 1px solid #007cba; }
        .btn-outline:hover { background: #007cba; color: white; }
        .btn-sm { padding: 6px 10px; font-size: 12px; }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007cba;
        }
        .stat-card h3 { font-size: 24px; color: #333; margin-bottom: 5px; }
        .stat-card p { color: #666; font-size: 14px; }
        
        /* Filtros */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group label {
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        .form-control {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
        }
        
        /* Tabla */
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
        }
        .table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Estados */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-draft { background: #fff3cd; color: #856404; }
        .status-published { background: #d4edda; color: #155724; }
        .status-archived { background: #f8d7da; color: #721c24; }
        
        /* AI Badge */
        .ai-badge {
            background: #e7f3ff;
            color: #0366d6;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
        }
        
        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background: #007cba;
            color: white;
            border-color: #007cba;
        }
        .pagination .current {
            background: #007cba;
            color: white;
            border-color: #007cba;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .header-actions { justify-content: center; }
            .filter-grid { grid-template-columns: 1fr; }
            .table-container { overflow-x: auto; }
            .table { min-width: 800px; }
        }
        
        /* Alertas */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Acciones de tabla */
        .table-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        /* Truncate text */
        .text-truncate {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Meta info */
        .meta-info {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📝 <?= $pageTitle ?></h1>
            <div class="header-actions">
                <a href="article-create.php" class="btn btn-primary">
                    ➕ Nuevo Artículo
                </a>
                <a href="article-create.php?ai=1" class="btn btn-success">
                    🤖 Generar con IA
                </a>
                <a href="dashboard.php" class="btn btn-outline">
                    🏠 Dashboard
                </a>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($initMessage)): ?>
            <div class="alert alert-<?= $initSuccess ? 'success' : 'danger' ?>">
                <?= $initSuccess ? '✅' : '❌' ?> <?= htmlspecialchars($initMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <?php if ($stats): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= number_format($stats['total']) ?></h3>
                    <p>Total de Artículos</p>
                </div>
                <div class="stat-card">
                    <h3><?= number_format($stats['by_status']['published'] ?? 0) ?></h3>
                    <p>Publicados</p>
                </div>
                <div class="stat-card">
                    <h3><?= number_format($stats['by_status']['draft'] ?? 0) ?></h3>
                    <p>Borradores</p>
                </div>
                <div class="stat-card">
                    <h3><?= number_format($stats['ai_generated']) ?></h3>
                    <p>Generados por IA</p>
                </div>
                <div class="stat-card">
                    <h3><?= number_format($stats['total_views']) ?></h3>
                    <p>Total de Vistas</p>
                    <form method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="action" value="init_views">
                        <button type="submit" class="btn btn-sm btn-outline" 
                                onclick="return confirm('¿Inicializar vistas en artículos? Esto pondrá en 0 las vistas NULL.')">
                            🔄 Inicializar Vistas
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="filters">
            <form method="GET">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="search">Buscar</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="Título, contenido, tags..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos los estados</option>
                            <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Borrador</option>
                            <option value="published" <?= ($_GET['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
                            <option value="archived" <?= ($_GET['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archivado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ai_generated">Tipo</label>
                        <select id="ai_generated" name="ai_generated" class="form-control">
                            <option value="">Manual + IA</option>
                            <option value="true" <?= ($_GET['ai_generated'] ?? '') === 'true' ? 'selected' : '' ?>>Solo IA</option>
                            <option value="false" <?= ($_GET['ai_generated'] ?? '') === 'false' ? 'selected' : '' ?>>Solo Manual</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_from">Desde</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                               value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">Hasta</label>
                        <input type="date" id="date_to" name="date_to" class="form-control"
                               value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                        <a href="?" class="btn btn-secondary btn-sm">🔄 Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tabla de artículos -->
        <div class="table-container">
            <?php if (empty($articles)): ?>
                <div style="padding: 40px; text-align: center; color: #666;">
                    <h3>📝 No hay artículos</h3>
                    <p>Crea tu primer artículo o ajusta los filtros de búsqueda</p>
                    <a href="article-create.php" class="btn btn-primary" style="margin-top: 15px;">
                        ➕ Crear Primer Artículo
                    </a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Estado</th>
                            <th>Autor</th>
                            <th>Vistas</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong class="text-truncate" title="<?= htmlspecialchars($article['title']) ?>">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </strong>
                                        <?php if ($article['excerpt']): ?>
                                            <div class="meta-info text-truncate" title="<?= htmlspecialchars($article['excerpt']) ?>">
                                                <?= htmlspecialchars($article['excerpt']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $article['status'] ?>">
                                        <?= ucfirst($article['status']) ?>
                                    </span>
                                    <?php if ($article['published_at']): ?>
                                        <div class="meta-info">
                                            Pub: <?= date('d/m/Y', strtotime($article['published_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($article['author']) ?>
                                    <div class="meta-info">
                                        ⏱️ <?= $article['reading_time'] ?> min
                                    </div>
                                </td>
                                <td>
                                    <?= number_format($article['views']) ?>
                                </td>
                                <td>
                                    <?php if ($article['ai_generated']): ?>
                                        <span class="ai-badge" title="Modelo: <?= htmlspecialchars($article['ai_model']) ?>">
                                            🤖 IA
                                        </span>
                                    <?php else: ?>
                                        ✍️ Manual
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($article['created_at'])) ?>
                                    <?php if ($article['updated_at'] !== $article['created_at']): ?>
                                        <div class="meta-info">
                                            Mod: <?= date('d/m/Y', strtotime($article['updated_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="article-create.php?id=<?= $article['id'] ?>" 
                                           class="btn btn-sm btn-primary" title="Editar">
                                            ✏️
                                        </a>
                                        <a href="article-view.php?id=<?= $article['id'] ?>" 
                                           class="btn btn-sm btn-secondary" title="Ver">
                                            👁️
                                        </a>
                                        <?php if ($article['status'] === 'draft'): ?>
                                            <a href="article-actions.php?action=publish&id=<?= $article['id'] ?>" 
                                               class="btn btn-sm btn-success" title="Publicar"
                                               onclick="return confirm('¿Publicar este artículo?')">
                                                📤
                                            </a>
                                        <?php elseif ($article['status'] === 'published'): ?>
                                            <a href="article-actions.php?action=draft&id=<?= $article['id'] ?>" 
                                               class="btn btn-sm btn-warning" title="Borrador"
                                               onclick="return confirm('¿Convertir a borrador?')">
                                                📝
                                            </a>
                                        <?php endif; ?>
                                        <a href="article-actions.php?action=delete&id=<?= $article['id'] ?>" 
                                           class="btn btn-sm btn-danger" title="Eliminar"
                                           onclick="return confirm('¿Eliminar este artículo permanentemente?')">
                                            🗑️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <?php if ($pagination && $pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>">
                                ← Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <?php if ($i === $pagination['current_page']): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>">
                                Siguiente →
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align: center; color: #666; font-size: 14px; margin-top: 10px;">
                        Página <?= $pagination['current_page'] ?> de <?= $pagination['total_pages'] ?> 
                        (<?= number_format($pagination['total']) ?> artículos total)
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>