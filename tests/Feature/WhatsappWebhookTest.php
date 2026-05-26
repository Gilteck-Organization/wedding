<?php

namespace Tests\Feature;

use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.verify_token' => 'test-verify-token',
            'services.whatsapp.app_secret' => null,
        ]);
    }

    public function test_verification_handshake_returns_challenge_for_matching_token(): void
    {
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=challenge-abc')
            ->assertOk()
            ->assertSee('challenge-abc');
    }

    public function test_verification_handshake_rejects_wrong_token(): void
    {
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=challenge-abc')
            ->assertForbidden();
    }

    public function test_delivery_status_updates_guest_record(): void
    {
        $guest = Guest::query()->create([
            'name' => 'Test',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'whatsapp_message_id' => 'wamid.ABC',
            'whatsapp_status' => 'sent',
        ]);

        $this->postJson('/webhook/whatsapp', [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.ABC',
                            'status' => 'delivered',
                            'timestamp' => (string) now()->getTimestamp(),
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $guest->refresh();
        $this->assertSame('delivered', $guest->whatsapp_status);
    }

    public function test_failed_status_records_error_message(): void
    {
        $guest = Guest::query()->create([
            'name' => 'Test',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'whatsapp_message_id' => 'wamid.FAIL',
            'whatsapp_status' => 'sent',
        ]);

        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.FAIL',
                            'status' => 'failed',
                            'timestamp' => (string) now()->getTimestamp(),
                            'errors' => [[
                                'title' => 'Recipient not on WhatsApp',
                                'error_data' => ['details' => 'The recipient phone number is not a WhatsApp user.'],
                            ]],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $guest->refresh();
        $this->assertSame('failed', $guest->whatsapp_status);
        $this->assertSame('The recipient phone number is not a WhatsApp user.', $guest->whatsapp_error);
    }

    public function test_signature_check_rejects_invalid_signature(): void
    {
        config(['services.whatsapp.app_secret' => 'super_secret']);

        $this->postJson('/webhook/whatsapp', ['entry' => []], [
            'X-Hub-Signature-256' => 'sha256=invalid',
        ])->assertForbidden();
    }

    public function test_signature_check_accepts_valid_signature(): void
    {
        config(['services.whatsapp.app_secret' => 'super_secret']);

        $body = ['entry' => []];
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'super_secret');

        $this->call(
            method: 'POST',
            uri: '/webhook/whatsapp',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            content: $raw,
        )->assertOk();
    }
}
