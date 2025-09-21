<?php

declare(strict_types=1);

namespace LivewireTranslations;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use LivewireTranslations\Commands\CreateTranslationCommand;
use LivewireTranslations\Http\Controllers\LanguageController;
use LivewireTranslations\Livewire\TranslationModal;
use LivewireTranslations\Services\LanguageManager;

class TranslationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishConfiguration();
        $this->publishViews();
        $this->publishMigrations();
        $this->loadViews();
        $this->loadRoutes();
        $this->registerCommands();
        $this->registerLivewireComponents();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/livewire-translations.php', 'livewire-translations');
        $this->registerServices();
    }

    private function publishConfiguration(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/livewire-translations.php' => config_path('livewire-translations.php'),
            ], 'livewire-translations-config');
        }
    }

    private function publishViews(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/views' => resource_path('views/vendor/livewire-translations'),
            ], 'livewire-translations-views');
        }
    }

    private function publishMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/database/migrations' => database_path('migrations'),
            ], 'livewire-translations-migrations');
        }
    }

    private function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'livewire-translations');
    }

    private function loadRoutes(): void
    {
        if (file_exists(__DIR__.'/routes/api.php')) {
            $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateTranslationCommand::class,
            ]);
        }
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('translation-modal', TranslationModal::class);
    }

    private function registerServices(): void
    {
        $this->app->singleton(LanguageManager::class);
    }
}