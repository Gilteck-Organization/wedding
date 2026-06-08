<?php

namespace App\Console\Commands;

use App\Models\Guest;
use App\Services\AccessCard\AccessCardImageGenerator;
use App\Services\Whatsapp\WhatsappClient;
use App\Services\Whatsapp\WhatsappException;
use App\Services\Whatsapp\WhatsappSendGuard;
use App\Services\Whatsapp\WhatsappTemplateComponents;
use App\Support\Phone;
use Illuminate\Console\Command;
use RuntimeException;

class WhatsappTest extends Command
{
    protected $signature = 'whatsapp:test
        {phone : The recipient phone number (any common format)}
        {--name=Test Guest : Override guest name in the template body}
        {--guest= : Approved guest access token (defaults to guest with this phone)}';

    protected $description = 'Send the approved WhatsApp template with that guest\'s access card image URL';

    public function handle(WhatsappClient $client, AccessCardImageGenerator $imageGenerator): int
    {
        $rawPhone = (string) $this->argument('phone');
        $to = Phone::toWhatsapp($rawPhone);

        if ($to === null) {
            $this->error("Could not normalize phone '{$rawPhone}' to E.164.");

            return self::FAILURE;
        }

        $templateName = (string) config('services.whatsapp.template_name');
        $language = (string) config('services.whatsapp.template_language', 'en');

        try {
            WhatsappSendGuard::assertReadyToSend();
            $guest = $this->resolveGuest($to);
            $imageGenerator->ensureCached($guest);
            $headerImageUrl = $imageGenerator->publicUrl($guest);
            $components = WhatsappTemplateComponents::forAccessCard($guest, $headerImageUrl);
        } catch (WhatsappException|RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line('Guest: '.$guest->name.' (token: '.$guest->access_token.')');
        $this->line('Sending to '.$to.' using template '.$templateName.' ('.$language.')...');
        $this->line('Phone number ID: '.config('services.whatsapp.phone_number_id'));
        $this->line('Header image: '.$headerImageUrl);

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

    private function resolveGuest(string $normalizedPhone): Guest
    {
        $token = $this->option('guest');

        if (is_string($token) && $token !== '') {
            $guest = Guest::query()
                ->where('access_token', $token)
                ->where('is_approved', true)
                ->whereNotNull('qr_code')
                ->first();

            if ($guest === null) {
                $this->error("No approved guest found for access token '{$token}'.");

                exit(self::FAILURE);
            }

            return $this->applyNameOverride($guest);
        }

        $guest = Guest::query()
            ->where('is_approved', true)
            ->whereNotNull('qr_code')
            ->get()
            ->first(fn (Guest $candidate): bool => Phone::toWhatsapp($candidate->phone) === $normalizedPhone);

        if ($guest === null) {
            $this->error('No approved guest with a QR code matches this phone number.');
            $this->line('Approve the RSVP first, or pass --guest=ACCESS_TOKEN explicitly.');

            exit(self::FAILURE);
        }

        return $this->applyNameOverride($guest);
    }

    private function applyNameOverride(Guest $guest): Guest
    {
        $overrideName = (string) $this->option('name');

        if ($overrideName !== '' && $overrideName !== 'Test Guest') {
            $guest->name = $overrideName;
        }

        return $guest;
    }
}
