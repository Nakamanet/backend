<?php

namespace App\Http\Requests\Auth;

use App\Rules\CloudflareTurnstile;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'username'  => 'required|string|max:50|unique:Users,username',
            'email'     => 'required|email|max:100|unique:Users,email',
            'password'  => 'required|string|min:8',
            'birthdate' => [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(15)->toDateString(),
                'after:' . now()->subYears(120)->toDateString(),
            ],
            'localisation'    => 'nullable|string|max:100',
            'turnstile_token' => ['required', 'string', new CloudflareTurnstile()],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique'           => 'This username is already taken.',
            'email.unique'              => 'An account with this email already exists.',
            'password.min'              => 'Password must be at least 8 characters.',
            'birthdate.before_or_equal' => 'You must be at least 15 years old to register.',
            'birthdate.after'           => 'Please enter a valid birthdate.',
            'turnstile_token.required'  => 'Anti-bot verification is required.',
        ];
    }
}
