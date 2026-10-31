<?php
/** 404, enquiry submission, sitemap and robots. */

function page_not_found(): void
{
    $content = '<div class="flex min-h-[70vh] items-center justify-center bg-background px-4"><div class="max-w-md text-center"><h1 class="text-7xl font-bold text-foreground">404</h1>'
        . '<h2 class="mt-4 text-xl font-semibold text-foreground">Page not found</h2><p class="mt-2 text-sm text-muted-foreground">The page you\'re looking for doesn\'t exist or has been moved.</p>'
        . '<div class="mt-6"><a href="' . url('/') . '" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90">Go home</a></div></div></div>';
    render_layout(['title' => 'Page not found | Nishtha Global', 'robots' => 'noindex', 'og' => [], 'jsonld' => []], $content, 404);
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function leads_db(): PDO
{
    $dir = dirname(__DIR__, 2) . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $db = new PDO('sqlite:' . $dir . '/leads.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS leads (id INTEGER PRIMARY KEY AUTOINCREMENT, created_at TEXT, name TEXT, company TEXT, phone TEXT, email TEXT, product_interest TEXT, message TEXT, source_page TEXT, referrer TEXT, utm_source TEXT, utm_medium TEXT, utm_campaign TEXT, ip TEXT)');
    return $db;
}

/** POST /enquiry — same validation and abuse protections as submitEnquiry (enquiry.functions.ts). */
function handle_enquiry(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
    }
    $in = fn(string $k, int $max) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
    if ($in('website', 100) !== '') {
        json_out(['ok' => false, 'error' => 'Unable to send enquiry.'], 400);
    }
    $d = [
        'name' => $in('name', 100), 'phone' => $in('phone', 40), 'email' => $in('email', 255), 'company' => $in('company', 500),
        'product_interest' => $in('product_interest', 500), 'message' => $in('message', 10000), 'source_page' => $in('source_page', 500),
        'referrer' => $in('referrer', 500), 'utm_source' => $in('utm_source', 500), 'utm_medium' => $in('utm_medium', 500), 'utm_campaign' => $in('utm_campaign', 500),
    ];
    if ($d['name'] === '' || mb_strlen($d['phone']) < 5) {
        json_out(['ok' => false, 'error' => 'Please enter your name and a valid phone number.'], 422);
    }
    if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        json_out(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
    }

    $settings = get_public_settings();
    if (!empty($settings['recaptcha_enabled'])) {
        $secret = getenv('RECAPTCHA_SECRET_KEY') ?: '';
        $token = (string) ($_POST['g-recaptcha-response'] ?? '');
        if ($secret === '') {
            json_out(['ok' => false, 'error' => 'Form protection is not configured. Please contact us by phone.'], 503);
        }
        if ($token === '') {
            json_out(['ok' => false, 'error' => 'Please complete the security check.'], 422);
        }
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $token]), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $res = json_decode((string) curl_exec($ch), true);
        curl_close($ch);
        if (empty($res['success'])) {
            json_out(['ok' => false, 'error' => 'Security check failed. Please try again.'], 422);
        }
    }

    try {
        $db = leads_db();
        $recent = $db->prepare("SELECT COUNT(*) FROM leads WHERE phone = ? AND created_at >= ?");
        $recent->execute([$d['phone'], gmdate('Y-m-d H:i:s', time() - 600)]);
        if ((int) $recent->fetchColumn() >= 3) {
            json_out(['ok' => false, 'error' => 'Please wait before sending another enquiry.'], 429);
        }
        $st = $db->prepare('INSERT INTO leads (created_at,name,company,phone,email,product_interest,message,source_page,referrer,utm_source,utm_medium,utm_campaign,ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([gmdate('Y-m-d H:i:s'), $d['name'], $d['company'], $d['phone'], $d['email'], $d['product_interest'], $d['message'], $d['source_page'], $d['referrer'], $d['utm_source'], $d['utm_medium'], $d['utm_campaign'], $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Throwable $ex) {
        json_out(['ok' => false, 'error' => 'Unable to send enquiry. Please call us instead.'], 500);
    }

    // Optional: mirror into the live Supabase `leads` table when a service-role key is configured.
    if (cfg('supabase_service_key')) {
        $row = array_map(fn($v) => $v === '' ? null : $v, $d);
        sb_request('POST', 'leads', $row, cfg('supabase_service_key'), ['Prefer: return=minimal']);
    }
    if (cfg('enquiry_email')) {
        $body = '';
        foreach ($d as $k => $v) {
            $body .= ucfirst(str_replace('_', ' ', $k)) . ': ' . $v . "\n";
        }
        @mail(cfg('enquiry_email'), 'New enquiry from ' . $d['name'], $body, 'From: ' . site()['email']);
    }
    json_out(['ok' => true]);
}

function handle_sitemap(): void
{
    $s = site();
    $origin = $s['url'];
    $overrides = [];
    foreach (sb_get('page_seo?select=path,noindex&limit=1000') as $r) {
        $overrides[$r['path']] = !empty($r['noindex']);
    }
    $entries = [];
    foreach (['/', '/about', '/contact', '/industries', '/valve-automation', '/products', '/blog', '/fire-fighting-services', '/insulation-services'] as $p) {
        $entries[$p] = null;
    }
    $cg = get_catalogue();
    $cats = [];
    foreach ($cg['categories'] as $c) {
        $cats[$c['id']] = $c;
        if (!empty($c['robots_index'])) {
            $entries['/products/' . $c['slug']] = $c['updated_at'] ?? null;
        }
    }
    foreach ($cg['types'] as $t) {
        $c = $cats[$t['category_id']] ?? null;
        if ($c && !empty($c['robots_index']) && !empty($t['robots_index'])) {
            $entries['/products/' . $c['slug'] . '/' . $t['slug']] = $t['updated_at'] ?? null;
        }
    }
    foreach (get_published_posts() as $p) {
        if (!empty($p['robots_index'])) {
            $entries['/blog/' . $p['slug']] = $p['updated_at'] ?? null;
        }
    }
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($entries as $path => $lastmod) {
        if (!empty($overrides[$path])) {
            continue;
        }
        echo '<url><loc>' . e($origin . ($path === '/' ? '' : $path)) . '</loc>' . ($lastmod ? '<lastmod>' . e($lastmod) . '</lastmod>' : '') . '</url>';
    }
    echo '</urlset>';
}

function handle_robots(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nAllow: /\nDisallow: /storage\nDisallow: /app\nSitemap: " . site()['url'] . "/sitemap.xml\n";
}
