<?php

namespace App\Http\Requests\Admin\Concerns;

trait ImageRules
{
    /** Rules for an optional image upload field plus its alt text and "remove" checkbox. */
    protected function imageRules(string $name): array
    {
        return [
            $name => [
                'nullable', 'file',
                'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
                'dimensions:min_width=200,min_height=200,max_width=6000,max_height=6000',
            ],
            $name.'_alt' => ['nullable', 'string', 'max:255', 'required_with:'.$name],
            'remove_'.$name => ['sometimes', 'boolean'],
        ];
    }
}
