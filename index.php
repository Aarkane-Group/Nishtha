<?php
/**
 * Front controller — plain PHP, no framework. Every request that is not a real file lands here
 * (see .htaccess) and is routed by path.
 */
declare(strict_types=1);

require __DIR__ . '/app/supabase.php';
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/data.php';
require __DIR__ . '/app/icons.php';
require __DIR__ . '/app/layout.php';
foreach (['home', 'static', 'catalogue', 'blog', 'system'] as $page) {
    require __DIR__ . "/app/pages/$page.php";
}
foreach (['auth', 'ui', 'pages_core', 'pages_content', 'pages_system'] as $f) {
    require __DIR__ . "/app/admin/$f.php";
}

$path = request_path();
$seg = $path === '/' ? [] : explode('/', trim($path, '/'));
$n = count($seg);

// Static pages
$static = [
    '/'                        => 'page_home',
    '/about'                   => 'page_about',
    '/contact'                 => 'page_contact',
    '/industries'              => 'page_industries',
    '/valve-automation'        => 'page_valve_automation',
    '/products'                => 'page_products_index',
    '/blog'                    => 'page_blog_index',
    '/fire-fighting-services'  => fn() => page_service('fire-fighting-services'),
    '/insulation-services'     => fn() => page_service('insulation-services'),
    '/enquiry'                 => 'handle_enquiry',
    '/sitemap.xml'             => 'handle_sitemap',
    '/robots.txt'              => 'handle_robots',
];
// Team dashboard
if ($path === '/auth') {
    admin_page_login();
    exit;
}
if ($path === '/auth/logout') {
    admin_session();
    csrf_check();
    admin_logout();
    redirect('/auth', 303);
}
if ($seg && $seg[0] === 'admin') {
    header('Cache-Control: no-store');
    if (!admin_auth()) {
        redirect('/auth', 302);
    }
    admin_dispatch($seg);
    exit;
}
// Uploaded media lives in a private bucket; the live site serves it, so hand off to it.
if ($n === 4 && $seg[0] === 'api' && $seg[1] === 'public' && $seg[2] === 'media') {
    redirect('https://nishthaglobal1.lovable.app/api/public/media/' . rawurlencode($seg[3] ?? ''), 302);
}

if (isset($static[$path])) {
    $static[$path]();
    exit;
}

// Catalogue: /products/{category}[/{type}[/{variant}]]
if ($seg[0] === 'products' && $n >= 2 && $n <= 4) {
    $data = get_catalogue_detail($seg[1], $seg[2] ?? null, $seg[3] ?? null);
    if (!$data && $n <= 3) {
        $target = get_legacy_redirect($path);
        if ($target) {
            redirect($target['new_path'], (int) ($target['status_code'] ?: 301));
        }
    }
    render_catalogue($data, $path);
    exit;
}

if ($seg[0] === 'blog' && $n === 2) {
    page_blog_post($seg[1]);
    exit;
}

// Legacy short URLs: /{category} and /{category}/{product} permanently redirect to canonical paths.
if ($n <= 2) {
    $cg = get_catalogue();
    $category = find_by($cg['categories'], fn($c) => $c['slug'] === $seg[0]);
    if ($category && $n === 1) {
        redirect('/products/' . rawurlencode($category['slug']));
    }
    if ($category && $n === 2 && find_by($cg['types'], fn($t) => $t['category_id'] === $category['id'] && $t['slug'] === $seg[1])) {
        redirect('/products/' . rawurlencode($category['slug']) . '/' . rawurlencode($seg[1]));
    }
}

page_not_found();
