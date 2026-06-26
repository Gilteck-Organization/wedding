<?php

namespace Tests\Feature;

use App\Models\AccessName;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\AccessCard\AccessCardImageGenerator;
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

    public function test_access_card_page_is_public_and_shows_card_without_name_gate(): void
    {
        AccessName::query()->create(['name' => 'SharedCode']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->get(route('access-card', $guest));

        $response->assertOk();
        $response->assertSee('Jane Guest', false);
        $response->assertDontSee('Access name', false);
    }

    public function test_access_card_image_returns_jpeg_for_approved_guest(): void
    {
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->get(route('access-card.image', $guest));

        $response->assertOk();
        $this->assertStringContainsString('image/jpeg', (string) $response->headers->get('Content-Type'));
        $this->assertGreaterThan(50_000, strlen((string) $response->getContent()));
    }

    public function test_access_card_image_renderer_draws_guest_name_with_freetype(): void
    {
        if (! function_exists('imagettftext')) {
            $this->markTestSkipped('FreeType is required to render guest names on access cards.');
        }

        $guest = $this->approvedGuestWithVerifyQr('Visible Name Guest');
        $binary = app(AccessCardImageGenerator::class)->render($guest);

        $this->assertStringStartsWith("\xFF\xD8", $binary);
        $this->assertGreaterThan(50_000, strlen($binary));
    }

    public function test_access_card_image_returns_not_found_when_not_approved(): void
    {
        $guest = Guest::query()->create([
            'name' => 'Pending Guest',
            'phone' => '555-0100',
            'is_approved' => false,
        ]);

        $this->get(route('access-card.image', $guest))->assertNotFound();
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

        $response->assertOk();
        $guest->refresh();
        $this->assertNotNull($guest->qr_verified_at);

        $response->assertSee('Attendance verified', false);
        $response->assertSee('Jane Guest', false);

        $secondScan = $this->get(route('access-card.verify', $guest));
        $secondScan->assertOk();
        $secondScan->assertSee('Already scanned', false);
    }

    public function test_second_qr_scan_shows_already_scanned_page(): void
    {
        AccessName::query()->create(['name' => 'Family Gate']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $this->post(route('access-card.verify.submit', $guest), [
            'name' => 'Family Gate',
        ]);

        $guest->refresh();

        $response = $this->get(route('access-card.verify', $guest));

        $response->assertOk();
        $response->assertSee('Already scanned', false);
        $response->assertSee('Do not admit', false);
        $response->assertDontSee('OK to admit', false);
    }

    public function test_verify_submit_after_scan_does_not_reset_verification(): void
    {
        AccessName::query()->create(['name' => 'Family Gate']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $firstResponse = $this->post(route('access-card.verify.submit', $guest), ['name' => 'Family Gate']);
        $firstResponse->assertOk();
        $guest->refresh();
        $firstVerifiedAt = $guest->qr_verified_at;

        $response = $this->post(route('access-card.verify.submit', $guest), ['name' => 'Family Gate']);

        $response->assertRedirect(route('access-card.verify', $guest));
        $guest->refresh();
        $this->assertTrue($firstVerifiedAt->equalTo($guest->qr_verified_at));
    }

    public function test_same_access_name_works_for_different_guest_qr_codes(): void
    {
        AccessName::query()->create(['name' => 'OneCode']);
        $guestA = $this->approvedGuestWithVerifyQr('Alice');
        $guestB = $this->approvedGuestWithVerifyQr('Bob');

        $this->post(route('access-card.verify.submit', $guestA), ['name' => 'OneCode']);
        $this->assertNotNull($guestA->fresh()->qr_verified_at);

        $this->post(route('access-card.verify.submit', $guestB), ['name' => 'OneCode']);
        $this->assertNotNull($guestB->fresh()->qr_verified_at);

        $this->get(route('access-card.verify', $guestA))->assertSee('Already scanned', false);
        $this->get(route('access-card.verify', $guestB))->assertSee('Already scanned', false);
    }

    public function test_verify_submit_with_wrong_name_redirects_home_silently(): void
    {
        AccessName::query()->create(['name' => 'RightOnly']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->post(route('access-card.verify.submit', $guest), [
            'name' => 'Wrong Name',
        ]);

        $response->assertRedirect(route('wedding.home'));
        $this->assertNull($guest->fresh()->qr_verified_at);
    }

    public function test_verify_fails_when_no_access_names_configured(): void
    {
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        $response = $this->post(route('access-card.verify.submit', $guest), [
            'name' => 'Anything',
        ]);

        $response->assertRedirect(route('wedding.home'));
    }

    public function test_authenticated_user_sees_verified_page_on_first_verify_scan(): void
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
        $this->assertNotNull($guest->fresh()->qr_verified_at);
    }

    public function test_authenticated_user_sees_already_scanned_on_second_verify_scan(): void
    {
        $user = User::factory()->create();
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');
        $guest->update(['qr_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('access-card.verify', $guest));

        $response->assertOk();
        $response->assertSee('Already scanned', false);
        $response->assertDontSee('OK to admit', false);
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

    public function test_verified_page_shows_party_after_check_in(): void
    {
        AccessName::query()->create(['name' => 'Gate']);
        $guest = $this->approvedGuestWithVerifyQr('Jane Guest');

        Rsvp::query()->create([
            'guest_id' => $guest->id,
            'name' => 'Jane Guest',
            'phone' => '555-0100',
            'attendance' => 'yes',
            'guest_count' => 2,
            'message' => null,
        ]);

        $response = $this->post(route('access-card.verify.submit', $guest), ['name' => 'Gate']);

        $response->assertOk();
        $response->assertSee('Attendance verified', false);
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
