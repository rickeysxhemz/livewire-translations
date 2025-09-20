<?php

declare(strict_types=1);

namespace LivewireTranslations\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

trait TranslatableTrait
{
    protected static array $translatableAttributes = [];
    protected static array $translatableAttributesSet = [];

    public static function bootTranslatableTrait(): void
    {
        static::$translatableAttributes = static::getTranslatableAttributes();
        // Create a set for O(1) lookups
        static::$translatableAttributesSet = array_flip(static::$translatableAttributes);
    }

    public function getTranslationModelName(): string
    {
        $modelName = class_basename($this);
        $namespace = config('livewire-translations.translation_model_namespace', 'App\\Models\\Translations');
        
        return $namespace . '\\' . $modelName . 'Translation';
    }

    public function translations(): HasMany
    {
        return $this->hasMany($this->getTranslationModelName());
    }

    public function translation(?string $languageCode = null): ?object
    {
        $languageCode ??= app()->getLocale();
        
        return $this->translations()
            ->where('language_code', $languageCode)
            ->first();
    }

    public function getTranslatedAttribute(string $attribute, ?string $languageCode = null): ?string
    {
        $translation = $this->translation($languageCode);
        
        return $translation?->{$attribute} ?? $this->getOriginal($attribute);
    }

    public function __get($key): mixed
    {
        // Use O(1) lookup instead of O(n) in_array
        if (isset(static::$translatableAttributesSet[$key])) {
            return $this->getTranslatedAttribute($key);
        }

        return parent::__get($key);
    }

    public static function getTranslatableAttributes(): array
    {
        return property_exists(static::class, 'translatable') 
            ? static::$translatable 
            : [];
    }

    public function saveTranslation(array $data, string $languageCode): object
    {
        // Validate data against fillable fields to prevent mass assignment
        $translationModel = $this->getTranslationModelName();
        $allowedFields = (new $translationModel())->getFillable();
        $safeData = array_intersect_key($data, array_flip($allowedFields));

        $translation = $this->translation($languageCode);

        if ($translation) {
            $translation->update($safeData);
            return $translation;
        }

        $safeData['language_code'] = $languageCode;
        return $this->translations()->create($safeData);
    }

    public function deleteTranslation(string $languageCode): bool
    {
        return $this->translation($languageCode)?->delete() ?? false;
    }

    public function getAvailableLanguages(): SupportCollection
    {
        return $this->translations()
            ->pluck('language_code')
            ->unique();
    }

    public function hasTranslation(string $languageCode): bool
    {
        return $this->translations()
            ->where('language_code', $languageCode)
            ->exists();
    }

    public function scopeWithTranslation($query, string $languageCode): mixed
    {
        return $query->whereHas('translations', 
            fn($q) => $q->where('language_code', $languageCode)
        );
    }

    public function getTranslatedAttributes(?string $languageCode = null): array
    {
        $translation = $this->translation($languageCode);
        
        return collect(static::$translatableAttributes)
            ->mapWithKeys(fn($attribute) => [
                $attribute => $translation?->{$attribute} ?? $this->getOriginal($attribute)
            ])
            ->toArray();
    }
}
