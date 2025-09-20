<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    */
    'default_language' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Languages Table Configuration
    |--------------------------------------------------------------------------
    */
    'languages_table' => 'languages',

    /*
    |--------------------------------------------------------------------------
    | Translation Configuration
    |--------------------------------------------------------------------------
    */
    'translation_suffix' => '_translations',
    'translation_model_namespace' => 'App\\Models\\Translations',

    /*
    |--------------------------------------------------------------------------
    | Auto-Detection Settings
    |--------------------------------------------------------------------------
    */
    'auto_detect_translatable_fields' => true,
    'common_translatable_fields' => [
        'name',
        'title',
        'description',
        'content',
        'summary',
        'excerpt',
        'meta_title',
        'meta_description',
        'slug',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration (Tailwind CSS v4)
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'modal' => [
            'size' => 'max-w-4xl', // Tailwind max-width classes
            'backdrop' => true,
            'keyboard' => true,
        ],
        'colors' => [
            'primary' => 'blue',
            'success' => 'green',
            'danger' => 'red',
            'warning' => 'yellow',
            'info' => 'sky',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'prefix' => 'api/translations',
        'middleware' => ['api', 'auth'], // Default to authenticated routes
        'rate_limit' => '60,1', // Rate limiting: 60 requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
        'validate_language_codes' => true, // Validate language code format
        'max_translation_length' => 65535, // Maximum length for translation fields
        'allowed_file_extensions' => ['php'], // Allowed file extensions for generated files
    ],
];
