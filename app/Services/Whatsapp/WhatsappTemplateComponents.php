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
        return self::bodyNameOnly($guest);
    }

    /**
     * Thank-you template (body name only — same shape as reminder).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forThankYou(Guest $guest): array
    {
        return self::bodyNameOnly($guest);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function bodyNameOnly(Guest $guest): array
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
            $buttonUrl = self::accessCardButtonUrl($guest);

            if ($buttonUrl !== '') {
                $components[] = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => (string) config('services.whatsapp.template_url_button_index', 0),
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $buttonUrl,
                        ],
                    ],
                ];
            }
        }

        return $components;
    }

    public static function accessCardButtonUrl(Guest $guest): string
    {
        $token = (string) $guest->access_token;

        if ($token === '') {
            return '';
        }

        $mode = (string) config('services.whatsapp.template_button_url_mode', 'full');

        if ($mode === 'token') {
            return $token;
        }

        $publicRoot = config('services.whatsapp.public_app_url');

        if (is_string($publicRoot) && $publicRoot !== '') {
            return rtrim($publicRoot, '/').'/access-card/'.$token;
        }

        return route('access-card', $guest, absolute: true);
    }
}
