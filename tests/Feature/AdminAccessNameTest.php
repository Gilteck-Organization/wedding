<?php

namespace Tests\Feature;

use App\Models\AccessName;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessNameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_guest_redirected_from_access_names_index(): void
    {
        $response = $this->get(route('admin.access-names.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_access_names_page(): void
    {
        $user = User::factory()->create();
        AccessName::query()->create(['name' => 'Alpha Code']);

        $response = $this->actingAs($user)->get(route('admin.access-names.index'));

        $response->assertOk();
        $response->assertSee('Access names', false);
        $response->assertSee('Alpha Code', false);
    }

    public function test_admin_can_add_access_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.access-names.store'), [
            'name' => 'New Secret',
        ]);

        $response->assertRedirect(route('admin.access-names.index'));
        $this->assertTrue(AccessName::query()->where('name', 'New Secret')->exists());
    }

    public function test_admin_can_remove_access_name(): void
    {
        $user = User::factory()->create();
        $row = AccessName::query()->create(['name' => 'To Delete']);

        $response = $this->actingAs($user)->delete(route('admin.access-names.destroy', $row));

        $response->assertRedirect(route('admin.access-names.index'));
        $this->assertNull(AccessName::query()->find($row->id));
    }
}
