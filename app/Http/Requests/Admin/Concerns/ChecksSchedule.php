<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Support\AdminDate;
use Illuminate\Validation\Validator;

trait ChecksSchedule
{
    protected function checkSchedule(Validator $validator): void
    {
        if ($this->input('status') !== 'scheduled' || $validator->errors()->has('published_at')) {
            return;
        }

        $date = AdminDate::fromInput($this->input('published_at'));

        if ($date === null) {
            $validator->errors()->add('published_at', 'Choose a publication date for scheduled content.');
        } elseif ($date->lte(now())) {
            $validator->errors()->add('published_at', 'A scheduled publication date must be in the future.');
        }
    }
}
