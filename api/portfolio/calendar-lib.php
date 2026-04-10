<?php
/**
 * Utilidades de Google Calendar para la API pública.
 * - Lee credenciales OAuth desde env o config.local.php (si existe)
 * - Gestiona almacenamiento de tokens en BD cuando está disponible
 *
 * Requisitos (Fase 1):
 * - GOOGLE_OAUTH_CLIENT_ID
 * - GOOGLE_OAUTH_CLIENT_SECRET
 * - GOOGLE_OAUTH_REDIRECT_URI
 * - GOOGLE_CALENDAR_ID (opcional, default 'primary')
 */

function calendar_json_response($success, $data = null, $error = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

function calendar_get_env($key, $default = null) {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    return $default;
}

function calendar_try_load_local_config() {
    // Cargar config.local.php si existe (sin romper si no está).
    // Necesario para reutilizar el patrón actual del proyecto.
    $configPath = __DIR__ . '/../../admin/config/config.local.php';
    if (file_exists($configPath)) {
        if (!defined('ADMIN_ACCESS')) {
            define('ADMIN_ACCESS', true);
        }
        require_once $configPath;
        return true;
    }
    return false;
}

function calendar_get_oauth_config() {
    // 1) BD (preferido en este proyecto)
    $db = calendar_db();
    if ($db) {
        try {
            $rows = $db->fetchAll(
                "SELECT config_key, config_value FROM system_config WHERE config_key IN (?, ?, ?, ?, ?)",
                [
                    'google_calendar_enabled',
                    'google_oauth_client_id',
                    'google_oauth_client_secret',
                    'google_oauth_redirect_uri',
                    'google_calendar_id'
                ]
            );
            $map = [];
            foreach ($rows as $r) {
                $map[$r['config_key']] = $r['config_value'];
            }
            $enabled = ($map['google_calendar_enabled'] ?? '') === 'true' || ($map['google_calendar_enabled'] ?? '') === '1';
            if ($enabled) {
                return [
                    'client_id' => $map['google_oauth_client_id'] ?? null,
                    'client_secret' => $map['google_oauth_client_secret'] ?? null,
                    'redirect_uri' => $map['google_oauth_redirect_uri'] ?? null,
                    'calendar_id' => $map['google_calendar_id'] ?? 'primary',
                ];
            }
        } catch (Exception $e) {
            // Fallback silencioso
        }
    }

    // 2) config.local.php
    calendar_try_load_local_config();
    if (function_exists('get_google_calendar_config')) {
        $cfg = get_google_calendar_config();
        if (is_array($cfg) && ($cfg['enabled'] ?? false)) {
            return [
                'client_id' => $cfg['client_id'] ?? null,
                'client_secret' => $cfg['client_secret'] ?? null,
                'redirect_uri' => $cfg['redirect_uri'] ?? null,
                'calendar_id' => $cfg['calendar_id'] ?? 'primary',
            ];
        }
    }

    // 3) ENV fallback
    return [
        'client_id' => calendar_get_env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => calendar_get_env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => calendar_get_env('GOOGLE_OAUTH_REDIRECT_URI'),
        'calendar_id' => calendar_get_env('GOOGLE_CALENDAR_ID', 'primary'),
    ];
}

function calendar_require_oauth_config() {
    $cfg = calendar_get_oauth_config();
    if (empty($cfg['client_id']) || empty($cfg['client_secret']) || empty($cfg['redirect_uri'])) {
        calendar_json_response(false, null, [
            'message' => 'Credenciales OAuth de Google no configuradas.',
            'code' => 'GOOGLE_OAUTH_NOT_CONFIGURED',
            'hint' => 'Define GOOGLE_OAUTH_CLIENT_ID / GOOGLE_OAUTH_CLIENT_SECRET / GOOGLE_OAUTH_REDIRECT_URI en el entorno.'
        ], 500);
    }
    return $cfg;
}

function calendar_db() {
    // Intentar cargar Database del admin si es posible
    $dbPath = __DIR__ . '/../../admin/config/database.php';
    if (file_exists($dbPath)) {
        if (!class_exists('Database')) {
            define('ADMIN_ACCESS', true);
            require_once $dbPath;
        }
        if (class_exists('Database')) {
            return Database::getInstance();
        }
    }
    return null;
}

function calendar_token_storage_available($db) {
    if (!$db) return false;
    try {
        $db->fetchOne("SELECT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function calendar_get_token_from_db($db) {
    if (!$db) return null;
    try {
        // Tabla mínima: system_config(key_name,value)
        // Reutilizamos si existe, si no, se creará en migración posterior.
        $row = $db->fetchOne("SELECT config_value FROM system_config WHERE config_key = ?", ['google_calendar_oauth_token']);
        if (!$row || empty($row['config_value'])) return null;
        return json_decode($row['config_value'], true);
    } catch (Exception $e) {
        return null;
    }
}

function calendar_save_token_to_db($db, $token) {
    if (!$db) return false;
    $json = json_encode($token, JSON_UNESCAPED_UNICODE);
    try {
        // Upsert compatible
        $existing = $db->fetchOne("SELECT config_key FROM system_config WHERE config_key = ?", ['google_calendar_oauth_token']);
        if ($existing) {
            $db->execute("UPDATE system_config SET config_value = ? WHERE config_key = ?", [$json, 'google_calendar_oauth_token']);
        } else {
            $db->execute("INSERT INTO system_config (config_key, config_value) VALUES (?, ?)", ['google_calendar_oauth_token', $json]);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function calendar_build_auth_url($cfg, $state) {
    $params = [
        'client_id' => $cfg['client_id'],
        'redirect_uri' => $cfg['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'access_type' => 'offline',
        'prompt' => 'consent',
        'include_granted_scopes' => 'true',
        'state' => $state,
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function calendar_exchange_code_for_token($cfg, $code) {
    $payload = http_build_query([
        'code' => $code,
        'client_id' => $cfg['client_id'],
        'client_secret' => $cfg['client_secret'],
        'redirect_uri' => $cfg['redirect_uri'],
        'grant_type' => 'authorization_code'
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        throw new Exception('Error en cURL: ' . $err);
    }

    $json = json_decode($resp, true);
    if ($httpCode >= 400) {
        $msg = $json['error_description'] ?? $json['error'] ?? 'Token exchange failed';
        throw new Exception($msg);
    }

    // Normalizar timestamps
    $json['created_at'] = time();
    return $json;
}

function calendar_refresh_access_token($cfg, $refreshToken) {
    $payload = http_build_query([
        'client_id' => $cfg['client_id'],
        'client_secret' => $cfg['client_secret'],
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token'
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        throw new Exception('Error en cURL: ' . $err);
    }

    $json = json_decode($resp, true);
    if ($httpCode >= 400) {
        $msg = $json['error_description'] ?? $json['error'] ?? 'Refresh failed';
        throw new Exception($msg);
    }

    $json['created_at'] = time();
    return $json;
}

function calendar_get_valid_access_token() {
    $cfg = calendar_require_oauth_config();
    $db = calendar_db();

    $token = calendar_get_token_from_db($db);
    if (!$token) {
        calendar_json_response(false, null, [
            'message' => 'Google Calendar no está autorizado todavía.',
            'code' => 'GOOGLE_OAUTH_NOT_AUTHORIZED',
            'hint' => 'Abre /api/portfolio/calendar-auth-start.php para autorizar.'
        ], 401);
    }

    $accessToken = $token['access_token'] ?? null;
    $refreshToken = $token['refresh_token'] ?? null;
    $expiresIn = (int)($token['expires_in'] ?? 0);
    $createdAt = (int)($token['created_at'] ?? 0);

    $isExpired = !$accessToken || !$createdAt || !$expiresIn || (time() >= ($createdAt + $expiresIn - 60));

    if ($isExpired) {
        if (!$refreshToken) {
            calendar_json_response(false, null, [
                'message' => 'Token expirado y sin refresh_token. Reautoriza el acceso.',
                'code' => 'GOOGLE_OAUTH_REAUTH_REQUIRED',
                'hint' => 'Vuelve a ejecutar el flujo OAuth.'
            ], 401);
        }

        $refreshed = calendar_refresh_access_token($cfg, $refreshToken);
        // Mantener refresh_token anterior si Google no lo devuelve
        if (empty($refreshed['refresh_token'])) {
            $refreshed['refresh_token'] = $refreshToken;
        }
        calendar_save_token_to_db($db, $refreshed);
        $accessToken = $refreshed['access_token'];
    }

    return [$cfg, $accessToken];
}

function calendar_google_api_request($method, $url, $accessToken, $body = null, $query = null) {
    if ($query) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json'
    ];

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        throw new Exception('Error en cURL: ' . $err);
    }

    $json = json_decode($resp, true);
    if ($httpCode >= 400) {
        $msg = $json['error']['message'] ?? $json['error_description'] ?? 'Google API error';
        throw new Exception($msg);
    }

    return $json;
}
