<?php

/**
 * Mide lecturas públicas en paralelo.
 *
 * La API tiene que estar levantada. Por defecto usa http://127.0.0.1:8001.
 * LOAD_BASE_URL=http://127.0.0.1:8000 php scripts/load-public-reads.php
 */

$base = rtrim((string) (getenv('LOAD_BASE_URL') ?: 'http://127.0.0.1:8001'), '/');
$paths = [
    '/api/v1/news',
    '/api/v1/polls',
    '/api/v1/metrics',
    '/api/v1/search?q=horario',
];
$levels = [1, 4, 8, 16];
$requestsPerLevel = 10;
$timeoutSeconds = 8;
$slowMilliseconds = 2000;

$probe = probe($base.'/api/v1/ping', $timeoutSeconds);
if ($probe !== 200) {
    fwrite(STDERR, "La API no respondió en {$base}/api/v1/ping (código {$probe}).\n");
    exit(1);
}

echo "Lecturas públicas contra {$base}\n";
echo str_pad('a_la_vez', 10)
    .str_pad('peticiones', 12)
    .str_pad('correctas', 12)
    .str_pad('errores', 10)
    .str_pad('p50_ms', 10)
    .str_pad('p95_ms', 10)
    ."por_segundo\n";

$breakingLevel = null;

foreach ($levels as $concurrency) {
    $result = runLevel($base, $paths, $concurrency, $requestsPerLevel, $timeoutSeconds);
    echo str_pad((string) $result['concurrency'], 10)
        .str_pad((string) $result['requests'], 12)
        .str_pad((string) $result['ok'], 12)
        .str_pad((string) $result['errors'], 10)
        .str_pad((string) round($result['p50']), 10)
        .str_pad((string) round($result['p95']), 10)
        .number_format($result['rps'], 1, '.', '')
        ."\n";

    $failed = $result['errors'] > 0 || $result['p95'] >= $slowMilliseconds;
    if ($failed && $breakingLevel === null) {
        $breakingLevel = $concurrency;
    }
}

if ($breakingLevel === null) {
    echo "Las {$levels[array_key_last($levels)]} lecturas a la vez respondieron en menos de {$slowMilliseconds} ms.\n";
} else {
    echo "A partir de {$breakingLevel} lecturas a la vez hay errores o la respuesta tarda {$slowMilliseconds} ms o más.\n";
}

$burst = burstUntilLimited($base.'/api/v1/debates', 80, $timeoutSeconds);
echo "La lista de debates aceptó {$burst['ok']} lecturas seguidas y la siguiente respondió {$burst['stopped']}.\n";

exit($breakingLevel === 1 ? 1 : 0);

/**
 * @return array{ok: int, stopped: int}
 */
function burstUntilLimited(string $url, int $max, int $timeoutSeconds): array
{
    $ok = 0;
    $stopped = 0;

    for ($attempt = 0; $attempt < $max; $attempt++) {
        $result = oneRequest($url, $timeoutSeconds);
        if ($result['ok']) {
            $ok++;

            continue;
        }

        $stopped = $result['code'];
        break;
    }

    return ['ok' => $ok, 'stopped' => $stopped];
}

/**
 * @return array{ok: bool, code: int}
 */
function oneRequest(string $url, int $timeoutSeconds): array
{
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($handle);
    $code = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $errno = curl_errno($handle);
    curl_close($handle);
    $payload = is_string($body) ? json_decode($body, true) : null;
    $ok = $errno === 0
        && $code >= 200
        && $code < 300
        && is_array($payload)
        && array_key_exists('data', $payload);

    return ['ok' => $ok, 'code' => $code];
}

function probe(string $url, int $timeoutSeconds): int
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return $code;
}

/**
 * @param  list<string>  $paths
 * @return array{concurrency: int, requests: int, ok: int, errors: int, p50: float, p95: float, rps: float}
 */
function runLevel(string $base, array $paths, int $concurrency, int $total, int $timeoutSeconds): array
{
    $multi = curl_multi_init();
    $handles = [];
    $latencies = [];
    $ok = 0;
    $errors = 0;
    $started = 0;
    $finished = 0;
    $wallStart = microtime(true);

    $add = function () use (&$started, $total, $multi, &$handles, $base, $paths, $timeoutSeconds): void {
        if ($started >= $total) {
            return;
        }

        $path = $paths[$started % count($paths)];
        $handle = curl_init($base.$path);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        curl_multi_add_handle($multi, $handle);
        $handles[spl_object_id($handle)] = $handle;
        $started++;
    };

    for ($slot = 0; $slot < $concurrency; $slot++) {
        $add();
    }

    $active = 0;
    do {
        do {
            $status = curl_multi_exec($multi, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        while ($info = curl_multi_info_read($multi)) {
            $handle = $info['handle'];
            $body = curl_multi_getcontent($handle);
            $code = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $errno = curl_errno($handle);
            $latencies[] = (float) curl_getinfo($handle, CURLINFO_TOTAL_TIME) * 1000;
            $payload = is_string($body) ? json_decode($body, true) : null;
            $valid = $errno === 0
                && $code >= 200
                && $code < 300
                && is_array($payload)
                && array_key_exists('data', $payload);

            if ($valid) {
                $ok++;
            } else {
                $errors++;
            }

            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);
            unset($handles[spl_object_id($handle)]);
            $finished++;
            $add();
        }

        if ($active > 0) {
            curl_multi_select($multi, 0.25);
        }
    } while ($finished < $total);

    curl_multi_close($multi);
    sort($latencies);
    $elapsed = max(microtime(true) - $wallStart, 0.001);

    return [
        'concurrency' => $concurrency,
        'requests' => $total,
        'ok' => $ok,
        'errors' => $errors,
        'p50' => percentile($latencies, 50),
        'p95' => percentile($latencies, 95),
        'rps' => $total / $elapsed,
    ];
}

/**
 * @param  list<float>  $sorted
 */
function percentile(array $sorted, int $percent): float
{
    if ($sorted === []) {
        return 0;
    }

    $index = (int) ceil((count($sorted) * $percent) / 100) - 1;

    return $sorted[max(0, min($index, count($sorted) - 1))];
}
