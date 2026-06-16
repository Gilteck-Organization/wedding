<?php

namespace Tests\Feature;

use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRsvpManualCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_rsvp_from_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.rsvps.create'))
            ->assertOk()
            ->assertSee('Add RSVP', false);

        $this->actingAs($user)
            ->post(route('admin.rsvps.store'), [
                'name' => 'Manual Guest',
                'phone' => '+2348012345678',
                'attendance' => 'yes',
                'message' => 'Added manually',
            ])
            ->assertRedirect(route('admin.rsvps.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rsvps', [
            'name' => 'Manual Guest',
            'phone' => '+2348012345678',
            'attendance' => 'yes',
            'guest_count' => 1,
        ]);
    }

    public function test_guest_cannot_create_rsvp_from_admin_panel(): void
    {
        $this->post(route('admin.rsvps.store'), [
            'name' => 'Blocked Guest',
            'phone' => '+2348099999999',
            'attendance' => 'yes',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, Rsvp::query()->count());
    }
}
