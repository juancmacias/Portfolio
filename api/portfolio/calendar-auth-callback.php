<?php
require_once __DIR__ . '/calendar-lib.php';

try {
    $cfg = calendar_require_oauth_config();

    $code = $_GET['code'] ?? null;
    if (!$code) {
        throw new Exception('Falta parámetro code');
    }

    $token = calendar_exchange_code_for_token($cfg, $code);

    $db = calendar_db();
    if (!$db) {
        throw new Exception('No se pudo inicializar base de datos para guardar el token');
    }

    $ok = calendar_save_token_to_db($db, $token);
    if (!$ok) {
        throw new Exception('No se pudo guardar el token en system_config');
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Google Calendar autorizado</title></head><body>';
    echo '<h1>Autorización completada</h1>';
    echo '<p>Ya puedes volver a la página de “Agendar una reunión” y consultar huecos.</p>';
    echo '</body></html>';

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Error OAuth</title></head><body>';
    echo '<h1>Error</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
}
