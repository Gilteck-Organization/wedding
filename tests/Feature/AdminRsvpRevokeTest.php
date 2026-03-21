<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRsvpRevokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_when_revoking_attendance(): void
    {
        $guest = Guest::query()->create([
            'name' => 'Test Guest',
            'phone' => '555-0100',
            'email' => null,
            'is_approved' => true,
        ]);

        $guest->update([
            'qr_code' => route('access-card.verify', $guest),
        ]);

        $rsvp = Rsvp::query()->create([
            'guest_id' => $guest->id,
            'name' => 'Test Guest',
            'phone' => '555-0100',
            'attendance' => 'yes',
            'guest_count' => 2,
            'message' => null,
        ]);

        $response = $this->post(route('admin.rsvps.revoke-attendance', $rsvp));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_revoke_attendance_and_unapprove_guest(): void
    {
        $user = User::factory()->create();

        $guest = Guest::query()->create([
            'name' => 'Test Guest',
            'phone' => '555-0100',
            'email' => null,
            'is_approved' => true,
        ]);

        $guest->update([
            'qr_code' => route('access-card.verify', $guest),
        ]);

        $rsvp = Rsvp::query()->create([
            'guest_id' => $guest->id,
            'name' => 'Test Guest',
            'phone' => '555-0100',
            'attendance' => 'yes',
            'guest_count' => 2,
            'message' => null,
        ]);

        $response = $this->actingAs($user)->post(route('admin.rsvps.revoke-attendance', $rsvp));

        $response->assertRedirect(route('admin.rsvps.index'));
        $response->assertSessionHas('success');

        $rsvp->refresh();
        $this->assertSame('no', $rsvp->attendance);
        $this->assertNull($rsvp->guest_count);

        $guest->refresh();
        $this->assertFalse($guest->is_approved);
        $this->assertNull($guest->qr_code);
    }

    public function test_approving_after_revoke_restores_attendance_and_party_size(): void
    {
        $user = User::factory()->create();

        $guest = Guest::query()->create([
            'name' => 'Test Guest',
            'phone' => '555-0100',
            'email' => null,
            'is_approved' => true,
        ]);

        $guest->update([
            'qr_code' => route('access-card.verify', $guest),
        ]);

        $rsvp = Rsvp::query()->create([
            'guest_id' => $guest->id,
            'name' => 'Test Guest',
            'phone' => '555-0100',
            'attendance' => 'yes',
            'guest_count' => 2,
            'message' => null,
        ]);

        $this->actingAs($user)->post(route('admin.rsvps.revoke-attendance', $rsvp));

        $rsvp->refresh();
        $this->assertSame('no', $rsvp->attendance);

        $this->actingAs($user)->post(route('admin.rsvps.approve', $rsvp));

        $rsvp->refresh();
        $this->assertSame('yes', $rsvp->attendance);
        $this->assertSame(1, $rsvp->guest_count);

        $guest->refresh();
        $this->assertTrue($guest->is_approved);
        $this->assertNotNull($guest->qr_code);
    }
}
