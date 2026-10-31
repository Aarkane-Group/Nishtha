<?php
/**
 * Central configuration. Company details mirror src/config/site.ts and are
 * overridden at runtime by site_settings.website_content.company from the live database.
 */
return [
    'site' => [
        'name'         => 'Nishtha Global',
        'legalName'    => 'Nishtha Engineering Services',
        'tagline'      => 'Industrial Valves Supplier in Vadodara',
        'logo'         => '/images/Logo-Small.png',
        'description'  => 'Nishtha Engineering Services is a one-stop solution for industrial valves — ball, butterfly, plug, gate, globe, check, API 6D, severe service and steam engineering products for Oil & Gas, Petrochemical, Process, Steel and Power industries.',
        'foundedYear'  => 2022,
        'city'         => 'Vadodara',
        'state'        => 'Gujarat',
        'address'      => '307, Cascade Complex, Nizampura Rd, Near Chhani Jakat Naka Circle, Nizampura, Vadodara, Gujarat 390024',
        'phone'        => '+91 95588 98767',
        'whatsappHref' => 'https://wa.me/919558898767',
        'email'        => 'ketan@nishthaglobal.com',
    ],

    // Live data source (the Supabase project behind the live site). The publishable key is read-only public.
    'supabase_url' => 'https://wsqvkvcownnfmcxxggax.supabase.co',
    'supabase_key' => 'sb_publishable_wlJozViUvTihd57RgjS-Qg_ZlAr8Nsh',

    // Seconds to cache live data on disk. Set 0 to always hit the live database.
    'cache_ttl' => 60,

    // Public site URL used for canonical links / sitemap. Empty = detect from the request.
    'site_url' => '',

    // Enquiries are always saved to storage/leads.sqlite. Set a recipient to also email each one
    // (needs a working mail()/SMTP setup in php.ini).
    'enquiry_email' => '',

    // Optional: Supabase *service role* key. If set, enquiries are also inserted into the live
    // `leads` table (anonymous inserts are disabled there). Never commit a real key.
    'supabase_service_key' => '',
];
