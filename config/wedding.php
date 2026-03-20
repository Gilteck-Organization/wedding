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
    | Total venue capacity (seats / guests) for the celebration. Used on the admin dashboard.
    */
    'venue_capacity' => (int) env('WEDDING_VENUE_CAPACITY', 350),

];
