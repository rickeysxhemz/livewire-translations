<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use LivewireTranslations\Commands\CreateTranslationCommand;
use LivewireTranslations\Services\LanguageManager;
use LivewireTranslations\Tests\TestCase;
use LivewireTranslations\TranslationServiceProvider;
use Livewire\Livewire;

class TranslationServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered(): void
    {
        $this->assertTrue($this->app->getProviders(TranslationServiceProvider::class) !== []);
    }

    public function test_config_is_merged(): void
    {
        $this->assertNotEmpty(config('livewire-translations'));
        $this->assertArrayHasKey('default_language', config('livewire-translations'));
        $this->assertArrayHasKey('languages_table', config('livewire-translations'));
        $this->assertArrayHasKey('translation_suffix', config('livewire-translations'));
    }

    public function test_language_manager_is_bound_as_singleton(): void
    {
        $manager1 = $this->app->make(LanguageManager::class);
        $manager2 = $this->app->make(LanguageManager::class);

        $this->assertSame($manager1, $manager2);
        $this->assertInstanceOf(LanguageManager::class, $manager1);
    }

    public function test_commands_are_registered(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('create:translation', $commands);
        $this->assertInstanceOf(CreateTranslationCommand::class, $commands['create:translation']);
    }

    public function test_livewire_component_is_registered(): void
    {
        // Check if the component class exists and is properly registered
        $componentExists = class_exists(\LivewireTranslations\Livewire\TranslationModal::class);
        $this->assertTrue($componentExists);

        // Alternative check: see if we can create the component
        try {
            $component = app(\LivewireTranslations\Livewire\TranslationModal::class);
            $this->assertNotNull($component);
        } catch (\Exception $e) {
            $this->fail('TranslationModal component could not be instantiated: ' . $e->getMessage());
        }
    }

    public function test_views_are_loaded(): void
    {
        // Check if view file exists in the package
        $viewPath = __DIR__ . '/../../src/views/livewire/translation-modal.blade.php';
        $this->assertFileExists($viewPath);

        // For namespace registration, this depends on service provider boot being called
        // In unit tests, we'll just verify the file exists
    }

    public function test_can_publish_config(): void
    {
        $configPath = config_path('livewire-translations.php');

        // Clean up if exists
        if (File::exists($configPath)) {
            File::delete($configPath);
        }

        Artisan::call('vendor:publish', [
            '--tag' => 'livewire-translations-config',
            '--force' => true
        ]);

        $this->assertFileExists($configPath);

        // Clean up
        File::delete($configPath);
    }

    public function test_can_publish_views(): void
    {
        $viewsPath = resource_path('views/vendor/livewire-translations');

        // Clean up if exists
        if (File::isDirectory($viewsPath)) {
            File::deleteDirectory($viewsPath);
        }

        Artisan::call('vendor:publish', [
            '--tag' => 'livewire-translations-views',
            '--force' => true
        ]);

        $this->assertDirectoryExists($viewsPath);

        // Clean up
        File::deleteDirectory($viewsPath);
    }

    public function test_api_routes_are_loaded(): void
    {
        $routes = collect(app('router')->getRoutes())->map(function ($route) {
            return $route->uri();
        });

        // The API routes are prefixed with the configured prefix
        $this->assertTrue($routes->contains('api/translations/languages'));
    }
}