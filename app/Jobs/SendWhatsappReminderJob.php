<?php

namespace App\Jobs;

use App\Models\Guest;
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
use Throwable;

class SendWhatsappReminderJob implements ShouldQueue
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

    public function uniqueId(): string
    {
        return 'whatsapp-reminder:'.$this->guest->getKey();
    }

    public function handle(WhatsappClient $client): void
    {
        $guest = $this->guest->fresh();

        if ($guest === null) {
            return;
        }

        if (! $guest->is_approved) {
            Log::info('WhatsApp reminder skipped: guest not approved.', ['guest_id' => $guest->id]);

            return;
        }

        if (! $this->force && $guest->whatsapp_reminder_sent_at !== null) {
            $this->queueAccessCardIfNeeded($guest);

            return;
        }

        $to = Phone::toWhatsapp($guest->phone);

        if ($to === null) {
            $guest->forceFill([
                'whatsapp_reminder_error' => 'Phone number could not be normalized to E.164 ('.$guest->phone.').',
            ])->save();

            return;
        }

        $templateName = (string) config('services.whatsapp.reminder_template_name');
        $language = (string) config('services.whatsapp.template_language', 'en');

        if ($templateName === '') {
            throw new WhatsappException('WHATSAPP_REMINDER_TEMPLATE_NAME is not configured.');
        }

        try {
            WhatsappSendGuard::assertConfigured();
            $components = WhatsappTemplateComponents::forReminder($guest);
            $response = $client->sendTemplate($to, $templateName, $language, $components);
        } catch (WhatsappException $e) {
            $this->recordFailure($guest, $e);

            if ($e->isRetryable() && $this->attempts() < $this->tries) {
                throw $e;
            }

            return;
        }

        $messageId = $client->extractMessageId($response);

        $guest->forceFill([
            'whatsapp_reminder_message_id' => $messageId,
            'whatsapp_reminder_sent_at' => Carbon::now(),
            'whatsapp_reminder_error' => null,
        ])->save();

        Log::info('WhatsApp reminder dispatched.', [
            'guest_id' => $guest->id,
            'wamid' => $messageId,
            'to' => $to,
            'guest' => $guest->name,
        ]);

        $this->queueAccessCard($guest);
    }

    public function failed(Throwable $exception): void
    {
        $guest = $this->guest->fresh();

        if ($guest === null) {
            return;
        }

        $guest->forceFill([
            'whatsapp_reminder_error' => mb_substr($exception->getMessage(), 0, 1000),
        ])->save();
    }

    private function queueAccessCardIfNeeded(Guest $guest): void
    {
        if ($guest->whatsapp_message_id !== null && $guest->whatsapp_status !== 'failed') {
            return;
        }

        $this->queueAccessCard($guest);
    }

    private function queueAccessCard(Guest $guest): void
    {
        $delaySeconds = max(0, (int) config('services.whatsapp.access_card_delay_seconds', 45));

        SendAccessCardWhatsappJob::dispatch($guest, force: $this->force)
            ->delay(now()->addSeconds($delaySeconds));
    }

    private function recordFailure(Guest $guest, WhatsappException $e): void
    {
        $guest->forceFill([
            'whatsapp_reminder_error' => mb_substr($e->getMessage(), 0, 1000),
        ])->save();
    }
}
