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

        if ((string) config('services.whatsapp.reminder_template_name') === '') {
            throw new WhatsappException('WHATSAPP_REMINDER_TEMPLATE_NAME is not set.');
        }
    }

    public static function isConfigured(): bool
    {
        try {
            self::assertConfigured();
        } catch (WhatsappException) {
            return false;
        }

        return true;
    }
}
