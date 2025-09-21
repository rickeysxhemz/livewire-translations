<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use LivewireTranslations\Livewire\TranslationModal;
use LivewireTranslations\Models\Language;
use LivewireTranslations\Tests\Support\Models\TestModel;
use LivewireTranslations\Tests\TestCase;

class TranslationModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestTables();
        $this->createTestLanguages();
    }

    protected function createTestTables(): void
    {
        if (!Schema::hasTable('test_models')) {
            $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function ($table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
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

    public function test_component_can_be_rendered(): void
    {
        Livewire::test(TranslationModal::class)
            ->assertStatus(200);
    }

    public function test_component_loads_active_languages(): void
    {
        Livewire::test(TranslationModal::class)
            ->assertSet('languages', function ($languages) {
                return $languages->count() === 2
                    && $languages->pluck('language_code')->contains('en')
                    && $languages->pluck('language_code')->contains('es')
                    && !$languages->pluck('language_code')->contains('fr');
            });
    }

    public function test_component_can_open_modal_for_model(): void
    {
        $model = TestModel::create([
            'name' => 'Test Model',
            'description' => 'Test Description'
        ]);

        Livewire::test(TranslationModal::class)
            ->call('openModal', TestModel::class, $model->id, ['name', 'description'])
            ->assertSet('isOpen', true)
            ->assertSet('modelClass', TestModel::class)
            ->assertSet('modelId', $model->id)
            ->assertSet('translatableFields', ['name', 'description']);
    }

    public function test_component_loads_existing_translations(): void
    {
        $model = TestModel::create([
            'name' => 'Test Model',
            'description' => 'Test Description'
        ]);

        $model->saveTranslation([
            'name' => 'Modelo de Prueba',
            'description' => 'Descripción de Prueba'
        ], 'es');

        Livewire::test(TranslationModal::class)
            ->call('openModal', TestModel::class, $model->id, ['name', 'description'])
            ->assertSet('translations.es.name', 'Modelo de Prueba')
            ->assertSet('translations.es.description', 'Descripción de Prueba');
    }

    public function test_component_can_save_translations(): void
    {
        $model = TestModel::create([
            'name' => 'Test Model',
            'description' => 'Test Description'
        ]);

        Livewire::test(TranslationModal::class)
            ->call('openModal', TestModel::class, $model->id, ['name', 'description'])
            ->set('translations.es.name', 'Modelo Español')
            ->set('translations.es.description', 'Descripción Española')
            ->call('saveTranslations')
            ->assertEmitted('translationsSaved');

        $this->assertDatabaseHas('test_model_translations', [
            'test_model_id' => $model->id,
            'language_code' => 'es',
            'name' => 'Modelo Español',
            'description' => 'Descripción Española'
        ]);
    }

    public function test_component_can_update_existing_translations(): void
    {
        $model = TestModel::create([
            'name' => 'Test Model',
            'description' => 'Test Description'
        ]);

        $model->saveTranslation([
            'name' => 'Old Spanish Name',
            'description' => 'Old Spanish Description'
        ], 'es');

        Livewire::test(TranslationModal::class)
            ->call('openModal', TestModel::class, $model->id, ['name', 'description'])
            ->set('translations.es.name', 'New Spanish Name')
            ->set('translations.es.description', 'New Spanish Description')
            ->call('saveTranslations');

        $this->assertDatabaseHas('test_model_translations', [
            'test_model_id' => $model->id,
            'language_code' => 'es',
            'name' => 'New Spanish Name',
            'description' => 'New Spanish Description'
        ]);
    }

    public function test_component_can_delete_translations(): void
    {
        $model = TestModel::create([
            'name' => 'Test Model',
            'description' => 'Test Description'
        ]);

        $model->saveTranslation([
            'name' => 'Spanish Name',
            'description' => 'Spanish Description'
        ], 'es');

        Livewire::test(TranslationModal::class)
            ->call('openModal', TestModel::class, $model->id, ['name', 'description'])
            ->call('deleteTranslation', 'es')
            ->assertEmitted('translationDeleted');

        $this->assertDatabaseMissing('test_model_translations', [
            'test_model_id' => $model->id,
            'language_code' => 'es'
        ]);
    }

    public function test_component_can_close_modal(): void
    {
        Livewire::test(TranslationModal::class)
            ->set('isOpen', true)
            ->call('closeModal')
            ->assertSet('isOpen', false)
            ->assertSet('modelClass', null)
            ->assertSet('modelId', null)
            ->assertSet('translatableFields', [])
            ->assertSet('translations', []);
    }

    public function test_component_validates_required_fields(): void
    {
        $model = TestModel::create([
            'name' => 'Test Model',
            'description' => 'Test Description'
        ]);

        Livewire::test(TranslationModal::class)
            ->call('openModal', TestModel::class, $model->id, ['name', 'description'])
            ->set('translations.es.name', '') // Empty required field
            ->call('saveTranslations')
            ->assertHasErrors(['translations.es.name']);
    }

    public function test_component_handles_invalid_model_class(): void
    {
        Livewire::test(TranslationModal::class)
            ->call('openModal', 'NonExistentClass', 1, ['name'])
            ->assertSet('isOpen', false);
    }

    public function test_component_handles_non_existent_model_id(): void
    {
        Livewire::test(TranslationModal::class)
            ->call('openModal', TestModel::class, 999, ['name'])
            ->assertSet('isOpen', false);
    }

    public function test_component_only_shows_active_languages(): void
    {
        Language::create([
            'language_code' => 'de',
            'name' => 'German',
            'is_active' => false,
            'sort_order' => 4
        ]);

        Livewire::test(TranslationModal::class)
            ->assertSet('languages', function ($languages) {
                return !$languages->pluck('language_code')->contains('de');
            });
    }

    public function test_component_respects_language_sort_order(): void
    {
        Language::where('language_code', 'es')->update(['sort_order' => 1]);
        Language::where('language_code', 'en')->update(['sort_order' => 2]);

        Livewire::test(TranslationModal::class)
            ->assertSet('languages', function ($languages) {
                return $languages->first()->language_code === 'es';
            });
    }
}