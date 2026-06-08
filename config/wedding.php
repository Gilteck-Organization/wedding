<?php

return [

    /*
    | Primary brand color (RGB). Keep in sync with --color-wedding-primary in resources/css/app.css.
    |
    | @see resources/css/app.css
    */
    'primary_rgb' => [
        'r' => 148,
        'g' => 97,
        'b' => 18,
    ],

    /*
    | Access card QR module color (#3a2c17).
    */
    'access_card_qr_rgb' => [
        'r' => 58,
        'g' => 44,
        'b' => 23,
    ],

    /*
    | Layout for server-rendered access card images (WhatsApp header, etc.).
    | Percentages mirror resources/css/app.css → .access-card-stage custom properties.
    */
    'access_card_image' => [
        'max_width' => 1200,
        'jpeg_quality' => 88,
        'qr_top_percent' => 75,
        'qr_left_percent' => 28,
        'qr_size_percent' => 17.14,
        'name_top_percent' => 58,
        'name_left_percent' => 50,
        'name_font_size_percent' => 3.4,
        'party_font_size_percent' => 2.9,
    ],

    /*
    | Total venue capacity (seats / guests) for the celebration. Used on the admin dashboard.
    */
    'venue_capacity' => (int) env('WEDDING_VENUE_CAPACITY', 350),

];
