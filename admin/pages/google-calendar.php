<?php
/**
 * Configuración Google Calendar (OAuth)
 * Guarda valores en system_config (texto plano - MVP)
 */

define('ADMIN_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../config/auth.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
  header('Location: login.php');
  exit;
}

$db = Database::getInstance();
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $enabled = isset($_POST['google_calendar_enabled']) ? 'true' : 'false';
        $clientId = trim($_POST['google_oauth_client_id'] ?? '');
        $clientSecret = trim($_POST['google_oauth_client_secret'] ?? '');
        $redirectUri = trim($_POST['google_oauth_redirect_uri'] ?? '');
        $calendarId = trim($_POST['google_calendar_id'] ?? 'primary');

        if ($enabled === 'true') {
            if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
                throw new Exception('Si activas Google Calendar, client_id, client_secret y redirect_uri son obligatorios.');
            }
        }

        $configs = [
            'google_calendar_enabled' => $enabled,
            'google_oauth_client_id' => $clientId,
            'google_oauth_client_secret' => $clientSecret,
            'google_oauth_redirect_uri' => $redirectUri,
            'google_calendar_id' => ($calendarId === '' ? 'primary' : $calendarId),
        ];

        foreach ($configs as $key => $value) {
            $sql = "INSERT INTO system_config (config_key, config_value) VALUES (?, ?)\n                    ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)";
            $db->query($sql, [$key, $value]);
        }

        $success = 'Configuración de Google Calendar guardada.';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// cargar valores actuales
$configs = [];
try {
    $rows = $db->fetchAll("SELECT config_key, config_value FROM system_config WHERE config_key LIKE 'google_%'");
    foreach ($rows as $r) {
        $configs[$r['config_key']] = $r['config_value'];
    }
} catch (Exception $e) {
    $configs = [];
}

$currentEnabled = ($configs['google_calendar_enabled'] ?? '') === 'true' || ($configs['google_calendar_enabled'] ?? '') === '1';
$currentClientId = $configs['google_oauth_client_id'] ?? '';
$currentClientSecret = $configs['google_oauth_client_secret'] ?? '';
$currentRedirectUri = $configs['google_oauth_redirect_uri'] ?? '';
$currentCalendarId = $configs['google_calendar_id'] ?? 'primary';

setBreadcrumb([
    ['title' => 'Configuración', 'url' => getRoute('settings')],
    ['title' => 'Google Calendar', 'url' => getRoute('google-calendar')]
]);

$pageTitle = 'Google Calendar';
$pageIcon = '📅';
$pageHeader = true;
$pageDescription = 'Configura OAuth para la funcionalidad “Agendar una reunión”. (MVP: secretos en texto plano en BD)';

$contentFile = ADMIN_ROOT . '/includes/pages/google-calendar-content.php';
include ADMIN_ROOT . '/includes/layouts/base.php';
