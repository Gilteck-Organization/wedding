<?php

namespace App\Services\Whatsapp;

use App\Models\Guest;

class WhatsappTemplateComponents
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forReminder(Guest $guest): array
    {
        $paramName = (string) config('services.whatsapp.template_body_param_name', 'n');

        return [
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forAccessCardWithLink(Guest $guest, string $headerImageUrl): array
    {
        return self::build($guest, ['link' => $headerImageUrl]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forAccessCardWithMediaId(Guest $guest, string $headerMediaId): array
    {
        return self::build($guest, ['id' => $headerMediaId]);
    }

    /**
     * @param  array<string, string>  $headerImage
     * @return array<int, array<string, mixed>>
     */
    private static function build(Guest $guest, array $headerImage): array
    {
        $paramName = (string) config('services.whatsapp.template_body_param_name', 'n');

        $components = [
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => $headerImage,
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

        if (config('services.whatsapp.template_url_button', true)) {
            $token = (string) $guest->access_token;

            if ($token !== '') {
                $components[] = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => (string) config('services.whatsapp.template_url_button_index', 0),
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $token,
                        ],
                    ],
                ];
            }
        }

        return $components;
    }
}
