<?php

namespace App\Http\Requests\Library;

use Illuminate\Foundation\Http\FormRequest;

class MangaLibraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'manga_id'     => 'required|integer',
            'status'       => 'required|in:reading,completed,on_hold,dropped,plan_to_read',
            'progress'     => 'nullable|integer|min:0',
            'reread_count' => 'nullable|integer|min:0',
            'score'        => 'nullable|integer|min:1|max:10',
            'is_private'   => 'boolean',
        ];
    }
}
