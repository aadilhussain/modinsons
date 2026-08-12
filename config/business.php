<?php

/**
 * Single source of truth for company details.
 * Edit here (or via .env) — never hard-code these in views.
 *
 * These are defaults only: anything saved on the admin Settings page is stored
 * in the settings table and overlaid on top of this file at boot.
 */
return [
    'name'        => 'Modi And Sons',
    'legal_name'  => 'Modi And Sons',
    'tagline'     => 'Electricals & Hardware — Wholesale, Supply, Retail',
    'owner'       => 'K. Kabra',
    'established' => 2012,
    'nature'      => ['Wholesaler', 'Distributor', 'Supplier', 'Retailer'],
    'gst'         => '08XXXXXXXXXX1ZY',

    'phone'     => env('BIZ_PHONE', '+918048202530'),
    'phone_alt' => env('BIZ_PHONE_ALT', ''),
    'whatsapp'  => env('BIZ_WHATSAPP', '918048202530'),
    'email'     => env('BIZ_EMAIL', 'modiandsons.nathdwara@gmail.com'),

    'address' => [
        'line1'   => 'Nathdwara Road',
        'city'    => 'Nathdwara',
        'district'=> 'Rajsamand',
        'state'   => 'Rajasthan',
        'pincode' => '313301',
        'country' => 'India',
    ],

    'hours' => 'Monday – Saturday, 9:30 AM – 8:00 PM',

    // Google Maps embed URL. Blank falls back to a search built from the address.
    'map_embed' => env('BIZ_MAP_EMBED', ''),

    'serves' => ['Nathdwara', 'Rajsamand', 'Udaipur', 'Chittorgarh', 'Bhilwara', 'Across Rajasthan'],

    // Map pin for local search. Blank omits geo coordinates from the schema
    // rather than publishing a wrong location.
    'geo' => [
        'lat' => env('BIZ_LAT', ''),
        'lng' => env('BIZ_LNG', ''),
    ],

    /*
     * Profiles on other sites. These become schema.org sameAs, which is how
     * search engines tie those listings to this site as one business.
     */
    'social' => [
        'indiamart' => 'https://www.indiamart.com/modi-and-sons-nathdwara/',
        'justdial'  => 'https://www.justdial.com/Nathdwara/Modi-and-Sons-Nathdwara-Road/',
        'facebook'  => '',
        'instagram' => '',
    ],

    'seo' => [
        // Fallback <meta name="description"> for pages that do not set their own.
        'description' => 'Modi And Sons, Nathdwara — wholesaler, distributor and supplier of PVC pipes, '
            .'electrical wires, fans, LED lights, water pumps, tarpaulin and fencing wire. Request a wholesale quote.',

        // Typical order value band, shown by Google for local businesses.
        'price_range' => '₹₹',

        // google-site-verification token (the content value only, not the tag).
        'verification' => env('GOOGLE_SITE_VERIFICATION', ''),
    ],

    'ga4' => env('GA4_ID', ''),
];
