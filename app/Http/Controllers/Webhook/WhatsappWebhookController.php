<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        Log::info('WhatsApp webhook event received.', [
            'payload' => $request->all(),
        ]);

        return response('', 200);
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
