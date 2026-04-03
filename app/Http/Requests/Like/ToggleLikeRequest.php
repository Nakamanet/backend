<?php

namespace App\Http\Requests\Like;

use Illuminate\Foundation\Http\FormRequest;

class ToggleLikeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'post_id'    => 'nullable|integer|exists:Posts,id',
            'comment_id' => 'nullable|integer|exists:Comments,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->post_id) && empty($this->comment_id)) {
                $validator->errors()->add('post_id', 'Either post_id or comment_id is required.');
            }
        });
    }
}
