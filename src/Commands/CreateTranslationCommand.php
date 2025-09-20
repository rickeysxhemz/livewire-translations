<?php

declare(strict_types=1);

namespace LivewireTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LivewireTranslations\Services\LanguageManager;

class CreateTranslationCommand extends Command
{
    protected $signature = 'create:translation {model : The name of the model to create translations for}';
    
    protected $description = 'Create translation model and migration for a given model';

    public function __construct(
        private readonly LanguageManager $languageManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelName = $this->argument('model');

        // Validate model name to prevent path traversal
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $modelName)) {
            $this->error("❌ Invalid model name. Must contain only letters, numbers, and underscores, starting with a letter.");
            return Command::FAILURE;
        }

        $this->info("🚀 Creating translations for model: {$modelName}");

        $this->ensureLanguagesTable();

        $fields = $this->getModelFields($modelName);
        
        if ($fields->isEmpty()) {
            $this->error("❌ Could not find migration for model: {$modelName}");
            return Command::FAILURE;
        }

        $translatableFields = $this->selectTranslatableFields($fields);

        if ($translatableFields->isEmpty()) {
            $this->warn('⚠️  No translatable fields selected. Exiting...');
            return Command::SUCCESS;
        }

        $this->generateTranslationModel($modelName, $translatableFields);
        $this->generateTranslationMigration($modelName, $translatableFields);

        $this->info('✅ Translation files created successfully!');
        $this->displayNextSteps($modelName);

        return Command::SUCCESS;
    }

    private function ensureLanguagesTable(): void
    {
        if (!Schema::hasTable(config('livewire-translations.languages_table', 'languages'))) {
            $this->info('📋 Creating languages table...');
            $this->languageManager->createLanguagesTable();
        }
    }

    private function getModelFields(string $modelName): Collection
    {
        $tableName = Str::snake(Str::pluralStudly($modelName));
        
        if (!Schema::hasTable($tableName)) {
            return collect();
        }

        $columns = Schema::getColumnListing($tableName);
        
        $nonTranslatableFields = [
            'id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at',
            'password', 'remember_token', 'email', 'phone', 'status'
        ];

        return collect($columns)->diff($nonTranslatableFields);
    }

    private function selectTranslatableFields(Collection $fields): Collection
    {
        $this->info('🔍 Available fields for translation:');
        
        $commonTranslatable = collect(config('livewire-translations.common_translatable_fields', []));

        $fields->each(function (string $field, int $index) use ($commonTranslatable) {
            $isCommon = $commonTranslatable->contains($field) ? ' 🌟 (commonly translatable)' : '';
            $this->info("  [{$index}] {$field}{$isCommon}");
        });

        $selected = $this->ask('🎯 Enter field indices to make translatable (comma-separated, e.g., 0,1,2)');
        
        if (!$selected) {
            return collect();
        }

        $selectedIndices = collect(explode(',', $selected))
            ->map(fn($index) => (int) trim($index));

        return $selectedIndices
            ->filter(fn($index) => $fields->has($index))
            ->map(fn($index) => $fields->get($index));
    }

    private function generateTranslationModel(string $modelName, Collection $fields): void
    {
        $stub = File::get(__DIR__.'/../stubs/translation-model.stub');
        
        $replacements = [
            '{{modelName}}' => $modelName,
            '{{translationModelName}}' => $modelName . 'Translation',
            '{{tableName}}' => Str::snake($modelName) . '_translations',
            '{{foreignKey}}' => Str::snake($modelName) . '_id',
            '{{fillableFields}}' => $this->generateFillableFields($fields),
            '{{namespace}}' => config('livewire-translations.translation_model_namespace', 'App\\Models\\Translations'),
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $stub);

        $directory = app_path('Models/Translations');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = $directory . '/' . $modelName . 'Translation.php';
        File::put($filename, $content);

        $this->info("📝 Translation model created: {$filename}");
    }

    private function generateTranslationMigration(string $modelName, Collection $fields): void
    {
        $stub = File::get(__DIR__.'/../stubs/translation-migration.stub');
        
        $tableName = Str::snake($modelName) . '_translations';
        $timestamp = now()->format('Y_m_d_His');
        
        $replacements = [
            '{{className}}' => 'Create' . Str::studly($tableName) . 'Table',
            '{{tableName}}' => $tableName,
            '{{foreignKey}}' => Str::snake($modelName) . '_id',
            '{{foreignTable}}' => Str::snake(Str::pluralStudly($modelName)),
            '{{fields}}' => $this->generateMigrationFields($fields),
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $stub);

        $filename = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");
        File::put($filename, $content);

        $this->info("🗄️  Translation migration created: {$filename}");
    }

    private function generateFillableFields(Collection $fields): string
    {
        return $fields
            ->push('language_code')
            ->map(fn($field) => "'{$field}'")
            ->join(', ');
    }

    private function generateMigrationFields(Collection $fields): string
    {
        return $fields
            ->map(fn($field) => "            \$table->text('{$field}')->nullable();")
            ->join("\n");
    }

    private function displayNextSteps(string $modelName): void
    {
        $this->info('📋 Next steps:');
        $this->info("   1️⃣  Run: php artisan migrate");
        $this->info("   2️⃣  Add TranslatableTrait to your {$modelName} model");
        $this->info("   3️⃣  Use the translation modal in your Livewire components");
    }
}
