<?php
/** Shared helpers: URLs, escaping, HTML sanitising, SEO head model. */

/** Sub-folder the app is served from (e.g. "/nishtha" under XAMPP), "" at the web root. */
function base_path(): string
{
    static $b;
    return $b ??= rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
}

/** Prefix root-relative paths with the base path so links work in a sub-folder. */
function url(string $path = '/'): string
{
    if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//')) {
        return $path;
    }
    return base_path() . $path;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function origin(): string
{
    $configured = cfg('site_url');
    if ($configured) {
        return rtrim($configured, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
}

/** Request path relative to the app root, without query string or trailing slash. */
function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = rawurldecode($path);
    $base = base_path();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . trim($path, '/');
    return $path;
}

function redirect(string $to, int $status = 301): never
{
    header('Location: ' . (preg_match('#^https?://#i', $to) ? $to : url($to)), true, $status);
    exit;
}

/** Company details: static defaults overlaid with the live site_settings.website_content.company. */
function site(): array
{
    static $s;
    if ($s === null) {
        $s = cfg('site');
        $company = get_website_content()['company'] ?? [];
        foreach (['name', 'legalName', 'logo', 'email', 'phone', 'address'] as $k) {
            if (!empty($company[$k]) && is_string($company[$k])) {
                $s[$k] = $company[$k];
            }
        }
        $s['phoneHref'] = 'tel:' . preg_replace('/[^+0-9]/', '', $s['phone']);
        $s['emailHref'] = 'mailto:' . $s['email'];
        $s['url'] = origin();
    }
    return $s;
}

function safe_href(string $href): string
{
    return preg_match('#^(https?://|mailto:|tel:|/(?!/)|\#)#i', $href) ? $href : '#';
}

/** Like safe_href but also rebases root-relative paths for sub-folder installs. */
function href(string $href): string
{
    return url(safe_href($href));
}

function string_list($value): array
{
    return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
}

function spec_list($value): array
{
    $out = [];
    if (is_array($value)) {
        foreach ($value as $i) {
            if (is_array($i) && is_string($i['label'] ?? null) && is_string($i['value'] ?? null)) {
                $out[] = ['label' => $i['label'], 'value' => $i['value']];
            }
        }
    }
    return $out;
}

/** Allow-list HTML sanitiser equivalent to src/lib/html.server.ts (sanitize-html). */
function clean_html(string $html): string
{
    if (trim($html) === '') {
        return '';
    }
    $allowed = array_flip(['address', 'article', 'aside', 'footer', 'header', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hgroup', 'main', 'nav', 'section', 'blockquote', 'dd', 'div', 'dl', 'dt', 'figcaption', 'figure', 'hr', 'li', 'ol', 'p', 'pre', 'ul', 'a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'dfn', 'em', 'i', 'kbd', 'mark', 'q', 'rb', 'rp', 'rt', 'rtc', 'ruby', 's', 'samp', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'time', 'u', 'var', 'wbr', 'caption', 'col', 'colgroup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'img']);
    $dropWithContent = array_flip(['script', 'style', 'iframe', 'object', 'embed', 'noscript', 'textarea', 'template', 'svg', 'math', 'form', 'select', 'option', 'button', 'input']);
    $attrs = ['a' => ['href', 'title', 'target', 'rel'], 'img' => ['src', 'alt', 'width', 'height'], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan']];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) {
        return e(strip_tags($html));
    }

    $walk = function (DOMNode $node) use (&$walk, $allowed, $dropWithContent, $attrs, $doc): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (!($child instanceof DOMElement)) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (isset($dropWithContent[$tag])) {
                $node->removeChild($child);
                continue;
            }
            $walk($child);
            if (!isset($allowed[$tag])) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                $ok = $name === 'class' || in_array($name, $attrs[$tag] ?? [], true);
                if ($ok && in_array($name, ['href', 'src'], true) && !preg_match('#^(https?:|mailto:|tel:|/(?!/)|\#)#i', trim($attr->value))) {
                    $ok = false;
                }
                if (!$ok) {
                    $child->removeAttribute($attr->name);
                }
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $doc->saveHTML($c);
    }
    return $out;
}

/** HTML-ish text to paragraphs (mirrors plainToHtml). */
function plain_to_html(string $text): string
{
    return implode('', array_map(fn($p) => '<p>' . nl2br(e($p), false) . '</p>', preg_split('/\n\n+/', $text)));
}

/**
 * Page head model. Keys: title, description, og (assoc), robots, canonical, jsonld (list), og_type.
 * Applies admin-managed per-page SEO overrides (page_seo) like withSeo() in src/lib/seo.ts.
 */
function apply_seo(array $head, ?array $seo): array
{
    if (!$seo) {
        return $head;
    }
    if (!empty($seo['meta_title'])) {
        $head['title'] = $seo['meta_title'];
    }
    if (!empty($seo['meta_description'])) {
        $head['description'] = $seo['meta_description'];
        $head['og']['og:description'] = $seo['og_description'] ?: $seo['meta_description'];
        $head['og']['twitter:description'] = $seo['meta_description'];
    } elseif (!empty($seo['og_description'])) {
        $head['og']['og:description'] = $seo['og_description'];
    }
    if (!empty($seo['og_title']) || !empty($seo['meta_title'])) {
        $head['og']['og:title'] = $seo['og_title'] ?: $seo['meta_title'];
    }
    if (!empty($seo['meta_title'])) {
        $head['og']['twitter:title'] = $seo['meta_title'];
    }
    if (!empty($seo['keywords'])) {
        $head['keywords'] = $seo['keywords'];
    }
    if (!empty($seo['og_image']) && str_starts_with($seo['og_image'], 'https://')) {
        $head['og']['og:image'] = $seo['og_image'];
        $head['og']['twitter:image'] = $seo['og_image'];
    }
    if (!empty($seo['noindex'])) {
        $head['robots'] = 'noindex, ' . (($seo['robots_follow'] ?? true) ? 'follow' : 'nofollow');
    } elseif (array_key_exists('robots_follow', $seo) && !$seo['robots_follow']) {
        $head['robots'] = 'index, nofollow';
    }
    if (!empty($seo['canonical_url'])) {
        $head['canonical'] = $seo['canonical_url'];
    }
    return $head;
}

/** Standard head for a public static page. */
function page_head(string $title, string $description, string $path): array
{
    $canonical = site()['url'] . ($path === '/' ? '' : $path);
    return [
        'title' => $title,
        'description' => $description,
        'canonical' => $canonical,
        'og' => [
            'og:title' => $title,
            'og:description' => $description,
            'og:type' => 'website',
            'og:url' => $canonical,
            'twitter:card' => 'summary_large_image',
        ],
        'jsonld' => [],
    ];
}
