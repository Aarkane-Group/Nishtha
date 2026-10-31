<?php
function page_home(): void
{
$s = site();
$web = get_website_content();
$home = $web['home'] ?? [];
$cat = get_catalogue();
$title = "Industrial Valve Supplier & Valve Automation in Vadodara | {$s['name']}";
$description = 'Authorised channel partner for industrial valves, pipe & tubing fittings and valve automation. Ball, gate, globe, check and butterfly valves with actuators, supplied across India from Vadodara, Gujarat.';
$head = page_head($title, $description, '/');

$defaults = [
    ['image' => '/images/12.jpg', 'eyebrow' => 'Valves · Fittings · Automation', 'title' => 'Industrial flow control, engineered around your process', 'text' => 'Reliable valve selection, project supply and documentation for demanding process industries.'],
    ['image' => '/images/5.jpg', 'eyebrow' => 'In-house automation', 'title' => 'Complete actuated valve packages, assembled and tested', 'text' => 'Pneumatic and electric actuator sizing, mounting, accessories and functional testing from one team.'],
    ['image' => '/images/Nishtha-Engg-12-scaled.jpg', 'eyebrow' => 'Project & maintenance supply', 'title' => 'Responsive support from enquiry to commissioning', 'text' => 'Genuine products, clear delivery commitments and practical application support across India.'],
];
$slides = !empty($web['slides']) && is_array($web['slides']) ? $web['slides'] : $defaults;

$capabilities = [
    ['package-check', 'Industrial valves', 'Ball, gate, globe, check, butterfly and control valves in carbon steel, stainless steel and exotic alloys, from ½ inch to large bore.'],
    ['wrench', 'Pipe & tubing fittings', 'Instrumentation tube fittings, compression fittings, forged and butt-weld fittings, manifolds and needle valves for process lines.'],
    ['cog', 'Valve automation house', 'Pneumatic and electric actuator sizing, mounting, limit switches, solenoid valves and positioners — assembled and tested in-house.'],
    ['gauge', 'Application engineering', 'Material and trim selection against media, pressure class and temperature, with datasheets and dimensional drawings on request.'],
    ['shield-check', 'Quality documentation', 'Test certificates, MTCs, hydro and seat-leakage reports, and third-party inspection support for project supplies.'],
    ['circle-check', 'Spares & after-sales', 'Repair kits, seat and seal replacement, actuator overhaul and retrofit of existing manual valves to automated operation.'],
];

ob_start(); ?>
<section class="relative isolate min-h-[560px] overflow-hidden lg:min-h-[640px]" aria-label="Featured services" data-hero>
  <?php foreach ($slides as $i => $slide): ?>
  <img src="<?= e(url($slide['image'])) ?>" alt="Industrial valve and engineering installation" data-hero-img class="absolute inset-0 h-full w-full object-cover transition-opacity duration-700 motion-reduce:transition-none <?= $i === 0 ? 'opacity-100' : 'opacity-0' ?>"<?= $i > 0 ? ' loading="lazy"' : '' ?>>
  <?php endforeach; ?>
  <div class="absolute inset-0 hero-overlay"></div>
  <div class="relative mx-auto flex min-h-[560px] max-w-7xl items-center px-4 py-20 sm:px-6 lg:min-h-[640px]">
    <div class="max-w-3xl">
      <p data-hero-eyebrow class="text-xs font-semibold uppercase tracking-[0.24em] text-highlight"><?= e($slides[0]['eyebrow']) ?></p>
      <h1 data-hero-title class="mt-5 text-3xl leading-[1.1] text-steel-foreground sm:text-5xl lg:text-6xl"><?= e($slides[0]['title']) ?></h1>
      <p data-hero-text class="mt-6 max-w-2xl text-base text-steel-foreground/80 lg:text-lg"><?= e($slides[0]['text']) ?></p>
      <div class="mt-9 flex flex-wrap gap-3">
        <a href="<?= url('/contact') ?>" class="<?= BTN_PRIMARY_LG ?>">Request a quotation</a>
        <a href="<?= url('/products') ?>" class="<?= BTN_OUTLINE_HERO_LG ?>">View product range</a>
      </div>
    </div>
  </div>
  <div class="absolute bottom-6 left-0 right-0 mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6">
    <div class="flex gap-2">
      <?php foreach ($slides as $i => $slide): ?><button type="button" data-hero-dot="<?= $i ?>" aria-label="Show slide <?= $i + 1 ?>" class="h-1.5 transition-all <?= $i === 0 ? 'w-10 bg-highlight' : 'w-5 bg-steel-foreground/50' ?>"></button><?php endforeach; ?>
    </div>
    <div class="flex gap-2">
      <button type="button" data-hero-prev aria-label="Previous slide" class="<?= BTN_BASE ?> border shadow-sm h-9 w-9 border-steel-foreground/30 bg-steel/70 text-steel-foreground hover:bg-steel"><?= icon('chevron-left') ?></button>
      <button type="button" data-hero-next aria-label="Next slide" class="<?= BTN_BASE ?> border shadow-sm h-9 w-9 border-steel-foreground/30 bg-steel/70 text-steel-foreground hover:bg-steel"><?= icon('chevron-right') ?></button>
    </div>
  </div>
  <script type="application/json" data-hero-slides><?= json_encode(array_map(fn($x) => ['eyebrow' => $x['eyebrow'], 'title' => $x['title'], 'text' => $x['text']], $slides), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
</section>

<?= section(
    '<div class="flex flex-wrap items-end justify-between gap-5"><div><div class="h-1 w-16 section-rule rounded-full"></div><h2 class="mt-5 text-2xl sm:text-3xl">' . e($home['range_title'] ?? '' ?: 'Explore our valve range') . '</h2><p class="mt-3 max-w-2xl text-muted-foreground">' . e($home['range_text'] ?? '' ?: 'Application-matched isolation, control and non-return valves for industrial duty.') . '</p></div><a href="' . url('/products') . '" class="text-sm font-semibold text-primary">View all products →</a></div>'
    . '<div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">'
    . implode('', array_map(fn($c) => '<a href="' . url('/products/' . rawurlencode($c['slug'])) . '" class="group rounded-md border border-border bg-card p-5 shadow-panel transition-shadow hover:shadow-lift">'
        . (!empty($c['image_url']) ? '<img src="' . e(url($c['image_url'])) . '" alt="' . e($c['name']) . '" class="aspect-square w-full object-contain p-3" loading="lazy">' : '')
        . '<h3 class="mt-4 text-lg group-hover:text-primary">' . e($c['name']) . '</h3><p class="mt-2 text-sm text-muted-foreground">' . e($c['short_description'] ?? '') . '</p></a>', array_slice($cat['categories'], 0, 8)))
    . '</div>'
) ?>

<?= section(
    '<div class="max-w-2xl"><div class="h-1 w-16 section-rule rounded-full"></div><h2 class="mt-5 text-2xl sm:text-3xl">' . e($home['support_title'] ?? '' ?: 'What we supply and support') . '</h2><p class="mt-4 text-muted-foreground">' . e($home['support_text'] ?? '' ?: 'One accountable partner for flow control — product selection, automation assembly, documentation and after-sales support.') . '</p></div>'
    . '<div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">'
    . implode('', array_map(fn($c) => '<article class="rounded-md border border-border bg-card p-6 shadow-panel transition-shadow hover:shadow-lift">' . icon($c[0], 'h-6 w-6 text-primary') . '<h3 class="mt-4 text-lg">' . e($c[1]) . '</h3><p class="mt-2 text-sm leading-relaxed text-muted-foreground">' . e($c[2]) . '</p></article>', $capabilities))
    . '</div>'
) ?>

<section class="surface-steel">
  <div class="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:grid-cols-3 sm:px-6">
    <?php foreach ([['2022', 'Serving process industries since'], ['Authorised', 'Channel partner for leading valve brands'], ['In-house', 'Valve automation assembly & testing']] as [$k, $v]): ?>
    <div><p class="font-display text-3xl font-bold text-highlight"><?= e($k) ?></p><p class="mt-2 text-sm text-steel-foreground/75"><?= e($v) ?></p></div>
    <?php endforeach; ?>
  </div>
</section>

<?php
$points = ['Quotations typically within one working day', 'Genuine, brand-warranted products with test certificates', 'Retrofit and automation of existing manual valves'];
echo section(
    '<div class="grid gap-10 lg:grid-cols-2 lg:gap-16"><div><div class="h-1 w-16 section-rule rounded-full"></div><h2 class="mt-5 text-2xl sm:text-3xl">' . e($home['contact_title'] ?? '' ?: 'Talk to an application engineer') . '</h2>'
    . '<p class="mt-4 text-muted-foreground">' . e($home['contact_text'] ?? '' ?: 'Send us your line specification and we will come back with the right valve, actuator and accessory package — including delivery schedule and documentation.') . '</p>'
    . '<div class="mt-8">' . contact_strip('home') . '</div>'
    . '<ul class="mt-8 space-y-3 text-sm text-muted-foreground">' . implode('', array_map(fn($p) => '<li class="flex gap-2.5">' . icon('circle-check', 'mt-0.5 h-4 w-4 shrink-0 text-success') . e($p) . '</li>', $points)) . '</ul></div>'
    . lead_form('home') . '</div>'
);
$content = ob_get_clean();
render_layout($head, $content);
}
