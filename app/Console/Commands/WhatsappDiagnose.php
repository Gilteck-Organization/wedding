<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WhatsappDiagnose extends Command
{
    protected $signature = 'whatsapp:diagnose';

    protected $description = 'Verify WhatsApp credentials, phone number ID, and template against Meta Graph API';

    public function handle(): int
    {
        $token = (string) config('services.whatsapp.access_token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $wabaId = (string) config('services.whatsapp.business_account_id');
        $templateName = (string) config('services.whatsapp.template_name');
        $reminderTemplateName = (string) config('services.whatsapp.reminder_template_name');
        $thankYouTemplateName = (string) config('services.whatsapp.thankyou_template_name');
        $version = (string) config('services.whatsapp.graph_version', 'v21.0');

        if ($token === '') {
            $this->error('WHATSAPP_ACCESS_TOKEN is not set.');

            return self::FAILURE;
        }

        $this->info('Checking token…');
        $me = Http::withToken($token)->get("https://graph.facebook.com/{$version}/me")->json();
        if (isset($me['error'])) {
            $this->error('Token invalid: '.($me['error']['message'] ?? 'unknown'));

            return self::FAILURE;
        }
        $this->line('  Token OK (user/app id: '.($me['id'] ?? '?').')');

        if ($wabaId !== '') {
            $waba = Http::withToken($token)->get("https://graph.facebook.com/{$version}/{$wabaId}")->json();
            if (isset($waba['error'])) {
                $this->warn('  WABA not readable: '.($waba['error']['message'] ?? ''));
            } else {
                $this->line('  WABA OK: '.($waba['name'] ?? $wabaId));
            }

            $phones = Http::withToken($token)->get("https://graph.facebook.com/{$version}/{$wabaId}/phone_numbers")->json();
            $numbers = $phones['data'] ?? [];
            if ($numbers === []) {
                $this->warn('  No phone numbers found on this WABA.');
            } else {
                $this->line('  Phone numbers on WABA:');
                foreach ($numbers as $number) {
                    $id = (string) ($number['id'] ?? '');
                    $display = (string) ($number['display_phone_number'] ?? '');
                    $match = $id === $phoneNumberId ? ' ← configured ID' : '';
                    $this->line("    - {$display} (id: {$id}){$match}");
                }
            }
        }

        if ($phoneNumberId === '') {
            $this->error('WHATSAPP_PHONE_NUMBER_ID is not set.');

            return self::FAILURE;
        }

        $phone = Http::withToken($token)->get("https://graph.facebook.com/{$version}/{$phoneNumberId}")->json();
        if (isset($phone['error'])) {
            $this->error('Configured WHATSAPP_PHONE_NUMBER_ID is invalid or not permitted.');
            $this->line('  '.($phone['error']['message'] ?? ''));
            if (! empty($numbers)) {
                $this->line('');
                $this->line('Use one of the IDs listed above in WHATSAPP_PHONE_NUMBER_ID.');
            }

            return self::FAILURE;
        }
        $this->line('  Phone number ID OK: '.($phone['display_phone_number'] ?? $phoneNumberId));

        if ($reminderTemplateName !== '' && $wabaId !== '') {
            $templates = Http::withToken($token)
                ->get("https://graph.facebook.com/{$version}/{$wabaId}/message_templates", [
                    'name' => $reminderTemplateName,
                ])
                ->json();
            $template = $templates['data'][0] ?? null;
            if ($template === null) {
                $this->warn("  Reminder template '{$reminderTemplateName}' not found on WABA.");
            } else {
                $this->line('  Reminder template OK: '.$reminderTemplateName.' ('.($template['status'] ?? '?').')');
            }
        }

        if ($templateName !== '' && $wabaId !== '') {
            $templates = Http::withToken($token)
                ->get("https://graph.facebook.com/{$version}/{$wabaId}/message_templates", [
                    'name' => $templateName,
                ])
                ->json();
            $template = $templates['data'][0] ?? null;
            if ($template === null) {
                $this->warn("  Template '{$templateName}' not found on WABA.");
            } else {
                $this->line('  Access card template OK: '.$templateName.' ('.($template['status'] ?? '?').')');
                $this->line('  Body param format: '.($template['parameter_format'] ?? 'POSITIONAL'));
            }
        }

        if ($thankYouTemplateName !== '' && $wabaId !== '') {
            $templates = Http::withToken($token)
                ->get("https://graph.facebook.com/{$version}/{$wabaId}/message_templates", [
                    'name' => $thankYouTemplateName,
                ])
                ->json();
            $template = $templates['data'][0] ?? null;
            if ($template === null) {
                $this->warn("  Thank-you template '{$thankYouTemplateName}' not found on WABA.");
            } else {
                $this->line('  Thank-you template OK: '.$thankYouTemplateName.' ('.($template['status'] ?? '?').')');
            }
        }

        $publicAppUrl = rtrim((string) config('services.whatsapp.public_app_url', ''), '/');

        if ($publicAppUrl === '') {
            $this->warn('  WHATSAPP_PUBLIC_APP_URL is not set — WhatsApp sends disabled until staging/production URL is configured.');
        } elseif (! str_starts_with($publicAppUrl, 'https://')) {
            $this->error('WHATSAPP_PUBLIC_APP_URL must be HTTPS.');
            $this->line('  Current: '.$publicAppUrl);

            return self::FAILURE;
        } elseif (str_contains($publicAppUrl, '.test') || str_contains($publicAppUrl, 'localhost')) {
            $this->error('WHATSAPP_PUBLIC_APP_URL must be a public staging or production host.');
            $this->line('  Current: '.$publicAppUrl);

            return self::FAILURE;
        } else {
            $this->line('  Access card image base URL: '.$publicAppUrl.'/access-card/{token}/image.jpg');
            $this->line('  Access card button URL (full): '.$publicAppUrl.'/access-card/{token}');
            $this->line('  Meta button template URL should be: {{1}} with sample '.$publicAppUrl.'/access-card/tizax');
        }

        $this->newLine();

        $this->info('Diagnosis complete. Run `php artisan whatsapp:test +234…` to send a test message.');

        return self::SUCCESS;
    }
}
