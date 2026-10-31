# Nishtha Global — plain PHP version

No framework, no Node at runtime. Requires PHP 8.1+ with `curl`, `pdo_sqlite`, `dom`, `mbstring`.

- `index.php` — front controller/router; `.htaccess` sends non-file requests to it (Apache `mod_rewrite`).
- `app/` — config, Supabase REST reader (60 s disk cache in `storage/cache`), helpers, layout, pages.
- `assets/css/app.css` — compiled Tailwind CSS (identical design tokens to `src/styles.css`); `assets/js/app.js` — vanilla JS.
- `images/` — copied from `public/images` (+ the three `src/assets` photos).

**Live data:** products, menu, FAQs, blog posts, SEO overrides, redirects, homepage copy and company details are read
from the same Supabase project the live site uses, so edits made in the live admin appear here within a minute.

**Enquiries** are saved to `storage/leads.sqlite` (anonymous inserts to the live `leads` table are disabled by design).
Set `enquiry_email` / `supabase_service_key` in `app/config.php` to also email or mirror them.

**Rebuilding CSS** after adding new Tailwind classes to PHP/JS: `npx @tailwindcss/cli -i build/tailwind.css -o assets/css/app.css --minify`

The admin panel is not ported — keep using `/admin` on the live site; it writes to the database this site reads.

## Admin panel

`/auth` signs in with the same Supabase accounts as the live site; `/admin` is the team dashboard.
It talks to the live database with the signed-in user's own token, so Supabase row-level security
decides what each account may change (only the `admin` role can save). Screens: dashboard, enquiries,
products & categories, mega menu, blog, SEO overrides + redirects, analytics integrations, homepage /
company settings, media library (upload + remove) and user roles. Saving clears the site's data cache.
Not ported: the visual section builder (page sections are edited as JSON), draft/publish for catalogue
edits, and listing other accounts' email addresses (needs the Supabase service-role key).
