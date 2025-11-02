<?php
/**
 * ========================================
 * GESTIÓN DE SITEMAP - PANEL ADMIN
 * ========================================
 */

require_once '../includes/config.php';
require_once '../classes/SitemapGenerator.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Gestión de Sitemap';
$currentPage = 'sitemap';

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
    </style>
</head>

<body>
    <?php include '../includes/layouts/base.php'; ?>
    
    <div class="admin-wrapper">
        <?php include '../includes/components/sidebar.php'; ?>
        
        <div class="admin-content">
            <?php include '../includes/components/header.php'; ?>
            
            <div class="content-area">
                <?php include '../includes/components/breadcrumb.php'; ?>
                
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
                            <button id="generateBtn" class="btn btn-light generate-btn">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Generar Sitemap
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

                <div class="result-container">
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

            function showSuccessResult(result) {
                resultContent.innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="bi bi-check-circle me-2"></i>${result.message}</h6>
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
                                <div class="stat-value">${result.execution_time}</div>
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
                
                // Auto-refresh página después de 3 segundos para mostrar nueva info
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
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
        });
    </script>
</body>
</html>