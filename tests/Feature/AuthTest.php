<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Mail\RegistrationCodeMail;
use App\Services\Auth\RegistrationCodeService;
use Illuminate\Support\Facades\Mail;

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
            'username'  => fake()->unique()->userName(),
            'email'     => fake()->unique()->safeEmail(),
            'password'  => 'password123',
            'birthdate' => '2000-01-01',
        ], $overrides);
    }

    /**
     * Run signup step 1 and return the code that was emailed.
     */
    private function requestCode(array $payload): string
    {
        Mail::fake();
        $this->fakeTurnstile();

        $this->postJson('/api/auth/register/send-code', $payload + ['turnstile_token' => 'test-token'])
             ->assertStatus(202);

        $code = null;
        Mail::assertSent(RegistrationCodeMail::class, function (RegistrationCodeMail $mail) use ($payload, &$code) {
            $code = $mail->code;
            return $mail->hasTo($payload['email']);
        });

        return $code;
    }

    public function test_user_can_register_with_the_emailed_code()
    {
        $payload = $this->registrationPayload();
        $code = $this->requestCode($payload);

        $this->postJson('/api/auth/register', $payload + ['code' => $code])
             ->assertStatus(201)
             ->assertJsonStructure(['token']);

        $this->assertNotNull(User::where('email', $payload['email'])->first()->email_verified_at);
    }

    public function test_code_cannot_be_reused()
    {
        $payload = $this->registrationPayload();
        $code = $this->requestCode($payload);

        $this->postJson('/api/auth/register', $payload + ['code' => $code])->assertStatus(201);

        // Same email is now taken anyway, but the code itself must be gone too.
        $this->assertFalse(app(RegistrationCodeService::class)->verify($payload['email'], $code));
    }

    public function test_registration_is_rejected_with_a_wrong_code()
    {
        $payload = $this->registrationPayload();
        $code = $this->requestCode($payload);
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postJson('/api/auth/register', $payload + ['code' => $wrong])
             ->assertStatus(422)
             ->assertJsonValidationErrors('code');

        $this->assertDatabaseMissing('Users', ['email' => $payload['email']]);
    }

    public function test_code_is_dropped_after_too_many_wrong_guesses()
    {
        $payload = $this->registrationPayload();
        $code = $this->requestCode($payload);
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < RegistrationCodeService::MAX_ATTEMPTS; $i++) {
            $this->postJson('/api/auth/register', $payload + ['code' => $wrong])->assertStatus(422);
        }

        $this->postJson('/api/auth/register', $payload + ['code' => $code])
             ->assertStatus(422)
             ->assertJsonValidationErrors('code');
    }

    public function test_registration_is_rejected_without_requesting_a_code()
    {
        $this->postJson('/api/auth/register', $this->registrationPayload(['code' => '123456']))
             ->assertStatus(422)
             ->assertJsonValidationErrors('code');
    }

    public function test_code_cannot_be_resent_during_the_cooldown()
    {
        $payload = $this->registrationPayload();
        $this->requestCode($payload);

        $this->postJson('/api/auth/register/send-code', $payload + ['turnstile_token' => 'test-token'])
             ->assertStatus(429);
    }

    public function test_send_code_is_rejected_when_turnstile_fails()
    {
        Mail::fake();
        $this->fakeTurnstile(success: false);

        $this->postJson('/api/auth/register/send-code', $this->registrationPayload(['turnstile_token' => 'test-token']))
             ->assertStatus(422)
             ->assertJsonValidationErrors('turnstile_token');

        Mail::assertNothingSent();
    }

    public function test_send_code_is_rejected_without_a_turnstile_token()
    {
        Mail::fake();
        $this->fakeTurnstile();

        $this->postJson('/api/auth/register/send-code', $this->registrationPayload())
             ->assertStatus(422)
             ->assertJsonValidationErrors('turnstile_token');

        Mail::assertNothingSent();
    }

    public function test_send_code_is_rejected_under_fifteen_years_old()
    {
        Mail::fake();
        $this->fakeTurnstile();

        $this->postJson('/api/auth/register/send-code', $this->registrationPayload([
            'birthdate'       => now()->subYears(10)->toDateString(),
            'turnstile_token' => 'test-token',
        ]))->assertStatus(422)
           ->assertJsonValidationErrors('birthdate');

        Mail::assertNothingSent();
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
