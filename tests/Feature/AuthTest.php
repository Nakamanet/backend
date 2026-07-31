<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Registration is guarded by GoogleRecaptchaV3, which calls Google's API.
     * Fake the HTTP round-trip rather than weakening the rule for the test env:
     * the rule must stay fail-closed in every environment.
     */
    private function fakeRecaptcha(float $score = 0.9, string $action = 'register'): void
    {
        Http::fake([
            'www.google.com/recaptcha/*' => Http::response([
                'success' => true,
                'action'  => $action,
                'score'   => $score,
            ]),
        ]);
    }

    public function test_user_can_register()
    {
        $this->fakeRecaptcha();

        $response = $this->postJson('/api/auth/register', [
            'username'              => fake()->unique()->userName(),
            'email'                 => fake()->unique()->safeEmail(),
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'birthdate'             => '2000-01-01',
            'recaptcha_token'       => 'test-token',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['token']);
    }

    public function test_registration_is_rejected_when_recaptcha_score_is_too_low()
    {
        $this->fakeRecaptcha(score: 0.1);

        $response = $this->postJson('/api/auth/register', [
            'username'              => fake()->unique()->userName(),
            'email'                 => fake()->unique()->safeEmail(),
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'birthdate'             => '2000-01-01',
            'recaptcha_token'       => 'test-token',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('recaptcha_token');
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
        $this->be(User::factory()->create(), 'api');

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    public function test_user_can_get_me()
    {
        $this->be(User::factory()->create(), 'api');

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
