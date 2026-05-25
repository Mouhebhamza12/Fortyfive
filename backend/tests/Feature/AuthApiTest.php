<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_auth_cookie(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertCookie('auth_token');

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseCount('auth_tokens', 1);
    }

    public function test_user_can_login_and_receive_auth_cookie(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $loginResponse->assertOk()
            ->assertCookie('auth_token');
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();
        $plainTextToken = str_repeat('a', 80);

        $user->authTokens()->create([
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDay(),
            'last_used_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->call('GET', '/api/auth/me', [], ['auth_token' => $plainTextToken], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }
}
