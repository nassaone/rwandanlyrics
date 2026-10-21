<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicationStatus;
use App\Enums\VerificationStatus;
use App\Http\Requests\Admin\Concerns\ChecksSchedule;
use App\Http\Requests\Admin\Concerns\ImageRules;
use App\Models\Artist;
use App\Models\ArtistAlias;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ArtistRequest extends FormRequest
{
    use ChecksSchedule, ImageRules;

    protected function prepareForValidation(): void
    {
        if (filled($this->input('country_code'))) {
            $this->merge(['country_code' => strtoupper(trim((string) $this->input('country_code')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('artists', 'slug')->ignore($this->route('artist'))],
            'bio' => ['nullable', 'string', 'max:10000'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'website' => ['nullable', 'url', 'max:255'],
            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
            'aliases' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'published_at' => ['nullable', 'date'],
            'verification_status' => ['required', Rule::enum(VerificationStatus::class)],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'noindex' => ['sometimes', 'boolean'],
        ] + $this->imageRules('image') + $this->imageRules('banner');
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $this->checkSchedule($validator);

                if ($validator->errors()->has('name') || blank($this->input('name'))) {
                    return;
                }

                $normalized = Artist::normalize((string) $this->input('name'));
                $ignoreId = $this->route('artist')?->id;

                $duplicate = Artist::where('normalized_name', $normalized)
                    ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->first();
                if ($duplicate) {
                    $validator->errors()->add('name', "An artist named \"{$duplicate->name}\" already exists.");

                    return;
                }

                $alias = ArtistAlias::with('artist')->where('normalized_alias', $normalized)
                    ->when($ignoreId, fn ($q) => $q->where('artist_id', '!=', $ignoreId))->first();
                if ($alias) {
                    $validator->errors()->add('name', "This name is already an alias of \"{$alias->artist->name}\".");
                }
            },
        ];
    }
}
