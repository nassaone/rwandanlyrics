<?php

namespace App\Http\Requests\Admin;

use App\Enums\PublicationStatus;
use App\Enums\RightsStatus;
use App\Enums\VerificationStatus;
use App\Http\Requests\Admin\Concerns\ChecksSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SongRequest extends FormRequest
{
    use ChecksSchedule;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('songs', 'slug')->ignore($this->route('song'))],
            'primary_artist_id' => ['required', 'integer', 'exists:artists,id'],
            'featured_artist_ids' => ['nullable', 'array'],
            'featured_artist_ids.*' => ['integer', 'exists:artists,id'],
            'album_id' => ['nullable', 'integer', 'exists:albums,id'],
            'track_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'release_date' => ['nullable', 'date'],
            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'lyrics' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', Rule::enum(PublicationStatus::class)],
            'published_at' => ['nullable', 'date'],
            'verification_status' => ['required', Rule::enum(VerificationStatus::class)],
            'rights_status' => ['required', Rule::enum(RightsStatus::class)],
            'rights_holder' => ['nullable', 'string', 'max:255'],
            'rights_source' => ['nullable', 'string', 'max:255'],
            'rights_notes' => ['nullable', 'string', 'max:2000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'noindex' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkSchedule($validator)];
    }
}
