<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content'    => 'sometimes|string|max:5000',
            'is_spoiler' => 'sometimes|boolean',
            'image_urls' => 'sometimes|array',
        ];
    }
}
