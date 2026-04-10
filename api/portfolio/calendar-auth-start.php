<?php
require_once __DIR__ . '/calendar-lib.php';

// Inicia OAuth. Abre este endpoint en el navegador.
$cfg = calendar_require_oauth_config();

// Estado simple (en MVP). En producción conviene persistir y validar CSRF.
$state = bin2hex(random_bytes(16));

$authUrl = calendar_build_auth_url($cfg, $state);

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html><head><meta charset="utf-8"><title>Autorizar Google Calendar</title></head><body>';
echo '<h1>Autorizar Google Calendar</h1>';
echo '<p>Haz clic para autorizar el acceso al calendario.</p>';
echo '<p><a href="' . htmlspecialchars($authUrl, ENT_QUOTES, 'UTF-8') . '">Continuar con Google</a></p>';
echo '</body></html>';
