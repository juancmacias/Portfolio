<?php
/**
 * Google Search Console Inspector - Standalone Version
 * Versión independiente sin dependencias de layout
 */

define('ADMIN_ACCESS', true);

// Habilitar errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/auth.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Configuración
$baseUrl = 'https://www.juancarlosmacias.es';
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], 'perfil.in') !== false) {
    $baseUrl = 'http://www.perfil.in';
}

// Procesar acciones
$action = $_GET['action'] ?? 'dashboard';
$result = null;
$error = null;
$inspector = null;

// Intentar cargar la clase del inspector
try {
    if (file_exists(__DIR__ . '/../classes/Providers/GoogleSearchConsoleInspector.php')) {
        require_once __DIR__ . '/../classes/Providers/GoogleSearchConsoleInspector.php';
        $inspector = new GoogleSearchConsoleInspector($baseUrl);
    } else {
        throw new Exception('GoogleSearchConsoleInspector class file not found');
    }
} catch (Exception $e) {
    $error = 'Error al inicializar el Inspector: ' . $e->getMessage();
}

// Solo procesar acciones si el inspector se cargó correctamente
if ($inspector && !$error) {
    try {
        switch ($action) {
            case 'sitemaps':
                $result = [
                    'type' => 'sitemaps',
                    'data' => $inspector->getSitemaps()
                ];
                break;
                
            case 'analytics':
                $days = intval($_GET['days'] ?? 30);
                $endDate = date('Y-m-d', strtotime('-3 days'));
                $startDate = date('Y-m-d', strtotime("-{$days} days"));
                
                $result = [
                    'type' => 'analytics',
                    'data' => $inspector->getSearchAnalytics([
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'dimensions' => ['page'],
                        'rowLimit' => 100
                    ])
                ];
                break;
                
            case 'coverage':
                $result = [
                    'type' => 'coverage',
                    'data' => $inspector->getCoverageAnalysis()
                ];
                break;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspector Google Search Console</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-search"></i> Inspector Google Search Console
            </span>
            <div>
                <a href="dashboard.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-house"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            
            <?php if (strpos($error, 'inicializar') !== false): ?>
            <hr>
            <p class="mb-0"><strong>Posibles soluciones:</strong></p>
            <ul class="mb-0 mt-2">
                <li>Verificar que existe <code>admin/classes/Providers/GoogleSearchConsoleInspector.php</code></li>
                <li>Instalar Google Client Library: <code>composer require google/apiclient:"^2.0"</code></li>
                <li>Verificar que existe <code>admin/config/service-account-credentials.json</code></li>
            </ul>
            <?php endif; ?>
            
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $action === 'dashboard' ? 'active' : '' ?>" href="?action=dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="?action=sitemaps">
                    <i class="bi bi-diagram-3"></i> Sitemaps
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="?action=analytics&days=30">
                    <i class="bi bi-graph-up"></i> Analíticas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="?action=coverage">
                    <i class="bi bi-pie-chart"></i> Cobertura
                </a>
            </li>
        </ul>

        <?php if ($action === 'dashboard'): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Bienvenido al Inspector de Google Search Console</h5>
                        <p>Herramienta para diagnosticar problemas de indexación.</p>
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <i class="bi bi-diagram-3 display-4 text-primary"></i>
                                        <h6 class="mt-2">Sitemaps</h6>
                                        <a href="?action=sitemaps" class="btn btn-sm btn-primary">Ver</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <i class="bi bi-graph-up display-4 text-info"></i>
                                        <h6 class="mt-2">Analíticas</h6>
                                        <a href="?action=analytics&days=30" class="btn btn-sm btn-info">Ver</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <i class="bi bi-pie-chart display-4 text-warning"></i>
                                        <h6 class="mt-2">Cobertura</h6>
                                        <a href="?action=coverage" class="btn btn-sm btn-warning">Analizar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($result && $result['type'] === 'sitemaps'): ?>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Estado de Sitemaps</h5>
                <?php if (empty($result['data'])): ?>
                <div class="alert alert-info">No se encontraron sitemaps.</div>
                <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Sitemap</th>
                            <th>Tipo</th>
                            <th>Último Envío</th>
                            <th>Estado</th>
                            <th>Errores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['data'] as $sitemap): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($sitemap['path']) ?></code></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($sitemap['type'] ?? 'N/A') ?></span></td>
                            <td><?= $sitemap['lastSubmitted'] ? date('Y-m-d H:i', strtotime($sitemap['lastSubmitted'])) : '-' ?></td>
                            <td>
                                <?php if ($sitemap['isPending']): ?>
                                    <span class="badge bg-warning">Pendiente</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Procesado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sitemap['errors'] && $sitemap['errors'] > 0): ?>
                                    <span class="badge bg-danger"><?= $sitemap['errors'] ?></span>
                                <?php else: ?>
                                    <span class="text-success">0</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($result && $result['type'] === 'analytics'): ?>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Analíticas de Búsqueda</h5>
                <div class="alert alert-info">
                    <strong>Período:</strong> <?= $result['data']['startDate'] ?> a <?= $result['data']['endDate'] ?>
                    &nbsp;|&nbsp; 
                    <strong>Total URLs:</strong> <?= $result['data']['totalRows'] ?>
                </div>
                <?php if (empty($result['data']['rows'])): ?>
                <div class="alert alert-warning">No hay datos disponibles.</div>
                <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th class="text-end">Clics</th>
                            <th class="text-end">Impresiones</th>
                            <th class="text-end">CTR</th>
                            <th class="text-end">Posición</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($result['data']['rows'], 0, 50) as $row): ?>
                        <tr>
                            <td><small><code><?= htmlspecialchars($row['keys'][0]) ?></code></small></td>
                            <td class="text-end"><strong><?= number_format($row['clicks']) ?></strong></td>
                            <td class="text-end"><?= number_format($row['impressions']) ?></td>
                            <td class="text-end"><?= number_format($row['ctr'] * 100, 2) ?>%</td>
                            <td class="text-end">
                                <span class="badge <?= $row['position'] <= 10 ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= number_format($row['position'], 1) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($result && $result['type'] === 'coverage'): ?>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Análisis de Cobertura</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h6>Sitemaps Enviados</h6>
                                <h3 class="text-primary"><?= count($result['data']['sitemaps']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h6>Páginas Indexadas</h6>
                                <h3 class="text-success"><?= $result['data']['indexedPagesCount'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
