<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LivewireTranslations\TranslationServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestDatabase();
    }

    protected function setUpTestDatabase(): void
    {
        // Create languages table if it doesn't exist
        if (!Schema::hasTable('languages')) {
            Schema::create('languages', function ($table) {
                $table->id();
                $table->string('language_code', 10)->unique();
                $table->string('name');
                $table->string('native_name')->nullable();
                $table->boolean('is_active')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->index(['is_active', 'sort_order']);
            });
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            TranslationServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(
            random_bytes(32)
        ));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('livewire-translations.languages_table', 'languages');
        $app['config']->set('livewire-translations.default_language', 'en');
        $app['config']->set('livewire-translations.translation_suffix', '_translations');
        $app['config']->set('livewire-translations.translation_model_namespace', 'LivewireTranslations\\Tests\\Support\\Models');
    }
}