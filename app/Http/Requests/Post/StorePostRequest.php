<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content'           => 'required|string|max:5000',
            'is_spoiler'        => 'sometimes|boolean',
            'image_urls'        => 'sometimes|nullable|array',
            'related_anime_id'  => 'sometimes|nullable|integer',
            'related_manga_id'  => 'sometimes|nullable|integer',
        ];
    }
}
