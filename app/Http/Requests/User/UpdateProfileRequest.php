<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'username'         => 'sometimes|string|max:50|unique:Users,username,' . $userId,
            'email'            => 'sometimes|email|max:100|unique:Users,email,' . $userId,
            'password'         => 'sometimes|string|min:8|confirmed',
            'birthdate'        => 'sometimes|date',
            'localisation'     => 'sometimes|nullable|string|max:100',
            'bio'              => 'sometimes|nullable|string|max:500',
            'avatar_url'       => 'sometimes|nullable|url',
            'banner_url'       => 'sometimes|nullable|url',
            'theme_preference' => 'sometimes|string|in:light,dark,system',
        ];
    }
}
