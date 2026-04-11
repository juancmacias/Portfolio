<?php
/**
 * Herramienta para obtener el Chat ID de Telegram
 * SEGURIDAD: Usa configuración desde config.local.php
 */
define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/config.local.php';

// Obtener configuración Telegram de forma segura
$telegramConfig = get_telegram_config();
$botToken = $telegramConfig['bot_token'] ?? '';
$isConfigured = !empty($botToken);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obtener Chat ID de Telegram</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">📱 Obtener tu Chat ID de Telegram</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!$isConfigured): ?>
                        <div class="alert alert-danger">
                            <strong>⚠️ Error:</strong> Bot Token no configurado en <code>config.local.php</code>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <strong>📝 Instrucciones:</strong> Sigue estos pasos para obtener tu Chat ID
                        </div>

                        <h5 class="mt-4">Paso 1: Envía un mensaje a tu bot</h5>
                        <ol>
                            <li>Abre Telegram</li>
                            <li>Busca tu bot por el username que creaste</li>
                            <li>Inicia la conversación enviando: <code>/start</code></li>
                        </ol>

                        <h5 class="mt-4">Paso 2: Obtén el Chat ID</h5>
                        <p>El bot token está configurado automáticamente desde <code>config.local.php</code></p>
                        
                        <button onclick="getChatId()" class="btn btn-primary">
                            🔍 Obtener Chat ID
                        </button>

                        <div id="result" class="mt-4" style="display:none;">
                            <div class="alert alert-success">
                                <h5>✅ Chat ID Encontrado:</h5>
                                <div id="chatIdDisplay"></div>
                            </div>
                        </div>

                        <div id="error" class="mt-4" style="display:none;">
                            <div class="alert alert-danger">
                                <h5>❌ Error:</h5>
                                <div id="errorDisplay"></div>
                            </div>
                        </div>

                        <div id="loading" class="mt-4" style="display:none;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2">Consultando Telegram API...</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isConfigured): ?>
                <div class="card shadow mt-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">📋 Método Alternativo (Manual)</h5>
                    </div>
                    <div class="card-body">
                        <p>Si el botón no funciona, obtén tu Chat ID manualmente:</p>
                        <ol>
                            <li>Asegúrate de haber enviado <code>/start</code> a tu bot</li>
                            <li>Abre esta URL en tu navegador:</li>
                        </ol>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="manualUrl" readonly
                                   value="Ver en código fuente (token oculto por seguridad)">
                            <button class="btn btn-outline-secondary" onclick="openManualUrl()">🌐 Abrir URL</button>
                        </div>
                        <ol start="3">
                            <li>Busca en la respuesta JSON: <code>"chat":{"id":<strong>123456789</strong></code></li>
                            <li>Ese número ↑ es tu Chat ID</li>
                        </ol>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Token obtenido de forma segura desde PHP (no visible en código fuente)
        const BOT_TOKEN = <?php echo json_encode($botToken); ?>;

        async function getChatId() {
            if (!BOT_TOKEN) {
                showError('Bot Token no configurado');
                return;
            }

            // Ocultar resultados previos
            document.getElementById('result').style.display = 'none';
            document.getElementById('error').style.display = 'none';
            document.getElementById('loading').style.display = 'block';

            try {
                const url = `https://api.telegram.org/bot${BOT_TOKEN}/getUpdates`;
                const response = await fetch(url);
                const data = await response.json();

                document.getElementById('loading').style.display = 'none';

                if (!data.ok) {
                    showError('Token inválido o error en la API de Telegram: ' + (data.description || 'Unknown error'));
                    return;
                }

                if (!data.result || data.result.length === 0) {
                    showError('No se encontraron mensajes. Asegúrate de haber enviado /start a tu bot primero.');
                    return;
                }

                // Obtener el chat ID del último mensaje
                const lastMessage = data.result[data.result.length - 1];
                const chatId = lastMessage.message?.chat?.id || lastMessage.message?.from?.id;

                if (!chatId) {
                    showError('No se pudo extraer el Chat ID. Verifica la respuesta de la API.');
                    return;
                }

                showResult(chatId, data.result);
            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                showError('Error al conectar con Telegram API: ' + error.message);
            }
        }

        function showResult(chatId, updates) {
            const resultDiv = document.getElementById('chatIdDisplay');
            resultDiv.innerHTML = `
                <h3 class="text-success">${chatId}</h3>
                <p><strong>Copia este número y pégalo en config.local.php:</strong></p>
                <pre class="bg-light p-3 border"><code>'chat_id' => '${chatId}'</code></pre>
                <button class="btn btn-sm btn-success" onclick="copyChatId('${chatId}')">📋 Copiar Chat ID</button>
                <hr>
                <details class="mt-3">
                    <summary>Ver mensajes detectados (${updates.length})</summary>
                    <pre class="bg-light p-3 border mt-2" style="max-height: 300px; overflow-y: auto;">${JSON.stringify(updates, null, 2)}</pre>
                </details>
            `;
            document.getElementById('result').style.display = 'block';
        }

        function showError(message) {
            document.getElementById('errorDisplay').textContent = message;
            document.getElementById('error').style.display = 'block';
        }

        function copyChatId(chatId) {
            navigator.clipboard.writeText(chatId).then(() => {
                alert('✅ Chat ID copiado al portapapeles: ' + chatId);
            }).catch(err => {
                alert('❌ Error al copiar: ' + err);
            });
        }

        function openManualUrl() {
            if (!BOT_TOKEN) {
                alert('Token no configurado');
                return;
            }
            const url = `https://api.telegram.org/bot${BOT_TOKEN}/getUpdates`;
            window.open(url, '_blank');
        }
    </script>
</body>
</html>
