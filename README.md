# Livewire Translations

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sxhemz/livewire-translations.svg?style=flat-square)](https://packagist.org/packages/sxhemz/livewire-translations)
[![Total Downloads](https://img.shields.io/packagist/dt/sxhemz/livewire-translations.svg?style=flat-square)](https://packagist.org/packages/sxhemz/livewire-translations)
[![License](https://img.shields.io/badge/license-GPL--3.0--or--later-blue.svg)](LICENSE)

A flexible, secure, and high-performance Laravel package for managing database translations with Livewire integration. This package provides a conventional approach to handle multilingual content using Eloquent models and includes a beautiful Livewire modal component for translation management.

## ✨ Features

- 🚀 **Easy Integration**: Add translations to any Eloquent model with a simple trait
- 🔒 **Security First**: Built-in protection against mass assignment, SQL injection, and unauthorized access
- ⚡ **High Performance**: Optimized queries with eager loading and O(1) attribute lookups
- 🎨 **Beautiful UI**: Livewire modal component with Tailwind CSS v4 styling
- 🛠️ **Artisan Commands**: Generate translation models and migrations automatically
- 🌍 **Language Management**: Complete API for managing languages with proper authorization
- 📱 **Responsive Design**: Mobile-friendly translation interface
- 🧪 **Well Tested**: Comprehensive test suite ensuring reliability

## 📋 Requirements

- PHP 8.4+
- Laravel 12.28+
- Livewire 3.6+

## 📦 Installation

Install the package via Composer:

```bash
composer require sxhemz/livewire-translations
```

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="LivewireTranslations\TranslationServiceProvider" --tag="config"
```

### Publish Views (Optional)

If you want to customize the Livewire modal component:

```bash
php artisan vendor:publish --provider="LivewireTranslations\TranslationServiceProvider" --tag="views"
```

## ⚙️ Configuration

The configuration file (`config/livewire-translations.php`) allows you to customize:

```php
return [
    // Default language for your application
    'default_language' => env('APP_LOCALE', 'en'),

    // Languages table name
    'languages_table' => 'languages',

    // Translation model configuration
    'translation_suffix' => '_translations',
    'translation_model_namespace' => 'App\\Models\\Translations',

    // Auto-detection of translatable fields
    'auto_detect_translatable_fields' => true,
    'common_translatable_fields' => [
        'name', 'title', 'description', 'content', 'summary',
        'excerpt', 'meta_title', 'meta_description', 'slug',
    ],

    // API security settings
    'api' => [
        'prefix' => 'api/translations',
        'middleware' => ['api', 'auth'], // Requires authentication
        'rate_limit' => '60,1', // 60 requests per minute
    ],

    // Security configuration
    'security' => [
        'validate_language_codes' => true,
        'max_translation_length' => 65535,
        'allowed_file_extensions' => ['php'],
    ],
];
```

## 🚀 Quick Start

### 1. Create a Translatable Model

Let's say you have a `Post` model that you want to make translatable:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LivewireTranslations\Traits\TranslatableTrait;

class Post extends Model
{
    use TranslatableTrait;

    protected $fillable = [
        'title',
        'content',
        'slug',
        'description',
        'is_published',
    ];

    // Define which fields should be translatable
    protected static array $translatable = [
        'title',
        'content',
        'description',
    ];
}
```

### 2. Generate Translation Files

Use the Artisan command to generate translation model and migration:

```bash
php artisan create:translation Post
```

This command will:
- Analyze your `posts` table structure
- Show available fields for translation
- Generate `PostTranslation` model in `App\Models\Translations\`
- Create a migration for the `post_translations` table
- Set up proper relationships and indexes

### 3. Run Migration

```bash
php artisan migrate
```

### 4. Add Authorization (Important!)

Define the `manage-translations` gate in your `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

public function boot()
{
    Gate::define('manage-translations', function ($user) {
        return $user->hasRole('admin') || $user->hasPermission('manage-translations');
    });
}
```

## 📚 Usage Guide

### Basic Translation Operations

```php
$post = Post::find(1);

// Save a translation
$post->saveTranslation([
    'title' => 'Título en Español',
    'content' => 'Contenido en español...',
    'description' => 'Descripción en español',
], 'es');

// Get a specific translation
$spanishTranslation = $post->translation('es');

// Get translated attribute for current locale
app()->setLocale('es');
echo $post->title; // Returns "Título en Español"

// Get translated attribute for specific language
$spanishTitle = $post->getTranslatedAttribute('title', 'es');

// Check if translation exists
if ($post->hasTranslation('es')) {
    echo "Spanish translation available";
}

// Get all translated attributes for a language
$allSpanishAttributes = $post->getTranslatedAttributes('es');

// Delete a translation
$post->deleteTranslation('es');

// Get available languages for this model
$languages = $post->getAvailableLanguages(); // Collection of language codes
```

### Query Scopes

```php
// Get posts that have Spanish translations
$postsWithSpanish = Post::withTranslation('es')->get();

// Get posts with their translations (eager loading)
$posts = Post::with('translations')->get();
```

### Language Management

```php
use LivewireTranslations\Services\LanguageManager;

$languageManager = app(LanguageManager::class);

// Get all active languages
$activeLanguages = $languageManager->getActiveLanguages();

// Get all languages (active and inactive)
$allLanguages = $languageManager->getAllLanguages();

// Create or update a language
$language = $languageManager->saveLanguage([
    'language_code' => 'de',
    'name' => 'German',
    'native_name' => 'Deutsch',
    'is_active' => true,
    'sort_order' => 1,
]);

// Toggle language status
$languageManager->toggleLanguage('de');

// Delete a language
$languageManager->deleteLanguage('de');
```

## 🎨 Livewire Component Usage

### In Your Blade Template

```blade
<div>
    <!-- Your model content -->
    <h1>{{ $post->title }}</h1>
    <p>{{ $post->description }}</p>

    <!-- Translation management button -->
    <button
        wire:click="$dispatch('openTranslationModal')"
        class="bg-blue-500 text-white px-4 py-2 rounded"
    >
        Manage Translations
    </button>

    <!-- Include the translation modal -->
    <livewire:translation-modal :model="$post" />
</div>
```

### In Your Livewire Component

```php
<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;

class PostManager extends Component
{
    public Post $post;

    public function mount(Post $post)
    {
        $this->post = $post;
    }

    public function render()
    {
        return view('livewire.post-manager');
    }
}
```

### Modal Component Features

The translation modal provides:
- **Multi-tab Interface**: Switch between languages easily
- **Form Validation**: Client-side and server-side validation
- **Auto-save**: Translations are saved automatically
- **Status Indicators**: Visual feedback for translated/untranslated content
- **Responsive Design**: Works on all screen sizes

## 🔒 Security Features

### API Protection

All API endpoints require authentication and proper authorization:

```bash
# These routes require authentication
GET /api/translations/languages
POST /api/translations/languages    # Requires 'manage-translations' permission
PUT /api/translations/languages/{code}    # Requires 'manage-translations' permission
DELETE /api/translations/languages/{code} # Requires 'manage-translations' permission
```

### Input Validation

- **Language Codes**: Validated against ISO format (`en`, `es-ES`, etc.)
- **Mass Assignment**: Only fillable fields are accepted
- **SQL Injection**: All inputs are sanitized and validated
- **Path Traversal**: File operations are restricted to safe paths

### Rate Limiting

API endpoints are rate-limited to 60 requests per minute by default.

## ⚡ Performance Optimizations

### Database Optimization

- **Optimized Indexes**: Composite indexes for common query patterns
- **Eager Loading**: N+1 query prevention
- **Efficient Queries**: Scoped queries for better performance

### Code Optimization

- **O(1) Lookups**: Attribute access uses hash maps for constant-time lookups
- **Memory Efficient**: Minimal memory footprint with lazy loading
- **Caching Ready**: Compatible with Laravel's caching systems

## 🧪 Testing

Run the package tests:

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suites
vendor/bin/phpunit tests/Unit/
vendor/bin/phpunit tests/Feature/
```

### Testing Your Implementation

```php
use App\Models\Post;
use LivewireTranslations\Models\Language;

class PostTranslationTest extends TestCase
{
    public function test_post_can_be_translated()
    {
        $post = Post::factory()->create([
            'title' => 'English Title',
            'content' => 'English content...',
        ]);

        $post->saveTranslation([
            'title' => 'Título en Español',
            'content' => 'Contenido en español...',
        ], 'es');

        $this->assertEquals('Título en Español', $post->getTranslatedAttribute('title', 'es'));
        $this->assertTrue($post->hasTranslation('es'));
    }
}
```

## 🎯 Advanced Usage

### Custom Translation Models

You can create custom translation models with additional fields:

```php
<?php

namespace App\Models\Translations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTranslation extends Model
{
    protected $fillable = [
        'title',
        'content',
        'description',
        'language_code',
        'meta_keywords', // Additional field
        'seo_title',     // Additional field
    ];

    protected $casts = [
        'meta_keywords' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Post::class);
    }
}
```

### Custom Validation Rules

Override validation in your Livewire component:

```php
protected function rules()
{
    return [
        'translations.*.title' => ['required', 'string', 'max:255'],
        'translations.*.content' => ['required', 'string', 'min:10'],
        'translations.*.description' => ['nullable', 'string', 'max:500'],
    ];
}
```

### Event Handling

Listen for translation events:

```php
// In your Livewire component
protected $listeners = [
    'translation-saved' => 'handleTranslationSaved',
    'translation-deleted' => 'handleTranslationDeleted',
];

public function handleTranslationSaved($data)
{
    $this->emit('notification', [
        'message' => "Translation saved for {$data['language']}",
        'type' => 'success'
    ]);
}
```

## 🔧 Customization

### Styling the Modal

The modal uses Tailwind CSS v4. You can customize the appearance by:

1. Publishing the views
2. Modifying the CSS classes in the blade template
3. Adding custom CSS for specific styling needs

### Extending the LanguageManager

```php
<?php

namespace App\Services;

use LivewireTranslations\Services\LanguageManager as BaseLanguageManager;

class CustomLanguageManager extends BaseLanguageManager
{
    public function getLanguagesWithStats()
    {
        return $this->getAllLanguages()->map(function ($language) {
            $language->translation_count = $this->getTranslationCount($language->language_code);
            return $language;
        });
    }

    private function getTranslationCount(string $languageCode): int
    {
        // Your custom logic to count translations
        return 0;
    }
}
```

Then bind it in your service provider:

```php
$this->app->singleton(LanguageManager::class, CustomLanguageManager::class);
```

## 🐛 Troubleshooting

### Common Issues

**1. Translation modal not showing**
- Ensure Livewire is properly installed and configured
- Check that the modal component is included in your view
- Verify JavaScript is loaded correctly

**2. Permission denied errors**
- Make sure the `manage-translations` gate is defined
- Check user permissions in your authorization logic
- Verify API middleware configuration

**3. Migration errors**
- Ensure the base model table exists before generating translations
- Check for naming conflicts with existing tables
- Verify database permissions

**4. Performance issues**
- Use eager loading for translations: `Model::with('translations')`
- Consider caching frequently accessed translations
- Monitor database query performance

### Debug Mode

Enable debug logging in your `.env`:

```env
LOG_LEVEL=debug
```

The package will log detailed information about translation operations.

## 📖 API Reference

### TranslatableTrait Methods

| Method | Description | Parameters | Return |
|--------|-------------|------------|---------|
| `getTranslationModelName()` | Get the translation model class name | - | `string` |
| `translations()` | Get translations relationship | - | `HasMany` |
| `translation($code)` | Get specific translation | `string $languageCode = null` | `?object` |
| `getTranslatedAttribute($attr, $code)` | Get translated attribute | `string $attribute, string $languageCode = null` | `?string` |
| `saveTranslation($data, $code)` | Save translation | `array $data, string $languageCode` | `object` |
| `deleteTranslation($code)` | Delete translation | `string $languageCode` | `bool` |
| `hasTranslation($code)` | Check if translation exists | `string $languageCode` | `bool` |
| `getAvailableLanguages()` | Get available language codes | - | `Collection` |
| `getTranslatedAttributes($code)` | Get all translated attributes | `string $languageCode = null` | `array` |

### LanguageManager Methods

| Method | Description | Parameters | Return |
|--------|-------------|------------|---------|
| `getActiveLanguages()` | Get active languages | - | `Collection` |
| `getAllLanguages()` | Get all languages | - | `Collection` |
| `saveLanguage($data)` | Create/update language | `array $data` | `Language` |
| `deleteLanguage($code)` | Delete language | `string $languageCode` | `bool` |
| `toggleLanguage($code)` | Toggle language status | `string $languageCode` | `bool` |
| `createLanguagesTable()` | Create languages table | - | `void` |

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass (`vendor/bin/phpunit`)
6. Commit your changes (`git commit -am 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

### Development Setup

```bash
git clone https://github.com/sxhemz/livewire-translations.git
cd livewire-translations
composer install
vendor/bin/phpunit
```

## 📄 License

This package is open-sourced software licensed under the [GPL-3.0-or-later](LICENSE) license.

## 🙏 Credits

- **Author**: [Waqas Majeed](mailto:waqasmajeed.25@gmail.com)
- **Contributors**: [All Contributors](../../contributors)

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/sxhemz/livewire-translations/issues)
- **Discussions**: [GitHub Discussions](https://github.com/sxhemz/livewire-translations/discussions)
- **Documentation**: [Wiki](https://github.com/sxhemz/livewire-translations/wiki)

## 📋 Version Information

### Current Status
- **Latest Stable**: v1.0.0
- **Development Branch**: main
- **Minimum PHP**: 8.4+
- **Laravel Compatibility**: 12.28+

### Release Notes
See [CHANGELOG.md](CHANGELOG.md) for detailed release notes and breaking changes.

### Semantic Versioning
This package follows [Semantic Versioning](https://semver.org/). For production applications, we recommend:
- Use exact versions for critical applications: `"sxhemz/livewire-translations": "1.0.0"`
- Use caret constraints for regular use: `"sxhemz/livewire-translations": "^1.0"`
- Never use development versions in production: `dev-main`

## 🔮 Roadmap

- [ ] Integration with Laravel Filament
- [ ] REST API endpoints for frontend frameworks
- [ ] Import/Export functionality for translations
- [ ] Translation validation and approval workflow
- [ ] Integration with translation services (Google Translate, DeepL)
- [ ] Performance dashboard and analytics
- [ ] Multi-tenant support

---

Made with ❤️ for the Laravel community