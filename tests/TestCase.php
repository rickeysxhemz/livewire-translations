<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LivewireTranslations\TranslationServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/Support/migrations');
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