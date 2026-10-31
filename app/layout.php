<?php
/** Layout and shared UI components (ports of src/components/site/*). */

const BTN_BASE = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0';
const BTN_PRIMARY_LG = BTN_BASE . ' bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-8';
const BTN_OUTLINE_HERO_LG = BTN_BASE . ' border shadow-sm h-10 px-8 border-steel-foreground/30 bg-transparent text-steel-foreground hover:bg-steel-foreground/10 hover:text-steel-foreground';

function nav_items(): array
{
    return [
        ['/', 'Home'], ['/about', 'About Us'], ['/products', 'Product'], ['/valve-automation', 'Valve Automation'],
        ['/industries', 'Industries'], ['/blog', 'Blog'], ['/contact', 'Contact'],
    ];
}

/** Product types of a category grouped by menu_group, preserving order (menu_visible only). */
function menu_groups(array $catalogue, string $categoryId): array
{
    $groups = [];
    foreach ($catalogue['types'] as $t) {
        if ($t['category_id'] !== $categoryId || empty($t['menu_visible'])) {
            continue;
        }
        $name = $t['menu_group'] ?? null;
        $key = $name ?? "\0main";
        $groups[$key]['name'] = $name;
        $groups[$key]['items'][] = $t;
    }
    return array_values($groups);
}

function menu_categories(array $catalogue): array
{
    return array_values(array_filter($catalogue['categories'], fn($c) => !empty($c['menu_visible'])));
}

function category_href(array $c): string
{
    return !empty($c['menu_href']) ? href($c['menu_href']) : url('/products/' . rawurlencode($c['slug']));
}

function type_href(array $c, array $t): string
{
    return !empty($t['menu_href']) ? href($t['menu_href']) : url('/products/' . rawurlencode($c['slug']) . '/' . rawurlencode($t['slug']));
}

function mega_menu(array $catalogue): string
{
    $cats = menu_categories($catalogue);
    ob_start(); ?>
    <div class="group relative" data-megamenu>
      <a href="<?= url('/products') ?>" class="inline-flex items-center gap-1 rounded-sm px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">Product <?= icon('chevron-down', 'h-3.5 w-3.5') ?></a>
      <?php if ($cats): ?>
      <div class="invisible fixed left-1/2 top-16 z-50 w-[min(1100px,95vw)] -translate-x-1/2 translate-y-2 border border-border bg-popover opacity-0 shadow-lift transition-all group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
        <div class="grid grid-cols-[260px_1fr]">
          <ul class="border-r border-border bg-muted/50 py-3">
            <?php foreach ($cats as $i => $c): ?>
            <li><a href="<?= e(category_href($c)) ?>" data-mega-tab="<?= $i ?>" class="flex items-center justify-between px-5 py-2.5 text-sm font-medium <?= $i === 0 ? 'bg-background text-primary' : 'text-popover-foreground hover:bg-background' ?>"><?= e($c['menu_label'] ?: $c['name']) ?> <?= icon('chevron-right', 'size-4') ?></a></li>
            <?php endforeach; ?>
          </ul>
          <div class="max-h-[70vh] overflow-y-auto p-6">
            <?php foreach ($cats as $i => $c): ?>
            <div data-mega-panel="<?= $i ?>" class="<?= $i === 0 ? '' : 'hidden' ?>">
              <div class="flex items-start gap-6">
                <div class="columns-1 gap-8 sm:columns-2 lg:columns-3 flex-1">
                  <?php foreach (menu_groups($catalogue, $c['id']) as $g): ?>
                  <div class="mb-5 break-inside-avoid">
                    <?php if ($g['name']): ?><p class="mb-2 text-xs font-semibold uppercase text-primary"><?= e($g['name']) ?></p><?php endif; ?>
                    <ul class="space-y-1">
                      <?php foreach ($g['items'] as $t): ?>
                      <li><a href="<?= e(type_href($c, $t)) ?>" class="block py-1 text-sm text-popover-foreground hover:text-primary"><?= e($t['menu_label'] ?: $t['name']) ?></a></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <?php endforeach; ?>
                </div>
                <?php if (!empty($c['image_url'])): ?><img src="<?= e(url($c['image_url'])) ?>" alt="<?= e($c['name']) ?>" class="hidden h-40 w-40 object-contain lg:block" loading="lazy"><?php endif; ?>
              </div>
              <a href="<?= url('/products/' . rawurlencode($c['slug'])) ?>" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-primary">View all <?= e($c['name']) ?> <?= icon('chevron-right', 'size-4') ?></a>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php return ob_get_clean();
}

function mobile_product_menu(array $catalogue): string
{
    ob_start(); ?>
    <div class="border-b border-border/60 py-2">
      <a href="<?= url('/products') ?>" class="block py-2 text-sm font-medium text-foreground">Product</a>
      <?php foreach (menu_categories($catalogue) as $c): ?>
      <div>
        <button type="button" data-mobile-cat aria-expanded="false" class="flex w-full items-center justify-between py-2 pl-3 text-sm text-muted-foreground">
          <?= e($c['menu_label'] ?: $c['name']) ?> <?= icon('chevron-down', 'size-4 transition-transform') ?>
        </button>
        <ul class="hidden pb-2 pl-6">
          <?php foreach ($catalogue['types'] as $t): if ($t['category_id'] !== $c['id'] || empty($t['menu_visible'])) continue; ?>
          <li><a href="<?= e(type_href($c, $t)) ?>" class="block py-1.5 text-sm text-foreground"><?= e($t['menu_label'] ?: $t['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
}

function site_header(): string
{
    $s = site();
    $cat = get_catalogue();
    $path = request_path();
    ob_start(); ?>
    <header class="sticky top-0 z-50 border-b border-border/70 bg-background/90 backdrop-blur">
      <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6">
        <a href="<?= url('/') ?>" class="flex items-center gap-2.5" aria-label="<?= e($s['name']) ?> home">
          <img src="<?= e(url($s['logo'])) ?>" alt="Nishtha Engineering Services" width="800" height="320" class="h-10 w-auto object-contain">
        </a>
        <nav class="ml-auto hidden items-center gap-1 lg:flex">
          <?php foreach (nav_items() as [$to, $label]):
              if ($to === '/products') { echo mega_menu($cat); continue; }
              $active = $to === '/' ? $path === '/' : ($path === $to || str_starts_with($path, $to . '/')); ?>
          <a href="<?= url($to) ?>" class="rounded-sm px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground <?= $active ? 'bg-accent text-accent-foreground' : 'text-muted-foreground' ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </nav>
        <a href="<?= e($s['phoneHref']) ?>" data-track="call_click" data-label="header" class="ml-auto inline-flex items-center gap-2 rounded-sm bg-primary px-3.5 py-2 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 lg:ml-0">
          <?= icon('phone', 'h-4 w-4') ?><span class="hidden sm:inline">Call now</span>
        </a>
        <button type="button" data-menu-toggle class="rounded-sm p-2 text-foreground transition-colors hover:bg-accent lg:hidden" aria-label="Open menu" aria-expanded="false">
          <span data-menu-open><?= icon('menu', 'h-5 w-5') ?></span><span data-menu-close class="hidden"><?= icon('x', 'h-5 w-5') ?></span>
        </button>
      </div>
      <nav data-mobile-nav class="hidden border-t border-border bg-background px-4 pb-4 lg:hidden">
        <?php foreach (nav_items() as [$to, $label]):
            if ($to === '/products') { echo mobile_product_menu($cat); continue; } ?>
        <a href="<?= url($to) ?>" class="block border-b border-border/60 py-3 text-sm font-medium text-foreground last:border-0"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
    </header>
    <?php return ob_get_clean();
}

function site_footer(): string
{
    $s = site();
    ob_start(); ?>
    <footer class="surface-steel mt-24">
      <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4">
        <div class="md:col-span-2">
          <p class="font-display text-lg font-bold"><?= e($s['legalName']) ?></p>
          <p class="mt-3 max-w-md text-sm text-steel-foreground/75"><?= e($s['description']) ?></p>
          <div class="mt-6 h-1 w-20 section-rule rounded-full"></div>
        </div>
        <div>
          <p class="font-display text-sm font-bold uppercase tracking-[0.16em]">Explore</p>
          <ul class="mt-4 space-y-2 text-sm text-steel-foreground/75">
            <?php foreach ([['/products', 'Valves &amp; Fittings'], ['/valve-automation', 'Valve Automation'], ['/industries', 'Industries Served'], ['/about', 'About Us'], ['/blog', 'Blog'], ['/contact', 'Request a Quote']] as [$to, $label]): ?>
            <li><a href="<?= url($to) ?>" class="hover:text-steel-foreground"><?= $label ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div>
          <p class="font-display text-sm font-bold uppercase tracking-[0.16em]">Get in touch</p>
          <ul class="mt-4 space-y-3 text-sm text-steel-foreground/80">
            <li><a href="<?= e($s['phoneHref']) ?>" data-track="call_click" data-label="footer" class="inline-flex items-center gap-2 hover:text-steel-foreground"><?= icon('phone') ?> <?= e($s['phone']) ?></a></li>
            <li><a href="<?= e($s['whatsappHref']) ?>" target="_blank" rel="noreferrer" data-track="whatsapp_click" data-label="footer" class="inline-flex items-center gap-2 hover:text-steel-foreground"><?= icon('message-circle') ?> WhatsApp</a></li>
            <li><a href="<?= e($s['emailHref']) ?>" data-track="email_click" data-label="footer" class="inline-flex items-center gap-2 hover:text-steel-foreground"><?= icon('mail') ?> <?= e($s['email']) ?></a></li>
            <li class="inline-flex items-start gap-2"><?= icon('map-pin', 'mt-0.5 h-4 w-4 shrink-0') ?> <?= e($s['address']) ?></li>
          </ul>
        </div>
      </div>
      <div class="border-t border-steel-foreground/15">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-steel-foreground/60 sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <p>© <?= date('Y') ?> <?= e($s['legalName']) ?>. All rights reserved.</p>
          <a href="<?= url('/admin') ?>" data-no-ajax class="hover:text-steel-foreground">Team dashboard</a>
        </div>
      </div>
    </footer>
    <?php return ob_get_clean();
}

function render_head(array $head): string
{
    $s = site();
    $title = $head['title'] ?? ($s['name'] . ' — ' . $s['tagline']);
    $desc = $head['description'] ?? $s['description'];
    $out = '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    $out .= '<title>' . e($title) . '</title>';
    $out .= '<meta name="description" content="' . e($desc) . '">';
    $out .= '<meta name="author" content="' . e($s['legalName']) . '">';
    $out .= '<meta name="robots" content="' . e($head['robots'] ?? 'index, follow') . '">';
    if (!empty($head['keywords'])) {
        $out .= '<meta name="keywords" content="' . e($head['keywords']) . '">';
    }
    $og = ['og:site_name' => $s['name'], 'og:type' => 'website', 'twitter:card' => 'summary_large_image'] + ($head['og'] ?? []);
    foreach ($og as $k => $v) {
        $out .= '<meta ' . (str_starts_with($k, 'og:') ? 'property' : 'name') . '="' . e($k) . '" content="' . e($v) . '">';
    }
    if (!empty($head['canonical'])) {
        $out .= '<link rel="canonical" href="' . e($head['canonical']) . '">';
    }
    $settings = get_public_settings();
    if (!empty($settings['google_site_verification'])) {
        $out .= '<meta name="google-site-verification" content="' . e($settings['google_site_verification']) . '">';
    }
    if (!empty($settings['bing_site_verification'])) {
        $out .= '<meta name="msvalidate.01" content="' . e($settings['bing_site_verification']) . '">';
    }
    $org = [
        '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $s['legalName'], 'alternateName' => $s['name'],
        'description' => $s['description'], 'foundingDate' => (string) $s['foundedYear'], 'email' => $s['email'], 'telephone' => $s['phone'],
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $s['city'], 'addressRegion' => $s['state'], 'addressCountry' => 'IN'],
    ];
    foreach (array_merge([$org], $head['jsonld'] ?? []) as $schema) {
        $out .= '<script type="application/ld+json">' . str_replace('</', '<\/', json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</script>';
    }
    return $out;
}

function render_layout(array $head, string $content, ?int $status = null): void
{
    if ($status) {
        http_response_code($status);
    }
    $head = apply_seo($head, get_page_seo(request_path()));
    $settings = get_public_settings();
    $js = [
        'base' => base_path(),
        'supabaseUrl' => cfg('supabase_url'),
        'supabaseKey' => cfg('supabase_key'),
        'consentRequired' => !empty($settings['cookie_consent_enabled']),
        'ga' => !empty($settings['ga_enabled']) ? ($settings['ga_measurement_id'] ?? null) : null,
        'clarity' => !empty($settings['clarity_enabled']) ? ($settings['clarity_project_id'] ?? null) : null,
        'gtm' => !empty($settings['gtm_enabled']) ? ($settings['gtm_container_id'] ?? null) : null,
        'recaptchaKey' => !empty($settings['recaptcha_enabled']) ? ($settings['recaptcha_site_key'] ?? null) : null,
    ];
    $v = @filemtime(__DIR__ . '/../assets/css/app.css') ?: 1;
    ?><!DOCTYPE html>
<html lang="en">
<head>
<?= render_head($head) ?>

<link rel="icon" href="<?= url('/favicon.ico') ?>" type="image/x-icon">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&amp;family=Barlow:wght@400;500;600;700&amp;display=swap">
<link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>?v=<?= $v ?>">
</head>
<body>
<div id="app" class="flex min-h-screen flex-col">
<?= site_header() ?>
<main class="flex-1"><?= $content ?></main>
<?= site_footer() ?>
</div>
<div id="cookie-consent" class="fixed inset-x-0 bottom-0 z-50 hidden border-t border-border bg-card px-6 py-5 shadow-panel">
  <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4">
    <p class="max-w-2xl text-sm">We use optional analytics cookies to understand visits and improve our website.</p>
    <div class="flex gap-2">
      <button type="button" data-consent="declined" class="<?= BTN_BASE ?> border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2">Decline</button>
      <button type="button" data-consent="accepted" class="<?= BTN_BASE ?> bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2">Accept analytics</button>
    </div>
  </div>
</div>
<script>window.NG = <?= json_encode($js, JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= url('/assets/js/app.js') ?>?v=<?= @filemtime(__DIR__ . '/../assets/js/app.js') ?: 1 ?>" defer></script>
</body>
</html>
<?php
}

/* ---------- Layout building blocks (SiteLayout.tsx) ---------- */

function page_header(string $eyebrow, string $title, string $intro): string
{
    return '<section class="surface-steel"><div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-20">'
        . '<p class="text-xs font-semibold uppercase tracking-[0.22em] text-highlight">' . e($eyebrow) . '</p>'
        . '<h1 class="mt-4 max-w-3xl text-3xl leading-tight sm:text-4xl lg:text-5xl">' . e($title) . '</h1>'
        . '<p class="mt-5 max-w-2xl text-base text-steel-foreground/75 lg:text-lg">' . e($intro) . '</p></div></section>';
}

function section(string $inner, string $class = ''): string
{
    return '<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-20 ' . $class . '">' . $inner . '</section>';
}

function contact_strip(string $source): string
{
    $s = site();
    ob_start(); ?>
    <div class="grid gap-3 sm:grid-cols-3">
      <a href="<?= e($s['phoneHref']) ?>" data-track="call_click" data-label="<?= e($source) ?>" class="flex items-center gap-3 rounded-md border border-border bg-card p-4 transition-colors hover:border-primary">
        <?= icon('phone', 'h-5 w-5 text-primary') ?>
        <span><span class="block text-xs uppercase tracking-wider text-muted-foreground">Call</span><span class="block text-sm font-semibold"><?= e($s['phone']) ?></span></span>
      </a>
      <a href="<?= e($s['whatsappHref']) ?>" target="_blank" rel="noreferrer" data-track="whatsapp_click" data-label="<?= e($source) ?>" class="flex items-center gap-3 rounded-md border border-border bg-card p-4 transition-colors hover:border-primary">
        <?= icon('message-circle', 'h-5 w-5 text-success') ?>
        <span><span class="block text-xs uppercase tracking-wider text-muted-foreground">WhatsApp</span><span class="block text-sm font-semibold">Chat with sales</span></span>
      </a>
      <a href="<?= e($s['emailHref']) ?>" data-track="email_click" data-label="<?= e($source) ?>" class="flex items-center gap-3 rounded-md border border-border bg-card p-4 transition-colors hover:border-primary">
        <?= icon('mail', 'h-5 w-5 text-highlight') ?>
        <span><span class="block text-xs uppercase tracking-wider text-muted-foreground">Email</span><span class="block text-sm font-semibold"><?= e($s['email']) ?></span></span>
      </a>
    </div>
    <?php return ob_get_clean();
}

function lead_form(string $sourcePage, ?string $productName = null): string
{
    $interests = ['Industrial valves', 'Pipe & tubing fittings', 'Valve automation', 'Spares & servicing', 'Other'];
    $options = ($productName && !in_array($productName, $interests, true)) ? array_merge([$productName], $interests) : $interests;
    $selected = $productName ?? $interests[0];
    $field = 'mt-1.5 w-full rounded-sm border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring';
    ob_start(); ?>
    <div data-lead-wrap>
      <div data-lead-done class="hidden rounded-md border border-success/40 bg-card p-8 text-center shadow-panel">
        <h3 class="text-xl">Thank you — enquiry received</h3>
        <p class="mt-3 text-sm text-muted-foreground">An application engineer will review your requirement and reply within one working day.</p>
        <button type="button" data-lead-again class="mt-6 rounded-sm border border-input px-4 py-2 text-sm font-medium transition-colors hover:bg-accent">Send another enquiry</button>
      </div>
      <form data-lead-form action="<?= url('/enquiry') ?>" method="post" class="rounded-md border border-border bg-card p-6 shadow-panel sm:p-8">
        <input type="hidden" name="source_page" value="<?= e($sourcePage) ?>">
        <h3 class="text-xl">Request a quotation</h3>
        <p class="mt-2 text-sm text-muted-foreground">Share your specification — size, class, media and quantity — and we will revert with availability and pricing.</p>
        <p data-lead-error class="mt-4 hidden rounded-sm border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive"></p>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
          <label class="block text-sm font-medium">Name*<input name="name" required maxlength="100" autocomplete="name" class="<?= $field ?>"></label>
          <label class="block text-sm font-medium">Company<input name="company" maxlength="500" autocomplete="organization" class="<?= $field ?>"></label>
          <label class="block text-sm font-medium">Phone*<input name="phone" required minlength="5" maxlength="40" type="tel" autocomplete="tel" class="<?= $field ?>"></label>
          <label class="block text-sm font-medium">Email<input name="email" type="email" maxlength="255" autocomplete="email" class="<?= $field ?>"></label>
          <label class="block text-sm font-medium sm:col-span-2">Requirement
            <select name="product_interest" class="<?= $field ?>">
              <?php foreach ($options as $o): ?><option value="<?= e($o) ?>"<?= $o === $selected ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
            </select>
          </label>
          <label class="block text-sm font-medium sm:col-span-2">Specification / message
            <textarea name="message" rows="4" maxlength="10000" placeholder="e.g. 6 inch ball valve, Class 300, SS316, flanged, qty 12" class="<?= $field ?>"></textarea>
          </label>
        </div>
        <label class="hidden" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
        <input type="hidden" name="referrer" value="">
        <input type="hidden" name="utm_source" value=""><input type="hidden" name="utm_medium" value=""><input type="hidden" name="utm_campaign" value="">
        <div data-recaptcha class="mt-4 overflow-x-auto"></div>
        <button type="submit" class="mt-6 w-full rounded-sm bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60">Send enquiry</button>
      </form>
    </div>
    <?php return ob_get_clean();
}

function breadcrumb_chevron(): string
{
    return icon('chevron-right', 'size-3');
}
