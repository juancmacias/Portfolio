<?php
/**
 * ========================================
 * GESTIÓN DE SITEMAP - PANEL ADMIN
 * ========================================
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/config.local.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once '../classes/SitemapGenerator.php';

$auth = new AdminAuth();

// Verificar autenticación
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getUser();

$pageTitle = 'Gestión de Sitemap';
$currentPage = 'sitemap-manager';

// Obtener información del sitemap actual
$generator = new SitemapGenerator('https://www.juancarlosmacias.es');
$sitemapInfo = $generator->getSitemapInfo();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Portfolio Admin</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/base.css" rel="stylesheet">
    <link href="../assets/css/components.css" rel="stylesheet">
    <link href="../assets/css/layout.css" rel="stylesheet">
    
    <style>
        /* Estilos base del dashboard */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: white; padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; text-decoration: none; color: white; background: #007bff; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .row { display: flex; flex-wrap: wrap; margin: -10px; }
        .col-md-8 { flex: 0 0 66.666667%; padding: 10px; }
        .col-md-4 { flex: 0 0 33.333333%; padding: 10px; }
        .col-lg-8 { flex: 0 0 66.666667%; padding: 10px; }
        .col-lg-4 { flex: 0 0 33.333333%; padding: 10px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { padding: 15px 20px; border-bottom: 1px solid #ddd; font-weight: 600; }
        .card-body { padding: 20px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .mb-0 { margin-bottom: 0; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 16px; }
        .mb-4 { margin-bottom: 24px; }
        .mt-3 { margin-top: 16px; }
        .mt-4 { margin-top: 24px; }
        .mt-5 { margin-top: 32px; }
        .opacity-75 { opacity: 0.75; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; }
        .bg-success { background-color: #28a745; color: white; }
        .bg-warning { background-color: #ffc107; color: black; }
        .text-success { color: #28a745; }
        .text-warning { color: #ffc107; }
        .text-muted { color: #6c757d; }
        .small { font-size: 0.875rem; }
        
        .sitemap-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #007bff;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .stat-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        
        .generate-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .notify-btn {
            border-radius: 25px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #007bff;
        }
        
        .notify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            background: #007bff;
            color: white;
        }
        
        .progress-container {
            display: none;
            margin-top: 1rem;
        }
        
        .result-container {
            display: none;
            margin-top: 1rem;
        }
        
        .url-list {
            max-height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .url-item {
            padding: 0.5rem;
            border-bottom: 1px solid #dee2e6;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .url-item:last-child {
            border-bottom: none;
        }
        
        .current-urls-container {
            margin-bottom: 2rem;
        }
        
        .url-info a {
            color: #0d6efd;
            word-break: break-all;
        }
        
        .url-info a:hover {
            color: #0a58ca;
        }
        
        .url-meta {
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .url-item {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
            background: white;
            margin-bottom: 0.25rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .url-item:hover {
            background: #f8f9fa;
        }
        
        .notification-results {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .notification-item {
            padding: 0.5rem;
            border-bottom: 1px solid #dee2e6;
            background: white;
            margin-bottom: 0.25rem;
            border-radius: 4px;
        }
        
        .notification-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .status-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .spinner-border {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            border: 0.25em solid currentcolor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
        
        @keyframes spinner-border {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.375rem;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .progress {
            height: 1rem;
            background-color: #e9ecef;
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background-color: #007bff;
            transition: width 0.6s ease;
        }
        
        .progress-bar-striped {
            background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
            background-size: 1rem 1rem;
        }
        
        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }
        
        @keyframes progress-bar-stripes {
            0% { background-position-x: 1rem; }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🗺️ Generador de Sitemap</h1>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($user['name'] ?? $user['username']); ?></span>
            <a href="dashboard.php" class="btn">📊 Dashboard</a>
            <a href="logout.php" class="btn btn-danger">🚪 Salir</a>
        </div>
    </div>

    <div class="container">
        <!-- Header Principal -->
        <div class="sitemap-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-diagram-3"></i>
                        Generador de Sitemap
                    </h1>
                    <p class="mb-0 opacity-75">
                        Genera automáticamente el sitemap.xml analizando la estructura completa de tu sitio web
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <button id="generateBtn" class="btn btn-light generate-btn me-2">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Generar Sitemap
                    </button>
                    <button id="notifyBtn" class="btn btn-outline-primary notify-btn">
                        <i class="bi bi-megaphone me-2"></i>
                        Notificar Buscadores
                    </button>
                </div>
            </div>
        </div>

                <!-- Información del sitemap actual -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Estado del Sitemap Actual
                                </h5>
                                <?php if ($sitemapInfo['exists']): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        No existe
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if ($sitemapInfo['exists']): ?>
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <div class="stat-value"><?= $sitemapInfo['url_count'] ?></div>
                                            <div class="stat-label">URLs incluidas</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value"><?= $sitemapInfo['size'] ?></div>
                                            <div class="stat-label">Tamaño del archivo</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value">
                                                <?php if ($sitemapInfo['is_recent']): ?>
                                                    <i class="bi bi-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-clock text-warning"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="stat-label">
                                                <?= $sitemapInfo['is_recent'] ? 'Actualizado' : 'Desactualizado' ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="info-card">
                                        <h6><i class="bi bi-calendar3 me-2"></i>Última actualización</h6>
                                        <p class="mb-0"><?= $sitemapInfo['last_modified'] ?></p>
                                    </div>
                                    
                                    <div class="info-card">
                                        <h6><i class="bi bi-folder2 me-2"></i>Ubicación del archivo</h6>
                                        <p class="mb-0">
                                            <code><?= htmlspecialchars($sitemapInfo['path']) ?></code>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="mt-3 text-muted">No existe sitemap.xml</h5>
                                        <p class="text-muted">Genera tu primer sitemap para mejorar el SEO de tu sitio</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    ¿Qué es un Sitemap?
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted">
                                    Un sitemap.xml es un archivo que lista todas las páginas importantes de tu sitio web, 
                                    ayudando a los motores de búsqueda como Google a encontrar e indexar tu contenido.
                                </p>
                                
                                <h6 class="mt-3">Beneficios:</h6>
                                <ul class="small text-muted">
                                    <li>Mejora el SEO del sitio</li>
                                    <li>Indexación más rápida</li>
                                    <li>Mejor visibilidad en buscadores</li>
                                    <li>Análisis de estructura del sitio</li>
                                </ul>
                                
                                <div class="mt-3 p-2 bg-info bg-opacity-10 rounded">
                                    <small class="text-info">
                                        <i class="bi bi-info-circle me-1"></i>
                                        El sitemap se genera automáticamente analizando https://www.juancarlosmacias.es
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progreso y resultados -->
                <div class="progress-container">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Generando...</span>
                            </div>
                            <h5 class="mt-3">Generando Sitemap</h5>
                            <p class="text-muted">Analizando estructura del sitio web...</p>
                            <div class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de URLs actuales del sitemap -->
                <div class="current-urls-container">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-list-ul me-2"></i>
                                URLs en el Sitemap Actual
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="current-urls-content">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm me-2"></div>
                                    Cargando URLs del sitemap...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="result-container";
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                Resultado de la Generación
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="resultContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/utils.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const generateBtn = document.getElementById('generateBtn');
            const progressContainer = document.querySelector('.progress-container');
            const resultContainer = document.querySelector('.result-container');
            const resultContent = document.getElementById('resultContent');

            generateBtn.addEventListener('click', async function() {
                // Deshabilitar botón y mostrar progreso
                generateBtn.disabled = true;
                generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generando...';
                progressContainer.style.display = 'block';
                resultContainer.style.display = 'none';

                try {
                    const response = await fetch('../api/sitemap-generator.php?action=generate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });

                    const result = await response.json();

                    // Ocultar progreso
                    progressContainer.style.display = 'none';

                    if (result.success) {
                        showSuccessResult(result);
                    } else {
                        showErrorResult(result.message);
                    }

                } catch (error) {
                    progressContainer.style.display = 'none';
                    showErrorResult('Error de conexión: ' + error.message);
                } finally {
                    // Rehabilitar botón
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Generar Sitemap';
                }
            });

            // Evento para notificar buscadores
            const notifyBtn = document.getElementById('notifyBtn');
            notifyBtn.addEventListener('click', async function() {
                // Verificar que existe un sitemap
                const sitemapExists = document.querySelector('.current-urls-container .url-list');
                if (!sitemapExists || sitemapExists.children.length === 0) {
                    showErrorResult('Primero debes generar un sitemap antes de notificar a los buscadores');
                    return;
                }

                // Deshabilitar botón y mostrar estado
                notifyBtn.disabled = true;
                notifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Notificando...';

                try {
                    const response = await fetch('../api/sitemap-generator.php?action=notify', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        showNotificationResult(result);
                    } else {
                        showErrorResult(result.error || result.message || 'Error desconocido');
                    }

                } catch (error) {
                    showErrorResult('Error de conexión: ' + error.message);
                } finally {
                    // Rehabilitar botón
                    notifyBtn.disabled = false;
                    notifyBtn.innerHTML = '<i class="bi bi-megaphone me-2"></i>Notificar Buscadores';
                }
            });

            function showSuccessResult(result) {
                // Verificar si es entorno local o producción
                const isLocal = result.environment === 'local';
                const alertClass = isLocal ? 'alert-info' : 'alert-success';
                const icon = isLocal ? 'bi-info-circle' : 'bi-check-circle';
                
                let environmentNote = '';
                if (isLocal) {
                    environmentNote = `
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Entorno de desarrollo:</strong> Este sitemap fue generado con URLs de ejemplo 
                            ya que no es posible acceder al sitio de producción desde el entorno local.
                        </div>
                    `;
                }
                
                resultContent.innerHTML = `
                    <div class="alert ${alertClass}">
                        <h6><i class="${icon} me-2"></i>${result.message}</h6>
                        ${result.note ? `<small class="text-muted">${result.note}</small>` : ''}
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value">${result.urls_found}</div>
                                <div class="stat-label">URLs encontradas</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value">${result.file_size}</div>
                                <div class="stat-label">Tamaño generado</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value">${result.execution_time || 'N/A'}</div>
                                <div class="stat-label">Tiempo de ejecución</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-value">${result.generated_at}</div>
                                <div class="stat-label">Generado</div>
                            </div>
                        </div>
                    </div>
                    
                    ${environmentNote}
                    
                    ${result.urls && result.urls.length > 0 ? `
                        <h6 class="mt-4">URLs incluidas en el sitemap:</h6>
                        <div class="url-list">
                            ${result.urls.map(url => `
                                <div class="url-item">
                                    <i class="bi bi-link-45deg me-2 text-primary"></i>
                                    ${url.url}
                                    <small class="text-muted ms-2">(${url.changefreq}, priority: ${url.priority})</small>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                `;
                
                resultContainer.style.display = 'block';
                
                // Recargar URLs actuales después de generar
                setTimeout(() => {
                    loadCurrentSitemapUrls();
                }, 1000);
            }

            function showErrorResult(message) {
                resultContent.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Error</h6>
                        <p class="mb-0">${message}</p>
                    </div>
                `;
                resultContainer.style.display = 'block';
            }

            function showNotificationResult(result) {
                const isLocal = result.environment === 'local';
                const alertClass = isLocal ? 'alert-info' : 'alert-success';
                const icon = isLocal ? 'bi-info-circle' : 'bi-megaphone';
                
                let resultsHtml = '';
                if (result.results) {
                    resultsHtml = `
                        <h6 class="mt-4 mb-3">
                            <i class="bi bi-search me-2"></i>Resultados por buscador:
                        </h6>
                        <div class="notification-results">
                    `;
                    
                    for (const [engine, engineResult] of Object.entries(result.results)) {
                        const statusIcon = engineResult.success ? 'bi-check-circle text-success' : 'bi-x-circle text-danger';
                        resultsHtml += `
                            <div class="notification-item d-flex justify-content-between align-items-center">
                                <div class="engine-info">
                                    <i class="${statusIcon} me-2"></i>
                                    <strong>${engine}</strong>
                                </div>
                                <div class="status-info">
                                    <span class="badge ${engineResult.success ? 'bg-success' : 'bg-danger'}">
                                        ${engineResult.status_code}
                                    </span>
                                    <small class="text-muted ms-2">${engineResult.message}</small>
                                </div>
                            </div>
                        `;
                    }
                    resultsHtml += '</div>';
                }

                let environmentNote = '';
                if (isLocal) {
                    environmentNote = `
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Entorno de desarrollo:</strong> ${result.note || 'Las notificaciones han sido simuladas para pruebas.'}
                        </div>
                    `;
                }

                resultContent.innerHTML = `
                    <div class="alert ${alertClass}">
                        <h6><i class="${icon} me-2"></i>${result.message}</h6>
                        <p class="mb-0">
                            <small>Sitemap: <code>${result.sitemap_url}</code></small>
                        </p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stat-item">
                                <div class="stat-value">${result.successful_notifications || 0}</div>
                                <div class="stat-label">Notificaciones exitosas</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-item">
                                <div class="stat-value">${result.total_engines || 0}</div>
                                <div class="stat-label">Buscadores contactados</div>
                            </div>
                        </div>
                    </div>
                    
                    ${environmentNote}
                    ${resultsHtml}
                `;
                
                resultContainer.style.display = 'block';
            }

            // Cargar URLs del sitemap actual al cargar la página
            loadCurrentSitemapUrls();

            function loadCurrentSitemapUrls() {
                const currentUrlsContent = document.getElementById('current-urls-content');
                
                fetch('../api/sitemap-generator.php?action=read')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.exists) {
                            displayCurrentUrls(data);
                        } else {
                            currentUrlsContent.innerHTML = `
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-file-earmark-x" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">No existe sitemap.xml</p>
                                    <small>Genere uno para ver las URLs incluidas</small>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        currentUrlsContent.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Error cargando URLs: ${error.message}
                            </div>
                        `;
                    });
            }

            function displayCurrentUrls(data) {
                const currentUrlsContent = document.getElementById('current-urls-content');
                
                let urlsHtml = `
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="stat-item">
                                <div class="stat-value">${data.total_urls}</div>
                                <div class="stat-label">URLs totales</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-item">
                                <div class="stat-value">${data.file_size}</div>
                                <div class="stat-label">Tamaño del archivo</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-item">
                                <div class="stat-value">${data.last_modified}</div>
                                <div class="stat-label">Última modificación</div>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mt-4 mb-3">
                        <i class="bi bi-link-45deg me-2"></i>URLs incluidas:
                    </h6>
                    <div class="url-list">
                `;
                
                data.urls.forEach(url => {
                    const priorityClass = getPriorityClass(url.priority);
                    const changefreqBadge = getChangefreqBadge(url.changefreq);
                    
                    urlsHtml += `
                        <div class="url-item d-flex justify-content-between align-items-center">
                            <div class="url-info">
                                <i class="bi bi-link-45deg me-2 text-primary"></i>
                                <a href="${url.loc}" target="_blank" class="text-decoration-none">
                                    ${url.loc}
                                </a>
                            </div>
                            <div class="url-meta">
                                ${changefreqBadge}
                                <span class="badge ${priorityClass} ms-1">
                                    Priority: ${url.priority}
                                </span>
                                <small class="text-muted ms-2">${url.lastmod}</small>
                            </div>
                        </div>
                    `;
                });
                
                urlsHtml += '</div>';
                currentUrlsContent.innerHTML = urlsHtml;
            }

            function getPriorityClass(priority) {
                const p = parseFloat(priority);
                if (p >= 1.0) return 'bg-success';
                if (p >= 0.8) return 'bg-info';
                if (p >= 0.6) return 'bg-warning';
                return 'bg-secondary';
            }

            function getChangefreqBadge(changefreq) {
                const badges = {
                    'always': 'bg-danger',
                    'hourly': 'bg-warning',
                    'daily': 'bg-info',
                    'weekly': 'bg-primary',
                    'monthly': 'bg-success',
                    'yearly': 'bg-secondary',
                    'never': 'bg-dark'
                };
                
                const badgeClass = badges[changefreq] || 'bg-secondary';
                return `<span class="badge ${badgeClass}">${changefreq}</span>`;
            }
        });
    </script>
</body>
</html>