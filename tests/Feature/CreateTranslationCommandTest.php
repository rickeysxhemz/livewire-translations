<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use LivewireTranslations\Models\Language;
use LivewireTranslations\Tests\TestCase;

class CreateTranslationCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure languages table exists
        if (!Schema::hasTable('languages')) {
            Language::query()->getConnection()->getSchemaBuilder()->create('languages', function ($table) {
                $table->id();
                $table->string('language_code', 10)->unique();
                $table->string('name');
                $table->string('native_name')->nullable();
                $table->boolean('is_active')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function test_command_displays_error_for_non_existent_model(): void
    {
        $this->artisan('create:translation NonExistentModel')
            ->expectsOutput('❌ Could not find migration for model: NonExistentModel')
            ->assertExitCode(1);
    }

    public function test_command_detects_available_fields_for_existing_model(): void
    {
        $this->artisan('create:translation Post')
            ->expectsOutput('🔍 Available fields for translation:')
            ->expectsOutput('  [0] title 🌟 (commonly translatable)')
            ->expectsOutput('  [1] content 🌟 (commonly translatable)')
            ->expectsOutput('  [2] slug 🌟 (commonly translatable)')
            ->expectsOutput('  [3] description 🌟 (commonly translatable)')
            ->expectsQuestion('🎯 Enter field indices to make translatable (comma-separated, e.g., 0,1,2)', '')
            ->expectsOutput('⚠️  No translatable fields selected. Exiting...')
            ->assertExitCode(0);
    }

    public function test_command_generates_translation_files_for_selected_fields(): void
    {
        // Clean up any existing files
        $modelPath = app_path('Models/Translations/PostTranslation.php');
        if (File::exists($modelPath)) {
            File::delete($modelPath);
        }

        $migrationPattern = database_path('migrations/*_create_post_translations_table.php');
        foreach (glob($migrationPattern) as $file) {
            File::delete($file);
        }

        $this->artisan('create:translation Post')
            ->expectsOutput('🔍 Available fields for translation:')
            ->expectsQuestion('🎯 Enter field indices to make translatable (comma-separated, e.g., 0,1,2)', '0,1,3')
            ->expectsOutput('📝 Translation model created:')
            ->expectsOutput('🗄️  Translation migration created:')
            ->expectsOutput('✅ Translation files created successfully!')
            ->expectsOutput('📋 Next steps:')
            ->expectsOutput('   1️⃣  Run: php artisan migrate')
            ->expectsOutput('   2️⃣  Add TranslatableTrait to your Post model')
            ->expectsOutput('   3️⃣  Use the translation modal in your Livewire components')
            ->assertExitCode(0);

        // Verify translation model was created
        $this->assertTrue(File::exists($modelPath));

        // Verify migration was created
        $migrationFiles = glob($migrationPattern);
        $this->assertCount(1, $migrationFiles);

        // Check model content
        $modelContent = File::get($modelPath);
        $this->assertStringContainsString('class PostTranslation extends Model', $modelContent);
        $this->assertStringContainsString("'title', 'content', 'description', 'language_code'", $modelContent);

        // Check migration content
        $migrationContent = File::get($migrationFiles[0]);
        $this->assertStringContainsString('create_post_translations_table', $migrationContent);
        $this->assertStringContainsString("\$table->text('title')->nullable();", $migrationContent);
        $this->assertStringContainsString("\$table->text('content')->nullable();", $migrationContent);
        $this->assertStringContainsString("\$table->text('description')->nullable();", $migrationContent);
    }

    public function test_command_creates_languages_table_if_not_exists(): void
    {
        Schema::dropIfExists('languages');

        $this->artisan('create:translation Post')
            ->expectsOutput('📋 Creating languages table...')
            ->expectsQuestion('🎯 Enter field indices to make translatable (comma-separated, e.g., 0,1,2)', '')
            ->assertExitCode(0);

        $this->assertTrue(Schema::hasTable('languages'));
        $this->assertDatabaseHas('languages', [
            'language_code' => 'en',
            'name' => 'English',
            'is_active' => true,
        ]);
    }

    public function test_command_handles_invalid_field_indices(): void
    {
        $this->artisan('create:translation Post')
            ->expectsQuestion('🎯 Enter field indices to make translatable (comma-separated, e.g., 0,1,2)', '0,99,invalid')
            ->expectsOutput('✅ Translation files created successfully!')
            ->assertExitCode(0);

        // Should only process valid indices (0 in this case)
        $modelPath = app_path('Models/Translations/PostTranslation.php');
        if (File::exists($modelPath)) {
            $modelContent = File::get($modelPath);
            $this->assertStringContainsString("'title', 'language_code'", $modelContent);
        }
    }

    protected function tearDown(): void
    {
        // Clean up created files
        $modelPath = app_path('Models/Translations/PostTranslation.php');
        if (File::exists($modelPath)) {
            File::delete($modelPath);
        }

        $migrationPattern = database_path('migrations/*_create_post_translations_table.php');
        foreach (glob($migrationPattern) as $file) {
            File::delete($file);
        }

        // Clean up directory if empty
        $translationsDir = app_path('Models/Translations');
        if (File::exists($translationsDir) && count(File::allFiles($translationsDir)) === 0) {
            File::deleteDirectory($translationsDir);
        }

        parent::tearDown();
    }
}