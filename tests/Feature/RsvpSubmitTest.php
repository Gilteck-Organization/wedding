<?php

namespace Tests\Feature;

use App\Models\Rsvp;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RsvpSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['wedding.rsvp_open' => true]);
    }

    public function test_post_rsvp_hits_store_action_and_creates_record(): void
    {
        $response = $this->post(route('rsvp.submit'), [
            'name' => 'Ada Lovelace',
            'phone' => '+2348012345678',
            'attendance' => 'yes',
        ]);

        $response->assertRedirect(route('wedding.home').'#rsvp');
        $response->assertSessionHas('rsvp_success', true);

        $this->assertDatabaseHas('rsvps', [
            'name' => 'Ada Lovelace',
            'phone' => '+2348012345678',
            'attendance' => 'yes',
        ]);
    }

    public function test_post_rsvp_does_not_match_get_only_redirect_route(): void
    {
        $request = Request::create('/rsvp', 'POST');
        $route = $this->app->make('router')->getRoutes()->match($request);

        $this->assertSame('rsvp.submit', $route->getName());
    }

    public function test_get_rsvp_redirects_to_home_fragment(): void
    {
        $this->get('/rsvp')
            ->assertRedirect('/#rsvp');
    }

    public function test_duplicate_phone_shows_validation_error(): void
    {
        Rsvp::query()->create([
            'guest_id' => null,
            'name' => 'Existing Guest',
            'phone' => '+2348012345678',
            'attendance' => 'yes',
            'guest_count' => 1,
            'message' => null,
        ]);

        $response = $this->from(route('wedding.home'))
            ->post(route('rsvp.submit'), [
                'name' => 'Another Guest',
                'phone' => '+2348012345678',
                'attendance' => 'yes',
            ]);

        $response->assertRedirect(route('wedding.home'));
        $response->assertSessionHasErrors('phone');
        $this->assertSame(1, Rsvp::query()->count());
    }
}
