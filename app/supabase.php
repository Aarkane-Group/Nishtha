<?php
/** Minimal Supabase REST client with a small on-disk cache for reads. */

function cfg(string $key, $default = null)
{
    static $config;
    $config ??= require __DIR__ . '/config.php';
    return $config[$key] ?? $default;
}

function sb_request(string $method, string $path, ?array $body = null, ?string $key = null, array $extraHeaders = []): array
{
    $key ??= cfg('supabase_key');
    $ch = curl_init(rtrim(cfg('supabase_url'), '/') . '/rest/v1/' . ltrim($path, '/'));
    $headers = array_merge(['apikey: ' . $key, 'Accept: application/json'], $extraHeaders);
    if (!str_starts_with($key, 'sb_')) {
        $headers[] = 'Authorization: Bearer ' . $key;
    }
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        // XAMPP on Windows often ships without a CA bundle; fall back rather than fail every request.
        CURLOPT_SSL_VERIFYPEER => !(PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') && !ini_get('openssl.cafile')),
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, $raw === false ? '' : (string) $raw];
}

/** GET a PostgREST query, e.g. sb_get('product_types?select=*&published=eq.true&order=sort_order'). */
function sb_get(string $query, ?int $ttl = null): array
{
    $ttl ??= (int) cfg('cache_ttl', 60);
    $dir = dirname(__DIR__) . '/storage/cache';
    $file = $dir . '/' . md5($query) . '.json';
    if ($ttl > 0 && is_file($file) && time() - filemtime($file) < $ttl) {
        return json_decode((string) file_get_contents($file), true) ?? [];
    }
    [$status, $raw] = sb_request('GET', $query);
    $data = json_decode($raw, true);
    if ($status >= 200 && $status < 300 && is_array($data)) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @file_put_contents($file, $raw);
        return $data;
    }
    // Live database unreachable: serve the last good copy rather than breaking the page.
    if (is_file($file)) {
        return json_decode((string) file_get_contents($file), true) ?? [];
    }
    return [];
}
