<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappThankYouJob;
use App\Models\Guest;
use App\Models\User;
use App\Services\Whatsapp\WhatsappClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappThankYouTest extends TestCase
{
    use RefreshDatabase;

    private function configureThankYou(): void
    {
        config([
            'services.whatsapp.access_token' => 'TEST_TOKEN',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.thankyou_template_name' => 'thank_fifi_kiki',
            'services.whatsapp.template_language' => 'en',
            'services.whatsapp.template_body_param_name' => 'n',
            'services.whatsapp.graph_version' => 'v21.0',
        ]);
    }

    public function test_bulk_thank_you_only_queues_checked_in_guests(): void
    {
        Bus::fake();
        $this->configureThankYou();

        $user = User::factory()->create();

        $attended = Guest::query()->create([
            'name' => 'Attended Guest',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify',
            'qr_verified_at' => now(),
        ]);

        Guest::query()->create([
            'name' => 'Not Checked In',
            'phone' => '+1 (984) 658-1829',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify2',
            'qr_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('admin.whatsapp.send'), [
                'intent' => 'thank_you:all_pending',
            ])
            ->assertRedirect(route('admin.whatsapp.index'))
            ->assertSessionHas('success');

        Bus::assertDispatched(SendWhatsappThankYouJob::class, function (SendWhatsappThankYouJob $job) use ($attended): bool {
            return $job->guest->is($attended);
        });

        Bus::assertDispatchedTimes(SendWhatsappThankYouJob::class, 1);
    }

    public function test_bulk_thank_you_skips_selected_guests_who_were_not_checked_in(): void
    {
        Bus::fake();
        $this->configureThankYou();

        $user = User::factory()->create();

        $notCheckedIn = Guest::query()->create([
            'name' => 'Not Checked In',
            'phone' => '+1 (984) 658-1829',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify2',
        ]);

        $this->actingAs($user)
            ->post(route('admin.whatsapp.send'), [
                'intent' => 'thank_you:selected',
                'guest_ids' => [$notCheckedIn->id],
            ])
            ->assertRedirect(route('admin.whatsapp.index'));

        Bus::assertNotDispatched(SendWhatsappThankYouJob::class);
    }

    public function test_thank_you_job_sends_template_for_checked_in_guest(): void
    {
        $this->configureThankYou();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.thankyou123'],
                ],
            ], 200),
        ]);

        $guest = Guest::query()->create([
            'name' => 'Anki Gilbert Okosso',
            'phone' => '+237670000000',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify',
            'qr_verified_at' => now(),
        ]);

        (new SendWhatsappThankYouJob($guest))->handle(app(WhatsappClient::class));

        $guest->refresh();

        $this->assertSame('wamid.thankyou123', $guest->whatsapp_thankyou_message_id);
        $this->assertNotNull($guest->whatsapp_thankyou_sent_at);
        $this->assertNull($guest->whatsapp_thankyou_error);

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return ($body['template']['name'] ?? null) === 'thank_fifi_kiki'
                && ($body['template']['components'][0]['parameters'][0]['text'] ?? null) === 'Anki Gilbert Okosso'
                && ($body['template']['components'][0]['parameters'][0]['parameter_name'] ?? null) === 'n';
        });
    }

    public function test_thank_you_job_skips_guest_without_check_in(): void
    {
        $this->configureThankYou();

        Http::fake();

        $guest = Guest::query()->create([
            'name' => 'Not There',
            'phone' => '+237670000001',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify',
        ]);

        (new SendWhatsappThankYouJob($guest))->handle(app(WhatsappClient::class));

        $guest->refresh();

        $this->assertNull($guest->whatsapp_thankyou_sent_at);
        Http::assertNothingSent();
    }

    public function test_thank_you_job_is_idempotent_unless_forced(): void
    {
        $this->configureThankYou();

        Http::fake();

        $guest = Guest::query()->create([
            'name' => 'Already Thanked',
            'phone' => '+237670000002',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify',
            'qr_verified_at' => now(),
            'whatsapp_thankyou_sent_at' => now()->subHour(),
            'whatsapp_thankyou_message_id' => 'wamid.old',
        ]);

        (new SendWhatsappThankYouJob($guest))->handle(app(WhatsappClient::class));

        Http::assertNothingSent();
        $this->assertSame('wamid.old', $guest->fresh()->whatsapp_thankyou_message_id);
    }
}
