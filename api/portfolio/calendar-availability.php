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
    $durationMinutes = (int)($input['duration_minutes'] ?? 30);
    $timezone = $input['timezone'] ?? 'Europe/Madrid';

    if (!$start || !$end) {
        throw new Exception('Parámetros start/end requeridos');
    }
    if ($durationMinutes < 10 || $durationMinutes > 180) {
        throw new Exception('duration_minutes fuera de rango');
    }

    [$cfg, $accessToken] = calendar_get_valid_access_token();

    $calendarId = $cfg['calendar_id'] ?? 'primary';

    // 1) Pedir ocupación con FreeBusy
    $freeBusy = calendar_google_api_request(
        'POST',
        'https://www.googleapis.com/calendar/v3/freeBusy',
        $accessToken,
        [
            'timeMin' => $start,
            'timeMax' => $end,
            'timeZone' => $timezone,
            'items' => [
                ['id' => $calendarId]
            ]
        ]
    );

    $busy = $freeBusy['calendars'][$calendarId]['busy'] ?? [];

    // 2) Calcular huecos libres dentro de un horario laboral simple.
    // Reglas MVP:
    // - Horario: 10:00-14:00 y 16:00-19:00
    // - Buffer: 10 minutos (antes/después de eventos busy)
    // - Slots segmentados en durationMinutes
    $bufferMinutes = 10;
    $workWindows = [
        ['start' => '10:00', 'end' => '14:00'],
        ['start' => '16:00', 'end' => '19:00'],
    ];

    $startDt = new DateTime($start);
    $endDt = new DateTime($end);

    // Normalizar busy a intervalos DateTime (con buffer)
    $busyIntervals = [];
    foreach ($busy as $b) {
        if (empty($b['start']) || empty($b['end'])) continue;
        $bs = new DateTime($b['start']);
        $be = new DateTime($b['end']);
        $bs->modify('-' . $bufferMinutes . ' minutes');
        $be->modify('+' . $bufferMinutes . ' minutes');
        $busyIntervals[] = ['start' => $bs, 'end' => $be];
    }
    usort($busyIntervals, function($a, $b) {
        return $a['start'] <=> $b['start'];
    });

    // Merge busy solapados
    $mergedBusy = [];
    foreach ($busyIntervals as $int) {
        if (empty($mergedBusy)) {
            $mergedBusy[] = $int;
            continue;
        }
        $lastIdx = count($mergedBusy) - 1;
        $last = $mergedBusy[$lastIdx];
        if ($int['start'] <= $last['end']) {
            if ($int['end'] > $last['end']) {
                $mergedBusy[$lastIdx]['end'] = $int['end'];
            }
        } else {
            $mergedBusy[] = $int;
        }
    }

    $slots = [];
    $cursorDay = clone $startDt;
    $cursorDay->setTime(0, 0, 0);

    while ($cursorDay < $endDt) {
        foreach ($workWindows as $win) {
            [$h1, $m1] = array_map('intval', explode(':', $win['start']));
            [$h2, $m2] = array_map('intval', explode(':', $win['end']));

            $winStart = (clone $cursorDay)->setTime($h1, $m1, 0);
            $winEnd = (clone $cursorDay)->setTime($h2, $m2, 0);

            // Clamp al rango solicitado
            if ($winEnd <= $startDt || $winStart >= $endDt) {
                continue;
            }
            if ($winStart < $startDt) $winStart = clone $startDt;
            if ($winEnd > $endDt) $winEnd = clone $endDt;

            // Construir intervalos libres dentro de la ventana
            $freeStart = clone $winStart;
            foreach ($mergedBusy as $b) {
                if ($b['end'] <= $freeStart) continue;
                if ($b['start'] >= $winEnd) break;

                // hay hueco [freeStart, b.start)
                if ($b['start'] > $freeStart) {
                    $freeEnd = min($b['start'], $winEnd);
                    $slots = array_merge($slots, calendar_segment_slots($freeStart, $freeEnd, $durationMinutes));
                }

                if ($b['end'] > $freeStart) {
                    $freeStart = clone $b['end'];
                }
                if ($freeStart >= $winEnd) break;
            }

            // Resto de ventana
            if ($freeStart < $winEnd) {
                $slots = array_merge($slots, calendar_segment_slots($freeStart, $winEnd, $durationMinutes));
            }
        }
        $cursorDay->modify('+1 day');
    }

    // Limitar slots para no inundar (MVP)
    $slots = array_slice($slots, 0, 40);

    calendar_json_response(true, [
        'slots' => $slots,
        'timezone' => $timezone,
        'duration_minutes' => $durationMinutes,
        'note' => 'Huecos calculados con FreeBusy + horario laboral MVP.'
    ]);

} catch (Exception $e) {
    calendar_json_response(false, null, [
        'message' => $e->getMessage()
    ], 400);
}

function calendar_segment_slots($startDt, $endDt, $durationMinutes) {
    $out = [];
    $cursor = clone $startDt;

    // redondeo simple a 5 minutos
    $minute = (int)$cursor->format('i');
    $rounded = (int)(ceil($minute / 5) * 5) % 60;
    if ($rounded !== $minute) {
        $cursor->setTime((int)$cursor->format('H'), $rounded, 0);
        if ($rounded === 0) {
            // overflow hora
            $cursor->modify('+1 hour');
        }
    }

    while (true) {
        $slotEnd = (clone $cursor)->modify('+' . (int)$durationMinutes . ' minutes');
        if ($slotEnd > $endDt) break;
        $out[] = [
            'start' => $cursor->format(DateTime::ATOM),
            'end' => $slotEnd->format(DateTime::ATOM),
        ];
        $cursor->modify('+5 minutes');
    }

    return $out;
}
