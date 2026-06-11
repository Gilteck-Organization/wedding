<?php

namespace App\Jobs;

use App\Models\Guest;
use App\Services\AccessCard\AccessCardImageGenerator;
use App\Services\Whatsapp\WhatsappClient;
use App\Services\Whatsapp\WhatsappException;
use App\Services\Whatsapp\WhatsappSendGuard;
use App\Services\Whatsapp\WhatsappTemplateComponents;
use App\Support\Phone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendAccessCardWhatsappJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        public Guest $guest,
        public bool $force = false,
    ) {}

    /**
     * Prevent two identical jobs running at once for the same guest.
     */
    public function uniqueId(): string
    {
        return 'whatsapp-access-card:'.$this->guest->getKey();
    }

    public function handle(WhatsappClient $client, AccessCardImageGenerator $imageGenerator): void
    {
        $guest = $this->guest->fresh();

        if ($guest === null) {
            return;
        }

        if (! $guest->is_approved) {
            Log::info('WhatsApp send skipped: guest not approved.', ['guest_id' => $guest->id]);

            return;
        }

        if (! $this->force && $guest->whatsapp_message_id !== null && $guest->whatsapp_status !== 'failed') {
            Log::info('WhatsApp send skipped: already delivered or in-flight.', [
                'guest_id' => $guest->id,
                'status' => $guest->whatsapp_status,
                'message_id' => $guest->whatsapp_message_id,
            ]);

            return;
        }

        if (! $this->force && $guest->whatsapp_reminder_sent_at === null) {
            Log::info('WhatsApp access card skipped: reminder not sent yet.', ['guest_id' => $guest->id]);

            return;
        }

        $to = Phone::toWhatsapp($guest->phone);

        if ($to === null) {
            $guest->forceFill([
                'whatsapp_status' => 'failed',
                'whatsapp_status_at' => Carbon::now(),
                'whatsapp_error' => 'Phone number could not be normalized to E.164 ('.$guest->phone.').',
                'whatsapp_attempts' => $guest->whatsapp_attempts + 1,
            ])->save();

            return;
        }

        $templateName = (string) config('services.whatsapp.template_name');
        $language = (string) config('services.whatsapp.template_language', 'en');

        if ($templateName === '') {
            throw new WhatsappException('WHATSAPP_TEMPLATE_NAME is not configured.');
        }

        try {
            WhatsappSendGuard::assertConfigured();

            if ($this->force) {
                $imageGenerator->clearCache($guest);
            }

            $imagePath = $imageGenerator->ensureCached($guest);
            $headerMediaId = $client->uploadImage($imagePath);
            $components = WhatsappTemplateComponents::forAccessCardWithMediaId($guest, $headerMediaId);
            $response = $client->sendTemplate($to, $templateName, $language, $components);
        } catch (WhatsappException $e) {
            $this->recordFailure($guest, $e);

            if ($e->isRetryable() && $this->attempts() < $this->tries) {
                throw $e;
            }

            return;
        } catch (RuntimeException $e) {
            $this->recordFailure($guest, new WhatsappException($e->getMessage()));

            return;
        }

        $messageId = $client->extractMessageId($response);

        $guest->forceFill([
            'whatsapp_message_id' => $messageId,
            'whatsapp_status' => 'sent',
            'whatsapp_status_at' => Carbon::now(),
            'whatsapp_last_sent_at' => Carbon::now(),
            'whatsapp_attempts' => $guest->whatsapp_attempts + 1,
            'whatsapp_error' => null,
        ])->save();

        Log::info('WhatsApp access card dispatched.', [
            'guest_id' => $guest->id,
            'wamid' => $messageId,
            'to' => $to,
            'guest' => $guest->name,
            'access_token' => $guest->access_token,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $guest = $this->guest->fresh();

        if ($guest === null) {
            return;
        }

        $guest->forceFill([
            'whatsapp_status' => 'failed',
            'whatsapp_status_at' => Carbon::now(),
            'whatsapp_error' => mb_substr($exception->getMessage(), 0, 1000),
        ])->save();
    }

    private function recordFailure(Guest $guest, WhatsappException $e): void
    {
        $guest->forceFill([
            'whatsapp_status' => $e->isRetryable() && $this->attempts() < $this->tries ? 'retrying' : 'failed',
            'whatsapp_status_at' => Carbon::now(),
            'whatsapp_error' => mb_substr($e->getMessage(), 0, 1000),
            'whatsapp_attempts' => $guest->whatsapp_attempts + 1,
        ])->save();
    }
}
