<?php

namespace Tests\Feature;

use App\Jobs\SendAccessCardWhatsappJob;
use App\Jobs\SendWhatsappReminderJob;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class WhatsappAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_admin_page_lists_approved_guests(): void
    {
        $user = User::factory()->create();

        Guest::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify',
            'whatsapp_status' => 'failed',
            'whatsapp_error' => 'Recipient is not a valid WhatsApp user',
        ]);

        $this->actingAs($user)
            ->get(route('admin.whatsapp.index'))
            ->assertOk()
            ->assertSee('Ada Lovelace', false)
            ->assertSee('Failed', false)
            ->assertSee('Recipient is not a valid WhatsApp user', false);
    }

    public function test_bulk_send_queues_reminder_job_for_failed_guests(): void
    {
        Bus::fake();

        $user = User::factory()->create();

        config([
            'services.whatsapp.access_token' => 'TEST_TOKEN',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.template_name' => 'wedding_access_card',
            'services.whatsapp.reminder_template_name' => 'wedding_access_reminder',
        ]);

        $failedGuest = Guest::query()->create([
            'name' => 'Failed Guest',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify',
            'whatsapp_reminder_sent_at' => now(),
            'whatsapp_status' => 'failed',
        ]);

        Guest::query()->create([
            'name' => 'Delivered Guest',
            'phone' => '+1 (984) 658-1829',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify2',
            'whatsapp_reminder_sent_at' => now(),
            'whatsapp_status' => 'delivered',
        ]);

        $this->actingAs($user)
            ->post(route('admin.whatsapp.send'), [
                'action' => 'all_failed',
            ])
            ->assertRedirect(route('admin.whatsapp.index'));

        Bus::assertDispatched(SendAccessCardWhatsappJob::class, function (SendAccessCardWhatsappJob $job) use ($failedGuest): bool {
            return $job->guest->is($failedGuest) && $job->force === true;
        });
    }

    public function test_bulk_send_queues_reminder_for_guests_without_reminder(): void
    {
        Bus::fake();

        $user = User::factory()->create();

        config([
            'services.whatsapp.access_token' => 'TEST_TOKEN',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.template_name' => 'wedding_access_card',
            'services.whatsapp.reminder_template_name' => 'wedding_access_reminder',
        ]);

        $guest = Guest::query()->create([
            'name' => 'New Guest',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'qr_code' => 'https://example.com/verify',
        ]);

        $this->actingAs($user)
            ->post(route('admin.whatsapp.send'), [
                'action' => 'all_pending',
            ])
            ->assertRedirect(route('admin.whatsapp.index'));

        Bus::assertDispatched(SendWhatsappReminderJob::class, function (SendWhatsappReminderJob $job) use ($guest): bool {
            return $job->guest->is($guest);
        });
    }
}
