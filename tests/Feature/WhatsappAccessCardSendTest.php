<?php

namespace Tests\Feature;

use App\Jobs\SendAccessCardWhatsappJob;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\Whatsapp\WhatsappClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappAccessCardSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.access_token' => 'TEST_TOKEN',
            'services.whatsapp.template_name' => 'rsvp_approved_access_card',
            'services.whatsapp.template_language' => 'en',
            'services.whatsapp.graph_version' => 'v21.0',
            'services.whatsapp.header_image_url' => 'https://fifiandkiki.com/images/slide-1.png',
            'services.whatsapp.button_url_param_enabled' => true,
            'services.whatsapp.app_secret' => null,
        ]);
    }

    public function test_approval_dispatches_whatsapp_job_after_transaction_commit(): void
    {
        Bus::fake();

        $user = User::factory()->create();

        $rsvp = Rsvp::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'attendance' => 'yes',
            'guest_count' => 1,
            'message' => null,
        ]);

        $this->actingAs($user)
            ->post(route('admin.rsvps.approve', $rsvp))
            ->assertRedirect(route('admin.rsvps.index'));

        Bus::assertDispatchedAfterResponse(SendAccessCardWhatsappJob::class);
    }

    public function test_job_posts_correct_payload_to_meta_graph_api(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '19846581828', 'wa_id' => '19846581828']],
                'messages' => [['id' => 'wamid.TEST123']],
            ], 200),
        ]);

        $guest = Guest::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
        ]);

        (new SendAccessCardWhatsappJob($guest))->handle(app(WhatsappClient::class));

        Http::assertSent(function ($request) use ($guest): bool {
            if (! str_contains($request->url(), '/v21.0/1234567890/messages')) {
                return false;
            }

            $body = $request->data();

            return $body['to'] === '19846581828'
                && $body['type'] === 'template'
                && $body['template']['name'] === 'rsvp_approved_access_card'
                && $body['template']['language']['code'] === 'en'
                && $body['template']['components'][0]['type'] === 'header'
                && $body['template']['components'][0]['parameters'][0]['image']['link'] === 'https://fifiandkiki.com/images/slide-1.png'
                && $body['template']['components'][1]['parameters'][0]['text'] === $guest->name
                && $body['template']['components'][2]['sub_type'] === 'url'
                && $body['template']['components'][2]['parameters'][0]['text'] === $guest->access_token;
        });

        $guest->refresh();

        $this->assertSame('wamid.TEST123', $guest->whatsapp_message_id);
        $this->assertSame('sent', $guest->whatsapp_status);
        $this->assertNotNull($guest->whatsapp_last_sent_at);
        $this->assertSame(1, $guest->whatsapp_attempts);
        $this->assertNull($guest->whatsapp_error);
    }

    public function test_job_records_failure_when_meta_returns_error(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Recipient is not a valid WhatsApp user',
                    'code' => 131_026,
                ],
            ], 400),
        ]);

        $guest = Guest::query()->create([
            'name' => 'Test',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
        ]);

        try {
            (new SendAccessCardWhatsappJob($guest))->handle(app(WhatsappClient::class));
        } catch (\Throwable) {
        }

        $guest->refresh();
        $this->assertNotNull($guest->whatsapp_error);
        $this->assertContains($guest->whatsapp_status, ['failed', 'retrying']);
    }

    public function test_job_skips_when_already_sent_unless_forced(): void
    {
        Http::fake();

        $guest = Guest::query()->create([
            'name' => 'Test',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'whatsapp_message_id' => 'wamid.PREVIOUS',
            'whatsapp_status' => 'delivered',
        ]);

        (new SendAccessCardWhatsappJob($guest))->handle(app(WhatsappClient::class));

        Http::assertNothingSent();
    }

    public function test_force_resend_bypasses_idempotency_check(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.RESEND']],
            ], 200),
        ]);

        $guest = Guest::query()->create([
            'name' => 'Test',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'whatsapp_message_id' => 'wamid.OLD',
            'whatsapp_status' => 'delivered',
        ]);

        (new SendAccessCardWhatsappJob($guest, force: true))
            ->handle(app(WhatsappClient::class));

        $guest->refresh();
        $this->assertSame('wamid.RESEND', $guest->whatsapp_message_id);
    }

    public function test_job_records_failure_when_phone_cannot_be_normalized(): void
    {
        Http::fake();

        $guest = Guest::query()->create([
            'name' => 'Test',
            'phone' => 'abc',
            'is_approved' => true,
        ]);

        (new SendAccessCardWhatsappJob($guest))->handle(app(WhatsappClient::class));

        $guest->refresh();
        $this->assertSame('failed', $guest->whatsapp_status);
        $this->assertNotNull($guest->whatsapp_error);
        Http::assertNothingSent();
    }
}
