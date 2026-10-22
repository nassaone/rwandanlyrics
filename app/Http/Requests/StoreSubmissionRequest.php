<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // enforced by SubmissionPolicy in the controller
    }

    public function rules(): array
    {
        return [
            'song' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:200'],
            'artist_name' => ['required', 'string', 'max:200'],
            'album_title' => ['nullable', 'string', 'max:200'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'lyrics' => ['required', 'string', 'min:10', 'max:20000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'rights_holder' => ['nullable', 'string', 'max:200'],
            'rights_source' => ['nullable', 'string', 'max:500'],
            'rights_confirmed' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return ['rights_confirmed.accepted' => 'You must confirm you have the right to submit these lyrics.'];
    }
}
