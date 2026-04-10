<?php
/**
 * Test de Notificaciones Telegram
 * Página para probar y configurar las notificaciones de Telegram
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getUser();

require_once __DIR__ . '/../classes/TelegramNotifier.php';

$message = '';
$testResult = null;

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telegram = new TelegramNotifier();
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'test_connection':
            $testResult = $telegram->testConnection();
            break;
            
        case 'test_chat_message':
            $success = $telegram->notifyChatMessage(
                '¿Cuál es tu experiencia en desarrollo web?',
                'Tengo más de 5 años de experiencia en desarrollo full-stack con especialización en React y PHP.',
                ['ip' => '127.0.0.1']
            );
            $message = $success ? '✅ Notificación de chat enviada' : '❌ Error al enviar notificación';
            break;
            
        case 'test_contact_form':
            $success = $telegram->notifyContactForm([
                'name' => 'Juan Pérez',
                'email' => 'juan@example.com',
                'subject' => 'Consulta sobre proyecto',
                'message' => 'Me gustaría discutir un proyecto de desarrollo web para mi empresa.'
            ]);
            $message = $success ? '✅ Notificación de contacto enviada' : '❌ Error al enviar notificación';
            break;
            
        case 'test_error':
            $success = $telegram->notifyError(
                'Test error: Division by zero',
                ['file' => 'test.php', 'line' => 42, 'url' => '/admin/test']
            );
            $message = $success ? '✅ Notificación de error enviada' : '❌ Error al enviar notificación';
            break;
            
        case 'test_admin_login':
            $success = $telegram->notifyAdminLogin($user['username'] ?? 'admin', true);
            $message = $success ? '✅ Notificación de login enviada' : '❌ Error al enviar notificación';
            break;
            
        case 'test_article_view':
            $success = $telegram->notifyArticleView(
                'Guía Completa de React Hooks en 2024',
                'react-hooks-guia-completa'
            );
            $message = $success ? '✅ Notificación de artículo enviada' : '❌ Error al enviar notificación';
            break;
            
        case 'test_custom':
            $customMessage = $_POST['custom_message'] ?? '';
            if ($customMessage) {
                $success = $telegram->sendMessage($customMessage);
                $message = $success ? '✅ Mensaje personalizado enviado' : '❌ Error al enviar mensaje';
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Telegram - Portfolio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-telegram"></i> Test Notificaciones Telegram
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

    <div class="container mt-4">
        <?php if ($message): ?>
        <div class="alert alert-<?= strpos($message, '✅') !== false ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($testResult): ?>
        <div class="alert alert-<?= $testResult['success'] ? 'success' : 'danger' ?> alert-dismissible fade show">
            <h5 class="alert-heading">
                <?= $testResult['success'] ? '✅ Conexión Exitosa' : '❌ Error de Conexión' ?>
            </h5>
            <?php if ($testResult['success']): ?>
                <p><strong>Bot Token:</strong> <?= htmlspecialchars($testResult['bot_token']) ?></p>
                <p><strong>Chat ID:</strong> <?= htmlspecialchars($testResult['chat_id']) ?></p>
                <p class="mb-0"><?= htmlspecialchars($testResult['message']) ?></p>
            <?php else: ?>
                <p class="mb-0"><strong>Error:</strong> <?= htmlspecialchars($testResult['error']) ?></p>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Instrucciones de Configuración -->
        <div class="card border-info mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle-fill"></i> Configuración Requerida</h5>
            </div>
            <div class="card-body">
                <h6><strong>Paso 1:</strong> Crear Bot de Telegram</h6>
                <ol>
                    <li>Abre Telegram y busca <strong>@BotFather</strong></li>
                    <li>Envía el comando: <code>/newbot</code></li>
                    <li>Sigue las instrucciones para crear tu bot</li>
                    <li>Copia el <strong>Bot Token</strong> que te proporciona</li>
                </ol>

                <h6 class="mt-3"><strong>Paso 2:</strong> Obtener tu Chat ID</h6>
                <ol>
                    <li>Inicia una conversación con tu bot recién creado</li>
                    <li>Envía cualquier mensaje (ej: <code>/start</code>)</li>
                    <li>Visita: <code>https://api.telegram.org/bot&lt;TU_TOKEN&gt;/getUpdates</code></li>
                    <li>Busca <code>"chat":{"id":123456789</code> → ese número es tu <strong>Chat ID</strong></li>
                </ol>

                <h6 class="mt-3"><strong>Paso 3:</strong> Configurar en config.local.php</h6>
                <pre class="bg-light p-3"><code>function get_telegram_config() {
    return [
        'enabled' => true,
        'bot_token' => 'TU_BOT_TOKEN_AQUI',
        'chat_id' => 'TU_CHAT_ID_AQUI',
        // ... resto de configuración
    ];
}</code></pre>
                
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Importante:</strong> El archivo <code>config.local.php</code> está en <code>.gitignore</code> por seguridad.
                    Copia <code>config.local.example.php</code> si aún no lo has hecho.
                </div>
            </div>
        </div>

        <!-- Test de Conexión -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-broadcast"></i> Test de Conexión</h5>
            </div>
            <div class="card-body">
                <p>Verifica que tu bot esté correctamente configurado:</p>
                <form method="POST">
                    <input type="hidden" name="action" value="test_connection">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Probar Conexión
                    </button>
                </form>
            </div>
        </div>

        <!-- Tests de Notificaciones -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-bell"></i> Pruebas de Notificaciones</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border-secondary">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-chat-dots"></i> Chat RAG</h6>
                                <p class="card-text small">Simula un mensaje en el chat conversacional</p>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="test_chat_message">
                                    <button type="submit" class="btn btn-sm btn-success">Enviar Test</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-secondary">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-envelope"></i> Formulario Contacto</h6>
                                <p class="card-text small">Simula un formulario de contacto</p>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="test_contact_form">
                                    <button type="submit" class="btn btn-sm btn-info">Enviar Test</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-secondary">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-exclamation-triangle"></i> Error del Sistema</h6>
                                <p class="card-text small">Simula un error en el sistema</p>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="test_error">
                                    <button type="submit" class="btn btn-sm btn-danger">Enviar Test</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-secondary">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-shield-lock"></i> Login Admin</h6>
                                <p class="card-text small">Simula un login en admin panel</p>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="test_admin_login">
                                    <button type="submit" class="btn btn-sm btn-warning">Enviar Test</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card border-secondary">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-book"></i> Vista de Artículo</h6>
                                <p class="card-text small">Simula la lectura de un artículo</p>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="test_article_view">
                                    <button type="submit" class="btn btn-sm btn-secondary">Enviar Test</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensaje Personalizado -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Mensaje Personalizado</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="test_custom">
                    <div class="mb-3">
                        <label class="form-label">Mensaje (admite Markdown):</label>
                        <textarea name="custom_message" class="form-control" rows="4" placeholder="*Negrita* _Cursiva_ `Código`">🧪 *Test personalizado*

Este es un mensaje de prueba con formato Markdown.

✅ Lista de características:
- Soporte para Markdown
- Emojis incluidos
- Notificaciones en tiempo real</textarea>
                    </div>
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-send"></i> Enviar Mensaje
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
