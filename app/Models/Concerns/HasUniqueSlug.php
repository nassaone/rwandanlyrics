<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUniqueSlug
{
    abstract protected function slugSource(): string;

    protected static function bootHasUniqueSlug(): void
    {
        static::creating(function (Model $model) {
            if (blank($model->slug)) {
                $model->slug = $model->uniqueSlugFor((string) $model->{$model->slugSource()});
            }
        });
    }

    public function uniqueSlugFor(string $value): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $i = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
