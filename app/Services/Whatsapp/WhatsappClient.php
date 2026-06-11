<?php

namespace App\Services\Whatsapp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappClient
{
    /**
     * @param  array<int, array<string, mixed>>  $components
     * @return array<string, mixed>
     *
     * @throws WhatsappException
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        string $languageCode,
        array $components,
    ): array {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        try {
            $response = $this->client()
                ->post($this->endpoint('messages'), $payload)
                ->throw();
        } catch (RequestException $e) {
            $errorBody = $e->response->json();
            $status = $e->response->status();

            Log::warning('WhatsApp send failed.', [
                'to' => $to,
                'template' => $templateName,
                'status' => $status,
                'error' => $errorBody,
            ]);

            throw new WhatsappException(
                $this->extractMessage($errorBody) ?? 'WhatsApp API error.',
                is_array($errorBody) ? $errorBody : null,
                $status,
                $e,
            );
        }

        return $response->json();
    }

    /**
     * Upload a JPEG access card to Meta. Returns a media id for template header parameters.
     *
     * @throws WhatsappException
     */
    public function uploadImage(string $absolutePath): string
    {
        if (! is_file($absolutePath)) {
            throw new WhatsappException('Access card image file not found: '.$absolutePath);
        }

        $token = (string) config('services.whatsapp.access_token');

        try {
            $response = Http::withToken($token)
                ->attach(
                    'file',
                    fopen($absolutePath, 'r'),
                    basename($absolutePath),
                    ['Content-Type' => 'image/jpeg'],
                )
                ->post($this->endpoint('media'), [
                    'messaging_product' => 'whatsapp',
                    'type' => 'image/jpeg',
                ])
                ->throw();
        } catch (RequestException $e) {
            $errorBody = $e->response->json();
            $status = $e->response->status();

            Log::warning('WhatsApp media upload failed.', [
                'path' => $absolutePath,
                'status' => $status,
                'error' => $errorBody,
            ]);

            throw new WhatsappException(
                $this->extractMessage($errorBody) ?? 'WhatsApp media upload failed.',
                is_array($errorBody) ? $errorBody : null,
                $status,
                $e,
            );
        }

        $mediaId = $response->json('id');

        if (! is_string($mediaId) || $mediaId === '') {
            throw new WhatsappException('WhatsApp media upload did not return a media id.');
        }

        return $mediaId;
    }

    /**
     * Extract the `wamid` (WhatsApp message id) from a send response.
     */
    public function extractMessageId(array $sendResponse): ?string
    {
        return $sendResponse['messages'][0]['id'] ?? null;
    }

    private function client(): PendingRequest
    {
        $token = (string) config('services.whatsapp.access_token');

        return Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->connectTimeout(5);
    }

    private function endpoint(string $path): string
    {
        $version = (string) config('services.whatsapp.graph_version', 'v21.0');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');

        return "https://graph.facebook.com/{$version}/{$phoneNumberId}/{$path}";
    }

    /**
     * @param  array<string, mixed>|null  $errorBody
     */
    private function extractMessage(?array $errorBody): ?string
    {
        if ($errorBody === null) {
            return null;
        }

        return $errorBody['error']['error_user_msg']
            ?? $errorBody['error']['message']
            ?? null;
    }
}
