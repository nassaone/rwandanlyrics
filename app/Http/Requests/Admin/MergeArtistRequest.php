<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeArtistRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'target_id' => ['required', 'integer', 'exists:artists,id', Rule::notIn([$this->route('artist')->id])],
        ];
    }

    public function messages(): array
    {
        return ['target_id.not_in' => 'Choose a different artist to merge into.'];
    }
}
