<?php
/** Data access mirroring src/lib/*.functions.ts, reading from the live database. */

function get_catalogue(): array
{
    static $c;
    return $c ??= [
        'categories' => sb_get('product_categories?select=*&published=eq.true&order=sort_order'),
        'types'      => sb_get('product_types?select=*&published=eq.true&order=sort_order'),
        'variants'   => sb_get('product_variants?select=*&published=eq.true&order=sort_order'),
    ];
}

function find_by(array $rows, callable $fn): ?array
{
    foreach ($rows as $r) {
        if ($fn($r)) {
            return $r;
        }
    }
    return null;
}

function get_catalogue_detail(string $cat, ?string $type = null, ?string $variant = null): ?array
{
    $cg = get_catalogue();
    $category = find_by($cg['categories'], fn($r) => $r['slug'] === $cat);
    $t = $type !== null ? find_by($cg['types'], fn($r) => $r['category_id'] === ($category['id'] ?? null) && $r['slug'] === $type) : null;
    $v = $variant !== null ? find_by($cg['variants'], fn($r) => $r['product_type_id'] === ($t['id'] ?? null) && $r['slug'] === $variant) : null;
    if (!$category || ($type !== null && !$t) || ($variant !== null && !$v)) {
        return null;
    }
    $owner = $v ?? $t ?? $category;
    foreach ([&$category, &$t] as &$rec) {
        if (is_array($rec) && is_array($rec['sections'] ?? null)) {
            foreach ($rec['sections'] as &$s) {
                if (is_array($s) && isset($s['html']) && is_string($s['html'])) {
                    $s['html'] = clean_html($s['html']);
                }
            }
            unset($s);
        }
    }
    unset($rec);
    $owner = $v ?? $t ?? $category;
    $ownerType = $v ? 'product_variant' : ($t ? 'product_type' : 'category');
    $faqs = sb_get('content_faqs?select=question,answer&owner_type=eq.' . $ownerType . '&owner_id=eq.' . rawurlencode($owner['id']) . '&published=eq.true&order=sort_order');
    // Section-based pages take their FAQs from the visible faq sections instead.
    if (is_array($owner['sections'] ?? null)) {
        $faqs = [];
        foreach ($owner['sections'] as $s) {
            if (is_array($s) && ($s['kind'] ?? '') === 'faq' && !empty($s['visible']) && is_array($s['rows'] ?? null)) {
                foreach ($s['rows'] as $row) {
                    if (is_array($row) && is_string($row['label'] ?? null) && is_string($row['value'] ?? null)) {
                        $faqs[] = ['question' => $row['label'], 'answer' => $row['value']];
                    }
                }
            }
        }
    }
    return ['catalogue' => $cg, 'category' => $category, 'type' => $t, 'variant' => $v, 'faqs' => $faqs];
}

function get_legacy_redirect(string $path): ?array
{
    $rows = sb_get('slug_redirects?select=new_path,status_code&active=eq.true&old_path=eq.' . rawurlencode(rtrim($path, '/')) . '&limit=1');
    return $rows[0] ?? null;
}

function get_website_content(): array
{
    static $w;
    if ($w === null) {
        $rows = sb_get('site_settings?select=website_content&id=eq.1');
        $w = is_array($rows[0]['website_content'] ?? null) ? $rows[0]['website_content'] : [];
    }
    return $w;
}

function get_public_settings(): array
{
    static $s;
    return $s ??= (sb_get('site_settings?select=ga_measurement_id,ga_enabled,clarity_project_id,clarity_enabled,gtm_container_id,gtm_enabled,google_site_verification,bing_site_verification,cookie_consent_enabled,recaptcha_enabled,recaptcha_site_key&id=eq.1')[0] ?? []);
}

function get_page_seo(string $path): ?array
{
    $rows = sb_get('page_seo?select=path,meta_title,meta_description,keywords,og_image,canonical_url,noindex,og_title,og_description,robots_follow,focus_keyword,schema_type&path=eq.' . rawurlencode($path) . '&limit=1');
    return $rows[0] ?? null;
}

function published_filter(): string
{
    $now = rawurlencode(gmdate('Y-m-d\TH:i:s\Z'));
    return 'published=eq.true&published_at=lte.' . $now . '&or=(scheduled_at.is.null,scheduled_at.lte.' . $now . ')';
}

function get_published_posts(): array
{
    return sb_get('blog_posts?select=*&' . published_filter() . '&order=published_at.desc');
}

function get_published_post(string $slug): ?array
{
    $rows = sb_get('blog_posts?select=*&slug=eq.' . rawurlencode($slug) . '&' . published_filter() . '&limit=1');
    $p = $rows[0] ?? null;
    if ($p && ($p['content_format'] ?? '') === 'html') {
        $p['content'] = clean_html($p['content']);
    }
    return $p;
}

/** Static engineering-service pages (from src/data/catalogue.ts). */
function services(): array
{
    return [
        'fire-fighting-services' => [
            'slug' => 'fire-fighting-services', 'name' => 'Fire Fighting Services', 'image' => '/images/8-e1718791125460.jpg',
            'summary' => 'Design, supply, installation and maintenance of fire fighting systems — hydrant, sprinkler, deluge and foam systems with the right valves and fittings.',
            'points' => ['Fire hydrant & sprinkler systems', 'Deluge and foam systems', 'Fire-rated valves, landing valves and fittings', 'Annual maintenance contracts and testing'],
            'title' => 'Fire Fighting Services in Vadodara', 'alt' => 'Industrial fire fighting system installation',
        ],
        'insulation-services' => [
            'slug' => 'insulation-services', 'name' => 'Insulation Services', 'image' => '/images/Nishtha-Engg-12-scaled.jpg',
            'summary' => 'Hot and cold insulation for pipelines, valves, tanks and equipment to save energy, protect personnel and control process temperatures.',
            'points' => ['Hot insulation for steam and process lines', 'Cold & cryogenic insulation', 'Removable valve insulation jackets', 'Aluminium / SS cladding'],
            'title' => 'Industrial Insulation Services in Vadodara', 'alt' => 'Industrial piping insulation work',
        ],
    ];
}
