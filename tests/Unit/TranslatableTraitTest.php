<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LivewireTranslations\Tests\Support\Models\TestModel;
use LivewireTranslations\Tests\Support\Models\TestModelTranslation;
use LivewireTranslations\Tests\TestCase;

class TranslatableTraitTest extends TestCase
{
    use RefreshDatabase;

    protected TestModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();

        $this->model = TestModel::create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);
    }

    protected function createTables(): void
    {
        if (!Schema::hasTable('test_models')) {
            $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function ($table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('non_translatable')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('test_model_translations')) {
            $this->app['db']->connection()->getSchemaBuilder()->create('test_model_translations', function ($table) {
                $table->id();
                $table->foreignId('test_model_id')->constrained()->onDelete('cascade');
                $table->string('language_code', 10);
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['test_model_id', 'language_code']);
            });
        }
    }

    public function test_translatable_attributes_are_set_correctly(): void
    {
        $this->assertEquals(['name', 'description'], TestModel::getTranslatableAttributes());
    }

    public function test_translation_model_name_is_generated_correctly(): void
    {
        $expected = 'LivewireTranslations\\Tests\\Support\\Models\\TestModelTranslation';
        $this->assertEquals($expected, $this->model->getTranslationModelName());
    }

    public function test_translations_relationship_exists(): void
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $this->model->translations());
    }

    public function test_can_save_translation(): void
    {
        $translation = $this->model->saveTranslation([
            'name' => 'Spanish Name',
            'description' => 'Spanish Description'
        ], 'es');

        $this->assertInstanceOf(TestModelTranslation::class, $translation);
        $this->assertEquals('Spanish Name', $translation->name);
        $this->assertEquals('Spanish Description', $translation->description);
        $this->assertEquals('es', $translation->language_code);
    }

    public function test_can_get_translation(): void
    {
        $this->model->saveTranslation([
            'name' => 'French Name',
            'description' => 'French Description'
        ], 'fr');

        $translation = $this->model->translation('fr');

        $this->assertNotNull($translation);
        $this->assertEquals('French Name', $translation->name);
        $this->assertEquals('French Description', $translation->description);
    }

    public function test_returns_null_for_non_existent_translation(): void
    {
        $translation = $this->model->translation('de');
        $this->assertNull($translation);
    }

    public function test_get_translated_attribute_returns_translation(): void
    {
        $this->model->saveTranslation(['name' => 'Italian Name'], 'it');

        $translatedName = $this->model->getTranslatedAttribute('name', 'it');
        $this->assertEquals('Italian Name', $translatedName);
    }

    public function test_get_translated_attribute_fallback_to_original(): void
    {
        $translatedName = $this->model->getTranslatedAttribute('name', 'pt');
        $this->assertEquals('Original Name', $translatedName);
    }

    public function test_magic_getter_returns_translated_attribute(): void
    {
        $this->model->saveTranslation(['name' => 'Dutch Name'], 'nl');
        app()->setLocale('nl');

        $this->assertEquals('Dutch Name', $this->model->name);
    }

    public function test_can_update_existing_translation(): void
    {
        $translation = $this->model->saveTranslation(['name' => 'German Name'], 'de');
        $originalId = $translation->id;

        $updatedTranslation = $this->model->saveTranslation(['name' => 'Updated German Name'], 'de');

        $this->assertEquals($originalId, $updatedTranslation->id);
        $this->assertEquals('Updated German Name', $updatedTranslation->name);
    }

    public function test_can_delete_translation(): void
    {
        $this->model->saveTranslation(['name' => 'Russian Name'], 'ru');

        $this->assertTrue($this->model->hasTranslation('ru'));

        $deleted = $this->model->deleteTranslation('ru');

        $this->assertTrue($deleted);
        $this->assertFalse($this->model->hasTranslation('ru'));
    }

    public function test_delete_non_existent_translation_returns_false(): void
    {
        $deleted = $this->model->deleteTranslation('xyz');
        $this->assertFalse($deleted);
    }

    public function test_get_available_languages(): void
    {
        $this->model->saveTranslation(['name' => 'Spanish Name'], 'es');
        $this->model->saveTranslation(['name' => 'French Name'], 'fr');

        $languages = $this->model->getAvailableLanguages();

        $this->assertCount(2, $languages);
        $this->assertTrue($languages->contains('es'));
        $this->assertTrue($languages->contains('fr'));
    }

    public function test_has_translation(): void
    {
        $this->assertFalse($this->model->hasTranslation('ja'));

        $this->model->saveTranslation(['name' => 'Japanese Name'], 'ja');

        $this->assertTrue($this->model->hasTranslation('ja'));
    }

    public function test_scope_with_translation(): void
    {
        $model2 = TestModel::create(['name' => 'Model 2']);

        $this->model->saveTranslation(['name' => 'Chinese Name'], 'zh');

        $modelsWithChinese = TestModel::withTranslation('zh')->get();
        $modelsWithSpanish = TestModel::withTranslation('es')->get();

        $this->assertCount(1, $modelsWithChinese);
        $this->assertEquals($this->model->id, $modelsWithChinese->first()->id);
        $this->assertCount(0, $modelsWithSpanish);
    }

    public function test_get_translated_attributes(): void
    {
        $this->model->saveTranslation([
            'name' => 'Korean Name',
            'description' => 'Korean Description'
        ], 'ko');

        $attributes = $this->model->getTranslatedAttributes('ko');

        $this->assertEquals([
            'name' => 'Korean Name',
            'description' => 'Korean Description'
        ], $attributes);
    }

    public function test_get_translated_attributes_fallback(): void
    {
        $attributes = $this->model->getTranslatedAttributes('unknown');

        $this->assertEquals([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ], $attributes);
    }

    public function test_save_translation_respects_fillable_fields(): void
    {
        $translation = $this->model->saveTranslation([
            'name' => 'Safe Name',
            'unsafe_field' => 'Unsafe Value'
        ], 'test');

        $this->assertEquals('Safe Name', $translation->name);
        $this->assertNull($translation->unsafe_field ?? null);
    }
}