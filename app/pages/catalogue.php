<?php
/** Category / product-type / variant pages (CataloguePage.tsx + ProductSections.tsx). */

function accordion(array $items): string
{
    $html = '<div class="mt-6 max-w-4xl" data-accordion>';
    foreach ($items as $i => [$q, $a]) {
        $html .= '<div class="border-b"><h3 class="flex"><button type="button" data-acc-trigger aria-expanded="false" class="flex flex-1 items-center justify-between py-4 text-sm font-medium cursor-pointer transition-all hover:underline text-left">' . e($q)
            . icon('chevron-down', 'h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200') . '</button></h3>'
            . '<div data-acc-panel class="hidden overflow-hidden text-sm"><div class="pb-4 pt-0">' . e($a) . '</div></div></div>';
    }
    return $html . '</div>';
}

function catalogue_head(?array $d, string $path): array
{
    $s = site();
    if (!$d) {
        return ['title' => 'Product unavailable | Nishtha Global', 'robots' => 'noindex', 'og' => [], 'jsonld' => []];
    }
    $rec = $d['variant'] ?? $d['type'] ?? $d['category'];
    $title = $rec['meta_title'] ?: "{$rec['name']} | Industrial Valve Solutions | {$s['name']}";
    $desc = $rec['meta_description'] ?: ($rec['short_description'] ?: 'Explore ' . strtolower($rec['name']) . ' from Nishtha Engineering Services, Vadodara. Request product information, application guidance, availability and a quotation.');
    $canonical = $rec['canonical_url'] ?: $s['url'] . $path;
    $og = [
        'og:title' => $rec['og_title'] ?: $title, 'og:description' => $rec['og_description'] ?: $desc, 'og:type' => 'website',
        'og:url' => $canonical, 'twitter:card' => 'summary_large_image',
    ];
    if (str_starts_with((string) ($rec['og_image'] ?? ''), 'https://')) {
        $og['og:image'] = $rec['og_image'];
        $og['twitter:image'] = $rec['og_image'];
    }
    $crumbs = [['Home', $s['url']], ['Products', $s['url'] . '/products'], [$d['category']['name'], $s['url'] . '/products/' . $d['category']['slug']]];
    if ($d['type']) {
        $crumbs[] = [$d['type']['name'], $s['url'] . '/products/' . $d['category']['slug'] . '/' . $d['type']['slug']];
    }
    if ($d['variant']) {
        $crumbs[] = [$d['variant']['name'], $s['url'] . $path];
    }
    $schemas = [
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]], $crumbs, array_keys($crumbs))],
        ['@context' => 'https://schema.org', '@type' => $rec['schema_type'] ?: ($d['type'] ? 'Product' : 'CollectionPage'), 'name' => $rec['name'], 'description' => $desc, 'url' => $canonical],
    ];
    if ($d['faqs']) {
        $schemas[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']]], $d['faqs'])];
    }
    return [
        'title' => $title, 'description' => $desc, 'canonical' => $canonical, 'og' => $og,
        'robots' => ($rec['robots_index'] ? 'index' : 'noindex') . ', ' . ($rec['robots_follow'] ? 'follow' : 'nofollow'),
        'jsonld' => ($rec['schema_type'] ?? '') === 'None' ? [] : $schemas,
    ];
}

function breadcrumb(array $d, bool $withPadding): string
{
    $c = $d['category'];
    $html = '<div class="border-b border-border bg-muted/50"><nav aria-label="Breadcrumb" class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 py-4 text-xs text-muted-foreground sm:px-6">'
        . '<a href="' . url('/') . '">Home</a>' . breadcrumb_chevron() . '<a href="' . url('/products') . '">Products</a>' . breadcrumb_chevron()
        . '<a href="' . url('/products/' . rawurlencode($c['slug'])) . '">' . e($c['name']) . '</a>';
    if ($d['type']) {
        $html .= breadcrumb_chevron();
        $html .= $d['variant']
            ? '<a href="' . url('/products/' . rawurlencode($c['slug']) . '/' . rawurlencode($d['type']['slug'])) . '">' . e($d['type']['name']) . '</a>'
            : ($withPadding ? '<a href="' . url('/products/' . rawurlencode($c['slug']) . '/' . rawurlencode($d['type']['slug'])) . '">' . e($d['type']['name']) . '</a>' : '<span>' . e($d['type']['name']) . '</span>');
    }
    if ($d['variant']) {
        $html .= breadcrumb_chevron() . '<span aria-current="page">' . e($d['variant']['name']) . '</span>';
    }
    return $html . '</nav></div>';
}

function product_card(array $child, string $href, string $cta, ?string $image): string
{
    return '<article class="group border border-border bg-card p-6 transition-shadow hover:shadow-lift">'
        . ($image ? '<div class="aspect-[4/3] w-full"><img src="' . e(url($image)) . '" alt="' . e($child['name']) . '" class="h-full w-full object-contain p-3" loading="lazy"></div>' : '')
        . '<h3 class="mt-6 text-xl">' . e($child['name']) . '</h3>'
        . (!empty($child['short_description']) ? '<p class="mt-3 text-sm text-muted-foreground">' . e($child['short_description']) . '</p>' : '')
        . '<a href="' . e($href) . '" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-primary">' . e($cta) . ' ' . icon('arrow-right') . '</a></article>';
}

function image_panel(array $images, string $alt, ?string $main = null): string
{
    $main ??= $images[0];
    $html = '<div data-gallery><div class="flex aspect-[4/3] items-center justify-center bg-card p-8"><img data-gallery-main src="' . e(url($main)) . '" alt="' . e($alt) . '" class="h-full w-full object-contain"></div>';
    if (count($images) > 1) {
        $html .= '<div class="mt-3 flex flex-wrap gap-3">';
        foreach ($images as $i => $src) {
            $html .= '<button type="button" data-gallery-thumb="' . e(url($src)) . '" aria-label="Show ' . e($alt) . ' image" class="size-20 border bg-card p-1 ' . ($src === $main ? 'border-primary' : 'border-border') . '"><img src="' . e(url($src)) . '" alt="" class="h-full w-full object-contain"></button>';
        }
        $html .= '</div>';
    }
    return $html . '</div>';
}

/** Section-driven layout used when a category/type has editor-managed sections. */
function product_sections(array $sections, array $d): string
{
    $cat = $d['category'];
    $type = $d['type'];
    $rec = $type ?? $cat;
    $out = '';
    foreach ($sections as $s) {
        if (!is_array($s) || empty($s['visible'])) {
            continue;
        }
        $kind = $s['kind'] ?? '';
        $title = (string) ($s['title'] ?? '');
        $html = $s['html'] ?? null;
        $richOn = !empty($s['html_enabled']);

        if ($kind === 'hero') {
            $imgs = string_list($s['images'] ?? []);
            $btns = '';
            foreach ($s['buttons'] ?? [] as $b) {
                $btns .= '<a href="' . e(href($b['href'] ?? '#')) . '" class="' . BTN_PRIMARY_LG . '">' . e($b['label'] ?? '') . icon('arrow-right') . '</a>';
            }
            $body = $richOn ? '<div class="rich-content mt-6 text-steel-foreground/75">' . ($html ?? '') . '</div>'
                : (!empty($s['text']) ? '<p class="mt-6 max-w-2xl text-lg leading-relaxed text-steel-foreground/75">' . e($s['text']) . '</p>' : '');
            $out .= '<section class="surface-steel"><div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:py-20 ' . ($imgs ? 'lg:grid-cols-[1.1fr_0.9fr]' : '') . '"><div class="self-center">'
                . '<p class="text-xs font-semibold uppercase text-highlight">' . e($type ? $cat['name'] : 'Product range') . '</p>'
                . '<h1 class="mt-4 max-w-3xl text-4xl leading-tight lg:text-5xl">' . e($title) . '</h1>' . $body
                . '<div class="mt-8 flex flex-wrap gap-3">' . $btns . '</div></div>'
                . ($imgs ? '<div>' . image_panel($imgs, $rec['name']) . '</div>' : '') . '</div></section>';
            continue;
        }
        if ($kind === 'enquiry') {
            $body = $richOn ? '<div class="rich-content mt-5 text-muted-foreground">' . ($html ?? '') . '</div>' : '<p class="mt-5 max-w-lg text-muted-foreground">' . e($s['text'] ?? '') . '</p>';
            $out .= '<section id="quotation" class="scroll-mt-24 border-t border-border bg-muted/50">' . section('<div class="grid gap-12 lg:grid-cols-2"><div><p class="text-xs font-semibold uppercase text-primary">Application engineering</p><h2 class="mt-4 text-3xl">' . e($title) . '</h2>' . $body . '</div>'
                . lead_form('products/' . $cat['slug'] . '/' . ($type['slug'] ?? ''), $rec['name']) . '</div>') . '</section>';
            continue;
        }
        if ($kind === 'range') {
            $cards = '';
            foreach ($d['catalogue']['types'] as $t) {
                if ($t['category_id'] === $cat['id']) {
                    $cards .= product_card($t, url('/products/' . rawurlencode($cat['slug']) . '/' . rawurlencode($t['slug'])), 'Explore type', $t['image_url'] ?? null);
                }
            }
            $out .= section('<div id="range"><h2 class="text-3xl">' . e($title) . '</h2><div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">' . $cards . '</div></div>');
            continue;
        }
        if ($richOn && $html !== null && $kind !== 'html') {
            $out .= section('<h2 class="text-2xl">' . e($title) . '</h2><div class="rich-content mt-6">' . $html . '</div>');
            continue;
        }
        if (($kind === 'text' && empty($s['text'])) || ($kind === 'html' && empty($s['html'])) || ($kind === 'list' && empty($s['items'])) || (in_array($kind, ['table', 'faq'], true) && empty($s['rows']))) {
            continue;
        }
        $c = '<h2 class="text-2xl">' . e($title) . '</h2>';
        switch ($kind) {
            case 'text':
                $c .= '<p class="mt-6 max-w-3xl whitespace-pre-line leading-relaxed text-muted-foreground">' . e($s['text']) . '</p>';
                break;
            case 'html':
                $c .= '<div class="rich-content mt-6">' . $s['html'] . '</div>';
                break;
            case 'list':
                $c .= '<ul class="mt-6 grid gap-4 sm:grid-cols-2">' . implode('', array_map(fn($i) => '<li class="flex gap-3 border-b border-border py-4">' . icon('check', 'size-5 shrink-0 text-primary') . e($i) . '</li>', $s['items'])) . '</ul>';
                break;
            case 'table':
                $c .= '<dl class="mt-6 max-w-4xl divide-y divide-border">' . implode('', array_map(fn($r) => '<div class="grid gap-2 py-4 sm:grid-cols-[0.5fr_1fr]"><dt class="font-semibold">' . e($r['label'] ?? '') . '</dt><dd class="text-muted-foreground">' . e($r['value'] ?? '') . '</dd></div>', $s['rows'])) . '</dl>';
                break;
            case 'faq':
                $c .= accordion(array_map(fn($r) => [$r['label'] ?? '', $r['value'] ?? ''], $s['rows']));
                break;
            case 'gallery':
                $c .= '<div class="mt-6 grid gap-4 sm:grid-cols-3">' . implode('', array_map(fn($src, $i) => '<img src="' . e(url($src)) . '" alt="' . e($rec['name'] . ' ' . ($i + 1)) . '" class="aspect-[4/3] w-full object-contain">', string_list($s['images'] ?? []), array_keys(string_list($s['images'] ?? [])))) . '</div>';
                break;
            case 'documents':
            case 'related':
                $c .= '<ul class="mt-6 space-y-3">' . implode('', array_map(fn($b) => '<li><a class="text-primary underline" href="' . e(href($b['href'] ?? '#')) . '">' . e($b['label'] ?? '') . '</a></li>', $s['buttons'] ?? [])) . '</ul>';
                break;
        }
        $out .= $kind === 'table' ? '<section class="bg-muted">' . section($c) . '</section>' : section($c);
    }
    return $out;
}

function render_catalogue(?array $d, string $path): void
{
    if (!$d) {
        $head = catalogue_head(null, $path);
        render_layout($head, section('<h1 class="text-3xl">Product unavailable</h1><a href="' . url('/products') . '" class="' . BTN_BASE . ' mt-6 bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2">Explore products</a>'), 404);
        return;
    }
    $cat = $d['category'];
    $type = $d['type'];
    $variant = $d['variant'];
    $head = catalogue_head($d, $path);
    $rec = $variant ?? $type ?? $cat;
    $image = $variant['main_image_url'] ?? ($type['image_url'] ?? (!$type ? ($cat['image_url'] ?? null) : null));
    $gallery = (!$variant && $type) ? string_list($type['gallery'] ?? []) : [];
    $children = $type
        ? array_values(array_filter($d['catalogue']['variants'], fn($v) => $v['product_type_id'] === $type['id']))
        : array_values(array_filter($d['catalogue']['types'], fn($t) => $t['category_id'] === $cat['id']));
    $features = string_list($rec['features'] ?? []);
    $specs = spec_list(($variant ?? $type)['specifications'] ?? null);
    $sections = is_array(($type ?? $cat)['sections'] ?? null) ? ($type ?? $cat)['sections'] : [];

    if (!$variant && $sections) {
        render_layout($head, breadcrumb($d, false) . product_sections($sections, $d));
        return;
    }

    ob_start();
    echo breadcrumb($d, true);
    $eyebrow = $variant ? ($type['name'] ?? '') : ($type ? $cat['name'] : 'Product range');
    $images = $gallery && count($gallery) > 1 ? $gallery : ($image ? [$image] : []);
    ?>
    <section class="surface-steel"><div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:py-20 <?= $image ? 'lg:grid-cols-[1.1fr_0.9fr]' : '' ?>"><div class="self-center">
      <p class="text-xs font-semibold uppercase text-highlight"><?= e($eyebrow) ?></p>
      <h1 class="mt-4 max-w-3xl text-4xl leading-tight lg:text-5xl"><?= e($rec['name']) ?></h1>
      <?php if (!empty($rec['short_description'])): ?><p class="mt-6 max-w-2xl text-lg leading-relaxed text-steel-foreground/75"><?= e($rec['short_description']) ?></p><?php endif; ?>
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="#quotation" class="<?= BTN_PRIMARY_LG ?>">Request a quotation <?= icon('arrow-right') ?></a>
        <?php if ($children && !$variant): ?><a href="#range" class="<?= BTN_BASE ?> border shadow-sm h-10 px-8 border-steel-foreground/40 bg-transparent text-steel-foreground hover:bg-steel-foreground/10 hover:text-steel-foreground"><?= $type ? 'Available designs' : 'Explore product types' ?></a><?php endif; ?>
      </div></div>
      <?php if ($image): ?><div><?= image_panel($images ?: [$image], $rec['name'], $image) ?></div><?php endif; ?>
    </div></section>
    <?php
    if (!empty($rec['description'])) {
        echo section('<div class="grid gap-8 lg:grid-cols-[0.5fr_1fr]"><h2 class="text-2xl">Overview</h2><p class="max-w-3xl whitespace-pre-line leading-relaxed text-muted-foreground">' . e($rec['description']) . '</p></div>');
    }
    if (!$variant && $children) {
        $cards = '';
        foreach ($children as $ch) {
            $img = $ch['main_image_url'] ?? $ch['image_url'] ?? null;
            $link = $type
                ? url('/products/' . rawurlencode($cat['slug']) . '/' . rawurlencode($type['slug']) . '/' . rawurlencode($ch['slug']))
                : url('/products/' . rawurlencode($cat['slug']) . '/' . rawurlencode($ch['slug']));
            $cards .= product_card($ch, $link, $type ? 'View design' : 'Explore type', $img);
        }
        echo section('<div id="range" class="scroll-mt-24"><p class="text-xs font-semibold uppercase text-primary">' . ($type ? 'Available designs' : 'Product types') . '</p><h2 class="mt-3 text-3xl">' . e($type ? $type['name'] . ' designs' : 'Explore ' . strtolower($cat['name'])) . '</h2><div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">' . $cards . '</div></div>');
    }
    if ($features) {
        echo section('<h2 class="text-2xl">Key features</h2><ul class="mt-6 grid gap-4 sm:grid-cols-2">' . implode('', array_map(fn($f) => '<li class="flex gap-3 border-b border-border py-4">' . icon('check', 'size-5 shrink-0 text-primary') . '<span>' . e($f) . '</span></li>', $features)) . '</ul>');
    }
    if ($specs) {
        echo '<section class="bg-muted">' . section('<h2 class="text-2xl">Technical specifications</h2><dl class="mt-6 max-w-4xl divide-y divide-border">' . implode('', array_map(fn($sp) => '<div class="grid gap-2 py-4 sm:grid-cols-[0.5fr_1fr]"><dt class="font-semibold">' . e($sp['label']) . '</dt><dd class="text-muted-foreground">' . e($sp['value']) . '</dd></div>', $specs)) . '</dl>') . '</section>';
    }
    foreach ([['Applications', 'applications'], ['Industries served', 'industries'], ['Benefits', 'benefits']] as [$label, $key]) {
        $items = string_list($rec[$key] ?? []);
        if ($items) {
            echo section('<h2 class="text-2xl">' . $label . '</h2><ul class="mt-6 grid gap-4 sm:grid-cols-3">' . implode('', array_map(fn($i) => '<li class="border-l-2 border-primary pl-4 text-muted-foreground">' . e($i) . '</li>', $items)) . '</ul>');
        }
    }
    if ($d['faqs']) {
        echo section('<h2 class="text-2xl">Frequently asked questions</h2>' . accordion(array_map(fn($f) => [$f['question'], $f['answer']], $d['faqs'])));
    }
    echo '<section id="quotation" class="scroll-mt-24 border-t border-border bg-muted/50">' . section('<div class="grid gap-12 lg:grid-cols-2"><div><p class="text-xs font-semibold uppercase text-primary">Application engineering</p><h2 class="mt-4 text-3xl">Specify your requirement.</h2><p class="mt-5 max-w-lg text-muted-foreground">Share your process conditions, required configuration and quantity. Our team will confirm suitable options and available documentation.</p></div>'
        . lead_form('products/' . $cat['slug'] . '/' . ($type['slug'] ?? '') . '/' . ($variant['slug'] ?? ''), $rec['name']) . '</div>') . '</section>';
    render_layout($head, ob_get_clean());
}
