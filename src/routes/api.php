<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LivewireTranslations\Http\Controllers\LanguageController;

Route::middleware(config('livewire-translations.api.middleware', ['api', 'auth']))
    ->prefix(config('livewire-translations.api.prefix', 'api/translations'))
    ->group(function () {
        Route::get('/languages', [LanguageController::class, 'index']);

        // Protected routes requiring authentication
        Route::middleware(['can:manage-translations'])->group(function () {
            Route::post('/languages', [LanguageController::class, 'store']);
            Route::put('/languages/{languageCode}', [LanguageController::class, 'update']);
            Route::delete('/languages/{languageCode}', [LanguageController::class, 'destroy']);
            Route::patch('/languages/{languageCode}/toggle', [LanguageController::class, 'toggle']);
        });
    });