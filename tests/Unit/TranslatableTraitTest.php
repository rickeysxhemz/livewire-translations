<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Unit;

use Illuminate\Support\Facades\App;
use LivewireTranslations\Models\Language;
use LivewireTranslations\Tests\Support\Models\Post;
use LivewireTranslations\Tests\Support\Models\PostTranslation;
use LivewireTranslations\Tests\TestCase;

class TranslatableTraitTest extends TestCase
{
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->post = Post::create([
            'title' => 'Original Title',
            'content' => 'Original Content',
            'description' => 'Original Description',
            'slug' => 'original-title',
            'is_published' => true,
        ]);

        Language::create([
            'language_code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Language::create([
            'language_code' => 'es',
            'name' => 'Spanish',
            'native_name' => 'Español',
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    public function test_translatable_attributes_are_defined(): void
    {
        $attributes = Post::getTranslatableAttributes();

        $this->assertEquals(['title', 'content', 'description'], $attributes);
    }

    public function test_translation_model_name_is_generated_correctly(): void
    {
        $modelName = $this->post->getTranslationModelName();

        $this->assertEquals('LivewireTranslations\\Tests\\Support\\Models\\PostTranslation', $modelName);
    }

    public function test_translations_relationship_exists(): void
    {
        $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Título en Español',
            'content' => 'Contenido en Español',
        ]);

        $this->assertCount(1, $this->post->translations);
        $this->assertInstanceOf(PostTranslation::class, $this->post->translations->first());
    }

    public function test_translation_method_returns_correct_translation(): void
    {
        $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Título en Español',
            'content' => 'Contenido en Español',
        ]);

        $translation = $this->post->translation('es');

        $this->assertNotNull($translation);
        $this->assertEquals('Título en Español', $translation->title);
        $this->assertEquals('es', $translation->language_code);
    }

    public function test_translation_method_returns_null_for_non_existent_language(): void
    {
        $translation = $this->post->translation('fr');

        $this->assertNull($translation);
    }

    public function test_translated_attribute_returns_translation_value(): void
    {
        $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Título en Español',
        ]);

        $title = $this->post->getTranslatedAttribute('title', 'es');

        $this->assertEquals('Título en Español', $title);
    }

    public function test_translated_attribute_returns_original_value_when_no_translation(): void
    {
        $title = $this->post->getTranslatedAttribute('title', 'fr');

        $this->assertEquals('Original Title', $title);
    }

    public function test_magic_getter_returns_translated_attribute(): void
    {
        App::setLocale('es');

        $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Título en Español',
        ]);

        $this->assertEquals('Título en Español', $this->post->title);
    }

    public function test_magic_getter_returns_original_attribute_when_no_translation(): void
    {
        App::setLocale('fr');

        $this->assertEquals('Original Title', $this->post->title);
    }

    public function test_save_translation_creates_new_translation(): void
    {
        $translation = $this->post->saveTranslation([
            'title' => 'Título en Español',
            'content' => 'Contenido en Español',
        ], 'es');

        $this->assertInstanceOf(PostTranslation::class, $translation);
        $this->assertEquals('es', $translation->language_code);
        $this->assertEquals('Título en Español', $translation->title);
        $this->assertDatabaseHas('post_translations', [
            'post_id' => $this->post->id,
            'language_code' => 'es',
            'title' => 'Título en Español',
        ]);
    }

    public function test_save_translation_updates_existing_translation(): void
    {
        $existingTranslation = $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Old Title',
            'content' => 'Old Content',
        ]);

        $updatedTranslation = $this->post->saveTranslation([
            'title' => 'New Title',
            'content' => 'New Content',
        ], 'es');

        $this->assertEquals($existingTranslation->id, $updatedTranslation->id);
        $this->assertEquals('New Title', $updatedTranslation->title);
        $this->assertEquals('New Content', $updatedTranslation->content);
    }

    public function test_delete_translation_removes_translation(): void
    {
        $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Título en Español',
        ]);

        $result = $this->post->deleteTranslation('es');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('post_translations', [
            'post_id' => $this->post->id,
            'language_code' => 'es',
        ]);
    }

    public function test_delete_translation_returns_false_for_non_existent_translation(): void
    {
        $result = $this->post->deleteTranslation('fr');

        $this->assertFalse($result);
    }

    public function test_get_available_languages_returns_translation_languages(): void
    {
        $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Título en Español',
        ]);

        $this->post->translations()->create([
            'language_code' => 'fr',
            'title' => 'Titre en Français',
        ]);

        $languages = $this->post->getAvailableLanguages();

        $this->assertCount(2, $languages);
        $this->assertContains('es', $languages->toArray());
        $this->assertContains('fr', $languages->toArray());
    }

    public function test_has_translation_returns_correct_boolean(): void
    {
        $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Título en Español',
        ]);

        $this->assertTrue($this->post->hasTranslation('es'));
        $this->assertFalse($this->post->hasTranslation('fr'));
    }

    public function test_with_translation_scope_filters_models(): void
    {
        $post2 = Post::create([
            'title' => 'Another Post',
            'content' => 'Another Content',
            'slug' => 'another-post',
        ]);

        $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Título en Español',
        ]);

        $postsWithSpanishTranslation = Post::withTranslation('es')->get();

        $this->assertCount(1, $postsWithSpanishTranslation);
        $this->assertEquals($this->post->id, $postsWithSpanishTranslation->first()->id);
    }

    public function test_get_translated_attributes_returns_all_translatable_attributes(): void
    {
        $this->post->translations()->create([
            'language_code' => 'es',
            'title' => 'Título en Español',
            'content' => 'Contenido en Español',
        ]);

        $attributes = $this->post->getTranslatedAttributes('es');

        $this->assertEquals([
            'title' => 'Título en Español',
            'content' => 'Contenido en Español',
            'description' => 'Original Description', // fallback to original
        ], $attributes);
    }
}