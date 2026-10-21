<?php

namespace App\Http\Requests\Admin;

use App\Enums\AlbumType;
use App\Enums\PublicationStatus;
use App\Http\Requests\Admin\Concerns\ChecksSchedule;
use App\Http\Requests\Admin\Concerns\ImageRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AlbumRequest extends FormRequest
{
    use ChecksSchedule, ImageRules;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('albums', 'slug')->ignore($this->route('album'))],
            'type' => ['required', Rule::enum(AlbumType::class)],
            'release_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'artist_ids' => ['required', 'array', 'min:1'],
            'artist_ids.*' => ['integer', 'exists:artists,id'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'noindex' => ['sometimes', 'boolean'],
        ] + $this->imageRules('cover');
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkSchedule($validator)];
    }
}
