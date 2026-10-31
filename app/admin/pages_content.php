<?php
/** Products & categories, mega menu, blog, SEO + redirects. */

function json_lines_specs(array $specs): string
{
    return implode("\n", array_map(fn($s) => $s['label'] . ' | ' . $s['value'], spec_list($specs)));
}

function parse_specs(string $text): array
{
    $out = [];
    foreach (lines($text) as $l) {
        [$label, $value] = array_pad(array_map('trim', explode('|', $l, 2)), 2, '');
        if ($label !== '') {
            $out[] = ['label' => $label, 'value' => $value];
        }
    }
    return $out;
}

function add_redirect(string $old, string $new): void
{
    if ($old !== $new) {
        sbu('POST', 'slug_redirects?on_conflict=old_path', ['old_path' => $old, 'new_path' => $new, 'active' => true, 'status_code' => 301], ['Prefer: resolution=merge-duplicates,return=minimal']);
    }
}

/* ---------------- Products ---------------- */

function admin_page_products(): void
{
    $cats = sbu_get('product_categories?select=*&order=sort_order');
    $catName = array_column($cats, 'name', 'id');
    $catSlug = array_column($cats, 'slug', 'id');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $kind = post('kind') === 'category' ? 'category' : 'type';
        $table = $kind === 'category' ? 'product_categories' : 'product_types';
        $id = post('id');
        $action = post('action');
        $back = '/admin/products' . ($kind === 'category' ? '?view=categories' : '');
        if ($id !== '' && !preg_match('/^[0-9a-f-]{36}$/', $id)) {
            redirect($back, 303);
        }
        if ($action === 'delete' && $id) {
            [$st, $d] = sbu('DELETE', $table . '?id=eq.' . $id);
            write_result($st, $d === [] ? [['ok' => 1]] : $d, 'Deleted.');
            redirect($back, 303);
        }
        if ($action === 'toggle' && $id) {
            [$st, $d] = sbu('PATCH', $table . '?id=eq.' . $id, ['published' => post_bool('published')]);
            write_result($st, $d, 'Visibility updated.');
            redirect($back, 303);
        }
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', post('slug') ?: post('name')), '-'));
        $sections = post('sections') === '' ? [] : json_decode(post('sections'), true);
        if (!is_array($sections) || post('name') === '' || $slug === '') {
            flash('Name and slug are required, and Sections must be a valid JSON list.', 'err');
            redirect($back . (str_contains($back, '?') ? '&' : '?') . ($id ? 'edit=' . $kind . ':' . $id : 'new=' . $kind), 303);
        }
        $row = [
            'name' => post('name'), 'slug' => $slug, 'short_description' => post_null('short_description'), 'description' => post_null('description'),
            'image_url' => post_null('image_url'), 'published' => post_bool('published'), 'sort_order' => (int) post('sort_order', '0'),
            'menu_label' => post_null('menu_label'), 'menu_href' => post_null('menu_href'), 'menu_visible' => post_bool('menu_visible'),
            'features' => lines(post('features')), 'applications' => lines(post('applications')), 'industries' => lines(post('industries')), 'benefits' => lines(post('benefits')),
            'sections' => $sections,
        ] + seo_payload();
        if ($kind === 'type') {
            $row += ['category_id' => post('category_id'), 'menu_group' => post_null('menu_group'), 'specifications' => parse_specs(post('specifications')), 'gallery' => lines(post('gallery'))];
        }
        if ($id) {
            $old = sbu_get($table . '?select=slug' . ($kind === 'type' ? ',category_id' : '') . '&id=eq.' . $id)[0] ?? null;
            [$st, $d] = sbu('PATCH', $table . '?id=eq.' . $id, $row);
            if (write_result($st, $d, 'Saved.') && $old && $old['slug'] !== $slug) {
                $oldPath = $kind === 'type' ? '/products/' . ($catSlug[$old['category_id']] ?? '') . '/' . $old['slug'] : '/products/' . $old['slug'];
                $newPath = $kind === 'type' ? '/products/' . ($catSlug[$row['category_id']] ?? '') . '/' . $slug : '/products/' . $slug;
                add_redirect($oldPath, $newPath);
            }
            redirect($back . (str_contains($back, '?') ? '&' : '?') . 'edit=' . $kind . ':' . $id, 303);
        }
        [$st, $d] = sbu('POST', $table, $row);
        write_result($st, $d, 'Created.');
        redirect($back, 303);
    }

    $edit = (string) ($_GET['edit'] ?? '');
    $new = (string) ($_GET['new'] ?? '');
    if ($edit !== '' || $new !== '') {
        [$kind, $id] = $edit !== '' ? array_pad(explode(':', $edit, 2), 2, '') : [$new, ''];
        $kind = $kind === 'category' ? 'category' : 'type';
        $table = $kind === 'category' ? 'product_categories' : 'product_types';
        $r = $id && preg_match('/^[0-9a-f-]{36}$/', $id) ? (sbu_get($table . '?select=*&id=eq.' . $id)[0] ?? null) : null;
        if ($id && !$r) {
            flash('Record not found.', 'err');
            redirect('/admin/products', 303);
        }
        $r ??= ['published' => true, 'menu_visible' => true, 'sort_order' => 0, 'robots_index' => true, 'robots_follow' => true, 'schema_type' => $kind === 'type' ? 'Product' : 'CollectionPage'];
        $j = fn($k) => implode("\n", string_list($r[$k] ?? []));
        $basics = '<div class="grid gap-4 sm:grid-cols-2">' . f_input('name', 'Name', $r['name'] ?? '', 'required') . f_input('slug', 'URL slug', $r['slug'] ?? '', 'placeholder="auto from name"')
            . ($kind === 'type' ? f_select('category_id', 'Category', $catName, $r['category_id'] ?? array_key_first($catName)) : '')
            . f_input('image_url', 'Main image path or URL', $r['image_url'] ?? '') . f_input('sort_order', 'Sort order', $r['sort_order'] ?? 0, 'type="number"')
            . '<div class="sm:col-span-2">' . f_input('short_description', 'Short description', $r['short_description'] ?? '') . '</div>'
            . '<div class="sm:col-span-2">' . f_text('description', 'Overview / description', $r['description'] ?? '', 6) . '</div>'
            . '<div class="flex flex-col gap-2">' . f_check('published', 'Published (visible on website)', !empty($r['published'])) . '</div></div>';
        $lists = '<div class="grid gap-4 sm:grid-cols-2">' . f_text('features', 'Key features', $j('features'), 6, '(one per line)') . f_text('benefits', 'Benefits', $j('benefits'), 6, '(one per line)')
            . f_text('applications', 'Applications', $j('applications'), 5, '(one per line)') . f_text('industries', 'Industries served', $j('industries'), 5, '(one per line)')
            . ($kind === 'type' ? f_text('specifications', 'Technical specifications', json_lines_specs($r['specifications'] ?? []), 6, '(Label | Value, one per line)') . f_text('gallery', 'Gallery images', $j('gallery'), 6, '(one path or URL per line)') : '') . '</div>';
        $menu = '<div class="grid gap-4 sm:grid-cols-2">' . f_input('menu_label', 'Menu label (blank = name)', $r['menu_label'] ?? '') . f_input('menu_href', 'Custom menu link (optional)', $r['menu_href'] ?? '')
            . ($kind === 'type' ? f_input('menu_group', 'Menu group heading', $r['menu_group'] ?? '') : '') . '<div class="flex items-end">' . f_check('menu_visible', 'Show in mega menu', !empty($r['menu_visible'])) . '</div></div>';
        $sections = f_text('sections', 'Page sections (JSON list — advanced)', !empty($r['sections']) ? json_encode($r['sections'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '', 14, 'Controls the hero, text, lists, FAQs and enquiry blocks. Leave empty for the standard layout.');
        $public = !empty($r['slug']) ? ($kind === 'type' ? '/products/' . ($catSlug[$r['category_id']] ?? '') . '/' . $r['slug'] : '/products/' . $r['slug']) : '';
        $form = '<form method="post">' . f_csrf() . '<input type="hidden" name="kind" value="' . $kind . '"><input type="hidden" name="id" value="' . e($id) . '"><input type="hidden" name="action" value="save">'
            . a_title(($id ? 'Edit ' : 'New ') . ($kind === 'type' ? 'product' : 'category'), $r['name'] ?? '', '<div class="flex gap-2">' . ($public ? '<a target="_blank" class="' . A_BTN_OUT . '" href="' . url($public) . '">View ↗</a>' : '') . '<a class="' . A_BTN_OUT . '" href="' . url('/admin/products' . ($kind === 'category' ? '?view=categories' : '')) . '">Back</a><button class="' . A_BTN . '">Save</button></div>')
            . '<div class="space-y-6">' . a_card('Basics', $basics) . a_card('Content lists', $lists) . a_card('Mega menu', $menu) . a_card('Search engine (SEO)', seo_fields($r)) . a_card('Page sections', $sections) . '</div></form>';
        admin_render('Edit product', $form);
    }

    // List
    $view = ($_GET['view'] ?? '') === 'categories' ? 'categories' : 'types';
    $q = strtolower(trim((string) ($_GET['q'] ?? '')));
    $rows = $view === 'categories' ? $cats : sbu_get('product_types?select=id,name,slug,category_id,published,sort_order,updated_at&order=sort_order');
    $kind = $view === 'categories' ? 'category' : 'type';
    $tr = '';
    foreach ($rows as $r) {
        if ($q !== '' && !str_contains(strtolower($r['name'] . ' ' . $r['slug']), $q)) {
            continue;
        }
        $tr .= '<tr class="border-t border-border"><td class="py-2.5 pr-3 font-medium">' . e($r['name']) . '<p class="text-xs font-normal text-muted-foreground">' . e($r['slug']) . '</p></td>'
            . ($view === 'types' ? '<td class="pr-3">' . e($catName[$r['category_id']] ?? '') . '</td>' : '')
            . '<td class="pr-3"><form method="post" class="inline">' . f_csrf() . '<input type="hidden" name="kind" value="' . $kind . '"><input type="hidden" name="id" value="' . e($r['id']) . '"><input type="hidden" name="action" value="toggle"><input type="hidden" name="published" value="' . ($r['published'] ? '0' : '1') . '"><button class="rounded-full px-2 py-0.5 text-[11px] font-semibold ' . ($r['published'] ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground') . '" title="Click to toggle">' . ($r['published'] ? 'Published' : 'Hidden') . '</button></form></td>'
            . '<td class="whitespace-nowrap text-right"><a class="' . A_BTN_OUT . ' !py-1 text-xs" href="' . url('/admin/products?edit=' . $kind . ':' . $r['id']) . '">Edit</a> '
            . '<form method="post" class="inline" data-confirm="Delete ' . e($r['name']) . '? This cannot be undone.">' . f_csrf() . '<input type="hidden" name="kind" value="' . $kind . '"><input type="hidden" name="id" value="' . e($r['id']) . '"><input type="hidden" name="action" value="delete"><button class="' . A_BTN_DANGER . '">Delete</button></form></td></tr>';
    }
    $body = a_title($view === 'categories' ? 'Product Categories' : 'All Products', 'Edit what appears on the product pages and menu.', '<a class="' . A_BTN . '" href="' . url('/admin/products?new=' . $kind) . '">New ' . ($view === 'categories' ? 'category' : 'product') . '</a>')
        . a_card('', '<form class="mb-4 flex gap-2">' . ($view === 'categories' ? '<input type="hidden" name="view" value="categories">' : '') . '<input name="q" value="' . e($_GET['q'] ?? '') . '" placeholder="Search" class="' . A_INPUT . ' max-w-sm"><button class="' . A_BTN_OUT . '">Search</button></form><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="text-xs uppercase text-muted-foreground"><tr><th class="py-2">Name</th>' . ($view === 'types' ? '<th>Category</th>' : '') . '<th>Status</th><th></th></tr></thead><tbody>' . $tr . '</tbody></table></div>');
    admin_render('Products', $body);
}

/* ---------------- Mega menu ---------------- */

function admin_page_menu(): void
{
    $cats = sbu_get('product_categories?select=id,name,menu_label,menu_href,menu_visible,sort_order&order=sort_order');
    $types = sbu_get('product_types?select=id,name,category_id,menu_label,menu_group,menu_href,menu_visible,sort_order&order=sort_order');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $changed = 0;
        $fail = false;
        foreach ([['product_categories', $cats, 'c'], ['product_types', $types, 't']] as [$table, $rows, $p]) {
            foreach ($rows as $r) {
                $i = $r['id'];
                if (!isset($_POST[$p . '_label'][$i])) {
                    continue;
                }
                $new = ['menu_label' => trim($_POST[$p . '_label'][$i]) ?: null, 'menu_href' => trim($_POST[$p . '_href'][$i] ?? '') ?: null, 'menu_visible' => ($_POST[$p . '_vis'][$i] ?? '0') === '1', 'sort_order' => (int) ($_POST[$p . '_sort'][$i] ?? 0)];
                if ($p === 't') {
                    $new['menu_group'] = trim($_POST['t_group'][$i] ?? '') ?: null;
                }
                $diff = false;
                foreach ($new as $k => $v) {
                    if (($r[$k] ?? null) !== $v) {
                        $diff = true;
                    }
                }
                if ($diff) {
                    [$st, $d] = sbu('PATCH', $table . '?id=eq.' . $i, $new);
                    if ($st >= 300 || $d === []) {
                        $fail = true;
                    } else {
                        $changed++;
                    }
                }
            }
        }
        cache_clear();
        $fail ? flash('Some changes could not be saved — check your admin permission.', 'err') : flash($changed ? "Menu updated ($changed items)." : 'No changes to save.');
        redirect('/admin/s/mega-menu', 303);
    }
    $inp = fn($n, $v, $w = '') => '<input name="' . $n . '" value="' . e($v) . '" class="w-full rounded-sm border border-input bg-background px-2 py-1 text-sm ' . $w . '">';
    $row = fn($p, $r, $extra = '') => '<tr class="border-t border-border"><td class="py-2 pr-2 font-medium">' . e($r['name']) . '</td><td class="pr-2">' . $inp($p . '_label[' . $r['id'] . ']', $r['menu_label'] ?? '') . '</td>' . $extra
        . '<td class="pr-2">' . $inp($p . '_href[' . $r['id'] . ']', $r['menu_href'] ?? '') . '</td><td class="pr-2 w-20">' . $inp($p . '_sort[' . $r['id'] . ']', $r['sort_order'], '') . '</td>'
        . '<td><input type="hidden" name="' . $p . '_vis[' . $r['id'] . ']" value="0"><input type="checkbox" name="' . $p . '_vis[' . $r['id'] . ']" value="1"' . (!empty($r['menu_visible']) ? ' checked' : '') . '></td></tr>';
    $ct = '';
    foreach ($cats as $c) {
        $ct .= $row('c', $c);
    }
    $tt = '';
    foreach ($cats as $c) {
        $tt .= '<tr><td colspan="6" class="bg-muted/60 px-2 py-1.5 text-xs font-semibold uppercase text-primary">' . e($c['name']) . '</td></tr>';
        foreach ($types as $t) {
            if ($t['category_id'] === $c['id']) {
                $tt .= $row('t', $t, '<td class="pr-2">' . $inp('t_group[' . $t['id'] . ']', $t['menu_group'] ?? '') . '</td>');
            }
        }
    }
    $th = fn($cols) => '<thead class="text-xs uppercase text-muted-foreground"><tr>' . implode('', array_map(fn($c) => '<th class="py-2 pr-2">' . $c . '</th>', $cols)) . '</tr></thead>';
    $body = '<form method="post">' . f_csrf() . a_title('Mega Menu', 'Control the labels, order, grouping and visibility of the product menu.', '<button class="' . A_BTN . '">Save menu</button>')
        . '<div class="space-y-6">' . a_card('Categories', '<div class="overflow-x-auto"><table class="w-full text-left text-sm">' . $th(['Name', 'Menu label', 'Custom link', 'Order', 'Visible']) . '<tbody>' . $ct . '</tbody></table></div>')
        . a_card('Products', '<div class="overflow-x-auto"><table class="w-full text-left text-sm">' . $th(['Name', 'Menu label', 'Group heading', 'Custom link', 'Order', 'Visible']) . '<tbody>' . $tt . '</tbody></table></div>') . '</div></form>';
    admin_render('Mega Menu', $body);
}

/* ---------------- Blog ---------------- */

function admin_page_blog(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $id = post('id');
        if ($id !== '' && !preg_match('/^[0-9a-f-]{36}$/', $id)) {
            redirect('/admin/blog', 303);
        }
        if (post('action') === 'delete' && $id) {
            [$st, $d] = sbu('DELETE', 'blog_posts?id=eq.' . $id);
            write_result($st, $d === [] ? [['ok' => 1]] : $d, 'Post deleted.');
            redirect('/admin/blog', 303);
        }
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', post('slug') ?: post('title')), '-'));
        if (post('title') === '' || $slug === '') {
            flash('Title and slug are required.', 'err');
            redirect('/admin/blog?' . ($id ? 'edit=' . $id : 'new=1'), 303);
        }
        $format = post('content_format') === 'html' ? 'html' : 'text';
        $pub = post('published_at');
        $row = [
            'title' => post('title'), 'slug' => $slug, 'excerpt' => post_null('excerpt'), 'content' => $format === 'html' ? clean_html((string) ($_POST['content'] ?? '')) : (string) ($_POST['content'] ?? ''),
            'content_format' => $format, 'cover_image' => post_null('cover_image'), 'author_name' => post_null('author_name'), 'published' => post_bool('published'),
            'published_at' => $pub !== '' ? gmdate('Y-m-d\TH:i:s\Z', strtotime($pub)) : gmdate('Y-m-d\TH:i:s\Z'),
            'tags' => array_values(array_filter(array_map('trim', explode(',', post('tags'))))), 'gallery' => lines(post('gallery')),
        ] + seo_payload();
        if ($id) {
            $old = sbu_get('blog_posts?select=slug&id=eq.' . $id)[0] ?? null;
            [$st, $d] = sbu('PATCH', 'blog_posts?id=eq.' . $id, $row);
            if (write_result($st, $d, 'Post saved.') && $old && $old['slug'] !== $slug) {
                add_redirect('/blog/' . $old['slug'], '/blog/' . $slug);
            }
            redirect('/admin/blog?edit=' . $id, 303);
        }
        [$st, $d] = sbu('POST', 'blog_posts', $row);
        write_result($st, $d, 'Post created.');
        redirect(is_array($d) && isset($d[0]['id']) ? '/admin/blog?edit=' . $d[0]['id'] : '/admin/blog', 303);
    }

    $edit = (string) ($_GET['edit'] ?? '');
    if ($edit !== '' || isset($_GET['new'])) {
        $r = preg_match('/^[0-9a-f-]{36}$/', $edit) ? (sbu_get('blog_posts?select=*&id=eq.' . $edit)[0] ?? null) : null;
        if ($edit !== '' && !$r) {
            flash('Post not found.', 'err');
            redirect('/admin/blog', 303);
        }
        $r ??= ['published' => false, 'content_format' => 'html', 'robots_index' => true, 'robots_follow' => true, 'schema_type' => 'Article', 'published_at' => gmdate('c')];
        $dt = !empty($r['published_at']) ? date('Y-m-d\TH:i', strtotime($r['published_at'])) : '';
        $main = '<div class="grid gap-4 sm:grid-cols-2"><div class="sm:col-span-2">' . f_input('title', 'Title', $r['title'] ?? '', 'required') . '</div>' . f_input('slug', 'URL slug', $r['slug'] ?? '', 'placeholder="auto from title"')
            . f_input('author_name', 'Author', $r['author_name'] ?? '') . f_input('cover_image', 'Cover image path or URL', $r['cover_image'] ?? '') . f_input('published_at', 'Publish date', $dt, 'type="datetime-local"')
            . '<div class="sm:col-span-2">' . f_text('excerpt', 'Excerpt', $r['excerpt'] ?? '', 3) . '</div>'
            . f_select('content_format', 'Content format', ['html' => 'HTML (sanitised)', 'text' => 'Plain text'], $r['content_format'] ?? 'html')
            . '<div class="flex items-end">' . f_check('published', 'Published', !empty($r['published'])) . '</div>'
            . '<div class="sm:col-span-2">' . f_text('content', 'Content', $r['content'] ?? '', 18) . '</div>'
            . f_input('tags', 'Tags (comma separated)', implode(', ', string_list($r['tags'] ?? []))) . '<div>' . f_text('gallery', 'Gallery images (one per line)', implode("\n", string_list($r['gallery'] ?? [])), 3) . '</div></div>';
        $id = $r['id'] ?? '';
        $del = $id ? '<form method="post" data-confirm="Delete this post permanently?" class="inline">' . f_csrf() . '<input type="hidden" name="id" value="' . e($id) . '"><input type="hidden" name="action" value="delete"><button class="' . A_BTN_DANGER . '">Delete</button></form>' : '';
        $body = '<form method="post">' . f_csrf() . '<input type="hidden" name="id" value="' . e($id) . '">'
            . a_title($id ? 'Edit post' : 'New post', $r['title'] ?? '', '<div class="flex gap-2">' . ($id && !empty($r['slug']) ? '<a target="_blank" class="' . A_BTN_OUT . '" href="' . url('/blog/' . rawurlencode($r['slug'])) . '">View ↗</a>' : '') . '<a class="' . A_BTN_OUT . '" href="' . url('/admin/blog') . '">Back</a><button class="' . A_BTN . '">Save</button></div>')
            . '<div class="space-y-6">' . a_card('Article', $main) . a_card('Search engine (SEO)', seo_fields($r)) . '</div></form>' . ($del ? '<div class="mt-6">' . $del . '</div>' : '');
        admin_render('Blog post', $body);
    }

    $posts = sbu_get('blog_posts?select=id,title,slug,published,published_at,updated_at&order=published_at.desc');
    $tr = '';
    foreach ($posts as $p) {
        $tr .= '<tr class="border-t border-border"><td class="py-2.5 pr-3 font-medium">' . e($p['title']) . '<p class="text-xs font-normal text-muted-foreground">/blog/' . e($p['slug']) . '</p></td><td class="pr-3"><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ' . ($p['published'] ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground') . '">' . ($p['published'] ? 'Published' : 'Draft') . '</span></td><td class="pr-3 text-xs">' . e(fmt_dt($p['published_at'])) . '</td><td class="text-right"><a class="' . A_BTN_OUT . ' !py-1 text-xs" href="' . url('/admin/blog?edit=' . $p['id']) . '">Edit</a></td></tr>';
    }
    admin_render('Blog', a_title('Blog Posts', 'Write and publish technical articles.', '<a class="' . A_BTN . '" href="' . url('/admin/blog?new=1') . '">New post</a>')
        . a_card('', '<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="text-xs uppercase text-muted-foreground"><tr><th class="py-2">Title</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>' . ($tr ?: '<tr><td colspan="4" class="py-8 text-center text-muted-foreground">No posts yet.</td></tr>') . '</tbody></table></div>'));
}

/* ---------------- SEO settings + redirects ---------------- */

function admin_page_seo(bool $withIntro = true): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $a = post('action');
        if ($a === 'seo_save') {
            $path = '/' . trim(post('path'), '/');
            $row = ['path' => $path, 'meta_title' => post_null('meta_title'), 'meta_description' => post_null('meta_description'), 'keywords' => post_null('keywords'), 'og_image' => post_null('og_image'),
                'canonical_url' => post_null('canonical_url'), 'noindex' => post_bool('noindex'), 'og_title' => post_null('og_title'), 'og_description' => post_null('og_description'),
                'robots_follow' => post_bool('robots_follow'), 'focus_keyword' => post_null('focus_keyword'), 'schema_type' => post('schema_type', 'WebPage')];
            [$st, $d] = sbu('POST', 'page_seo?on_conflict=path', $row, ['Prefer: resolution=merge-duplicates,return=representation']);
            write_result($st, $d, 'SEO saved for ' . $path . '.');
        } elseif ($a === 'seo_delete') {
            [$st, $d] = sbu('DELETE', 'page_seo?path=eq.' . rawurlencode(post('path')));
            write_result($st, $d === [] ? [['ok' => 1]] : $d, 'Override removed.');
        } elseif ($a === 'redirect_add') {
            $old = '/' . trim(post('old_path'), '/');
            $new = post('new_path');
            if ($old === '/' || $new === '') {
                flash('Enter both the old and new path.', 'err');
            } else {
                [$st, $d] = sbu('POST', 'slug_redirects?on_conflict=old_path', ['old_path' => $old, 'new_path' => $new, 'active' => true, 'status_code' => in_array((int) post('status_code'), [301, 302], true) ? (int) post('status_code') : 301], ['Prefer: resolution=merge-duplicates,return=representation']);
                write_result($st, $d, 'Redirect saved.');
            }
        } elseif ($a === 'redirect_toggle' && preg_match('/^[0-9a-f-]{36}$/', post('id'))) {
            [$st, $d] = sbu('PATCH', 'slug_redirects?id=eq.' . post('id'), ['active' => post_bool('active')]);
            write_result($st, $d, 'Redirect updated.');
        } elseif ($a === 'redirect_delete' && preg_match('/^[0-9a-f-]{36}$/', post('id'))) {
            [$st, $d] = sbu('DELETE', 'slug_redirects?id=eq.' . post('id'));
            write_result($st, $d === [] ? [['ok' => 1]] : $d, 'Redirect deleted.');
        }
        redirect(request_path(), 303);
    }
    $rows = sbu_get('page_seo?select=*&order=path');
    $edit = null;
    foreach ($rows as $r) {
        if (($r['path'] ?? '') === ($_GET['path'] ?? null)) {
            $edit = $r;
        }
    }
    $edit ??= ['path' => $_GET['path'] ?? '', 'schema_type' => 'WebPage', 'robots_follow' => true, 'noindex' => false];
    $list = '';
    foreach ($rows as $r) {
        $list .= '<tr class="border-t border-border"><td class="py-2 pr-3 font-medium">' . e($r['path']) . '</td><td class="pr-3 text-xs">' . e($r['meta_title']) . '</td><td class="whitespace-nowrap text-right"><a class="' . A_BTN_OUT . ' !py-1 text-xs" href="' . url(request_path() . '?path=' . rawurlencode($r['path'])) . '">Edit</a> '
            . '<form method="post" class="inline" data-confirm="Remove this SEO override?">' . f_csrf() . '<input type="hidden" name="action" value="seo_delete"><input type="hidden" name="path" value="' . e($r['path']) . '"><button class="' . A_BTN_DANGER . '">Remove</button></form></td></tr>';
    }
    $form = '<form method="post" class="space-y-4">' . f_csrf() . '<input type="hidden" name="action" value="seo_save">' . f_input('path', 'Page path (e.g. /about or /products/ball-valves)', $edit['path'], 'required placeholder="/about"')
        . '<div class="grid gap-4 sm:grid-cols-2"><div class="sm:col-span-2">' . f_input('meta_title', 'Meta title', $edit['meta_title'] ?? '') . '</div><div class="sm:col-span-2">' . f_text('meta_description', 'Meta description', $edit['meta_description'] ?? '', 3) . '</div>'
        . f_input('keywords', 'Keywords', $edit['keywords'] ?? '') . f_input('focus_keyword', 'Focus keyword', $edit['focus_keyword'] ?? '') . f_input('canonical_url', 'Canonical URL', $edit['canonical_url'] ?? '') . f_input('og_image', 'Social image URL (https://)', $edit['og_image'] ?? '')
        . f_input('og_title', 'Social title', $edit['og_title'] ?? '') . f_input('og_description', 'Social description', $edit['og_description'] ?? '')
        . f_select('schema_type', 'Structured data type', array_combine(['WebPage', 'Article', 'Product', 'CollectionPage', 'None'], ['WebPage', 'Article', 'Product', 'CollectionPage', 'None']), $edit['schema_type'] ?? 'WebPage')
        . '<div class="flex flex-col justify-end gap-2">' . f_check('noindex', 'Hide from search engines (noindex)', !empty($edit['noindex'])) . f_check('robots_follow', 'Allow link following', $edit['robots_follow'] ?? true) . '</div></div><button class="' . A_BTN . '">Save SEO</button></form>';
    $reds = sbu_get('slug_redirects?select=*&order=created_at.desc');
    $rt = '';
    foreach ($reds as $r) {
        $rt .= '<tr class="border-t border-border"><td class="py-2 pr-3">' . e($r['old_path']) . '</td><td class="pr-3">' . e($r['new_path']) . '</td><td class="pr-3">' . (int) $r['status_code'] . '</td><td class="whitespace-nowrap text-right">'
            . '<form method="post" class="inline">' . f_csrf() . '<input type="hidden" name="action" value="redirect_toggle"><input type="hidden" name="id" value="' . e($r['id']) . '"><input type="hidden" name="active" value="' . ($r['active'] ? '0' : '1') . '"><button class="' . A_BTN_OUT . ' !py-1 text-xs">' . ($r['active'] ? 'Active' : 'Paused') . '</button></form> '
            . '<form method="post" class="inline" data-confirm="Delete this redirect?">' . f_csrf() . '<input type="hidden" name="action" value="redirect_delete"><input type="hidden" name="id" value="' . e($r['id']) . '"><button class="' . A_BTN_DANGER . '">Delete</button></form></td></tr>';
    }
    $redForm = '<form method="post" class="mb-4 grid gap-3 sm:grid-cols-[1fr_1fr_100px_auto] sm:items-end">' . f_csrf() . '<input type="hidden" name="action" value="redirect_add">' . f_input('old_path', 'Old path', '', 'required placeholder="/products/old"') . f_input('new_path', 'New path', '', 'required placeholder="/products/new"')
        . f_select('status_code', 'Type', [301 => '301', 302 => '302'], 301) . '<button class="' . A_BTN . '">Add</button></form>';
    $th = fn($cols) => '<thead class="text-xs uppercase text-muted-foreground"><tr>' . implode('', array_map(fn($c) => '<th class="py-2 pr-3">' . $c . '</th>', $cols)) . '</tr></thead>';
    admin_render('SEO', a_title('SEO Settings', 'Set the Google title, description, sharing image and indexing for any page.')
        . '<div class="grid gap-6 xl:grid-cols-2">' . a_card('Page SEO override', $form) . a_card('Pages with overrides', '<div class="overflow-x-auto"><table class="w-full text-left text-sm">' . $th(['Path', 'Title', '']) . '<tbody>' . ($list ?: '<tr><td colspan="3" class="py-6 text-center text-muted-foreground">No overrides yet.</td></tr>') . '</tbody></table></div>') . '</div>'
        . '<div class="mt-6">' . a_card('URL redirects', $redForm . '<div class="overflow-x-auto"><table class="w-full text-left text-sm">' . $th(['From', 'To', 'Code', '']) . '<tbody>' . ($rt ?: '<tr><td colspan="4" class="py-6 text-center text-muted-foreground">No redirects.</td></tr>') . '</tbody></table></div>') . '</div>');
}
