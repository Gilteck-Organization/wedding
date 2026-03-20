<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_card_page_loads_for_guest(): void
    {
        $guest = Guest::query()->create([
            'name' => 'Jane Guest',
            'phone' => '555-0100',
            'email' => null,
            'qr_code' => 'https://example.test/access-card/1',
            'is_approved' => true,
        ]);

        $response = $this->get(route('access-card', $guest));

        $response->assertOk();
        $response->assertSee('Jane Guest', false);
    }

    public function test_access_card_shows_and_more_when_party_has_additional_guests(): void
    {
        $guest = Guest::query()->create([
            'name' => 'Jane Guest',
            'phone' => '555-0100',
            'email' => null,
            'qr_code' => 'https://example.test/access-card/1',
            'is_approved' => true,
        ]);

        Rsvp::query()->create([
            'guest_id' => $guest->id,
            'name' => 'Jane Guest',
            'phone' => '555-0100',
            'attendance' => 'yes',
            'guest_count' => 6,
            'message' => null,
        ]);

        $response = $this->get(route('access-card', $guest));

        $response->assertOk();
        $response->assertSee('and 5 more', false);
    }

    public function test_access_card_does_not_show_and_more_when_only_one_guest(): void
    {
        $guest = Guest::query()->create([
            'name' => 'Solo Guest',
            'phone' => '555-0200',
            'email' => null,
            'qr_code' => 'https://example.test/access-card/2',
            'is_approved' => true,
        ]);

        Rsvp::query()->create([
            'guest_id' => $guest->id,
            'name' => 'Solo Guest',
            'phone' => '555-0200',
            'attendance' => 'yes',
            'guest_count' => 1,
            'message' => null,
        ]);

        $response = $this->get(route('access-card', $guest));

        $response->assertOk();
        $response->assertDontSee('and ', false);
    }
}
