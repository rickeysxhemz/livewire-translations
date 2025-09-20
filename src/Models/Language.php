<?php

declare(strict_types=1);

namespace LivewireTranslations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'language_code',
        'name',
        'native_name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('livewire-translations.languages_table', 'languages');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->native_name ?: $this->name,
        );
    }
}
