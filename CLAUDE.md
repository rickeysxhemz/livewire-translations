# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel/Livewire package for managing database translations. It provides a conventional translation system using Eloquent models and Livewire components for managing multilingual content.

## Development Commands

### Testing
```bash
# Run tests with PHPUnit
vendor/bin/phpunit

# Run tests with Pest (preferred)
vendor/bin/pest
```

### Code Quality
```bash
# Install dependencies
composer install

# Update dependencies
composer update
```

## Architecture

### Core Components

**TranslatableTrait** (`src/Traits/TranslatableTrait.php`)
- Applied to models that need translation support
- Provides methods for managing translations: `translation()`, `getTranslatedAttribute()`, `saveTranslation()`
- Automatically generates translation model relationships

**LanguageManager Service** (`src/Services/LanguageManager.php`)
- Manages language table creation and seeding
- Handles CRUD operations for languages
- Creates migrations dynamically when needed

**CreateTranslationCommand** (`src/Commands/CreateTranslationCommand.php`)
- Artisan command: `php artisan create:translation {model}`
- Analyzes existing model migrations to detect translatable fields
- Generates translation models and migrations automatically
- Interactive field selection with common translatable field suggestions

**TranslationModal Livewire Component** (`src/Livewire/TranslationModal.php`)
- Provides UI for managing translations
- Registered as 'translation-modal' component

### Configuration

All configuration in `src/config/livewire-translations.php`:
- Default language settings
- Translation table naming conventions
- UI configuration (Tailwind CSS v4)
- API endpoint configuration
- Auto-detection of translatable fields

### Translation Model Pattern

Translation models follow the pattern:
- Namespace: `App\Models\Translations\{ModelName}Translation`
- Table naming: `{model_name}_translations`
- Foreign key: `{model_name}_id`

### Service Provider

`TranslationServiceProvider` handles:
- Configuration merging and publishing
- View publishing and loading
- Route registration (API routes)
- Command registration
- Livewire component registration
- Service binding

## Key Development Patterns

1. **Translation Models**: Auto-generated with `create:translation` command, stored in `App\Models\Translations\`
2. **Translatable Fields**: Configurable via model `$translatable` property or auto-detected from common field names
3. **Language Management**: Centralized through `LanguageManager` service
4. **UI Integration**: Uses Livewire components with Tailwind CSS v4 styling

## Security & Performance Features

### Security Enhancements
- **API Authentication**: All API routes require authentication by default
- **Authorization Gates**: Management operations require `manage-translations` permission
- **Input Validation**: Language codes validated with regex patterns (`/^[a-z]{2}(-[A-Z]{2})?$/`)
- **Mass Assignment Protection**: Translation data filtered against model fillable fields
- **SQL Injection Prevention**: Table names and user inputs validated with strict patterns
- **Path Traversal Protection**: Model names sanitized before file operations
- **Error Logging**: Detailed error logging without exposing sensitive information to users
- **Rate Limiting**: API endpoints configured with rate limiting (60 requests/minute)

### Performance Optimizations
- **O(1) Attribute Lookup**: Translatable attributes use array flip for constant-time lookups
- **N+1 Query Prevention**: Translation loading uses eager loading with `whereIn` queries
- **Database Indexing**: Additional composite indexes on language_code and timestamp fields
- **Query Optimization**: Scoped queries for active/ordered language filtering

### Configuration Security
```php
'api' => [
    'middleware' => ['api', 'auth'], // Authentication required
    'rate_limit' => '60,1', // Rate limiting
],
'security' => [
    'validate_language_codes' => true,
    'max_translation_length' => 65535,
    'allowed_file_extensions' => ['php'],
],
```