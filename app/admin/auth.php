<?php
/**
 * Admin authentication. Signs in with the same Supabase accounts as the live site and calls
 * PostgREST with the user's own JWT, so the database's row-level security decides what they may do.
 */

function admin_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('ng_admin');
        session_set_cookie_params(['path' => base_path() ?: '/', 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function auth_request(string $grant, array $body): array
{
    $ch = curl_init(rtrim(cfg('supabase_url'), '/') . '/auth/v1/token?grant_type=' . $grant);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => ['apikey: ' . cfg('supabase_key'), 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => !(PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') && !ini_get('openssl.cafile')),
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, json_decode((string) $raw, true) ?: []];
}

function admin_login(string $email, string $password): ?string
{
    admin_session();
    [$status, $res] = auth_request('password', ['email' => $email, 'password' => $password]);
    if ($status !== 200 || empty($res['access_token'])) {
        return $res['error_description'] ?? $res['msg'] ?? 'Invalid email or password.';
    }
    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'access' => $res['access_token'], 'refresh' => $res['refresh_token'] ?? '',
        'expires' => time() + (int) ($res['expires_in'] ?? 3600) - 60,
        'uid' => $res['user']['id'] ?? '', 'email' => $res['user']['email'] ?? $email,
    ];
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return null;
}

function admin_logout(): void
{
    admin_session();
    $_SESSION = [];
    session_destroy();
}

/** Current auth array (refreshing the token if needed), or null when signed out. */
function admin_auth(): ?array
{
    admin_session();
    $a = $_SESSION['auth'] ?? null;
    if (!$a) {
        return null;
    }
    if ($a['expires'] <= time()) {
        [$status, $res] = auth_request('refresh_token', ['refresh_token' => $a['refresh']]);
        if ($status !== 200 || empty($res['access_token'])) {
            admin_logout();
            return null;
        }
        $a['access'] = $res['access_token'];
        $a['refresh'] = $res['refresh_token'] ?? $a['refresh'];
        $a['expires'] = time() + (int) ($res['expires_in'] ?? 3600) - 60;
        $_SESSION['auth'] = $a;
    }
    return $a;
}

function csrf_token(): string
{
    admin_session();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrf_check(): void
{
    admin_session();
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid or expired form. Go back, refresh the page and try again.');
    }
}

/** Authenticated PostgREST call. Returns [status, decoded-json]. */
function sbu(string $method, string $path, ?array $body = null, array $headers = []): array
{
    $a = admin_auth();
    if (!$a) {
        return [401, ['message' => 'Signed out']];
    }
    [$status, $raw] = sb_request($method, $path, $body, null, array_merge(['Authorization: Bearer ' . $a['access'], 'Prefer: return=representation'], $headers));
    return [$status, json_decode($raw, true) ?? []];
}

function sbu_get(string $query): array
{
    [$status, $data] = sbu('GET', $query);
    return $status >= 200 && $status < 300 && is_array($data) ? $data : [];
}

function sbu_count(string $table, string $filter = ''): int
{
    $a = admin_auth();
    [, $raw] = [0, ''];
    $ch = curl_init(rtrim(cfg('supabase_url'), '/') . '/rest/v1/' . $table . '?select=id' . ($filter ? '&' . $filter : ''));
    $hdr = [];
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true, CURLOPT_HEADER => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['apikey: ' . cfg('supabase_key'), 'Authorization: Bearer ' . $a['access'], 'Prefer: count=exact', 'Range-Unit: items', 'Range: 0-0'],
        CURLOPT_SSL_VERIFYPEER => !(PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') && !ini_get('openssl.cafile')),
    ]);
    $raw = (string) curl_exec($ch);
    curl_close($ch);
    return preg_match('#content-range:\s*[^/]*/(\d+)#i', $raw, $m) ? (int) $m[1] : 0;
}

function is_admin_user(): bool
{
    static $r;
    if ($r !== null) {
        return $r;
    }
    $a = admin_auth();
    if (!$a) {
        return $r = false;
    }
    [$status, $data] = sbu('POST', 'rpc/has_role', ['_user_id' => $a['uid'], '_role' => 'admin']);
    return $r = ($status === 200 && $data === true);
}

/** Clear the public site's data cache so edits show immediately. */
function cache_clear(): void
{
    foreach (glob(dirname(__DIR__, 2) . '/storage/cache/*.json') ?: [] as $f) {
        @unlink($f);
    }
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    admin_session();
    if ($msg !== null) {
        $_SESSION['flash'] = [$msg, $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Turn a PostgREST write result into a flash message. Empty result array = row-level security refused. */
function write_result(int $status, $data, string $ok): bool
{
    if ($status >= 200 && $status < 300 && (is_array($data) && ($data !== [] || $status === 204))) {
        flash($ok);
        cache_clear();
        return true;
    }
    if ($status >= 200 && $status < 300) {
        flash('Nothing was saved — your account may not have admin permission.', 'err');
    } else {
        flash(is_array($data) ? ($data['message'] ?? 'Save failed.') : 'Save failed.', 'err');
    }
    return false;
}
