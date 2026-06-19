<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RsvpSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_single_rsvp(): void
    {
        $user = User::factory()->create();

        $rsvp = Rsvp::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'attendance' => 'yes',
            'guest_count' => 1,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.rsvps.destroy', $rsvp))
            ->assertRedirect(route('admin.rsvps.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('rsvps', ['id' => $rsvp->id]);
        $this->assertFalse(Rsvp::query()->whereKey($rsvp->id)->exists());
    }

    public function test_soft_delete_revokes_linked_guest_approval(): void
    {
        $user = User::factory()->create();

        $guest = Guest::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'is_approved' => true,
            'qr_code' => 'https://example.test/card',
        ]);

        $rsvp = Rsvp::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'attendance' => 'yes',
            'guest_count' => 1,
            'guest_id' => $guest->id,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.rsvps.destroy', $rsvp))
            ->assertRedirect(route('admin.rsvps.index'));

        $guest->refresh();

        $this->assertFalse($guest->is_approved);
        $this->assertNull($guest->qr_code);
        $this->assertSoftDeleted('rsvps', ['id' => $rsvp->id]);
    }

    public function test_admin_can_bulk_soft_delete_rsvps(): void
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
            'attendance' => 'no',
        ]);

        $rsvpC = Rsvp::query()->create([
            'name' => 'Carol Guest',
            'phone' => '+1 (984) 658-1830',
            'attendance' => 'yes',
            'guest_count' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('admin.rsvps.bulk-delete'), [
                'rsvp_ids' => [$rsvpA->id, $rsvpB->id],
            ])
            ->assertRedirect(route('admin.rsvps.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('rsvps', ['id' => $rsvpA->id]);
        $this->assertSoftDeleted('rsvps', ['id' => $rsvpB->id]);
        $this->assertDatabaseHas('rsvps', ['id' => $rsvpC->id, 'deleted_at' => null]);
    }

    public function test_deleted_rsvp_phone_becomes_available_again(): void
    {
        $user = User::factory()->create();

        $rsvp = Rsvp::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'attendance' => 'yes',
            'guest_count' => 1,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.rsvps.destroy', $rsvp));

        $this->assertFalse(Rsvp::query()->where('phone', '+1 (984) 658-1828')->exists());
    }

    public function test_admin_can_view_deleted_rsvps_list(): void
    {
        $user = User::factory()->create();

        $rsvp = Rsvp::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'attendance' => 'yes',
            'guest_count' => 1,
        ]);

        $rsvp->delete();

        $this->actingAs($user)
            ->get(route('admin.rsvps.trashed'))
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertSee('Deleted RSVPs');
    }

    public function test_admin_can_restore_deleted_rsvp(): void
    {
        $user = User::factory()->create();

        $rsvp = Rsvp::query()->create([
            'name' => 'Ada Lovelace',
            'phone' => '+1 (984) 658-1828',
            'attendance' => 'yes',
            'guest_count' => 1,
        ]);

        $rsvp->delete();

        $this->actingAs($user)
            ->post(route('admin.rsvps.restore', $rsvp->id))
            ->assertRedirect(route('admin.rsvps.trashed'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rsvps', [
            'id' => $rsvp->id,
            'deleted_at' => null,
        ]);
    }
}
