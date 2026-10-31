<?php
/** About, contact, industries, valve automation and the two engineering-service pages. */

function page_about(): void
{
    $s = site();
    $head = page_head(
        "About Us — Valve Supplier & Automation Partner | {$s['name']}",
        "{$s['legalName']} is a {$s['city']}-based authorised channel partner for industrial valves, pipe & tubing fittings and valve automation, serving process industries across India since {$s['foundedYear']}.",
        '/about'
    );
    $values = [
        ['Right product, first time', 'Selection based on actual process conditions, not just catalogue numbers.'],
        ['Genuine supply', 'Authorised channel partnerships mean brand-warranted products with full traceability.'],
        ['Responsive support', 'Fast quotations, clear delivery commitments and after-sales help when you need it.'],
    ];
    $cards = implode('', array_map(fn($v) => '<article class="rounded-md border border-border bg-card p-6 shadow-panel"><h3 class="text-lg">' . e($v[0]) . '</h3><p class="mt-2 text-sm text-muted-foreground">' . e($v[1]) . '</p></article>', $values));
    $content = page_header('About us', "{$s['legalName']}: your flow-control partner in {$s['state']}", "Founded in {$s['foundedYear']} and headquartered in {$s['city']}, we are an established supplier and authorised channel partner for industrial valves and pipe & tubing fittings — and an industrial valve automation house.")
        . section('<div class="grid gap-10 lg:grid-cols-2 lg:gap-16"><div class="space-y-4 text-muted-foreground"><h2 class="text-2xl text-foreground sm:text-3xl">Who we are</h2>'
            . '<p>Our team combines product knowledge across leading valve and fitting brands with hands-on automation experience. We help EPC contractors, plant maintenance teams and OEM skid builders choose, automate and maintain the valves that keep their processes running safely.</p>'
            . '<p>From a single replacement valve to complete project packages with actuators, accessories and documentation, we take ownership of the supply from enquiry to commissioning support.</p></div>'
            . '<div class="grid gap-4">' . $cards . '</div></div>');
    render_layout($head, $content);
}

function page_contact(): void
{
    $s = site();
    $head = page_head(
        "Contact & Request a Quote | {$s['name']}, {$s['city']}",
        "Request a quotation for industrial valves, fittings or valve automation. Call, WhatsApp or email {$s['legalName']} in {$s['city']}, {$s['state']}.",
        '/contact'
    );
    $content = page_header('Contact', 'Request a quotation', 'Tell us what you need — our application engineers reply within one working day.')
        . section('<div class="grid gap-10 lg:grid-cols-2 lg:gap-16"><div><h2 class="text-2xl sm:text-3xl">Reach us directly</h2><p class="mt-3 text-muted-foreground">' . e($s['address']) . '</p><div class="mt-8">' . contact_strip('contact') . '</div></div>' . lead_form('contact') . '</div>');
    render_layout($head, $content);
}

function page_industries(): void
{
    $s = site();
    $head = page_head(
        "Industries We Serve — Oil & Gas, Chemical, Pharma | {$s['name']}",
        'Valves, fittings and automated valve packages for oil & gas, refineries, chemical and petrochemical plants, pharmaceuticals, power, water treatment, food processing and OEM skid builders.',
        '/industries'
    );
    $industries = [
        ['Oil, gas & refineries', 'Fire-safe ball valves, trunnion designs and high-class gate valves for hydrocarbon service, with fugitive-emission compliant stem sealing.'],
        ['Chemical & petrochemical', 'Corrosion-resistant alloy and lined valves for aggressive media, with gasket and trim selection matched to the process.'],
        ['Pharmaceutical & biotech', 'Hygienic diaphragm and sanitary ball valves, tube fittings and polished tubing for clean utilities and process lines.'],
        ['Power & steam', 'High-temperature globe, gate and check valves plus bellow-sealed designs for steam and condensate service.'],
        ['Water & effluent treatment', 'Butterfly, knife-gate and non-return valves for raw water, sludge and effluent handling, including actuated packages.'],
        ['Food, beverage & OEM skids', 'Compact automated assemblies and instrumentation fittings for packaged plants, skids and machine builders.'],
    ];
    $cards = implode('', array_map(fn($i) => '<article class="rounded-md border border-border bg-card p-6 shadow-panel"><h2 class="text-lg">' . e($i[0]) . '</h2><p class="mt-3 text-sm leading-relaxed text-muted-foreground">' . e($i[1]) . '</p></article>', $industries));
    $content = page_header('Industries', 'Flow control for demanding process environments', 'From hydrocarbon service to clean utilities, we match valve construction, trim and automation to the real operating conditions of your plant.')
        . section('<div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">' . $cards . '</div>')
        . '<section class="relative isolate"><img src="' . url('/images/industries.jpg') . '" alt="Refinery pipelines and valves at dusk" width="1200" height="800" loading="lazy" class="absolute inset-0 h-full w-full object-cover"><div class="absolute inset-0 hero-overlay"></div>'
        . '<div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6"><h2 class="max-w-2xl text-2xl text-steel-foreground sm:text-3xl">Project supplies, plant maintenance and breakdown support</h2>'
        . '<p class="mt-4 max-w-2xl text-steel-foreground/80">Whether it is a scheduled shutdown list or an urgent line-stop replacement, our team in ' . e($s['city']) . ' works to your delivery window.</p>'
        . '<a href="' . url('/contact') . '" class="mt-8 inline-block rounded-sm bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90">Discuss your requirement</a></div></section>';
    render_layout($head, $content);
}

function page_valve_automation(): void
{
    $s = site();
    $head = page_head(
        "Valve Automation — Pneumatic & Electric Actuators | {$s['name']}",
        'Valve automation house offering actuator sizing, mounting and testing: pneumatic and electric actuators, solenoid valves, limit switches, positioners and retrofit of manual valves.',
        '/valve-automation'
    );
    $steps = [
        ['01', 'Requirement study', 'We review media, pressure class, differential pressure, torque, cycle time, fail-safe action and area classification.'],
        ['02', 'Actuator sizing & selection', 'Torque calculation with safety factor, then selection of pneumatic (rack & pinion or scotch yoke) or electric actuators.'],
        ['03', 'Assembly & accessories', 'Mounting kits, solenoid valves, limit switch boxes, positioners, filter regulators, speed controls and manual overrides.'],
        ['04', 'Testing & documentation', 'Functional cycle testing, stroke verification and leakage checks, supplied with test reports and wiring details.'],
    ];
    $accessories = ['Pneumatic rack & pinion actuators', 'Scotch yoke actuators for large torque', 'Electric multi-turn & quarter-turn actuators', 'Solenoid valves (3/2 and 5/2, Ex-proof options)', 'Limit switch boxes & proximity sensors', 'Smart positioners (4–20 mA, HART)', 'Air filter regulators & lock-up valves', 'Manual gear override & handwheels'];
    $stepsHtml = implode('', array_map(fn($x) => '<li class="flex gap-4"><span class="font-display text-sm font-bold text-primary">' . $x[0] . '</span><span><span class="block font-display text-base font-semibold">' . e($x[1]) . '</span><span class="mt-1 block text-sm text-muted-foreground">' . e($x[2]) . '</span></span></li>', $steps));
    $accHtml = implode('', array_map(fn($a) => '<li class="rounded-sm border border-border bg-card px-4 py-3 text-sm font-medium">' . e($a) . '</li>', $accessories));
    $content = page_header('Valve automation house', 'Automated valve packages, sized and tested in-house', 'We convert manual valves into reliable automated assemblies — correctly sized actuators, the right accessories, and functional testing before dispatch.')
        . section('<div class="grid items-start gap-10 lg:grid-cols-2 lg:gap-14"><img src="' . url('/images/valve-automation.jpg') . '" alt="Pneumatic actuator mounted on a stainless steel butterfly valve" width="1200" height="912" loading="lazy" class="rounded-md border border-border object-cover shadow-panel"><div><div class="h-1 w-16 section-rule rounded-full"></div><h2 class="mt-5 text-2xl sm:text-3xl">How a package comes together</h2><ol class="mt-8 space-y-6">' . $stepsHtml . '</ol></div></div>')
        . '<section class="bg-muted/60"><div class="mx-auto max-w-7xl px-4 py-16 sm:px-6"><h2 class="text-2xl sm:text-3xl">Actuators and accessories</h2><ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">' . $accHtml . '</ul></div></section>'
        . section('<div class="grid gap-10 lg:grid-cols-2 lg:gap-16"><div><h2 class="text-2xl sm:text-3xl">Retrofit an existing valve</h2><p class="mt-4 text-muted-foreground">Already have manual valves in the line? Share the make, size, class and operating conditions and we will propose an automation package with mounting hardware, control accessories and ' . e($s['city']) . '-based support.</p><p class="mt-4 text-muted-foreground">We also handle actuator overhaul, seal replacement and spares for installed assemblies.</p></div>' . lead_form('valve-automation') . '</div>');
    render_layout($head, $content);
}

function page_service(string $slug): void
{
    $sv = services()[$slug];
    $s = site();
    $head = page_head("{$sv['title']} | {$s['name']}", $sv['summary'], '/' . $slug);
    $points = implode('', array_map(fn($p) => '<li class="flex gap-2 text-sm">' . icon('circle-check', 'h-4 w-4 text-success') . e($p) . '</li>', $sv['points']));
    $content = page_header('Engineering services', $sv['name'], $sv['summary'])
        . section('<div class="grid gap-12 lg:grid-cols-2"><div><img src="' . e(url($sv['image'])) . '" alt="' . e($sv['alt']) . '" class="aspect-[4/3] w-full rounded-md object-cover shadow-panel"><ul class="mt-7 grid gap-3 sm:grid-cols-2">' . $points . '</ul></div>' . lead_form($sv['slug']) . '</div>');
    render_layout($head, $content);
}

function page_products_index(): void
{
    $s = site();
    $cat = get_catalogue();
    $head = page_head(
        "Industrial Valves & Pipe Fittings Supplier | {$s['name']}",
        'Supplier of ball, gate, globe, check, butterfly and control valves plus instrumentation tube fittings, forged fittings and manifolds in carbon steel, stainless steel and alloy grades.',
        '/products'
    );
    $materials = ['Carbon steel (A105, WCB, LF2)', 'Stainless steel (SS304, SS316, SS316L, CF8M)', 'Duplex & super duplex', 'Alloy 20, Monel, Hastelloy, Inconel', 'Cast iron & ductile iron', 'PTFE / PFA lined'];
    $groups = implode('', array_map(fn($g) => '<article class="rounded-md border border-border bg-card p-6 shadow-panel">'
        . (!empty($g['image_url']) ? '<img src="' . e(url($g['image_url'])) . '" alt="' . e($g['name']) . '" class="aspect-[4/3] w-full object-contain p-3" loading="lazy">' : '')
        . '<h2 class="mt-4 text-lg">' . e($g['name']) . '</h2>' . (!empty($g['short_description']) ? '<p class="mt-2 text-sm text-muted-foreground">' . e($g['short_description']) . '</p>' : '')
        . '<a href="' . url('/products/' . rawurlencode($g['slug'])) . '" class="mt-4 inline-block text-sm font-semibold text-primary">Explore ' . e($g['name']) . ' →</a></article>', $cat['categories']));
    $services = implode('', array_map(fn($sv) => '<a href="' . url('/' . $sv['slug']) . '" class="group relative isolate min-h-64 overflow-hidden rounded-md"><img src="' . e(url($sv['image'])) . '" alt="' . e($sv['name']) . '" class="absolute inset-0 h-full w-full object-cover transition-transform group-hover:scale-105"><div class="absolute inset-0 hero-overlay"></div><div class="relative p-7 text-steel-foreground"><h3 class="text-xl">' . e($sv['name']) . '</h3><p class="mt-2 max-w-md text-sm text-steel-foreground/80">' . e($sv['summary']) . '</p></div></a>', array_values(services())));
    $mats = implode('', array_map(fn($m) => '<li class="rounded-sm border border-border bg-card px-4 py-3 text-sm font-medium">' . e($m) . '</li>', $materials));
    $content = page_header('Product range', 'Industrial valves, fittings and flow-control components', 'A comprehensive, brand-warranted portfolio for process plants — selected against your media, pressure class, temperature and end connection, and supplied with full test documentation.')
        . section('<div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">' . $groups . '</div>')
        . '<section class="bg-muted/60"><div class="mx-auto max-w-7xl px-4 py-16 sm:px-6"><h2 class="text-2xl sm:text-3xl">Engineering services</h2><div class="mt-8 grid gap-5 md:grid-cols-2">' . $services . '</div></div></section>'
        . '<section class="bg-muted/60"><div class="mx-auto max-w-7xl px-4 py-16 sm:px-6"><h2 class="text-2xl sm:text-3xl">Materials and specifications</h2><p class="mt-4 max-w-2xl text-muted-foreground">Sizes from ½ inch to large bore, classes from 150# to 2500#, with flanged, butt-weld, socket-weld, threaded and wafer end connections.</p><ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">' . $mats . '</ul></div></section>'
        . section('<h2 class="text-2xl sm:text-3xl">Need a specific item?</h2><p class="mt-3 max-w-2xl text-muted-foreground">Send your BOM or datasheet and we will confirm availability, lead time and price.</p><div class="mt-8">' . contact_strip('products') . '</div><a href="' . url('/contact') . '" class="mt-8 inline-block rounded-sm bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90">Request a quotation</a>');
    render_layout($head, $content);
}
