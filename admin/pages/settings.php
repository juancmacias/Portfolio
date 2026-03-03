<?php
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación
$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$success = null;
$error = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'save_general':
                $configs = [
                    'site_name' => $_POST['site_name'] ?? '',
                    'site_description' => $_POST['site_description'] ?? '',
                    'articles_per_page' => (int)($_POST['articles_per_page'] ?? 10),
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? 'true' : 'false',
                    'allow_comments' => isset($_POST['allow_comments']) ? 'true' : 'false',
                    'google_analytics_id' => $_POST['google_analytics_id'] ?? ''
                ];
                
                foreach ($configs as $key => $value) {
                    $sql = "INSERT INTO system_config (config_key, config_value) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)";
                    $db->query($sql, [$key, $value]);
                }
                
                $success = "Configuración general guardada exitosamente";
                break;
                
            case 'save_ai':
                $aiConfigs = [
                    'ai_default_provider' => $_POST['ai_default_provider'] ?? 'groq',
                    'ai_max_tokens' => (int)($_POST['ai_max_tokens'] ?? 2000),
                    'groq_api_key' => $_POST['groq_api_key'] ?? '',
                    'openai_api_key' => $_POST['openai_api_key'] ?? '',
                    'huggingface_api_key' => $_POST['huggingface_api_key'] ?? ''
                ];
                
                foreach ($aiConfigs as $key => $value) {
                    $sql = "INSERT INTO system_config (config_key, config_value) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)";
                    $db->query($sql, [$key, $value]);
                }
                
                $success = "Configuración de IA guardada exitosamente";
                break;
                
            case 'test_ai':
                $provider = $_POST['test_provider'] ?? 'groq';
                
                // Implementar test básico
                require_once __DIR__ . '/../classes/AIProviders.php';
                require_once __DIR__ . '/../classes/AIContentGenerator.php';
                
                $aiGenerator = new AIContentGenerator();
                $result = $aiGenerator->generateContent('Escribe un saludo breve de prueba', 'test', $provider);
                
                if ($result['success']) {
                    $success = "✅ Test de {$provider} exitoso: " . substr($result['content'], 0, 100) . "...";
                } else {
                    $error = "❌ Test de {$provider} falló: " . $result['error'];
                }
                break;

            case 'save_seo':
                $sameAsRaw   = $_POST['seo_business_sameas'] ?? '';
                $sameAsLines = preg_split('/\r\n|\r|\n/', $sameAsRaw);
                $sameAs      = [];

                foreach ($sameAsLines as $line) {
                    $url = trim($line);
                    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                        $sameAs[] = $url;
                    }
                }

                // Validaciones básicas
                $requiredFields = ['seo_business_name', 'seo_business_phone', 'seo_business_email'];
                foreach ($requiredFields as $field) {
                    if (empty(trim($_POST[$field] ?? ''))) {
                        throw new Exception('Los campos nombre, teléfono y email son obligatorios');
                    }
                }

                // Validar email
                if (!filter_var(trim($_POST['seo_business_email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('El formato del email no es válido');
                }

                // Sanear coordenadas geo
                $geoLat = (float)($_POST['seo_business_geo_lat'] ?? 40.3861);
                $geoLng = (float)($_POST['seo_business_geo_lng'] ?? -3.7161);

                // Guardar Google Analytics en system_config (separado del SEO de negocio)
                $gaId = trim($_POST['google_analytics_id'] ?? '');
                $db->query(
                    "INSERT INTO system_config (config_key, config_value) VALUES ('google_analytics_id', ?)
                     ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)",
                    [$gaId]
                );

                // UPSERT en tabla dedicada seo_business (siempre fila id=1)
                $sameAsJson = json_encode(
                    array_values(array_unique($sameAs)),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );

                $db->query(
                    "INSERT INTO seo_business
                        (id, name, description, service_type, price_range,
                         phone, email,
                         street_address, address_locality, address_region, postal_code, address_country,
                         geo_latitude, geo_longitude, same_as)
                     VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                         name             = VALUES(name),
                         description      = VALUES(description),
                         service_type     = VALUES(service_type),
                         price_range      = VALUES(price_range),
                         phone            = VALUES(phone),
                         email            = VALUES(email),
                         street_address   = VALUES(street_address),
                         address_locality = VALUES(address_locality),
                         address_region   = VALUES(address_region),
                         postal_code      = VALUES(postal_code),
                         address_country  = VALUES(address_country),
                         geo_latitude     = VALUES(geo_latitude),
                         geo_longitude    = VALUES(geo_longitude),
                         same_as          = VALUES(same_as)",
                    [
                        trim(strip_tags($_POST['seo_business_name']          ?? '')),
                        trim(strip_tags($_POST['seo_business_description']   ?? '')),
                        trim(strip_tags($_POST['seo_business_serviceType']   ?? '')),
                        trim($_POST['seo_business_priceRange']               ?? ''),
                        trim($_POST['seo_business_phone']                    ?? ''),
                        trim($_POST['seo_business_email']                    ?? ''),
                        trim(strip_tags($_POST['seo_business_streetAddress'] ?? '')),
                        trim(strip_tags($_POST['seo_business_addressLocality'] ?? '')),
                        trim(strip_tags($_POST['seo_business_addressRegion']   ?? '')),
                        trim($_POST['seo_business_postalCode']               ?? ''),
                        strtoupper(substr(trim($_POST['seo_business_addressCountry'] ?? 'ES'), 0, 2)),
                        $geoLat,
                        $geoLng,
                        $sameAsJson
                    ]
                );

                $success = "Configuración SEO guardada exitosamente";
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener configuraciones actuales
try {
    $configs = [];
    $configRows = $db->fetchAll("SELECT config_key, config_value FROM system_config");
    foreach ($configRows as $row) {
        $configs[$row['config_key']] = $row['config_value'];
    }
} catch (Exception $e) {
    $configs = [];
    $error = "Error al cargar configuración: " . $e->getMessage();
}

$defaultSeoBusiness = [
    'name'            => 'JCMS - Soluciones Full Stack con IA Generativa',
    'phone'           => '+34618309775',
    'email'           => 'juancmaciassalvador@gmail.com',
    'description'     => 'Desarrollador Full Stack especializado en React, PHP y soluciones con Inteligencia Artificial Generativa.',
    'serviceType'     => 'Full Stack Development',
    'priceRange'      => '€€',
    'streetAddress'   => 'Calle de Padre Oltra',
    'addressLocality' => 'Madrid',
    'addressRegion'   => 'Comunidad de Madrid',
    'postalCode'      => '28019',
    'addressCountry'  => 'ES',
    'geo'             => ['latitude' => 40.3861, 'longitude' => -3.7161],
    'sameAs' => [
        'https://www.linkedin.com/in/juancarlosmacias/',
        'https://github.com/juancmacias',
        'https://maps.app.goo.gl/eb43KR6oPFGrNgAn9',
        'https://play.google.com/store/apps/dev?id=7098282899285176966',
        'https://www.instagram.com/jcms_madrid/'
    ]
];

$currentSeoBusiness = $defaultSeoBusiness;
try {
    $seoRow = $db->fetchOne(
        "SELECT name, description, service_type, price_range,
                phone, email,
                street_address, address_locality, address_region, postal_code, address_country,
                geo_latitude, geo_longitude, same_as
         FROM seo_business
         WHERE is_active = 1
         ORDER BY id ASC
         LIMIT 1"
    );
    if ($seoRow && !empty($seoRow['name'])) {
        $decodedSameAs = json_decode($seoRow['same_as'] ?? '[]', true);
        $currentSeoBusiness = array_merge($defaultSeoBusiness, [
            'name'            => $seoRow['name'],
            'description'     => $seoRow['description']      ?? $defaultSeoBusiness['description'],
            'serviceType'     => $seoRow['service_type']     ?? $defaultSeoBusiness['serviceType'],
            'priceRange'      => $seoRow['price_range']      ?? $defaultSeoBusiness['priceRange'],
            'phone'           => $seoRow['phone'],
            'email'           => $seoRow['email'],
            'streetAddress'   => $seoRow['street_address']   ?? $defaultSeoBusiness['streetAddress'],
            'addressLocality' => $seoRow['address_locality'] ?? $defaultSeoBusiness['addressLocality'],
            'addressRegion'   => $seoRow['address_region']   ?? $defaultSeoBusiness['addressRegion'],
            'postalCode'      => $seoRow['postal_code']      ?? $defaultSeoBusiness['postalCode'],
            'addressCountry'  => $seoRow['address_country']  ?? $defaultSeoBusiness['addressCountry'],
            'geo'             => [
                'latitude'  => (float)($seoRow['geo_latitude']  ?? 40.3861),
                'longitude' => (float)($seoRow['geo_longitude'] ?? -3.7161)
            ],
            'sameAs' => is_array($decodedSameAs) && !empty($decodedSameAs)
                            ? $decodedSameAs
                            : $defaultSeoBusiness['sameAs']
        ]);
    }
} catch (Exception $e) {
    // Tabla aún no creada o error — se usa el fallback por defecto
    error_log('settings.php: no se pudo leer seo_business — ' . $e->getMessage());
}

$sameAsText = implode("\n", $currentSeoBusiness['sameAs']);
$currentGeo = $currentSeoBusiness['geo'] ?? ['latitude' => 40.3861, 'longitude' => -3.7161];

$pageTitle = "Configuración del Sistema";
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
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        
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
        
        /* Tabs */
        .tabs {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .tab-headers {
            display: flex;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tab-header {
            padding: 15px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            flex: 1;
            text-align: center;
        }
        
        .tab-header.active {
            color: #007cba;
            border-bottom-color: #007cba;
        }
        
        .tab-header:hover {
            background: #f8f9fa;
        }
        
        .tab-content {
            padding: 25px;
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Formularios */
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1);
        }
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
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
        
        /* Test section */
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .test-section h4 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .test-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* API Keys styling */
        .api-key-input {
            font-family: monospace;
            font-size: 13px;
        }
        
        .show-password {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>⚙️ <?= $pageTitle ?></h1>
            <div class="header-actions">
                <a href="dashboard.php" class="btn btn-outline">
                    🏠 Dashboard
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="tabs">
            <div class="tab-headers">
                <button class="tab-header active" onclick="switchTab('general')">
                    🌐 General
                </button>
                <button class="tab-header" onclick="switchTab('ai')">
                    🤖 Inteligencia Artificial
                </button>
                <button class="tab-header" onclick="switchTab('seo')">
                    🔍 SEO
                </button>
                <button class="tab-header" onclick="switchTab('security')">
                    🔒 Seguridad
                </button>
            </div>
            
            <!-- Tab General -->
            <div id="tab-general" class="tab-content active">
                <form method="POST">
                    <input type="hidden" name="action" value="save_general">
                    
                    <div class="form-section">
                        <h3>Información del Sitio</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="site_name">Nombre del Sitio</label>
                                <input type="text" id="site_name" name="site_name" class="form-control" 
                                       value="<?= htmlspecialchars($configs['site_name'] ?? 'Portfolio Juan Carlos Macías') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="articles_per_page">Artículos por Página</label>
                                <input type="number" id="articles_per_page" name="articles_per_page" 
                                       class="form-control" min="5" max="50"
                                       value="<?= htmlspecialchars($configs['articles_per_page'] ?? '10') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_description">Descripción del Sitio</label>
                            <textarea id="site_description" name="site_description" class="form-control" rows="3"><?= htmlspecialchars($configs['site_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Opciones</h3>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                       <?= ($configs['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                <label for="maintenance_mode">Modo Mantenimiento</label>
                            </div>
                            <div class="form-text">Activar para mostrar página de mantenimiento a visitantes</div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="allow_comments" name="allow_comments" 
                                       <?= ($configs['allow_comments'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                <label for="allow_comments">Permitir Comentarios</label>
                            </div>
                            <div class="form-text">Habilitar sistema de comentarios en artículos públicos</div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">💾 Guardar Configuración General</button>
                </form>
            </div>
            
            <!-- Tab IA -->
            <div id="tab-ai" class="tab-content">
                <form method="POST">
                    <input type="hidden" name="action" value="save_ai">
                    
                    <div class="form-section">
                        <h3>Configuración de IA</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ai_default_provider">Proveedor por Defecto</label>
                                <select id="ai_default_provider" name="ai_default_provider" class="form-control">
                                    <option value="groq" <?= ($configs['ai_default_provider'] ?? 'groq') === 'groq' ? 'selected' : '' ?>>Groq</option>
                                    <option value="openai" <?= ($configs['ai_default_provider'] ?? '') === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                                    <option value="huggingface" <?= ($configs['ai_default_provider'] ?? '') === 'huggingface' ? 'selected' : '' ?>>HuggingFace</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="ai_max_tokens">Máximo de Tokens</label>
                                <input type="number" id="ai_max_tokens" name="ai_max_tokens" 
                                       class="form-control" min="100" max="8000"
                                       value="<?= htmlspecialchars($configs['ai_max_tokens'] ?? '2000') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>API Keys</h3>
                        
                        <div class="form-group">
                            <label for="groq_api_key">Groq API Key</label>
                            <div class="show-password">
                                <input type="password" id="groq_api_key" name="groq_api_key" 
                                       class="form-control api-key-input" 
                                       value="<?= htmlspecialchars($configs['groq_api_key'] ?? '') ?>"
                                       placeholder="gsk_...">
                                <button type="button" class="toggle-password" onclick="togglePassword('groq_api_key')">👁️</button>
                            </div>
                            <div class="form-text">Obten tu API key en <a href="https://console.groq.com" target="_blank">console.groq.com</a></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="openai_api_key">OpenAI API Key</label>
                            <div class="show-password">
                                <input type="password" id="openai_api_key" name="openai_api_key" 
                                       class="form-control api-key-input" 
                                       value="<?= htmlspecialchars($configs['openai_api_key'] ?? '') ?>"
                                       placeholder="sk-...">
                                <button type="button" class="toggle-password" onclick="togglePassword('openai_api_key')">👁️</button>
                            </div>
                            <div class="form-text">Obten tu API key en <a href="https://platform.openai.com" target="_blank">platform.openai.com</a></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="huggingface_api_key">HuggingFace API Key</label>
                            <div class="show-password">
                                <input type="password" id="huggingface_api_key" name="huggingface_api_key" 
                                       class="form-control api-key-input" 
                                       value="<?= htmlspecialchars($configs['huggingface_api_key'] ?? '') ?>"
                                       placeholder="hf_...">
                                <button type="button" class="toggle-password" onclick="togglePassword('huggingface_api_key')">👁️</button>
                            </div>
                            <div class="form-text">Obten tu API key en <a href="https://huggingface.co/settings/tokens" target="_blank">huggingface.co</a></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">💾 Guardar Configuración de IA</button>
                    
                    <!-- Test Section -->
                    <div class="test-section">
                        <h4>🧪 Probar Conexiones</h4>
                        <p>Prueba las conexiones con los proveedores de IA configurados:</p>
                        
                        <div class="test-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="test_ai">
                                <input type="hidden" name="test_provider" value="groq">
                                <button type="submit" class="btn btn-outline">🔬 Test Groq</button>
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="test_ai">
                                <input type="hidden" name="test_provider" value="openai">
                                <button type="submit" class="btn btn-outline">🔬 Test OpenAI</button>
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="test_ai">
                                <input type="hidden" name="test_provider" value="huggingface">
                                <button type="submit" class="btn btn-outline">🔬 Test HuggingFace</button>
                            </form>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tab SEO -->
            <div id="tab-seo" class="tab-content">
                <form method="POST">
                    <input type="hidden" name="action" value="save_seo">

                    <div class="form-section">
                        <h3>🔍 Configuración SEO</h3>

                        <div class="form-group">
                            <label for="google_analytics_id">Google Analytics ID</label>
                            <input type="text" id="google_analytics_id" name="google_analytics_id"
                                   class="form-control"
                                   value="<?= htmlspecialchars($configs['google_analytics_id'] ?? '') ?>"
                                   placeholder="G-XXXXXXXXXX">
                            <div class="form-text">ID de medición de Google Analytics 4</div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>🏢 Business SEO (JSON-LD)</h3>

                        <!-- Identidad -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="seo_business_name">Nombre comercial <span style="color:red">*</span></label>
                                <input type="text" id="seo_business_name" name="seo_business_name" class="form-control"
                                       value="<?= htmlspecialchars($currentSeoBusiness['name']) ?>" required>
                                <div class="form-text">Debe coincidir exactamente con Google My Business.</div>
                            </div>

                            <div class="form-group">
                                <label for="seo_business_serviceType">Tipo de servicio</label>
                                <input type="text" id="seo_business_serviceType" name="seo_business_serviceType" class="form-control"
                                       value="<?= htmlspecialchars($currentSeoBusiness['serviceType'] ?? '') ?>"
                                       placeholder="Full Stack Development">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="seo_business_description">Descripción del negocio</label>
                            <textarea id="seo_business_description" name="seo_business_description" class="form-control" rows="2"
                                      placeholder="Descripción breve para schema.org ProfessionalService..."><?= htmlspecialchars($currentSeoBusiness['description'] ?? '') ?></textarea>
                            <div class="form-text">Aparece en el JSON-LD de la página de contacto.</div>
                        </div>

                        <!-- Contacto -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="seo_business_phone">Teléfono (E.164) <span style="color:red">*</span></label>
                                <input type="text" id="seo_business_phone" name="seo_business_phone" class="form-control"
                                       value="<?= htmlspecialchars($currentSeoBusiness['phone']) ?>" placeholder="+34600111222" required>
                            </div>

                            <div class="form-group">
                                <label for="seo_business_email">Email <span style="color:red">*</span></label>
                                <input type="email" id="seo_business_email" name="seo_business_email" class="form-control"
                                       value="<?= htmlspecialchars($currentSeoBusiness['email']) ?>" required>
                            </div>
                        </div>

                        <!-- Dirección -->
                        <div class="form-group">
                            <label for="seo_business_streetAddress">Dirección</label>
                            <input type="text" id="seo_business_streetAddress" name="seo_business_streetAddress" class="form-control"
                                   value="<?= htmlspecialchars($currentSeoBusiness['streetAddress']) ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="seo_business_addressLocality">Ciudad</label>
                                <input type="text" id="seo_business_addressLocality" name="seo_business_addressLocality" class="form-control"
                                       value="<?= htmlspecialchars($currentSeoBusiness['addressLocality']) ?>">
                            </div>

                            <div class="form-group">
                                <label for="seo_business_addressRegion">Región / Comunidad</label>
                                <input type="text" id="seo_business_addressRegion" name="seo_business_addressRegion" class="form-control"
                                       value="<?= htmlspecialchars($currentSeoBusiness['addressRegion']) ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="seo_business_postalCode">Código postal</label>
                                <input type="text" id="seo_business_postalCode" name="seo_business_postalCode" class="form-control"
                                       value="<?= htmlspecialchars($currentSeoBusiness['postalCode']) ?>">
                            </div>

                            <div class="form-group">
                                <label for="seo_business_addressCountry">País (ISO 3166-1 α-2)</label>
                                <input type="text" id="seo_business_addressCountry" name="seo_business_addressCountry" class="form-control"
                                       value="<?= htmlspecialchars($currentSeoBusiness['addressCountry']) ?>" maxlength="2"
                                       placeholder="ES" style="text-transform:uppercase">
                            </div>

                            <div class="form-group">
                                <label for="seo_business_priceRange">Rango de precio</label>
                                <input type="text" id="seo_business_priceRange" name="seo_business_priceRange" class="form-control"
                                       value="<?= htmlspecialchars($currentSeoBusiness['priceRange'] ?? '') ?>"
                                       placeholder="€€" maxlength="5">
                                <div class="form-text">Usa €, €€ o €€€ (Schema.org priceRange).</div>
                            </div>
                        </div>

                        <!-- Coordenadas -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="seo_business_geo_lat">Latitud</label>
                                <input type="number" step="0.0001" id="seo_business_geo_lat" name="seo_business_geo_lat" class="form-control"
                                       value="<?= htmlspecialchars((string)($currentGeo['latitude'] ?? '')) ?>"
                                       placeholder="40.3861">
                            </div>

                            <div class="form-group">
                                <label for="seo_business_geo_lng">Longitud</label>
                                <input type="number" step="0.0001" id="seo_business_geo_lng" name="seo_business_geo_lng" class="form-control"
                                       value="<?= htmlspecialchars((string)($currentGeo['longitude'] ?? '')) ?>"
                                       placeholder="-3.7161">
                                <div class="form-text">Coordenadas para <code>GeoCoordinates</code> en el JSON-LD.</div>
                            </div>
                        </div>

                        <!-- sameAs -->
                        <div class="form-group">
                            <label for="seo_business_sameas">sameAs — Perfiles y presencia web (una URL por línea)</label>
                            <textarea id="seo_business_sameas" name="seo_business_sameas" class="form-control" rows="7"
                                      placeholder="https://www.linkedin.com/in/...
https://github.com/...
https://maps.app.goo.gl/..."><?= htmlspecialchars($sameAsText) ?></textarea>
                            <div class="form-text">Se guardan solo URLs válidas y sin duplicados. Incluye LinkedIn, GitHub, Google Maps, etc.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">💾 Guardar Configuración SEO</button>
                </form>
            </div>
            
            <!-- Tab Security -->
            <div id="tab-security" class="tab-content">
                <div class="form-section">
                    <h3>🔒 Configuración de Seguridad</h3>
                    <p><em>Configuraciones de seguridad próximamente...</em></p>
                    
                    <ul style="margin-top: 20px; color: #666;">
                        <li>Configuración de sesiones</li>
                        <li>Límites de intentos de login</li>
                        <li>Configuración de CORS</li>
                        <li>Rate limiting para API</li>
                        <li>Configuración de HTTPS</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-header').forEach(header => {
                header.classList.remove('active');
            });
            
            // Mostrar tab seleccionado
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = '🙈';
            } else {
                field.type = 'password';
                button.textContent = '👁️';
            }
        }
    </script>
</body>
</html>