<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class GoogleRecaptchaV3 implements ValidationRule
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const MINIMUM_SCORE = 0.5;
    private const VERIFICATION_FAILED_MESSAGE = 'reCAPTCHA verification failed.';
    private const UNAVAILABLE_MESSAGE = 'Unable to verify reCAPTCHA at this time. Please try again.';

    public function __construct(private readonly string $action)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $body = $this->verify($value, $fail);

        if ($body === null) {
            return;
        }

        $isValid = ($body['success'] ?? false)
            && ($body['action'] ?? null) === $this->action
            && ($body['score'] ?? 0) >= self::MINIMUM_SCORE;

        if (! $isValid) {
            $fail(self::VERIFICATION_FAILED_MESSAGE);
        }
    }

    private function verify(mixed $token, Closure $fail): ?array
    {
        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret'   => config('services.recaptcha.secret'),
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
