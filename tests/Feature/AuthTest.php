<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'username'              => fake()->unique()->userName(),
            'email'                 => fake()->unique()->safeEmail(),
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'birthdate'             => '2000-01-01',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure(['token']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }

    public function test_user_can_logout()
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    public function test_user_can_get_me()
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'username', 'email']);
    }

    public function test_unauthenticated_user_cannot_access_me()
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }
}
