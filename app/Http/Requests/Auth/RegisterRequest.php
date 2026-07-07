<?php

namespace App\Http\Requests\Auth;

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
            'birthdate' => 'required|date',
            'localisation' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken.',
            'email.unique'    => 'An account with this email already exists.',
            'password.min'    => 'Password must be at least 8 characters.',
        ];
    }
}
