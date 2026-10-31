<?php
/** Homepage / global / general settings, media library, users. */

function admin_page_website(string $mode): void
{
    $row = sbu_get('site_settings?select=website_content,admin_preferences&id=eq.1')[0] ?? [];
    $wc = is_array($row['website_content'] ?? null) ? $row['website_content'] : [];
    $prefs = is_array($row['admin_preferences'] ?? null) ? $row['admin_preferences'] : [];

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $patch = [];
        if ($mode === 'homepage') {
            foreach (['range_title', 'range_text', 'support_title', 'support_text', 'contact_title', 'contact_text'] as $k) {
                $wc['home'][$k] = post($k);
            }
            $slides = [];
            foreach ($_POST['slide_image'] ?? [] as $i => $img) {
                $img = trim($img);
                if ($img !== '') {
                    $slides[] = ['image' => $img, 'eyebrow' => trim($_POST['slide_eyebrow'][$i] ?? ''), 'title' => trim($_POST['slide_title'][$i] ?? ''), 'text' => trim($_POST['slide_text'][$i] ?? '')];
                }
            }
            $wc['slides'] = $slides;
            $patch = ['website_content' => $wc];
        } elseif ($mode === 'global-settings') {
            foreach (['name', 'legalName', 'email', 'phone', 'address', 'logo'] as $k) {
                $wc['company'][$k] = post($k);
            }
            $patch = ['website_content' => $wc];
        } else {
            $landing = post('landing');
            $prefs['landing'] = in_array($landing, ['/admin/products', '/admin/blog', '/admin/enquiries'], true) ? $landing : '/admin';
            $patch = ['admin_preferences' => $prefs];
        }
        [$st, $d] = sbu('PATCH', 'site_settings?id=eq.1', $patch);
        write_result($st, $d, 'Saved. The website shows the change on the next page load.');
        redirect(request_path(), 303);
    }

    $save = '<button class="' . A_BTN . '">Save changes</button>';
    if ($mode === 'homepage') {
        $h = $wc['home'] ?? [];
        $cards = a_card('Homepage text', '<div class="grid gap-4">' . f_input('range_title', 'Product range heading', $h['range_title'] ?? '') . f_text('range_text', 'Product range text', $h['range_text'] ?? '', 2)
            . f_input('support_title', 'What we supply heading', $h['support_title'] ?? '') . f_text('support_text', 'What we supply text', $h['support_text'] ?? '', 2)
            . f_input('contact_title', 'Contact heading', $h['contact_title'] ?? '') . f_text('contact_text', 'Contact text', $h['contact_text'] ?? '', 3) . '</div>');
        $slides = array_pad($wc['slides'] ?? [], max(5, count($wc['slides'] ?? [])), ['image' => '', 'eyebrow' => '', 'title' => '', 'text' => '']);
        $sh = '';
        foreach ($slides as $i => $s) {
            $sh .= '<div class="rounded-md border border-border p-4"><p class="mb-3 text-xs font-semibold uppercase text-muted-foreground">Slide ' . ($i + 1) . '</p><div class="grid gap-3 sm:grid-cols-2">'
                . f_input('slide_image[' . $i . ']', 'Image path or URL', $s['image'] ?? '') . f_input('slide_eyebrow[' . $i . ']', 'Small heading', $s['eyebrow'] ?? '')
                . '<div class="sm:col-span-2">' . f_input('slide_title[' . $i . ']', 'Title', $s['title'] ?? '') . '</div><div class="sm:col-span-2">' . f_text('slide_text[' . $i . ']', 'Text', $s['text'] ?? '', 2) . '</div></div></div>';
        }
        $cards .= '<div class="mt-6">' . a_card('Hero slider (leave the image empty to remove a slide)', '<div class="space-y-4">' . $sh . '</div>') . '</div>';
        $title = 'Homepage Sections';
    } elseif ($mode === 'global-settings') {
        $c = array_merge(array_intersect_key(site(), array_flip(['name', 'legalName', 'email', 'phone', 'address', 'logo'])), $wc['company'] ?? []);
        $cards = a_card('Company details', '<div class="grid gap-4 sm:grid-cols-2">' . f_input('name', 'Brand name', $c['name']) . f_input('legalName', 'Legal name', $c['legalName']) . f_input('email', 'Email', $c['email']) . f_input('phone', 'Phone', $c['phone'])
            . f_input('logo', 'Logo path or URL', $c['logo']) . '<div class="sm:col-span-2">' . f_text('address', 'Address', $c['address'], 3) . '</div></div>');
        $title = 'Global Settings';
    } else {
        $cards = a_card('Admin preferences', f_select('landing', 'Open this screen after sign-in', ['/admin' => 'Dashboard', '/admin/products' => 'Products', '/admin/blog' => 'Blog posts', '/admin/enquiries' => 'Enquiries'], $prefs['landing'] ?? '/admin'));
        $title = 'General Settings';
    }
    admin_render($title, '<form method="post">' . f_csrf() . a_title($title, '', $save) . $cards . '</form>');
}

function storage_upload(string $path, string $mime, string $file): int
{
    $a = admin_auth();
    $ch = curl_init(rtrim(cfg('supabase_url'), '/') . '/storage/v1/object/website-media/' . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => file_get_contents($file), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => ['apikey: ' . cfg('supabase_key'), 'Authorization: Bearer ' . $a['access'], 'Content-Type: ' . $mime, 'x-upsert: false'],
        CURLOPT_SSL_VERIFYPEER => !(PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo') && !ini_get('openssl.cafile')),
    ]);
    curl_exec($ch);
    $st = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $st;
}

function admin_page_media(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        if (post('action') === 'delete' && preg_match('/^[0-9a-f-]{36}$/', post('id'))) {
            [$st, $d] = sbu('DELETE', 'media_library?id=eq.' . post('id'));
            write_result($st, $d === [] ? [['ok' => 1]] : $d, 'Removed from the library.');
        } elseif (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $f = $_FILES['file'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'application/pdf' => 'pdf'][$mime] ?? null;
            if (!$ext) {
                flash('Choose a JPG, PNG, WebP, GIF or PDF file.', 'err');
            } elseif ($f['size'] > 20 * 1024 * 1024) {
                flash('File must be under 20 MB.', 'err');
            } else {
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0x0fff) | 0x4000, random_int(0, 0x3fff) | 0x8000, random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff));
                $path = "website/$id.$ext";
                if (storage_upload($path, $mime, $f['tmp_name']) >= 300) {
                    flash('Upload failed — your account may not have admin permission.', 'err');
                } else {
                    [$st, $d] = sbu('POST', 'media_library', ['id' => $id, 'storage_path' => $path, 'public_url' => '/api/public/media/' . $id, 'file_name' => basename($f['name']), 'mime_type' => $mime, 'file_size' => (int) $f['size'], 'alt_text' => post('alt_text')]);
                    write_result($st, $d, 'Uploaded.');
                }
            }
        }
        redirect('/admin/s/media', 303);
    }
    $q = strtolower(trim((string) ($_GET['q'] ?? '')));
    $items = '';
    foreach (sbu_get('media_library?select=*&order=created_at.desc') as $m) {
        if ($q !== '' && !str_contains(strtolower($m['file_name'] . ' ' . $m['alt_text']), $q)) {
            continue;
        }
        $isImg = str_starts_with($m['mime_type'], 'image/');
        $items .= '<div class="rounded-md border border-border bg-card p-3">' . ($isImg ? '<img src="' . e(url($m['public_url'])) . '" alt="" class="aspect-[4/3] w-full rounded object-contain" loading="lazy">' : '<div class="flex aspect-[4/3] items-center justify-center rounded bg-muted text-sm">PDF</div>')
            . '<p class="mt-2 truncate text-sm font-medium">' . e($m['file_name']) . '</p><p class="text-xs text-muted-foreground">' . round($m['file_size'] / 1024) . ' KB</p>'
            . '<input readonly value="' . e($m['public_url']) . '" onfocus="this.select()" class="mt-2 w-full rounded-sm border border-input bg-background px-2 py-1 text-xs">'
            . '<form method="post" class="mt-2" data-confirm="Remove this file from the library?">' . f_csrf() . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . e($m['id']) . '"><button class="' . A_BTN_DANGER . '">Remove</button></form></div>';
    }
    $up = '<form method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">' . f_csrf() . '<label class="text-sm font-medium">File<input type="file" name="file" required accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" class="mt-1.5 block text-sm"></label>'
        . f_input('alt_text', 'Alt text', '') . '<button class="' . A_BTN . '">Upload</button></form>';
    admin_render('Media Library', a_title('Media Library', 'Images and PDFs for pages and articles. Paste a file\'s path into any image field.')
        . a_card('Upload', $up) . '<form class="my-5 flex gap-2"><input name="q" value="' . e($_GET['q'] ?? '') . '" placeholder="Search media" class="' . A_INPUT . ' max-w-sm"><button class="' . A_BTN_OUT . '">Search</button></form>'
        . '<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">' . ($items ?: '<p class="text-sm text-muted-foreground">No media yet.</p>') . '</div>');
}

function admin_page_users(): void
{
    $roles = ['pending', 'member', 'admin'];
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        csrf_check();
        $uid = post('user_id');
        $role = post('role');
        $a = admin_auth();
        if (preg_match('/^[0-9a-f-]{36}$/', $uid) && in_array($role, $roles, true) && $uid !== $a['uid']) {
            [$st, $d] = sbu('DELETE', 'user_roles?user_id=eq.' . $uid);
            $ok = $st < 300;
            if ($ok && $role !== 'pending') {
                [$st, $d] = sbu('POST', 'user_roles', ['user_id' => $uid, 'role' => $role]);
                $ok = $st < 300;
            }
            $ok ? (flash('Access updated.') ?? null) : flash('Could not change access — admin permission is required.', 'err');
        } else {
            flash('You cannot change your own access.', 'err');
        }
        redirect('/admin/s/users', 303);
    }
    $a = admin_auth();
    $tr = '';
    foreach (sbu_get('user_roles?select=user_id,role,created_at&order=created_at') as $r) {
        $opts = implode('', array_map(fn($x) => '<option' . ($x === $r['role'] ? ' selected' : '') . '>' . $x . '</option>', $roles));
        $me = $r['user_id'] === $a['uid'];
        $tr .= '<tr class="border-t border-border"><td class="py-3 pr-3 font-mono text-xs">' . e($r['user_id']) . ($me ? ' <span class="rounded-full bg-primary/10 px-2 py-0.5 font-sans text-[11px] font-semibold text-primary">you (' . e($a['email']) . ')</span>' : '') . '</td><td class="pr-3 text-xs">' . e(fmt_dt($r['created_at'])) . '</td><td>'
            . '<form method="post" data-confirm="Change access for this account?">' . f_csrf() . '<input type="hidden" name="user_id" value="' . e($r['user_id']) . '"><select name="role" onchange="this.form.requestSubmit()"' . ($me ? ' disabled' : '') . ' class="rounded border border-input bg-background px-2 py-1 text-sm">' . $opts . '</select></form></td></tr>';
    }
    $help = '<div class="mt-6 border-t border-border pt-4 text-sm"><p><strong>Admin:</strong> edit and publish content, integrations and team access.</p><p><strong>Member:</strong> read content and enquiries; cannot publish or manage access.</p><p><strong>Pending:</strong> no admin access.</p></div>';
    admin_render('Admin Users', a_title('Admin Users & Roles', 'Everyone who has been granted access to the dashboard.')
        . a_card('', '<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="text-xs uppercase text-muted-foreground"><tr><th class="py-2">Account ID</th><th>Added</th><th>Access</th></tr></thead><tbody>' . ($tr ?: '<tr><td colspan="3" class="py-6 text-center text-muted-foreground">No roles visible to your account.</td></tr>') . '</tbody></table></div>'
            . '<p class="mt-4 text-xs text-muted-foreground">Email addresses of other accounts are only visible to the live site\'s server. New people sign up on the live site\'s sign-in page, then appear here as soon as they are granted a role.</p>' . $help));
}

/** Route /admin/* */
function admin_dispatch(array $seg): void
{
    $sub = $seg[1] ?? '';
    $section = $seg[2] ?? '';
    match (true) {
        $sub === '' => admin_page_dashboard(),
        $sub === 'enquiries' => admin_page_enquiries(),
        $sub === 'products' => admin_page_products(),
        $sub === 'blog' => admin_page_blog(),
        $sub === 'seo' => admin_page_seo(),
        $sub === 'integrations' => admin_page_integrations(),
        $sub === 's' && in_array($section, ['mega-menu', 'navigation'], true) => admin_page_menu(),
        $sub === 's' && $section === 'media' => admin_page_media(),
        $sub === 's' && in_array($section, ['users', 'roles'], true) => admin_page_users(),
        $sub === 's' && $section === 'homepage' => admin_page_website('homepage'),
        $sub === 's' && $section === 'global-settings' => admin_page_website('global-settings'),
        $sub === 's' && $section === 'general' => admin_page_website('general'),
        $sub === 's' && $section === 'structured-data' => admin_page_seo(),
        default => page_not_found(),
    };
}
