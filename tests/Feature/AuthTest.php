<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

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
