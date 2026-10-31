<?php
/** Sign-in, dashboard, enquiries, integrations. */

function admin_page_login(): void
{
    admin_session();
    $error = null;
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $error = admin_login(post('email'), (string) ($_POST['password'] ?? ''));
        if (!$error) {
            redirect('/admin', 303);
        }
    }
    if (admin_auth()) {
        redirect('/admin', 302);
    }
    $s = site();
    ?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Team sign in | Nishtha Global</title><meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?= url('/favicon.ico') ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&amp;family=Barlow:wght@400;500;600;700&amp;display=swap">
<link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>?v=<?= @filemtime(dirname(__DIR__, 2) . '/assets/css/app.css') ?>"></head>
<body><div class="flex min-h-screen items-center justify-center bg-muted/60 px-4">
  <form method="post" class="w-full max-w-sm rounded-md border border-border bg-card p-8 shadow-panel">
    <a href="<?= url('/') ?>" class="font-display text-lg font-bold"><?= e($s['name']) ?></a>
    <h1 class="mt-4 text-2xl">Team sign in</h1>
    <?php if ($error): ?><p class="mt-4 rounded-sm border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert"><?= e($error) ?></p><?php endif; ?>
    <label class="mt-6 block text-sm font-medium">Email<input name="email" type="email" required autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>" class="<?= A_INPUT ?>"></label>
    <label class="mt-4 block text-sm font-medium">Password<input name="password" type="password" required minlength="8" autocomplete="current-password" class="<?= A_INPUT ?>"></label>
    <button class="mt-6 w-full rounded-sm bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">Sign in</button>
    <p class="mt-4 text-center text-xs text-muted-foreground">Same accounts as the live site. New team members can register on the live site's sign-in page.</p>
  </form></div></body></html>
<?php
    exit;
}

function admin_page_dashboard(): void
{
    $since = rawurlencode(gmdate('Y-m-d\TH:i:s\Z', time() - 30 * 86400));
    $events = sbu_get('tracked_events?select=event_type&created_at=gte.' . $since . '&limit=5000');
    $n = fn($t) => count(array_filter($events, fn($e) => $e['event_type'] === $t));
    $cards = [
        ['Published products', sbu_count('product_types', 'published=eq.true')], ['Product categories', sbu_count('product_categories', 'published=eq.true')],
        ['Published blog posts', sbu_count('blog_posts', 'published=eq.true')], ['New enquiries', sbu_count('leads', 'status=eq.new')],
        ['Awaiting follow-up', sbu_count('leads', 'status=eq.contacted')], ['Call clicks (30 days)', $n('call_click')],
        ['WhatsApp clicks (30 days)', $n('whatsapp_click')], ['Email clicks (30 days)', $n('email_click')],
    ];
    $grid = '';
    foreach ($cards as [$l, $v]) {
        $grid .= '<div class="rounded-lg border border-border bg-card p-4 shadow-sm"><p class="text-2xl font-bold">' . $v . '</p><p class="text-xs text-muted-foreground">' . e($l) . '</p></div>';
    }
    $leads = sbu_get('leads?select=id,name,product_interest,created_at,status&order=created_at.desc&limit=6');
    $posts = sbu_get('blog_posts?select=id,title&published=eq.true&order=published_at.desc&limit=5');
    $drafts = sbu_get('blog_posts?select=id,title&published=eq.false&order=updated_at.desc&limit=5');
    $li = '';
    foreach ($leads as $l) {
        $li .= '<li class="flex justify-between gap-3 py-2.5 text-sm"><span><span class="font-medium">' . e($l['name']) . '</span> <span class="text-muted-foreground">· ' . e($l['product_interest'] ?: 'General') . '</span></span><span class="whitespace-nowrap text-xs text-muted-foreground">' . e(fmt_dt($l['created_at'])) . ' · ' . e($l['status']) . '</span></li>';
    }
    $list = fn($rows, $empty) => $rows ? '<ul class="space-y-2 text-sm">' . implode('', array_map(fn($r) => '<li>' . e($r['title']) . '</li>', $rows)) . '</ul>' : '<p class="text-sm text-muted-foreground">' . $empty . '</p>';
    $actions = '';
    foreach ([['Write new blog post', '/admin/blog?new=1'], ['Edit products', '/admin/products'], ['View enquiries', '/admin/enquiries'], ['Configure SEO', '/admin/seo'], ['Configure analytics', '/admin/integrations']] as [$l, $to]) {
        $actions .= '<a href="' . url($to) . '" class="rounded-md border border-border px-3 py-2 text-sm hover:bg-muted">' . e($l) . '</a>';
    }
    $ext = '';
    foreach ([['Google Analytics', 'https://analytics.google.com/'], ['Microsoft Clarity', 'https://clarity.microsoft.com/'], ['Search Console', 'https://search.google.com/search-console']] as [$l, $u]) {
        $ext .= '<a class="rounded-md border border-border px-3 py-2 text-sm hover:bg-muted" href="' . $u . '" target="_blank" rel="noreferrer">' . $l . ' ↗</a>';
    }
    $body = a_title('Dashboard', 'Live overview of your website content, enquiries and calls.')
        . '<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">' . $grid . '</div><div class="mt-6 grid gap-6 lg:grid-cols-3"><div class="lg:col-span-2">'
        . a_card('Recent enquiries', $leads ? '<ul class="divide-y divide-border">' . $li . '</ul>' : '<p class="text-sm text-muted-foreground">No enquiries yet.</p>', '<a href="' . url('/admin/enquiries') . '" class="text-xs text-primary">View all</a>')
        . '</div>' . a_card('Quick actions', '<div class="grid gap-2">' . $actions . '</div>')
        . a_card('Recently published posts', $list($posts, 'No published posts.')) . a_card('Drafts waiting to publish', $list($drafts, 'No drafts.'))
        . a_card('Visitor traffic & recordings', '<div class="grid gap-2">' . $ext . '</div>') . '</div>';
    admin_render('Dashboard', $body);
}

function admin_page_enquiries(): void
{
    $statuses = ['new', 'contacted', 'won', 'lost'];
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        if (preg_match('/^[0-9a-f-]{36}$/', post('id')) && in_array(post('status'), $statuses, true)) {
            [$st, $d] = sbu('PATCH', 'leads?id=eq.' . post('id'), ['status' => post('status')]);
            write_result($st, $d, 'Status updated.');
        }
        redirect('/admin/enquiries' . (!empty($_GET['type']) ? '?type=quote' : ''), 303);
    }
    $quote = ($_GET['type'] ?? '') === 'quote';
    $q = strtolower(trim((string) ($_GET['q'] ?? '')));
    $rows = array_filter(sbu_get('leads?select=*&order=created_at.desc&limit=1000'), fn($l) => (!$quote || $l['product_interest']) && ($q === '' || str_contains(strtolower(implode(' ', [$l['name'], $l['email'], $l['phone'], $l['company']])), $q)));
    $tr = '';
    foreach ($rows as $l) {
        $opts = implode('', array_map(fn($s) => '<option' . ($s === $l['status'] ? ' selected' : '') . '>' . $s . '</option>', $statuses));
        $tr .= '<tr class="border-t border-border align-top"><td class="whitespace-nowrap py-2.5 pr-3 text-xs">' . e(fmt_dt($l['created_at'])) . '</td>'
            . '<td class="pr-3"><p class="font-medium">' . e($l['name']) . '</p><p class="text-xs text-muted-foreground">' . e($l['company']) . '</p></td>'
            . '<td class="pr-3"><p>' . e($l['phone']) . '</p><p class="text-xs text-muted-foreground">' . e($l['email']) . '</p></td>'
            . '<td class="max-w-xs pr-3"><p>' . e($l['product_interest']) . '</p><p class="text-xs text-muted-foreground">' . e($l['message']) . '</p></td>'
            . '<td class="pr-3 text-xs">' . e($l['source_page']) . '<br><span class="text-muted-foreground">' . e($l['utm_source'] ?: 'direct') . '</span></td>'
            . '<td><form method="post">' . f_csrf() . '<input type="hidden" name="id" value="' . e($l['id']) . '"><select name="status" onchange="this.form.submit()" class="rounded border border-input bg-background px-2 py-1 text-xs">' . $opts . '</select></form></td></tr>';
    }
    // Enquiries saved locally by this PHP site (when the live leads table could not be written).
    $local = '';
    try {
        foreach (leads_db()->query('SELECT * FROM leads ORDER BY id DESC LIMIT 200') as $l) {
            $local .= '<tr class="border-t border-border align-top"><td class="whitespace-nowrap py-2.5 pr-3 text-xs">' . e($l['created_at']) . ' UTC</td><td class="pr-3 font-medium">' . e($l['name']) . '<br><span class="text-xs font-normal text-muted-foreground">' . e($l['company']) . '</span></td><td class="pr-3">' . e($l['phone']) . '<br><span class="text-xs text-muted-foreground">' . e($l['email']) . '</span></td><td class="max-w-xs pr-3">' . e($l['product_interest']) . '<br><span class="text-xs text-muted-foreground">' . e($l['message']) . '</span></td><td class="text-xs">' . e($l['source_page']) . '</td></tr>';
        }
    } catch (Throwable $e) {
    }
    $th = fn($cols) => '<thead class="text-xs uppercase text-muted-foreground"><tr>' . implode('', array_map(fn($c) => '<th class="py-2">' . $c . '</th>', $cols)) . '</tr></thead>';
    $body = a_title($quote ? 'Quote Requests' : 'Contact Enquiries', "Everything sent through the website's forms.")
        . a_card('', '<form class="mb-4 flex gap-2"><input name="q" value="' . e($_GET['q'] ?? '') . '" placeholder="Search name, email, phone or company" class="' . A_INPUT . ' max-w-sm">' . ($quote ? '<input type="hidden" name="type" value="quote">' : '') . '<button class="' . A_BTN_OUT . '">Search</button></form>'
            . '<div class="overflow-x-auto"><table class="w-full text-left text-sm">' . $th(['Date', 'Name', 'Contact', 'Requirement', 'Source', 'Status']) . '<tbody>' . ($tr ?: '<tr><td colspan="6" class="py-8 text-center text-muted-foreground">No enquiries yet.</td></tr>') . '</tbody></table></div>')
        . ($local ? '<div class="mt-6">' . a_card('Received by this PHP site (local database)', '<div class="overflow-x-auto"><table class="w-full text-left text-sm">' . $th(['Date', 'Name', 'Contact', 'Requirement', 'Source']) . '<tbody>' . $local . '</tbody></table></div>') . '</div>' : '');
    admin_render('Enquiries', $body);
}

function admin_page_integrations(): void
{
    $fields = [
        ['ga_measurement_id', 'ga_enabled', 'Google Analytics 4', 'Measurement ID, e.g. G-XXXXXXXXXX', 'Analytics → Admin → Data streams → your website.'],
        ['clarity_project_id', 'clarity_enabled', 'Microsoft Clarity', 'Project ID', 'clarity.microsoft.com → Settings → Overview.'],
        ['gtm_container_id', 'gtm_enabled', 'Google Tag Manager (optional)', 'GTM-XXXXXXX', 'Only needed if you manage tags in Tag Manager.'],
        ['google_site_verification', null, 'Google Search Console', 'Verification content value', 'Search Console → Add property → HTML tag method; paste only the content value.'],
        ['bing_site_verification', null, 'Bing Webmaster Tools', 'Verification content value', 'Bing Webmaster → Add site → HTML meta tag.'],
    ];
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $p = [];
        foreach ($fields as [$key, $toggle]) {
            $p[$key] = post_null($key);
            if ($toggle) {
                $p[$toggle] = post_bool($toggle);
            }
        }
        $p['recaptcha_site_key'] = post_null('recaptcha_site_key');
        $p['recaptcha_enabled'] = post_bool('recaptcha_enabled');
        $p['cookie_consent_enabled'] = post_bool('cookie_consent_enabled');
        if ($p['ga_enabled'] && !preg_match('/^G-[A-Z0-9]+$/', (string) $p['ga_measurement_id'])) {
            flash('Enter a valid GA4 measurement ID.', 'err');
        } elseif ($p['clarity_enabled'] && !preg_match('/^[a-zA-Z0-9]+$/', (string) $p['clarity_project_id'])) {
            flash('Enter a valid Clarity project ID.', 'err');
        } else {
            [$st, $d] = sbu('PATCH', 'site_settings?id=eq.1', $p);
            write_result($st, $d, 'Saved. Tracking is active for visitors from their next page load.');
        }
        redirect('/admin/integrations', 303);
    }
    $s = sbu_get('site_settings?select=*&id=eq.1')[0] ?? [];
    $cards = '';
    foreach ($fields as [$key, $toggle, $label, $hint, $help]) {
        $val = (string) ($s[$key] ?? '');
        $on = $toggle ? !empty($s[$toggle]) : true;
        $badge = '<span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ' . ($val && $on ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground') . '">' . ($val && $on ? 'Configured' : 'Not configured') . '</span>';
        $cards .= a_card($label, '<input name="' . $key . '" value="' . e($val) . '" placeholder="' . e($hint) . '" class="' . A_INPUT . '"><p class="mt-2 text-xs text-muted-foreground">' . e($help) . '</p>' . ($toggle ? '<div class="mt-3">' . f_check($toggle, 'Enabled', $on) . '</div>' : ''), $badge);
    }
    $cards .= a_card('Google reCAPTCHA v2', f_input('recaptcha_site_key', 'Site key', $s['recaptcha_site_key'] ?? '') . '<div class="mt-3">' . f_check('recaptcha_enabled', 'Enable form protection', !empty($s['recaptcha_enabled'])) . '</div><p class="mt-3 text-sm text-muted-foreground">Set the secret key as the <code>RECAPTCHA_SECRET_KEY</code> environment variable on the server before enabling.</p>');
    $cards .= a_card('Cookie consent', f_check('cookie_consent_enabled', 'Ask permission before loading analytics', !empty($s['cookie_consent_enabled'])));
    admin_render('Integrations', '<form method="post">' . f_csrf() . a_title('Analytics Integrations', 'Connect Google Analytics, Microsoft Clarity and search engine verification.', '<button class="' . A_BTN . '">Save settings</button>') . '<div class="grid gap-4 lg:grid-cols-2">' . $cards . '</div></form>');
}
