<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Meta verification handshake. Called once when the webhook is saved in
     * the Meta App dashboard. We must echo back hub.challenge as plain text
     * when hub.verify_token matches our shared secret.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expected = config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && is_string($token) && is_string($expected) && hash_equals($expected, $token)) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed.', [
            'mode' => $mode,
            'token_present' => $token !== null,
            'expected_configured' => $expected !== null,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Incoming events from Meta (delivery status, read receipts, inbound
     * messages, template status updates). Always respond 200 quickly so
     * Meta does not retry; processing is logged for now.
     */
    public function handle(Request $request): Response
    {
        if (! $this->signatureIsValid($request)) {
            Log::warning('WhatsApp webhook signature mismatch.', [
                'has_signature' => $request->hasHeader('X-Hub-Signature-256'),
            ]);

            return response('Invalid signature', 403);
        }

        $payload = $request->all();

        $this->processStatuses($payload);

        Log::info('WhatsApp webhook event received.', [
            'payload' => $payload,
        ]);

        return response('', 200);
    }

    /**
     * Update the guest record for each delivery / read / failure status
     * Meta reports in the webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function processStatuses(array $payload): void
    {
        $entries = $payload['entry'] ?? [];

        if (! is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            if (! is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                $statuses = $change['value']['statuses'] ?? [];

                if (! is_array($statuses)) {
                    continue;
                }

                foreach ($statuses as $status) {
                    $this->applyStatus($status);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function applyStatus(array $status): void
    {
        $wamid = $status['id'] ?? null;
        $state = $status['status'] ?? null;
        $timestamp = $status['timestamp'] ?? null;

        if (! is_string($wamid) || ! is_string($state)) {
            return;
        }

        $guest = Guest::query()
            ->where('whatsapp_message_id', $wamid)
            ->orWhere('whatsapp_reminder_message_id', $wamid)
            ->first();

        if ($guest === null) {
            return;
        }

        $isReminder = $guest->whatsapp_reminder_message_id === $wamid;

        $statusAt = is_numeric($timestamp)
            ? Carbon::createFromTimestamp((int) $timestamp)
            : Carbon::now();

        if ($guest->whatsapp_status_at !== null && ! $isReminder && $guest->whatsapp_status_at->greaterThan($statusAt)) {
            return;
        }

        if ($isReminder) {
            if ($state === 'failed') {
                $errors = $status['errors'] ?? [];
                $first = is_array($errors) && isset($errors[0]) ? $errors[0] : null;
                $message = is_array($first)
                    ? ($first['error_data']['details'] ?? $first['title'] ?? $first['message'] ?? 'Unknown error')
                    : 'Unknown error';

                $guest->forceFill([
                    'whatsapp_reminder_error' => mb_substr((string) $message, 0, 1000),
                ])->save();
            }

            return;
        }

        $update = [
            'whatsapp_status' => $state,
            'whatsapp_status_at' => $statusAt,
        ];

        if ($state === 'failed') {
            $errors = $status['errors'] ?? [];
            $first = is_array($errors) && isset($errors[0]) ? $errors[0] : null;
            $message = is_array($first)
                ? ($first['error_data']['details'] ?? $first['title'] ?? $first['message'] ?? 'Unknown error')
                : 'Unknown error';

            $update['whatsapp_error'] = mb_substr((string) $message, 0, 1000);
        }

        $guest->forceFill($update)->save();
    }

    /**
     * Verify the X-Hub-Signature-256 header against the raw request body
     * using the app secret. Skipped only when no app secret is configured
     * (useful while bootstrapping the integration).
     */
    private function signatureIsValid(Request $request): bool
    {
        $appSecret = config('services.whatsapp.app_secret');

        if (! is_string($appSecret) || $appSecret === '') {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (! is_string($signature) || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }
}
