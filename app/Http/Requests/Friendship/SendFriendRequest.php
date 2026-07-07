<?php

namespace App\Http\Requests\Friendship;

use Illuminate\Foundation\Http\FormRequest;

class SendFriendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'addressee_id' => 'required|integer|exists:Users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'addressee_id.exists' => 'This user does not exist.',
        ];
    }
}
