<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_get_authenticated_user_profile(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_own_profile(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Carlos Pérez',
            'email' => 'carlos@example.com',
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', 'Carlos Pérez')
            ->assertJsonPath('user.email', 'carlos@example.com')
            ->assertJsonPath('user.role', 'user');
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Nombre anterior',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'name' => 'Nombre actualizado',
            'email' => 'new@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully')
            ->assertJsonPath('user.name', 'Nombre actualizado')
            ->assertJsonPath('user.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre actualizado',
            'email' => 'new@example.com',
        ]);
    }

    public function test_authenticated_user_cannot_update_profile_with_existing_email(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'current@example.com',
        ]);

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'name' => 'Nuevo nombre',
            'email' => 'taken@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_keep_same_email_when_updating_profile(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Nombre anterior',
            'email' => 'same@example.com',
        ]);

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'name' => 'Nombre actualizado',
            'email' => 'same@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'same@example.com');
    }

    public function test_authenticated_user_can_change_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->patchJson('/api/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password changed successfully');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_authenticated_user_cannot_change_password_with_wrong_current_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->patchJson('/api/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'Current password is incorrect',
            ]);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_password_confirmation_is_required(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->patchJson('/api/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }
}
