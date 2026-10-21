<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('languages', 'name')->ignore($this->route('language'))],
            'code' => ['nullable', 'string', 'max:10', Rule::unique('languages', 'code')->ignore($this->route('language'))],
        ];
    }
}
