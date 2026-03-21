<?php

namespace Tests\Feature;

use App\Models\Rsvp;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RsvpCapacityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_wedding_home_shows_rsvp_form_when_under_capacity(): void
    {
        Config::set('wedding.venue_capacity', 350);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Submit RSVP', false);
    }

    public function test_wedding_home_hides_rsvp_form_when_at_capacity(): void
    {
        Config::set('wedding.venue_capacity', 2);

        Rsvp::query()->create([
            'guest_id' => null,
            'name' => 'Guest One',
            'phone' => '555-0101',
            'attendance' => 'yes',
            'guest_count' => 1,
            'message' => null,
        ]);

        Rsvp::query()->create([
            'guest_id' => null,
            'name' => 'Guest Two',
            'phone' => '555-0102',
            'attendance' => 'yes',
            'guest_count' => 1,
            'message' => null,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Submit RSVP', false);
        $response->assertSee('so welcome here', false);
        $response->assertSee('Every seat for our celebration', false);
    }

    public function test_rsvp_store_rejected_when_capacity_is_full(): void
    {
        Config::set('wedding.venue_capacity', 1);

        Rsvp::query()->create([
            'guest_id' => null,
            'name' => 'Guest One',
            'phone' => '555-0101',
            'attendance' => 'yes',
            'guest_count' => 1,
            'message' => null,
        ]);

        $response = $this->post(route('rsvp.submit'), [
            'name' => 'New Guest',
            'phone' => '555-0200',
            'attendance' => 'yes',
            'guest_count' => '1',
        ]);

        $response->assertRedirect(route('wedding.home').'#rsvp');
        $response->assertSessionHasErrors('capacity');
        $this->assertSame(1, Rsvp::query()->count());
    }

    public function test_rsvp_store_rejected_when_party_size_exceeds_remaining_seats(): void
    {
        Config::set('wedding.venue_capacity', 3);

        Rsvp::query()->create([
            'guest_id' => null,
            'name' => 'Guest One',
            'phone' => '555-0101',
            'attendance' => 'yes',
            'guest_count' => 2,
            'message' => null,
        ]);

        $response = $this->from(route('wedding.home'))
            ->post(route('rsvp.submit'), [
                'name' => 'Guest Two',
                'phone' => '555-0200',
                'attendance' => 'yes',
                'guest_count' => '2',
            ]);

        $response->assertRedirect(route('wedding.home').'#rsvp');
        $response->assertSessionHasErrors('capacity');
        $this->assertSame(1, Rsvp::query()->count());
    }

    public function test_rsvp_store_succeeds_when_party_fits_in_remaining_seats(): void
    {
        Config::set('wedding.venue_capacity', 3);

        Rsvp::query()->create([
            'guest_id' => null,
            'name' => 'Guest One',
            'phone' => '555-0101',
            'attendance' => 'yes',
            'guest_count' => 2,
            'message' => null,
        ]);

        $response = $this->post(route('rsvp.submit'), [
            'name' => 'Guest Two',
            'phone' => '555-0200',
            'attendance' => 'yes',
            'guest_count' => '1',
        ]);

        $response->assertRedirect(route('wedding.home').'#rsvp');
        $response->assertSessionHasNoErrors();
        $this->assertSame(2, Rsvp::query()->count());
    }
}
