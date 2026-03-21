<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_guest_is_redirected_to_login_when_visiting_profile(): void
    {
        $response = $this->get(route('admin.profile.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_form(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('admin.profile.edit'));

        $response->assertOk();
        $response->assertSee('Profile', false);
        $response->assertSee('Admin User', false);
        $response->assertSee('admin@example.com', false);
    }

    public function test_authenticated_user_can_update_name_and_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function test_authenticated_user_can_update_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-secret-12',
        ]);

        $response = $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'old-secret-12',
            'password' => 'new-secret-12',
            'password_confirmation' => 'new-secret-12',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret-12', $user->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-secret-12',
        ]);

        $response = $this->actingAs($user)->from(route('admin.profile.edit'))->put(route('admin.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-secret-12',
            'password_confirmation' => 'new-secret-12',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $response->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertTrue(Hash::check('old-secret-12', $user->password));
    }

    public function test_email_must_be_unique_except_for_self(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $user = User::factory()->create(['email' => 'mine@example.com']);

        $response = $this->actingAs($user)->from(route('admin.profile.edit'))->put(route('admin.profile.update'), [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $response->assertSessionHasErrors('email');
    }
}
