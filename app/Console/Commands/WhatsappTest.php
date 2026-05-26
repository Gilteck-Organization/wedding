<?php

namespace App\Console\Commands;

use App\Services\Whatsapp\WhatsappClient;
use App\Services\Whatsapp\WhatsappException;
use App\Support\Phone;
use Illuminate\Console\Command;

class WhatsappTest extends Command
{
    protected $signature = 'whatsapp:test
        {phone : The recipient phone number (any common format)}
        {--name=Test Guest : Body variable {{1}} for the template}
        {--token=abcde : URL button suffix (the access token), if button URL var is enabled}';

    protected $description = 'Send the approved WhatsApp template to a single recipient for verification';

    public function handle(WhatsappClient $client): int
    {
        $rawPhone = (string) $this->argument('phone');
        $to = Phone::toWhatsapp($rawPhone);

        if ($to === null) {
            $this->error("Could not normalize phone '{$rawPhone}' to E.164.");

            return self::FAILURE;
        }

        $templateName = (string) config('services.whatsapp.template_name');
        $language = (string) config('services.whatsapp.template_language', 'en');

        if ($templateName === '') {
            $this->error('WHATSAPP_TEMPLATE_NAME is not configured.');

            return self::FAILURE;
        }

        $components = [
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => [
                            'link' => (string) (config('services.whatsapp.header_image_url') ?: asset('images/slide-1.png')),
                        ],
                    ],
                ],
            ],
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => (string) $this->option('name')],
                ],
            ],
        ];

        if (config('services.whatsapp.button_url_param_enabled')) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => (string) $this->option('token')],
                ],
            ];
        }

        $this->line('Sending to '.$to.' using template '.$templateName.' ('.$language.')...');

        try {
            $response = $client->sendTemplate($to, $templateName, $language, $components);
        } catch (WhatsappException $e) {
            $this->error('Send failed: '.$e->getMessage());
            $this->line('HTTP status: '.($e->httpStatus ?? 'n/a'));
            $this->line('Error body:');
            $this->line(json_encode($e->errorBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');

            return self::FAILURE;
        }

        $this->info('Sent. wamid='.($client->extractMessageId($response) ?? 'n/a'));
        $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');

        return self::SUCCESS;
    }
}
