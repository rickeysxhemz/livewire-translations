<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LivewireTranslations\Models\Language;
use LivewireTranslations\Services\LanguageManager;
use LivewireTranslations\Tests\TestCase;

class LanguageManagerTest extends TestCase
{
    use RefreshDatabase;

    protected LanguageManager $languageManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->languageManager = app(LanguageManager::class);
    }

    public function test_get_active_languages(): void
    {
        $this->createTestLanguages();

        $activeLanguages = $this->languageManager->getActiveLanguages();

        $this->assertCount(2, $activeLanguages);
        $this->assertTrue($activeLanguages->pluck('language_code')->contains('en'));
        $this->assertTrue($activeLanguages->pluck('language_code')->contains('es'));
        $this->assertFalse($activeLanguages->pluck('language_code')->contains('fr'));
    }

    public function test_get_all_languages(): void
    {
        $this->createTestLanguages();

        $allLanguages = $this->languageManager->getAllLanguages();

        $this->assertCount(3, $allLanguages);
        $this->assertEquals('en', $allLanguages->first()->language_code);
    }

    public function test_save_new_language(): void
    {
        $data = [
            'language_code' => 'jp',
            'name' => 'Japanese',
            'native_name' => '日本語',
            'is_active' => true,
            'sort_order' => 10
        ];

        $language = $this->languageManager->saveLanguage($data);

        $this->assertInstanceOf(Language::class, $language);
        $this->assertEquals('jp', $language->language_code);
        $this->assertEquals('Japanese', $language->name);
        $this->assertDatabaseHas('languages', $data);
    }

    public function test_update_existing_language(): void
    {
        $language = Language::create([
            'language_code' => 'de',
            'name' => 'German',
            'is_active' => false,
            'sort_order' => 5
        ]);

        $updatedData = [
            'language_code' => 'de',
            'name' => 'Deutsch',
            'native_name' => 'Deutsch',
            'is_active' => true,
            'sort_order' => 3
        ];

        $updatedLanguage = $this->languageManager->saveLanguage($updatedData);

        $this->assertEquals($language->id, $updatedLanguage->id);
        $this->assertEquals('Deutsch', $updatedLanguage->name);
        $this->assertTrue($updatedLanguage->is_active);
    }

    public function test_delete_existing_language(): void
    {
        $language = Language::create([
            'language_code' => 'ru',
            'name' => 'Russian',
            'is_active' => false,
            'sort_order' => 8
        ]);

        $deleted = $this->languageManager->deleteLanguage('ru');

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('languages', ['language_code' => 'ru']);
    }

    public function test_delete_non_existent_language_returns_false(): void
    {
        $deleted = $this->languageManager->deleteLanguage('xyz');

        $this->assertFalse($deleted);
    }

    public function test_toggle_language_status(): void
    {
        $language = Language::create([
            'language_code' => 'it',
            'name' => 'Italian',
            'is_active' => false,
            'sort_order' => 6
        ]);

        $toggled = $this->languageManager->toggleLanguage('it');

        $this->assertTrue($toggled);
        $this->assertTrue($language->fresh()->is_active);

        $this->languageManager->toggleLanguage('it');
        $this->assertFalse($language->fresh()->is_active);
    }

    public function test_toggle_non_existent_language_returns_false(): void
    {
        $toggled = $this->languageManager->toggleLanguage('xyz');

        $this->assertFalse($toggled);
    }

    public function test_validates_table_name_format(): void
    {
        config(['livewire-translations.languages_table' => 'invalid-table-name!']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table name format');

        $reflection = new \ReflectionClass($this->languageManager);
        $method = $reflection->getMethod('getLanguagesMigrationContent');
        $method->setAccessible(true);
        $method->invoke($this->languageManager);
    }

    public function test_migration_content_uses_configured_table_name(): void
    {
        config(['livewire-translations.languages_table' => 'custom_languages']);

        $reflection = new \ReflectionClass($this->languageManager);
        $method = $reflection->getMethod('getLanguagesMigrationContent');
        $method->setAccessible(true);

        $content = $method->invoke($this->languageManager);

        $this->assertStringContainsString("Schema::create('custom_languages'", $content);
        $this->assertStringContainsString("Schema::dropIfExists('custom_languages'", $content);
    }

    protected function createTestLanguages(): void
    {
        Language::create([
            'language_code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'sort_order' => 1
        ]);

        Language::create([
            'language_code' => 'es',
            'name' => 'Spanish',
            'is_active' => true,
            'sort_order' => 2
        ]);

        Language::create([
            'language_code' => 'fr',
            'name' => 'French',
            'is_active' => false,
            'sort_order' => 3
        ]);
    }
}