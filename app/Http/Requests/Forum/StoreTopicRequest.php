<?php

namespace App\Http\Requests\Forum;

use Illuminate\Foundation\Http\FormRequest;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Must be authenticated
    }

    public function rules(): array
    {
        return [
            'title'            => 'required|string|max:255',
            'content'          => 'required|string',
            'category'         => 'required|in:general,anime,manga,recommendations,spoilers',
            'related_anime_id' => 'nullable|integer',
            'related_manga_id' => 'nullable|integer',
        ];
    }
}
