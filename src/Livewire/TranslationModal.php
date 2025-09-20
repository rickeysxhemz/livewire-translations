<?php

declare(strict_types=1);

namespace LivewireTranslations\Livewire;

use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use LivewireTranslations\Services\LanguageManager;

class TranslationModal extends Component
{
    #[Reactive]
    public $model = null;
    
    #[Reactive]
    public $modelId = null;
    
    #[Reactive] 
    public $modelClass = null;

    public bool $showModal = false;
    public string $currentLanguage = 'en';
    public array $translations = [];
    public array $availableLanguages = [];
    public array $translatableFields = [];

    public function boot(LanguageManager $languageManager): void
    {
        $this->languageManager = $languageManager;
    }

    public function mount($model = null, $modelClass = null, $modelId = null): void
    {
        if ($model) {
            $this->model = $model;
            $this->modelClass = $model::class;
            $this->modelId = $model->id;
        } elseif ($modelClass && $modelId) {
            $this->modelClass = $modelClass;
            $this->modelId = $modelId;
            $this->model = $modelClass::find($modelId);
        }

        $this->loadData();
    }

    #[On('openTranslationModal')]
    public function openModal(?string $languageCode = null): void
    {
        $this->currentLanguage = $languageCode ?? config('livewire-translations.default_language', 'en');
        $this->showModal = true;
        $this->loadData();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function switchLanguage(string $languageCode): void
    {
        $this->currentLanguage = $languageCode;
    }

    public function saveTranslation(): void
    {
        $this->validate([
            "translations.{$this->currentLanguage}.*" => ['nullable', 'string'],
        ]);

        try {
            $translationData = $this->translations[$this->currentLanguage] ?? [];
            
            $translationData = array_filter(
                $translationData, 
                fn($value) => !empty(trim($value ?? ''))
            );

            if (!empty($translationData)) {
                $this->model->saveTranslation($translationData, $this->currentLanguage);
                $this->dispatch('translation-saved', [
                    'language' => $this->currentLanguage,
                    'model_id' => $this->modelId
                ]);
                session()->flash('message', '✅ Translation saved successfully!');
            } else {
                $this->model->deleteTranslation($this->currentLanguage);
                session()->flash('message', '🗑️ Translation deleted successfully!');
            }

            $this->loadTranslations();

        } catch (\Exception $e) {
            session()->flash('error', '❌ Error saving translation: ' . $e->getMessage());
        }
    }

    public function deleteTranslation(?string $languageCode = null): void
    {
        $languageCode ??= $this->currentLanguage;

        try {
            $this->model->deleteTranslation($languageCode);
            $this->loadTranslations();
            
            session()->flash('message', '🗑️ Translation deleted successfully!');
            
            $this->dispatch('translation-deleted', [
                'language' => $languageCode,
                'model_id' => $this->modelId
            ]);

        } catch (\Exception $e) {
            session()->flash('error', '❌ Error deleting translation: ' . $e->getMessage());
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire-translations::livewire.translation-modal');
    }

    private function loadData(): void
    {
        if (!$this->model) {
            return;
        }

        $this->availableLanguages = $this->languageManager->getActiveLanguages()->toArray();
        $this->translatableFields = $this->model::getTranslatableAttributes();
        $this->loadTranslations();
    }

    private function loadTranslations(): void
    {
        $this->translations = [];

        // Eager load all translations to prevent N+1 queries
        $languageCodes = collect($this->availableLanguages)->pluck('language_code');
        $existingTranslations = $this->model->translations()
            ->whereIn('language_code', $languageCodes)
            ->get()
            ->keyBy('language_code');

        foreach ($this->availableLanguages as $language) {
            $languageCode = $language['language_code'];
            $translation = $existingTranslations->get($languageCode);
            $this->translations[$languageCode] = [];

            foreach ($this->translatableFields as $field) {
                $this->translations[$languageCode][$field] = $translation?->{$field} ?? '';
            }
        }
    }

    public function getTranslationStatusProperty(): array
    {
        return collect($this->availableLanguages)
            ->mapWithKeys(fn($language) => [
                $language['language_code'] => $this->model->hasTranslation($language['language_code'])
            ])
            ->toArray();
    }
}
