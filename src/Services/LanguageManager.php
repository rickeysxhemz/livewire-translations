<?php

declare(strict_types=1);

namespace LivewireTranslations\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use LivewireTranslations\Models\Language;

class LanguageManager
{
    public function createLanguagesTable(): void
    {
        $tableName = config('livewire-translations.languages_table', 'languages');

        if (Schema::hasTable($tableName)) {
            // If table exists but has no languages, seed default languages
            if (Language::count() === 0) {
                $this->seedDefaultLanguages();
            }
            return;
        }

        $migrationContent = $this->getLanguagesMigrationContent();
        $timestamp = now()->format('Y_m_d_His');
        $filename = database_path("migrations/{$timestamp}_create_{$tableName}_table.php");

        File::put($filename, $migrationContent);

        Artisan::call('migrate', ['--path' => "database/migrations/{$timestamp}_create_{$tableName}_table.php"]);

        $this->seedDefaultLanguages();
    }

    public function getActiveLanguages(): Collection
    {
        return Language::active()->ordered()->get();
    }

    public function getAllLanguages(): Collection
    {
        return Language::ordered()->get();
    }

    public function saveLanguage(array $data): Language
    {
        return Language::updateOrCreate(
            ['language_code' => $data['language_code']],
            $data
        );
    }

    public function deleteLanguage(string $languageCode): bool
    {
        return Language::where('language_code', $languageCode)->first()?->delete() ?? false;
    }

    public function toggleLanguage(string $languageCode): bool
    {
        $language = Language::where('language_code', $languageCode)->first();
        
        if (!$language) {
            return false;
        }

        $language->is_active = !$language->is_active;
        return $language->save();
    }

    private function getDefaultLanguages(): array
    {
        return [
            ['language_code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'sort_order' => 1],
            ['language_code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'is_active' => false, 'sort_order' => 2],
            ['language_code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'is_active' => false, 'sort_order' => 3],
            ['language_code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_active' => false, 'sort_order' => 4],
            ['language_code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano', 'is_active' => false, 'sort_order' => 5],
            ['language_code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português', 'is_active' => false, 'sort_order' => 6],
            ['language_code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'is_active' => false, 'sort_order' => 7],
            ['language_code' => 'zh', 'name' => 'Chinese', 'native_name' => '中文', 'is_active' => false, 'sort_order' => 8],
        ];
    }

    private function seedDefaultLanguages(): void
    {
        collect($this->getDefaultLanguages())
            ->each(fn(array $language) => Language::firstOrCreate(
                ['language_code' => $language['language_code']],
                $language
            ));
    }

    private function getLanguagesMigrationContent(): string
    {
        $tableName = config('livewire-translations.languages_table', 'languages');

        // Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            throw new \InvalidArgumentException('Invalid table name format');
        }

        return "<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('language_code', 10)->unique();
            \$table->string('name');
            \$table->string('native_name')->nullable();
            \$table->boolean('is_active')->default(false);
            \$table->integer('sort_order')->default(0);
            \$table->timestamps();

            \$table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};";
    }
}
