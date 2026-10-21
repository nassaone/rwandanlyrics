<?php

namespace App\Http\Requests\Admin;

use App\Models\Redirect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RedirectRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['from_path', 'to_path'] as $key) {
            $value = trim((string) $this->input($key));
            $this->merge([$key => strlen($value) > 1 ? rtrim($value, '/') : $value]);
        }
    }

    public function rules(): array
    {
        // Internal paths only: one leading slash, no whitespace or backslashes, never a full URL.
        $path = 'regex:#^/(?!/)[^\s\\\\]*$#';

        return [
            'from_path' => ['required', 'string', 'max:255', $path, 'unique:redirects,from_path'],
            'to_path' => ['required', 'string', 'max:500', $path],
        ];
    }

    public function messages(): array
    {
        return [
            'from_path.regex' => 'Enter a path on this site that starts with a single slash, such as /old-page.',
            'to_path.regex' => 'Enter a path on this site that starts with a single slash, such as /songs.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['from_path', 'to_path'])) {
                    return;
                }

                $from = $this->input('from_path');
                $to = $this->input('to_path');

                if ($from === $to) {
                    $validator->errors()->add('to_path', 'The target must be different from the old path.');
                } elseif ($from === '/admin' || str_starts_with($from, '/admin/')) {
                    $validator->errors()->add('from_path', 'Admin pages cannot be redirected.');
                } elseif (Redirect::where('from_path', $to)->exists()) {
                    $validator->errors()->add('to_path', 'That target is itself redirected elsewhere. Use its final destination.');
                }
            },
        ];
    }
}
