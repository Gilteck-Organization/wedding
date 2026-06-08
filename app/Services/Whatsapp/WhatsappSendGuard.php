<?php

namespace App\Services\Whatsapp;

class WhatsappSendGuard
{
    /**
     * @throws WhatsappException
     */
    public static function assertConfigured(): void
    {
        if ((string) config('services.whatsapp.access_token') === '') {
            throw new WhatsappException('WHATSAPP_ACCESS_TOKEN is not set.');
        }

        if ((string) config('services.whatsapp.phone_number_id') === '') {
            throw new WhatsappException('WHATSAPP_PHONE_NUMBER_ID is not set.');
        }

        if ((string) config('services.whatsapp.template_name') === '') {
            throw new WhatsappException('WHATSAPP_TEMPLATE_NAME is not set.');
        }
    }

    /**
     * Credentials plus a public HTTPS URL Meta can use to fetch each guest's access card image.
     *
     * @throws WhatsappException
     */
    public static function assertReadyToSend(): void
    {
        self::assertConfigured();

        $publicAppUrl = config('services.whatsapp.public_app_url');

        if (! is_string($publicAppUrl) || $publicAppUrl === '') {
            throw new WhatsappException(
                'WHATSAPP_PUBLIC_APP_URL is not set. Set it to your staging or production URL '
                .'(e.g. https://staging.fifiandkiki.com) so Meta can fetch each guest\'s access card image.'
            );
        }

        if (! str_starts_with($publicAppUrl, 'https://')) {
            throw new WhatsappException(
                'WHATSAPP_PUBLIC_APP_URL must be HTTPS. Current: '.$publicAppUrl
            );
        }

        $host = parse_url($publicAppUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            throw new WhatsappException('WHATSAPP_PUBLIC_APP_URL is invalid: '.$publicAppUrl);
        }

        if (str_ends_with($host, '.test') || in_array($host, ['localhost', '127.0.0.1'], true)) {
            throw new WhatsappException(
                'WHATSAPP_PUBLIC_APP_URL must be a public staging or production host, not '.$host.'.'
            );
        }
    }

    public static function isReadyToSend(): bool
    {
        try {
            self::assertReadyToSend();
        } catch (WhatsappException) {
            return false;
        }

        return true;
    }
}
