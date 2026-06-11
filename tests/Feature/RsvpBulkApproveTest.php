<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RsvpBulkApproveTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_approve_all_pending_approves_guests(): void
    {
        $user = User::factory()->create();

        $rsvpA = Rsvp::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'attendance' => 'yes',
            'guest_count' => 1,
        ]);

        $guestB = Guest::query()->create([
            'name' => 'Bob Guest',
            'phone' => '+1 (984) 658-1829',
            'is_approved' => false,
        ]);

        $rsvpB = Rsvp::query()->create([
            'name' => 'Bob Guest',
            'phone' => '+1 (984) 658-1829',
            'attendance' => 'yes',
            'guest_count' => 1,
            'guest_id' => $guestB->id,
        ]);

        $this->actingAs($user)
            ->post(route('admin.rsvps.bulk-approve'), [
                'action' => 'all_pending',
            ])
            ->assertRedirect(route('admin.rsvps.index'))
            ->assertSessionHas('success');

        $rsvpA->refresh();
        $rsvpB->refresh();

        $this->assertTrue($rsvpA->guest?->is_approved);
        $this->assertTrue($rsvpB->guest?->is_approved);
        $this->assertNotNull($rsvpA->guest?->qr_code);
    }

    public function test_bulk_approve_selected_only_approves_checked_rsvps(): void
    {
        $user = User::factory()->create();

        $rsvpA = Rsvp::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'attendance' => 'yes',
            'guest_count' => 1,
        ]);

        $rsvpB = Rsvp::query()->create([
            'name' => 'Bob Guest',
            'phone' => '+1 (984) 658-1829',
            'attendance' => 'yes',
            'guest_count' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('admin.rsvps.bulk-approve'), [
                'action' => 'selected',
                'rsvp_ids' => [$rsvpA->id],
            ])
            ->assertRedirect(route('admin.rsvps.index'));

        $rsvpA->refresh();
        $rsvpB->refresh();

        $this->assertTrue($rsvpA->guest?->is_approved);
        $this->assertNull($rsvpB->guest);
    }
}
