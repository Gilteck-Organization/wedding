<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Services\AccessCard\MalformedAccessCardUrlResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MalformedAccessCardUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_returns_null_for_valid_access_card_path(): void
    {
        $this->assertNull(
            MalformedAccessCardUrlResolver::resolve('/access-card/ruuoe'),
        );
    }

    public function test_resolver_fixes_whatsapp_template_placeholder_prefix(): void
    {
        $this->assertSame(
            '/access-card/ruuoe',
            MalformedAccessCardUrlResolver::resolve(
                '/access-card/%7B%7B1%7D%7Dhttps://fifiandkiki.com/access-card/ruuoe',
            ),
        );
    }

    public function test_resolver_fixes_doubled_full_url_slug(): void
    {
        $this->assertSame(
            '/access-card/ruuoe',
            MalformedAccessCardUrlResolver::resolve(
                '/access-card/https://fifiandkiki.com/access-card/ruuoe',
            ),
        );
    }

    public function test_resolver_preserves_verify_suffix(): void
    {
        $this->assertSame(
            '/access-card/ruuoe/verify',
            MalformedAccessCardUrlResolver::resolve(
                '/access-card/%7B%7B1%7D%7Dhttps://fifiandkiki.com/access-card/ruuoe/verify',
            ),
        );
    }

    public function test_middleware_redirects_malformed_whatsapp_link_to_guest_card(): void
    {
        Guest::query()->create([
            'name' => 'Anki Gilbert Okosso',
            'phone' => '+237600000000',
            'access_token' => 'ruuoe',
            'is_approved' => true,
            'qr_code' => 'https://fifiandkiki.com/access-card/ruuoe/verify',
        ]);

        $response = $this->get(
            '/access-card/%7B%7B1%7D%7Dhttps://fifiandkiki.com/access-card/ruuoe',
        );

        $response->assertRedirect('/access-card/ruuoe');
    }

    public function test_valid_access_card_url_is_not_redirected(): void
    {
        Guest::query()->create([
            'name' => 'Anki Gilbert Okosso',
            'phone' => '+237600000000',
            'access_token' => 'ruuoe',
            'is_approved' => true,
            'qr_code' => 'https://fifiandkiki.com/access-card/ruuoe/verify',
        ]);

        $response = $this->get('/access-card/ruuoe');

        $response->assertOk();
        $response->assertSee('Anki Gilbert Okosso', false);
    }
}
