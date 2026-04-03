<?php

namespace App\Http\Requests\Forum;

use Illuminate\Foundation\Http\FormRequest;

class ReplyTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Must be authenticated
    }

    public function rules(): array
    {
        return [
            'content'   => 'required|string',
            'parent_id' => 'nullable|integer|exists:Forum_Replies,id',
        ];
    }
}
