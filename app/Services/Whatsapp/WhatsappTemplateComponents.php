<?php

namespace App\Services\Whatsapp;

use App\Models\Guest;

class WhatsappTemplateComponents
{
    /**
     * Build components for the approved `rsvp_approved_access_card` template:
     * - HEADER: image (per-guest public URL Meta fetches)
     * - BODY: named parameter `n` (guest name)
     * - BUTTONS: static quick reply (no API parameters)
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forAccessCard(Guest $guest, string $headerImageUrl): array
    {
        $paramName = (string) config('services.whatsapp.template_body_param_name', 'n');

        return [
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => [
                            'link' => $headerImageUrl,
                        ],
                    ],
                ],
            ],
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'parameter_name' => $paramName,
                        'text' => $guest->name,
                    ],
                ],
            ],
        ];
    }
}
