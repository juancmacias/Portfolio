<?php
require_once __DIR__ . '/calendar-lib.php';

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    calendar_json_response(false, null, ['message' => 'Método no permitido'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Datos JSON inválidos');
    }

    $start = $input['start'] ?? null;
    $end = $input['end'] ?? null;
    $timezone = $input['timezone'] ?? 'Europe/Madrid';
    $title = trim($input['title'] ?? 'Reunión');
    $calendarNotes = trim($input['notes'] ?? '');

    if (!$start || !$end) {
        throw new Exception('Parámetros start/end requeridos');
    }
    if ($title === '') {
        throw new Exception('title no puede estar vacío');
    }

    [$cfg, $accessToken] = calendar_get_valid_access_token();
    $calendarId = $cfg['calendar_id'] ?? 'primary';

    $event = [
        'summary' => $title,
        'description' => $calendarNotes,
        'start' => [
            'dateTime' => $start,
            'timeZone' => $timezone
        ],
        'end' => [
            'dateTime' => $end,
            'timeZone' => $timezone
        ]
    ];

    $created = calendar_google_api_request(
        'POST',
        'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events',
        $accessToken,
        $event
    );

    calendar_json_response(true, [
        'event' => [
            'id' => $created['id'] ?? null,
            'htmlLink' => $created['htmlLink'] ?? null
        ]
    ]);

} catch (Exception $e) {
    calendar_json_response(false, null, [
        'message' => $e->getMessage()
    ], 400);
}
