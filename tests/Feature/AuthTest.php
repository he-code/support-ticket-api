<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'message',
            'token',
            'user',
        ]);
        $response->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'message',
            'token',
            'user',
        ]);
        $response->assertJsonPath('user.email', 'test@example.com');
        $response->assertJsonMissingPath('user.password');
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            'message' => 'Invalid credentials',
        ]);
    }

    public function test_login_rejects_nosql_style_array_payloads(): void
    {
        User::factory()->create([
            'email' => 'secure@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => ['$ne' => 'secure@example.com'],
            'password' => ['$ne' => null],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password'])
            ->assertJsonMissingPath('token');
    }

    public function test_login_does_not_accept_sql_injection_style_credentials(): void
    {
        User::factory()->create([
            'email' => 'victim@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'victim@example.com',
            'password' => "' OR '1'='1",
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid credentials')
            ->assertJsonMissingPath('token');
    }

    public function test_login_rate_limits_repeated_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'rate-limit@example.com',
            'password' => Hash::make('password123'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'rate-limit@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'rate-limit@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429)
            ->assertJsonPath('message', 'Too many login attempts. Please try again later.')
            ->assertJsonMissingPath('token');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Logged out successfully',
        ]);
    }

    public function test_guest_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }
}
