<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;

trait NormalizesEmail
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
        }
    }
}
