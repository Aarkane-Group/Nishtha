<?php
function fmt_date(string $iso): string
{
    $t = strtotime($iso);
    return $t ? date('j F Y', $t) : '';
}

function page_blog_index(): void
{
    $s = site();
    $head = page_head("Industrial Valve Insights & Guides | {$s['name']}", 'Practical guidance on industrial valve selection, automation, severe service applications, maintenance and steam engineering from Nishtha Engineering Services.', '/blog');
    $posts = get_published_posts();
    $cards = '';
    foreach ($posts as $p) {
        $cards .= '<article class="overflow-hidden rounded-md border border-border bg-card shadow-panel"><img src="' . e(url($p['cover_image'] ?: '/images/gallery2-1.png')) . '" alt="' . e($p['title']) . '" class="aspect-[16/9] w-full object-cover">'
            . '<div class="p-6"><p class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">' . icon('calendar-days', 'h-3.5 w-3.5') . e(fmt_date($p['published_at'])) . '</p>'
            . '<h2 class="mt-3 text-xl">' . e($p['title']) . '</h2><p class="mt-3 line-clamp-3 text-sm text-muted-foreground">' . e($p['excerpt'] ?? '') . '</p>'
            . '<a href="' . url('/blog/' . rawurlencode($p['slug'])) . '" class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-primary">Read article ' . icon('arrow-right') . '</a></div></article>';
    }
    $content = page_header('Technical insights', 'Industrial valve knowledge, made practical', 'Selection guidance and field-focused articles for engineering, procurement and maintenance teams.')
        . section('<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">' . $cards . '</div>' . (!$posts ? '<p class="text-muted-foreground">Our first technical article is being prepared.</p>' : ''));
    render_layout($head, $content);
}

function page_blog_post(string $slug): void
{
    $s = site();
    $p = get_published_post($slug);
    if (!$p) {
        $target = get_legacy_redirect('/blog/' . $slug);
        if ($target) {
            redirect($target['new_path'], (int) ($target['status_code'] ?: 301));
        }
        page_not_found();
        return;
    }
    $title = $p['meta_title'] ?: "{$p['title']} | {$s['name']}";
    $desc = $p['meta_description'] ?: ($p['excerpt'] ?: 'Industrial valve insight from Nishtha Engineering Services.');
    $canonical = $p['canonical_url'] ?: "{$s['url']}/blog/{$p['slug']}";
    $og = ['og:title' => $p['og_title'] ?: $title, 'og:description' => $p['og_description'] ?: $desc, 'og:type' => 'article', 'og:url' => $canonical, 'twitter:card' => 'summary_large_image'];
    $img = $p['og_image'] ?: $p['cover_image'];
    if ($img && str_starts_with($img, 'https://')) {
        $og['og:image'] = $img;
        $og['twitter:image'] = $img;
    }
    $head = [
        'title' => $title, 'description' => $desc, 'canonical' => $canonical, 'og' => $og,
        'robots' => ($p['robots_index'] ? 'index' : 'noindex') . ', ' . ($p['robots_follow'] ? 'follow' : 'nofollow'),
        'jsonld' => ($p['schema_type'] ?? '') === 'None' ? [] : [[
            '@context' => 'https://schema.org', '@type' => $p['schema_type'] ?: 'Article', 'headline' => $p['title'], 'description' => $desc,
            'datePublished' => $p['published_at'], 'author' => ['@type' => 'Person', 'name' => $p['author_name'] ?: $s['name']],
            'publisher' => ['@type' => 'Organization', 'name' => $s['legalName']],
        ]],
    ];
    $gallery = '';
    foreach (string_list($p['gallery'] ?? []) as $i => $src) {
        $gallery .= '<img src="' . e(url($src)) . '" alt="' . e($p['title'] . ' ' . ($i + 1)) . '" class="w-full object-contain">';
    }
    $body = ($p['content_format'] ?? '') === 'html'
        ? '<div class="rich-content mt-8">' . $p['content'] . '</div>'
        : '<div class="mt-8 whitespace-pre-line text-base leading-8 text-muted-foreground">' . e($p['content']) . '</div>';
    $content = '<article><header class="surface-steel"><div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:py-20"><a href="' . url('/blog') . '" class="inline-flex items-center gap-2 text-sm text-steel-foreground/75">' . icon('arrow-left', 'size-4') . 'All articles</a>'
        . '<h1 class="mt-7 text-3xl leading-tight sm:text-5xl">' . e($p['title']) . '</h1>'
        . '<p class="mt-5 inline-flex items-center gap-2 text-sm text-steel-foreground/70">' . icon('calendar-days', 'size-4') . e(fmt_date($p['published_at'])) . ($p['author_name'] ? ' · ' . e($p['author_name']) : '') . '</p></div></header>'
        . ($p['cover_image'] ? '<img src="' . e(url($p['cover_image'])) . '" alt="' . e($p['title']) . '" class="mx-auto mt-10 aspect-[16/7] w-full max-w-5xl rounded-md object-cover px-4 sm:px-6">' : '')
        . '<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6"><p class="text-lg font-medium leading-relaxed">' . e($p['excerpt'] ?? '') . '</p>' . $body
        . '<div class="mt-8 grid gap-4 sm:grid-cols-2">' . $gallery . '</div></div></article>';
    render_layout($head, $content);
}
