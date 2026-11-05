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
     * Registration is guarded by CloudflareTurnstile, which calls Cloudflare's API.
     * Fake the HTTP round-trip rather than weakening the rule for the test env:
     * the rule must stay fail-closed in every environment.
     */
    private function fakeTurnstile(bool $success = true): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => $success]),
        ]);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'username'        => fake()->unique()->userName(),
            'email'           => fake()->unique()->safeEmail(),
            'password'        => 'password123',
            'birthdate'       => '2000-01-01',
            'turnstile_token' => 'test-token',
        ], $overrides);
    }

    public function test_user_can_register()
    {
        $this->fakeTurnstile();

        $this->postJson('/api/auth/register', $this->registrationPayload())
             ->assertStatus(201)
             ->assertJsonStructure(['token']);
    }

    public function test_registration_is_rejected_when_turnstile_fails()
    {
        $this->fakeTurnstile(success: false);

        $this->postJson('/api/auth/register', $this->registrationPayload())
             ->assertStatus(422)
             ->assertJsonValidationErrors('turnstile_token');
    }

    public function test_registration_is_rejected_without_a_turnstile_token()
    {
        $this->fakeTurnstile();

        $this->postJson('/api/auth/register', $this->registrationPayload(['turnstile_token' => null]))
             ->assertStatus(422)
             ->assertJsonValidationErrors('turnstile_token');
    }

    public function test_registration_is_rejected_under_fifteen_years_old()
    {
        $this->fakeTurnstile();

        $this->postJson('/api/auth/register', $this->registrationPayload([
            'birthdate' => now()->subYears(10)->toDateString(),
        ]))->assertStatus(422)
           ->assertJsonValidationErrors('birthdate');
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
