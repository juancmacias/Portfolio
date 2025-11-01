<?php
/**
 * Gestión de Proyectos - Lista
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/image_utils.php';

// Verificar autenticación
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Obtener parámetros de filtrado y paginación
$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Construir query base
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($type)) {
    $whereConditions[] = "project_type = ?";
    $params[] = $type;
}

if (!empty($status)) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

$whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

try {
    $db = Database::getInstance();
    
    // Contar total de proyectos
    $countSQL = "SELECT COUNT(*) as total FROM projects $whereClause";
    $totalProjects = $db->fetchOne($countSQL, $params)['total'];
    $totalPages = ceil($totalProjects / $limit);
    
    // Obtener proyectos de la página actual
    $projectsSQL = "SELECT * FROM projects $whereClause ORDER BY sort_order ASC, created_at DESC LIMIT $limit OFFSET $offset";
    $projects = $db->fetchAll($projectsSQL, $params);
    
} catch (Exception $e) {
    $error = 'Error al cargar proyectos: ' . $e->getMessage();
    $projects = [];
    $totalProjects = 0;
    $totalPages = 0;
}

// Mensajes flash
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proyectos - Admin Portfolio</title>
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
        .btn-small { padding: 6px 12px; font-size: 14px; }
        
        /* Stats Cards */
        .stats-grid, .stats {
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
        .filter-grid, .filters-row {
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
        .form-control, .form-group input, .form-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            width: 100%;
        }
        .form-control:focus, .form-group input:focus, .form-group select:focus {
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
        
        /* Estados y tipos de proyecto */
        .status-badge, .project-type, .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-archived { background: #f8d7da; color: #721c24; }
        .status-maintenance { background: #fff3cd; color: #856404; }
        
        .type-web { background: #e3f2fd; color: #1976d2; }
        .type-app { background: #f3e5f5; color: #7b1fa2; }
        .type-other { background: #e8f5e8; color: #388e3c; }
        
        /* Imagen de proyecto */
        .project-image {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        /* Título y descripción */
        .project-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        .project-description {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #666;
            font-size: 14px;
        }
        
        /* Enlaces de proyecto */
        .project-links a {
            margin-right: 10px;
            color: #007cba;
            text-decoration: none;
            font-size: 16px;
        }
        .project-links a:hover {
            color: #005a87;
        }
        
        /* Acciones */
        .actions-cell {
            white-space: nowrap;
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
            .filter-grid, .filters-row { grid-template-columns: 1fr; }
            .table-container { overflow-x: auto; }
            .table { min-width: 800px; }
            .project-description { max-width: 200px; }
            .stats { grid-template-columns: 1fr 1fr; }
        }
        
        @media (max-width: 480px) {
            .stats { grid-template-columns: 1fr; }
            .container { padding: 15px; }
        }
        
        /* Alertas */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-danger, .alert-error {
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
        <div class="header">
            <h1>🚀 Gestión de Proyectos</h1>
            <div>
                <a href="project-create.php" class="btn btn-primary">➕ Nuevo Proyecto</a>
                <a href="dashboard.php" class="btn btn-secondary">🏠 Dashboard</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <h3><?= $totalProjects ?></h3>
                <p>Total Proyectos</p>
            </div>
            <?php 
            try {
                $webCount = $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE project_type = 'web'")['count'];
                $appCount = $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE project_type = 'app'")['count'];
                $activeCount = $db->fetchOne("SELECT COUNT(*) as count FROM projects WHERE status = 'active'")['count'];
            } catch (Exception $e) {
                $webCount = $appCount = $activeCount = 0;
            }
            ?>
            <div class="stat-card">
                <h3><?= $webCount ?></h3>
                <p>Proyectos Web</p>
            </div>
            <div class="stat-card">
                <h3><?= $appCount ?></h3>
                <p>Apps Móviles</p>
            </div>
            <div class="stat-card">
                <h3><?= $activeCount ?></h3>
                <p>Activos</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <form method="GET" class="filters-row">
                <div class="form-group">
                    <label for="search">🔍 Buscar</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Título o descripción...">
                </div>
                <div class="form-group">
                    <label for="type">📱 Tipo</label>
                    <select id="type" name="type">
                        <option value="">Todos</option>
                        <option value="web" <?= $type === 'web' ? 'selected' : '' ?>>Web</option>
                        <option value="app" <?= $type === 'app' ? 'selected' : '' ?>>App</option>
                        <option value="other" <?= $type === 'other' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">📊 Estado</label>
                    <select id="status" name="status">
                        <option value="">Todos</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Activo</option>
                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archivado</option>
                        <option value="maintenance" <?= $status === 'maintenance' ? 'selected' : '' ?>>Mantenimiento</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="projects.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>

        <!-- Tabla de proyectos -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Proyecto</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Enlaces</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                📭 No se encontraron proyectos
                                <?php if (!empty($search) || !empty($type) || !empty($status)): ?>
                                    <br><a href="projects.php">Limpiar filtros</a>
                                <?php else: ?>
                                    <br><a href="project-create.php">Crear el primer proyecto</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td>
                                    <?php if ($project['image_path']): ?>
                                        <?php $imageUrl = $project['image_path']; ?>
                                        <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                             alt="<?= htmlspecialchars($project['title']) ?>" 
                                             class="project-image"
                                             onerror="this.style.display='none'">
                                    <?php else: ?>
                                        <div class="project-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">
                                            Sin imagen
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="project-title"><?= htmlspecialchars($project['title']) ?></div>
                                    <small style="color: #666;">slug: <?= htmlspecialchars($project['slug']) ?></small>
                                </td>
                                <td>
                                    <div class="project-description" title="<?= htmlspecialchars($project['description']) ?>">
                                        <?= htmlspecialchars($project['description']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="project-type type-<?= $project['project_type'] ?>">
                                        <?= strtoupper($project['project_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status status-<?= $project['status'] ?>">
                                        <?= ucfirst($project['status']) ?>
                                    </span>
                                </td>
                                <td class="project-links">
                                    <?php if ($project['demo_link']): ?>
                                        <a href="<?= htmlspecialchars($project['demo_link']) ?>" target="_blank" title="Ver Demo">🌐</a>
                                    <?php endif; ?>
                                    <?php if ($project['github_link']): ?>
                                        <a href="<?= htmlspecialchars($project['github_link']) ?>" target="_blank" title="Ver Código">⚡</a>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <a href="project-edit.php?id=<?= $project['id'] ?>" class="btn btn-primary btn-small">✏️ Editar</a>
                                    <a href="project-actions.php?action=delete&id=<?= $project['id'] ?>" 
                                       class="btn btn-danger btn-small"
                                       onclick="return confirm('¿Estás seguro de eliminar este proyecto?')">🗑️ Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">← Anterior</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Siguiente →</a>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 10px; color: #666;">
                Página <?= $page ?> de <?= $totalPages ?> (<?= $totalProjects ?> proyectos total)
            </div>
        <?php endif; ?>
    </div>
</body>
</html>