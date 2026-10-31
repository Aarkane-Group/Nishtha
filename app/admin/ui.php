<?php
/** Admin shell and form helpers (ports of AdminShell.tsx / admin form components). */

const A_INPUT = 'mt-1.5 w-full rounded-sm border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring';
const A_BTN = 'inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 disabled:opacity-60';
const A_BTN_OUT = 'inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent';
const A_BTN_DANGER = 'inline-flex items-center justify-center gap-2 rounded-md border border-destructive/50 px-3 py-1.5 text-xs font-medium text-destructive transition-colors hover:bg-destructive/10';

function admin_nav(): array
{
    return [
        ['Overview', [['Dashboard', '/admin']]],
        ['Content', [['Blog Posts', '/admin/blog'], ['Media Library', '/admin/s/media']]],
        ['Products', [['All Products', '/admin/products'], ['Product Categories', '/admin/products?view=categories'], ['Mega Menu', '/admin/s/mega-menu']]],
        ['Enquiries', [['Contact Enquiries', '/admin/enquiries'], ['Quote Requests', '/admin/enquiries?type=quote']]],
        ['SEO & Marketing', [['SEO Settings', '/admin/seo'], ['Analytics Integrations', '/admin/integrations'], ['Structured Data', '/admin/s/structured-data']]],
        ['Website', [['Homepage Sections', '/admin/s/homepage'], ['Global Settings', '/admin/s/global-settings']]],
        ['System', [['Admin Users', '/admin/s/users'], ['General Settings', '/admin/s/general']]],
    ];
}

function admin_render(string $title, string $body): void
{
    $a = admin_auth();
    $path = request_path();
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    $self = $path . ($qs ? '?' . $qs : '');
    $nav = '';
    foreach (admin_nav() as [$group, $items]) {
        $nav .= '<div><p class="px-2 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">' . e($group) . '</p>';
        foreach ($items as [$label, $to]) {
            $active = str_contains($to, '?') ? $self === $to : ($path === $to && !str_contains($self, '?view=categories') && !str_contains($self, 'type=quote'));
            if (!str_contains($to, '?') && $path === $to && $qs !== '' && ($to === '/admin/products' && str_contains($qs, 'view=categories') || $to === '/admin/enquiries' && str_contains($qs, 'type=quote'))) {
                $active = false;
            }
            $nav .= '<a href="' . url($to) . '" class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-sm ' . ($active ? 'bg-primary/10 font-semibold text-primary' : 'text-foreground/80 hover:bg-muted') . '">' . e($label) . '</a>';
        }
        $nav .= '</div>';
    }
    $f = flash();
    $flash = $f ? '<div class="mb-5 rounded-md border px-4 py-3 text-sm ' . ($f[1] === 'ok' ? 'border-success/40 bg-success/10 text-foreground' : 'border-destructive/40 bg-destructive/10 text-destructive') . '" role="status">' . e($f[0]) . '</div>' : '';
    $warn = is_admin_user() ? '' : '<div class="mb-5 rounded-md border border-highlight/50 bg-highlight/10 px-4 py-3 text-sm">Your account does not have the <strong>admin</strong> role, so changes will not be saved. Ask an administrator to grant access.</div>';
    ?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | Admin | Nishtha Global</title><meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?= url('/favicon.ico') ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&amp;family=Barlow:wght@400;500;600;700&amp;display=swap">
<link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>?v=<?= @filemtime(dirname(__DIR__, 2) . '/assets/css/app.css') ?>">
</head><body>
<div class="flex min-h-screen w-full bg-muted/40">
  <aside class="sticky top-0 hidden h-screen w-60 shrink-0 flex-col border-r border-border bg-card lg:flex">
    <a href="<?= url('/') ?>" class="border-b border-border px-5 py-4 font-display text-base font-bold">Nishtha Global</a>
    <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-4"><?= $nav ?></nav>
  </aside>
  <div class="flex min-w-0 flex-1 flex-col">
    <header class="flex items-center justify-between gap-3 border-b border-border bg-card px-4 py-3 sm:px-6">
      <details class="relative lg:hidden"><summary class="cursor-pointer list-none rounded-md border border-input px-3 py-1.5 text-sm">Menu</summary>
        <div class="absolute left-0 top-10 z-50 max-h-[80vh] w-64 space-y-5 overflow-y-auto rounded-md border border-border bg-card p-3 shadow-panel"><?= $nav ?></div></details>
      <a href="<?= url('/') ?>" target="_blank" class="hidden text-sm text-muted-foreground hover:text-foreground lg:inline">View website ↗</a>
      <div class="ml-auto flex items-center gap-3 text-sm"><span class="hidden text-muted-foreground sm:inline"><?= e($a['email'] ?? '') ?></span>
        <form method="post" action="<?= url('/auth/logout') ?>"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="<?= A_BTN_OUT ?>">Sign out</button></form></div>
    </header>
    <main class="min-w-0 flex-1 p-4 sm:p-6"><?= $flash . $warn . $body ?></main>
  </div>
</div>
<script>document.addEventListener('submit',function(e){var m=e.target.getAttribute('data-confirm');if(m&&!confirm(m))e.preventDefault();});</script>
</body></html>
<?php
    exit;
}

function a_title(string $title, string $intro = '', string $action = ''): string
{
    return '<div class="mb-6 flex flex-wrap items-end justify-between gap-3"><div><h1 class="text-2xl font-bold">' . e($title) . '</h1>' . ($intro ? '<p class="mt-1 text-sm text-muted-foreground">' . e($intro) . '</p>' : '') . '</div>' . $action . '</div>';
}

function a_card(string $title, string $body, string $action = ''): string
{
    return '<section class="rounded-lg border border-border bg-card shadow-sm">'
        . ($title ? '<div class="flex items-center justify-between border-b border-border px-5 py-3.5"><h2 class="text-sm font-semibold">' . e($title) . '</h2>' . $action . '</div>' : '')
        . '<div class="p-5">' . $body . '</div></section>';
}

function f_csrf(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function f_input(string $name, string $label, $value = '', string $extra = ''): string
{
    return '<label class="block text-sm font-medium">' . e($label) . '<input name="' . e($name) . '" value="' . e($value) . '" class="' . A_INPUT . '" ' . $extra . '></label>';
}

function f_text(string $name, string $label, $value = '', int $rows = 4, string $hint = ''): string
{
    return '<label class="block text-sm font-medium">' . e($label) . ($hint ? ' <span class="font-normal text-muted-foreground">' . e($hint) . '</span>' : '') . '<textarea name="' . e($name) . '" rows="' . $rows . '" class="' . A_INPUT . '">' . e($value) . '</textarea></label>';
}

function f_check(string $name, string $label, bool $checked): string
{
    return '<label class="flex items-center gap-2 text-sm"><input type="hidden" name="' . e($name) . '" value="0"><input type="checkbox" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . '> ' . e($label) . '</label>';
}

function f_select(string $name, string $label, array $options, $selected): string
{
    $o = '';
    foreach ($options as $val => $text) {
        $o .= '<option value="' . e($val) . '"' . ((string) $val === (string) $selected ? ' selected' : '') . '>' . e($text) . '</option>';
    }
    return '<label class="block text-sm font-medium">' . e($label) . '<select name="' . e($name) . '" class="' . A_INPUT . '">' . $o . '</select></label>';
}

function post(string $k, $default = ''): string
{
    $v = $_POST[$k] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function post_null(string $k): ?string
{
    $v = post($k);
    return $v === '' ? null : $v;
}

function post_bool(string $k): bool
{
    return ($_POST[$k] ?? '0') === '1';
}

/** Textarea lines -> list of non-empty strings. */
function lines(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $text)), fn($l) => $l !== ''));
}

function seo_fields(array $r, string $prefix = ''): string
{
    $robots = fn($n, $l, $d) => f_check($n, $l, array_key_exists($n, $r) ? (bool) $r[$n] : $d);
    return '<div class="grid gap-4 sm:grid-cols-2">'
        . '<div class="sm:col-span-2">' . f_input('meta_title', 'Meta title', $r['meta_title'] ?? '', 'maxlength="120"') . '</div>'
        . '<div class="sm:col-span-2">' . f_text('meta_description', 'Meta description', $r['meta_description'] ?? '', 3) . '</div>'
        . f_input('canonical_url', 'Canonical URL', $r['canonical_url'] ?? '') . f_input('focus_keyword', 'Focus keyword', $r['focus_keyword'] ?? '')
        . f_input('og_title', 'Social title', $r['og_title'] ?? '') . f_input('og_image', 'Social image URL (https://)', $r['og_image'] ?? '')
        . '<div class="sm:col-span-2">' . f_text('og_description', 'Social description', $r['og_description'] ?? '', 2) . '</div>'
        . f_select('schema_type', 'Structured data type', array_combine(['Article', 'Product', 'CollectionPage', 'WebPage', 'None'], ['Article', 'Product', 'CollectionPage', 'WebPage', 'None (disabled)']), $r['schema_type'] ?? 'WebPage')
        . '<div class="flex flex-col justify-end gap-2">' . $robots('robots_index', 'Allow search engines to index', true) . $robots('robots_follow', 'Allow search engines to follow links', true) . '</div></div>';
}

function seo_payload(): array
{
    return [
        'meta_title' => post_null('meta_title'), 'meta_description' => post_null('meta_description'), 'canonical_url' => post_null('canonical_url'),
        'focus_keyword' => post_null('focus_keyword'), 'og_title' => post_null('og_title'), 'og_description' => post_null('og_description'),
        'og_image' => post_null('og_image'), 'schema_type' => post('schema_type', 'WebPage'),
        'robots_index' => post_bool('robots_index'), 'robots_follow' => post_bool('robots_follow'),
    ];
}

function fmt_dt(?string $iso): string
{
    $t = $iso ? strtotime($iso) : false;
    return $t ? date('j M Y, H:i', $t) : '';
}
