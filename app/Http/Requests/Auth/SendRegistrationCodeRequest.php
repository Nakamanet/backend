<?php

namespace App\Http\Requests\Auth;

use App\Rules\CloudflareTurnstile;

/**
 * Step 1 of signup: validate the whole form (so "username taken" shows up before
 * the user goes to their inbox) and the Turnstile challenge, then email a code.
 */
class SendRegistrationCodeRequest extends RegisterRequest
{
    public function rules(): array
    {
        return array_merge(static::accountRules(), [
            'turnstile_token' => ['required', 'string', new CloudflareTurnstile()],
        ]);
    }
}
