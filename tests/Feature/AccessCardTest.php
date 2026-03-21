<?php

namespace Tests\Feature;

use App\Models\AccessName;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function approvedGuestWithVerifyQr(string $guestLabel): Guest
    {
        $guest = Guest::query()->create([
            'name' => $guestLabel,
            'phone' => '555-0100',
            'email' => null,
            'is_approved' => true,
        ]);

        $guest->update([
            'qr_code' => route('access-card.verify', $guest),
        ]);

        return $guest->fresh();
    }

    private function unlockSession(Guest $guest): void
    {
        session([$guest->sessionUnlockKey() => true]);
    }

    public function test_access_card_page_is_public_and_shows_card_without_name_gate(): void
    {
        AccessName::query()->create(['name' => 'SharedCode']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->get(route('access-card', $guest));

        $response->assertOk();
        $response->assertSee('Jane Guest', false);
        $response->assertDontSee('Access name', false);
    }

    public function test_verify_url_shows_access_name_gate_when_not_authenticated_or_unlocked(): void
    {
        AccessName::query()->create(['name' => 'SharedCode']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->get(route('access-card.verify', $guest));

        $response->assertOk();
        $response->assertSee('Access name', false);
        $response->assertDontSee('Staff verification', false);
    }

    public function test_verify_submit_with_valid_global_access_name_shows_verified_page(): void
    {
        AccessName::query()->create(['name' => 'Family Gate']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->post(route('access-card.verify.submit', $guest), [
            'name' => 'Family Gate',
        ]);

        $response->assertRedirect(route('access-card.verify', $guest));
        $this->assertTrue(session()->get($guest->sessionUnlockKey()));

        $verified = $this->get(route('access-card.verify', $guest));
        $verified->assertOk();
        $verified->assertSee('Attendance verified', false);
        $verified->assertSee('Jane Guest', false);
    }

    public function test_same_access_name_works_for_different_guest_qr_codes(): void
    {
        AccessName::query()->create(['name' => 'OneCode']);
        $guestA = $this->approvedGuestWithVerifyQr('Alice');
        $guestB = $this->approvedGuestWithVerifyQr('Bob');

        $this->post(route('access-card.verify.submit', $guestA), ['name' => 'OneCode']);
        $this->assertTrue(session()->get($guestA->sessionUnlockKey()));

        $this->post(route('access-card.verify.submit', $guestB), ['name' => 'OneCode']);
        $this->assertTrue(session()->get($guestB->sessionUnlockKey()));

        $this->get(route('access-card.verify', $guestA))->assertSee('Alice', false);
        $this->get(route('access-card.verify', $guestB))->assertSee('Bob', false);
    }

    public function test_verify_submit_with_wrong_name_redirects_home_silently(): void
    {
        AccessName::query()->create(['name' => 'RightOnly']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->post(route('access-card.verify.submit', $guest), [
            'name' => 'Wrong Name',
        ]);

        $response->assertRedirect(route('wedding.home'));
        $this->assertFalse(session()->get($guest->sessionUnlockKey(), false));
    }

    public function test_verify_fails_when_no_access_names_configured(): void
    {
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->post(route('access-card.verify.submit', $guest), [
            'name' => 'Anything',
        ]);

        $response->assertRedirect(route('wedding.home'));
    }

    public function test_authenticated_user_sees_verified_page_on_verify_url(): void
    {
        $user = User::factory()->create();
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        Rsvp::query()->create([
            'guest_id' => $guest->id,
            'name' => 'Jane Guest',
            'phone' => '555-0100',
            'attendance' => 'yes',
            'guest_count' => 2,
            'message' => null,
        ]);

        $response = $this->actingAs($user)->get(route('access-card.verify', $guest));

        $response->assertOk();
        $response->assertSee('Staff verification', false);
        $response->assertSee('Attendance verified', false);
        $response->assertSee('Jane Guest', false);
    }

    public function test_authenticated_user_sees_public_access_card_on_card_url(): void
    {
        AccessName::query()->create(['name' => 'X']);
        $user = User::factory()->create();
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->actingAs($user)->get(route('access-card', $guest));

        $response->assertOk();
        $response->assertSee('Jane Guest', false);
        $response->assertDontSee('Staff verification', false);
    }

    public function test_access_card_shows_party_line_when_multiple_guests(): void
    {
        AccessName::query()->create(['name' => 'X']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        Rsvp::query()->create([
            'guest_id' => $guest->id,
            'name' => 'Jane Guest',
            'phone' => '555-0100',
            'attendance' => 'yes',
            'guest_count' => 2,
            'message' => null,
        ]);

        $response = $this->get(route('access-card', $guest));

        $response->assertOk();
        $response->assertSee('Plus one guest', false);
    }

    public function test_access_card_does_not_show_plus_one_when_solo(): void
    {
        AccessName::query()->create(['name' => 'X']);
        $guest = $this->approvedGuestWithVerifyQr('Solo Guest');

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
        $response->assertDontSee('Plus one guest', false);
    }

    public function test_verified_page_shows_party_after_unlock(): void
    {
        AccessName::query()->create(['name' => 'Gate']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');
        $this->unlockSession($guest);

        Rsvp::query()->create([
            'guest_id' => $guest->id,
            'name' => 'Jane Guest',
            'phone' => '555-0100',
            'attendance' => 'yes',
            'guest_count' => 2,
            'message' => null,
        ]);

        $guest->refresh();

        $response = $this->get(route('access-card.verify', $guest));

        $response->assertOk();
        $response->assertSee('Party size', false);
    }

    public function test_access_card_url_with_numeric_id_is_not_routable(): void
    {
        Guest::query()->create([
            'name' => 'Jane Guest',
            'phone' => '555-0100',
            'email' => null,
            'is_approved' => true,
        ]);

        $response = $this->get('/access-card/1');

        $response->assertNotFound();
    }

    public function test_access_card_unknown_token_returns_not_found(): void
    {
        $token = 'zzzzz';

        $response = $this->get('/access-card/'.$token);

        $response->assertNotFound();
    }
}
