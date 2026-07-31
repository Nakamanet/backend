<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class CloudflareTurnstile implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private const VERIFICATION_FAILED_MESSAGE = 'Turnstile verification failed.';
    private const UNAVAILABLE_MESSAGE = 'Unable to verify Turnstile at this time. Please try again.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $body = $this->verify($value, $fail);

        if ($body === null) {
            return;
        }

        if (! ($body['success'] ?? false)) {
            $fail(self::VERIFICATION_FAILED_MESSAGE);
        }
    }

    private function verify(mixed $token, Closure $fail): ?array
    {
        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret'   => config('services.turnstile.secret'),
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $fail(self::UNAVAILABLE_MESSAGE);
            return null;
        }

        if (! $response->successful()) {
            $fail(self::UNAVAILABLE_MESSAGE);
            return null;
        }

        return $response->json();
    }
}
