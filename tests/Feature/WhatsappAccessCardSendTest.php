<?php

namespace Tests\Feature;

use App\Jobs\SendAccessCardWhatsappJob;
use App\Jobs\SendWhatsappReminderJob;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\AccessCard\AccessCardImageGenerator;
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
            'services.whatsapp.template_name' => 'wedding_access_card',
            'services.whatsapp.reminder_template_name' => 'wedding_access_reminder',
            'services.whatsapp.template_language' => 'en',
            'services.whatsapp.graph_version' => 'v21.0',
            'services.whatsapp.template_body_param_name' => 'n',
            'services.whatsapp.app_secret' => null,
            'services.whatsapp.public_app_url' => 'https://staging.fifiandkiki.com',
            'services.whatsapp.access_card_delay_seconds' => 45,
            'app.url' => 'http://wedding.test',
        ]);
    }

    private function approvedGuest(array $overrides = []): Guest
    {
        $guest = Guest::query()->create(array_merge([
            'name' => 'Test Guest',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
        ], $overrides));

        $guest->update([
            'qr_code' => route('access-card.verify', $guest, absolute: true),
        ]);

        return $guest->fresh();
    }

    private function runAccessCardJob(SendAccessCardWhatsappJob $job): void
    {
        $job->handle(
            app(WhatsappClient::class),
            app(AccessCardImageGenerator::class),
        );
    }

    private function runReminderJob(SendWhatsappReminderJob $job): void
    {
        $job->handle(app(WhatsappClient::class));
    }

    public function test_approval_dispatches_reminder_job_after_transaction_commit(): void
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

        Bus::assertDispatchedAfterResponse(SendWhatsappReminderJob::class);
    }

    public function test_reminder_job_sends_template_and_queues_access_card(): void
    {
        Bus::fake();

        Http::fake([
            'graph.facebook.com/v21.0/1234567890/messages' => Http::response([
                'messages' => [['id' => 'wamid.REMINDER123']],
            ], 200),
        ]);

        $guest = $this->approvedGuest();

        $this->runReminderJob(new SendWhatsappReminderJob($guest));

        Http::assertSent(function ($request) use ($guest): bool {
            if (! str_contains($request->url(), '/v21.0/1234567890/messages')) {
                return false;
            }

            $body = $request->data();

            return $body['to'] === '19846581828'
                && $body['type'] === 'template'
                && $body['template']['name'] === 'wedding_access_reminder'
                && $body['template']['components'][0]['type'] === 'body'
                && $body['template']['components'][0]['parameters'][0]['parameter_name'] === 'n'
                && $body['template']['components'][0]['parameters'][0]['text'] === $guest->name;
        });

        $guest->refresh();
        $this->assertSame('wamid.REMINDER123', $guest->whatsapp_reminder_message_id);
        $this->assertNotNull($guest->whatsapp_reminder_sent_at);
        $this->assertNull($guest->whatsapp_reminder_error);

        Bus::assertDispatched(SendAccessCardWhatsappJob::class, function (SendAccessCardWhatsappJob $job) use ($guest): bool {
            return $job->guest->is($guest);
        });
    }

    public function test_access_card_job_skips_when_reminder_not_sent(): void
    {
        Http::fake();

        $guest = $this->approvedGuest();

        $this->runAccessCardJob(new SendAccessCardWhatsappJob($guest));

        Http::assertNothingSent();
    }

    public function test_job_uploads_access_card_and_posts_media_id_to_meta_graph_api(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/1234567890/media' => Http::response([
                'id' => 'MEDIA_TEST_123',
            ], 200),
            'graph.facebook.com/v21.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '19846581828', 'wa_id' => '19846581828']],
                'messages' => [['id' => 'wamid.TEST123']],
            ], 200),
        ]);

        $guest = Guest::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'access_token' => 'abcde',
            'qr_code' => route('access-card.verify', 'abcde', absolute: true),
            'whatsapp_reminder_sent_at' => now(),
        ]);

        $this->runAccessCardJob(new SendAccessCardWhatsappJob($guest));

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/v21.0/1234567890/media');
        });

        Http::assertSent(function ($request) use ($guest): bool {
            if (! str_contains($request->url(), '/v21.0/1234567890/messages')) {
                return false;
            }

            $body = $request->data();

            return $body['to'] === '19846581828'
                && $body['type'] === 'template'
                && $body['template']['name'] === 'wedding_access_card'
                && $body['template']['language']['code'] === 'en'
                && $body['template']['components'][0]['type'] === 'header'
                && $body['template']['components'][0]['parameters'][0]['image']['id'] === 'MEDIA_TEST_123'
                && $body['template']['components'][1]['parameters'][0]['parameter_name'] === 'n'
                && $body['template']['components'][1]['parameters'][0]['text'] === $guest->name
                && $body['template']['components'][2]['type'] === 'button'
                && $body['template']['components'][2]['sub_type'] === 'url'
                && $body['template']['components'][2]['index'] === '0'
                && $body['template']['components'][2]['parameters'][0]['text'] === 'abcde'
                && count($body['template']['components']) === 3;
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

        $guest = $this->approvedGuest([
            'whatsapp_reminder_sent_at' => now(),
        ]);

        try {
            $this->runAccessCardJob(new SendAccessCardWhatsappJob($guest));
        } catch (\Throwable) {
        }

        $guest->refresh();
        $this->assertNotNull($guest->whatsapp_error);
        $this->assertContains($guest->whatsapp_status, ['failed', 'retrying']);
    }

    public function test_job_skips_when_already_sent_unless_forced(): void
    {
        Http::fake();

        $guest = $this->approvedGuest([
            'whatsapp_reminder_sent_at' => now(),
            'whatsapp_message_id' => 'wamid.PREVIOUS',
            'whatsapp_status' => 'delivered',
        ]);

        $this->runAccessCardJob(new SendAccessCardWhatsappJob($guest));

        Http::assertNothingSent();
    }

    public function test_force_resend_bypasses_idempotency_check(): void
    {
        Http::fake([
            'graph.facebook.com/v21.0/1234567890/media' => Http::response(['id' => 'MEDIA_RESEND'], 200),
            'graph.facebook.com/v21.0/1234567890/messages' => Http::response([
                'messages' => [['id' => 'wamid.RESEND']],
            ], 200),
        ]);

        $guest = $this->approvedGuest([
            'whatsapp_reminder_sent_at' => now(),
            'whatsapp_message_id' => 'wamid.OLD',
            'whatsapp_status' => 'delivered',
        ]);

        $this->runAccessCardJob(new SendAccessCardWhatsappJob($guest, force: true));

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
            'whatsapp_reminder_sent_at' => now(),
        ]);

        $this->runAccessCardJob(new SendAccessCardWhatsappJob($guest));

        $guest->refresh();
        $this->assertSame('failed', $guest->whatsapp_status);
        $this->assertNotNull($guest->whatsapp_error);
        Http::assertNothingSent();
    }

    public function test_job_records_failure_when_access_token_is_not_set(): void
    {
        Http::fake();

        config(['services.whatsapp.access_token' => null]);

        $guest = $this->approvedGuest([
            'whatsapp_reminder_sent_at' => now(),
        ]);

        $this->runAccessCardJob(new SendAccessCardWhatsappJob($guest));

        $guest->refresh();
        $this->assertSame('failed', $guest->whatsapp_status);
        $this->assertStringContainsString('WHATSAPP_ACCESS_TOKEN', (string) $guest->whatsapp_error);
        Http::assertNothingSent();
    }
}
