<?php

namespace App\Http\Requests\Library;

use Illuminate\Foundation\Http\FormRequest;

class AnimeLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'anime_id'      => 'required|integer',
            'status'        => 'required|in:watching,completed,on_hold,dropped,plan_to_watch',
            'progress'      => 'nullable|integer|min:0',
            'rewatch_count' => 'nullable|integer|min:0',
            'score'         => 'nullable|integer|min:1|max:10',
            'is_private'    => 'boolean',
        ];
    }
}
