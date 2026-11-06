<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;

/**
 * One-time codes proving that whoever registers actually owns the email.
 *
 * Codes live in the cache only (never in the Users table): an account is created
 * once the code is confirmed, so bots that never read the inbox leave no row behind.
 */
class RegistrationCodeService
{
    public const TTL_MINUTES = 10;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;

    /**
     * Generate and store a fresh code for this email, replacing any previous one.
     *
     * Returns null while the resend cooldown is still running.
     */
    public function issue(string $email): ?string
    {
        if (Cache::has($this->cooldownKey($email))) {
            return null;
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        Cache::put($this->codeKey($email), [
            'hash'       => $this->hash($code),
            'attempts'   => 0,
            'expires_at' => $expiresAt->getTimestamp(),
        ], $expiresAt);

        Cache::put($this->cooldownKey($email), true, now()->addSeconds(self::RESEND_COOLDOWN_SECONDS));

        return $code;
    }

    /**
     * Check a submitted code. A wrong guess burns an attempt; once the attempts are
     * used up the code is dropped and the user must ask for a new one.
     */
    public function verify(string $email, string $code): bool
    {
        $key = $this->codeKey($email);
        $entry = Cache::get($key);

        if (! is_array($entry)) {
            return false;
        }

        if (hash_equals($entry['hash'], $this->hash($code))) {
            return true;
        }

        $entry['attempts']++;

        if ($entry['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);
        } else {
            // Keep the original expiry rather than extending it on each guess.
            Cache::put($key, $entry, \Illuminate\Support\Carbon::createFromTimestamp($entry['expires_at']));
        }

        return false;
    }

    /**
     * Drop the code once it has been used, or when sending the email failed.
     */
    public function forget(string $email): void
    {
        Cache::forget($this->codeKey($email));
        Cache::forget($this->cooldownKey($email));
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function codeKey(string $email): string
    {
        return 'registration_code:' . $this->normalize($email);
    }

    private function cooldownKey(string $email): string
    {
        return 'registration_code_cooldown:' . $this->normalize($email);
    }

    private function normalize(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }
}
