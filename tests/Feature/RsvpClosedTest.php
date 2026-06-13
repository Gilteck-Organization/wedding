<?php

namespace Tests\Feature;

use App\Models\Rsvp;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RsvpClosedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['wedding.rsvp_open' => false]);
    }

    public function test_wedding_home_hides_rsvp_form_when_closed(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Submit RSVP', false)
            ->assertSee('RSVP is now closed', false);
    }

    public function test_rsvp_store_rejected_when_closed(): void
    {
        $this->post(route('rsvp.submit'), [
            'name' => 'New Guest',
            'phone' => '+2348012345678',
            'attendance' => 'yes',
        ])
            ->assertRedirect(route('wedding.home').'#rsvp')
            ->assertSessionHasErrors('capacity');

        $this->assertSame(0, Rsvp::query()->count());
    }
}
