<?php
/**
 * Google Search Console Inspector
 * Herramienta para diagnosticar problemas de indexación
 */

define('ADMIN_ACCESS', true);

require_once __DIR__ . '/../config/auth.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getUser();

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
                <span class="text-white mx-2">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['name'] ?? $user['username'] ?? 'Admin') ?>
                </span>
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
            <li class="nav-item">
                <a class="nav-link <?= $action === 'indexcoverage' ? 'active' : '' ?>" href="?action=indexcoverage">
                    <i class="bi bi-exclamation-triangle"></i> Index Coverage
                </a>
            </li>
        </ul>

        <?php if ($action === 'dashboard'): ?>
        
        <!-- Alerta de Limitaciones API -->
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="bi bi-info-circle-fill"></i> Información Importante</h5>
            <p><strong>Esta herramienta usa la API de Google Search Console</strong>, que tiene limitaciones:</p>
            <ul class="mb-2">
                <li>✅ <strong>Disponible aquí:</strong> Sitemaps, Analíticas de búsqueda</li>
                <li>❌ <strong>NO disponible aquí:</strong> Index Coverage Report (Soft 404, Descubierta sin indexar, etc.)</li>
            </ul>
            <p class="mb-0">
                Para ver <strong>motivos detallados de no indexación</strong>, usa:
                <a href="https://search.google.com/search-console/index?resource_id=sc-domain:juancarlosmacias.es" target="_blank" class="alert-link">
                    <i class="bi bi-box-arrow-up-right"></i> Search Console Web → Indexación → Páginas
                </a>
            </p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-speedometer2"></i> Bienvenido al Inspector GSC</h5>
                        <p class="text-muted">Herramienta para monitorear sitemaps y analíticas de búsqueda.</p>
                        
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <div class="card border-primary text-center">
                                    <div class="card-body">
                                        <i class="bi bi-diagram-3 display-4 text-primary"></i>
                                        <h6 class="mt-3">Sitemaps</h6>
                                        <p class="small text-muted">Estado de envíos</p>
                                        <a href="?action=sitemaps" class="btn btn-sm btn-primary">
                                            <i class="bi bi-arrow-right"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info text-center">
                                    <div class="card-body">
                                        <i class="bi bi-graph-up display-4 text-info"></i>
                                        <h6 class="mt-3">Analíticas</h6>
                                        <p class="small text-muted">Clics e impresiones</p>
                                        <a href="?action=analytics&days=30" class="btn btn-sm btn-info">
                                            <i class="bi bi-arrow-right"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success text-center">
                                    <div class="card-body">
                                        <i class="bi bi-pie-chart display-4 text-success"></i>
                                        <h6 class="mt-3">Cobertura</h6>
                                        <p class="small text-muted">Análisis básico</p>
                                        <a href="?action=coverage" class="btn btn-sm btn-success">
                                            <i class="bi bi-arrow-right"></i> Analizar
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-danger text-center">
                                    <div class="card-body">
                                        <i class="bi bi-exclamation-triangle display-4 text-danger"></i>
                                        <h6 class="mt-3">Index Coverage</h6>
                                        <p class="small text-muted">Guía para GSC web</p>
                                        <a href="?action=indexcoverage" class="btn btn-sm btn-danger">
                                            <i class="bi bi-arrow-right"></i> Ver Guía
                                        </a>
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bi bi-diagram-3"></i> Estado de Sitemaps</h5>
                    <a href="https://search.google.com/search-console/sitemaps?resource_id=sc-domain:juancarlosmacias.es" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right"></i> Ver en Search Console
                    </a>
                </div>
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bi bi-graph-up"></i> Analíticas de Búsqueda</h5>
                    <a href="https://search.google.com/search-console/performance/search-analytics?resource_id=sc-domain:juancarlosmacias.es" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-box-arrow-up-right"></i> Ver en Search Console
                    </a>
                </div>
                <div class="alert alert-info">
                    <strong>Período:</strong> <?= $result['data']['startDate'] ?> a <?= $result['data']['endDate'] ?>
                    &nbsp;|&nbsp; 
                    <strong>Total URLs:</strong> <?= $result['data']['totalRows'] ?>
                </div>
                <?php if (empty($result['data']['rows'])): ?>
                <div class="alert alert-warning">No hay datos disponibles para este período.</div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="bi bi-info-circle"></i> <strong>Interpretación:</strong>
                    Las URLs con <strong>0 impresiones</strong> pueden tener problemas de indexación.
                    Para ver motivos detallados, usa la pestaña 
                    <a href="?action=indexcoverage" class="alert-link"><strong>Index Coverage</strong></a>.
                </div>
                <div class="mb-3">
                    <label class="form-label">Filtrar resultados:</label>
                    <select class="form-select" onchange="location.href='?action=analytics&days='+this.value">
                        <option value="7" <?= ($_GET['days'] ?? 30) == 7 ? 'selected' : '' ?>>Últimos 7 días</option>
                        <option value="30" <?= ($_GET['days'] ?? 30) == 30 ? 'selected' : '' ?>>Últimos 30 días</option>
                        <option value="90" <?= ($_GET['days'] ?? 30) == 90 ? 'selected' : '' ?>>Últimos 90 días</option>
                    </select>
                </div>
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
                <?php if ($result['data']['totalRows'] > 50): ?>
                <div class="alert alert-info">
                    Mostrando primeras 50 de <?= $result['data']['totalRows'] ?> URLs. 
                    Para ver todas, usa <a href="https://search.google.com/search-console" target="_blank" class="alert-link">Search Console web</a>.
                </div>
                <?php endif; ?>
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

        <?php if ($action === 'indexcoverage'): ?>
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Index Coverage Report - Guía para Search Console Web</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong><i class="bi bi-info-circle-fill"></i> Importante:</strong> 
                    Los motivos detallados de no indexación (Soft 404, Descubierta sin indexar, etc.) <strong>NO están disponibles en la API</strong>.
                    Debes usar la interfaz web de Google Search Console.
                </div>

                <h6 class="mt-4"><i class="bi bi-arrow-right-circle"></i> Paso 1: Accede a Search Console</h6>
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <p>Abre directamente tu propiedad en Search Console:</p>
                        <a href="https://search.google.com/search-console/index?resource_id=sc-domain:juancarlosmacias.es" target="_blank" class="btn btn-primary">
                            <i class="bi bi-box-arrow-up-right"></i> Abrir Search Console → Indexación → Páginas
                        </a>
                    </div>
                </div>

                <h6 class="mt-4"><i class="bi bi-arrow-right-circle"></i> Paso 2: Revisa las Páginas No Indexadas</h6>
                <p>En la sección "¿Por qué hay páginas que no se indexan?", verás:</p>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Motivo</th>
                                <th>Significado</th>
                                <th>Solución</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-warning">
                                <td><strong>Página alternativa con etiqueta canónica adecuada</strong></td>
                                <td>Google detectó que la página tiene una URL canónica diferente (normal en sitios multiidioma o con parámetros)</td>
                                <td>✅ Esto es normal si realmente son páginas duplicadas. Verifica que la canónica apunte a la versión correcta.</td>
                            </tr>
                            <tr class="table-danger">
                                <td><strong>Soft 404</strong></td>
                                <td>Google detecta que la página está vacía o tiene contenido muy escaso</td>
                                <td>❌ <strong>Acción requerida:</strong> Agregar más contenido (mín. 300 palabras) o eliminar la página.</td>
                            </tr>
                            <tr class="table-info">
                                <td><strong>Descubierta: actualmente sin indexar</strong></td>
                                <td>Google encontró la URL pero aún no la ha rastreado</td>
                                <td>⏳ Esperar (puede tardar semanas) o solicitar indexación manual en URL Inspection.</td>
                            </tr>
                            <tr class="table-secondary">
                                <td><strong>Página con redirección</strong></td>
                                <td>La URL redirige a otra (301, 302, etc.)</td>
                                <td>✅ Normal si hiciste redirecciones. Verifica que apunten al destino correcto.</td>
                            </tr>
                            <tr class="table-secondary">
                                <td><strong>Duplicada: Google eligió canónica diferente</strong></td>
                                <td>Google decidió indexar otra versión de la página</td>
                                <td>⚠️ Revisar tag <code>&lt;link rel="canonical"&gt;</code> y asegurar que apunte a la versión deseada.</td>
                            </tr>
                            <tr class="table-secondary">
                                <td><strong>Rastreada: actualmente sin indexar</strong></td>
                                <td>Google rastreó la página pero decidió no indexarla (contenido de baja calidad)</td>
                                <td>❌ Mejorar contenido significativamente o considerar eliminar la página.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="mt-4"><i class="bi bi-arrow-right-circle"></i> Paso 3: Usar URL Inspection Tool</h6>
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <p>Para inspeccionar una URL específica:</p>
                        <ol>
                            <li>En Search Console, busca en la barra superior la URL completa</li>
                            <li>Presiona <kbd>Enter</kbd></li>
                            <li>Verás el estado detallado:
                                <ul>
                                    <li>✅ <strong>URL está en Google:</strong> Indexada correctamente</li>
                                    <li>❌ <strong>URL no está en Google:</strong> Motivo detallado y sugerencias</li>
                                </ul>
                            </li>
                            <li>Botón "Solicitar indexación" para forzar re-rastreo</li>
                        </ol>
                    </div>
                </div>

                <h6 class="mt-4"><i class="bi bi-arrow-right-circle"></i> Paso 4: Validar Correcciones</h6>
                <div class="card bg-light">
                    <div class="card-body">
                        <p>Después de corregir problemas:</p>
                        <ol>
                            <li>En Search Console → Indexación → Páginas</li>
                            <li>Click en el motivo de error (ej: "Soft 404")</li>
                            <li>Botón <strong>"Validar corrección"</strong></li>
                            <li>Google re-rastreará las URLs (puede tardar días)</li>
                            <li>Recibirás email con el resultado de la validación</li>
                        </ol>
                    </div>
                </div>

                <div class="alert alert-success mt-4">
                    <h6><i class="bi bi-lightbulb-fill"></i> Workflow Recomendado</h6>
                    <ol class="mb-0">
                        <li>Usa <strong>esta herramienta</strong> (Inspector GSC) para monitorear sitemaps y analíticas</li>
                        <li>Usa <strong>Search Console web</strong> para diagnóstico detallado de no indexación</li>
                        <li>Corrige problemas en tu sitio</li>
                        <li>Valida correcciones en Search Console</li>
                        <li>Monitorea mejoras en esta herramienta (tab Analíticas)</li>
                    </ol>
                </div>

                <div class="text-center mt-4">
                    <a href="https://search.google.com/search-console" target="_blank" class="btn btn-lg btn-primary">
                        <i class="bi bi-box-arrow-up-right"></i> Ir a Google Search Console
                    </a>
                    <a href="../../../doc/gsc-api-limitaciones.md" class="btn btn-lg btn-outline-secondary ms-2" target="_blank">
                        <i class="bi bi-file-text"></i> Ver Documentación Completa
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
