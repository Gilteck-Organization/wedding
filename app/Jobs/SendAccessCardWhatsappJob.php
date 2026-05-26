<?php

namespace App\Jobs;

use App\Models\Guest;
use App\Services\Whatsapp\WhatsappClient;
use App\Services\Whatsapp\WhatsappException;
use App\Support\Phone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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

    public function handle(WhatsappClient $client): void
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

        $components = $this->buildComponents($guest);

        try {
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

    /**
     * Build the template `components` array.
     *
     * Template shape (must match the approved template in Meta):
     *   - header: image (link)
     *   - body:   1 variable {{1}} = guest name
     *   - button: URL with 1 variable {{1}} = guest access token (short id)
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildComponents(Guest $guest): array
    {
        $components = [
            [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => 'image',
                        'image' => ['link' => $this->headerImageUrl()],
                    ],
                ],
            ],
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $guest->name],
                ],
            ],
        ];

        if (config('services.whatsapp.button_url_param_enabled')) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => $guest->access_token],
                ],
            ];
        }

        return $components;
    }

    private function headerImageUrl(): string
    {
        $configured = (string) config('services.whatsapp.header_image_url');

        if ($configured !== '') {
            return $configured;
        }

        return asset('images/slide-1.png');
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
