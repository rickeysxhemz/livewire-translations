<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LivewireTranslations\Models\Language;
use LivewireTranslations\Tests\TestCase;

class LanguageModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }
    public function test_language_can_be_created(): void
    {
        $language = Language::create([
            'language_code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertInstanceOf(Language::class, $language);
        $this->assertEquals('en', $language->language_code);
        $this->assertEquals('English', $language->name);
        $this->assertTrue($language->is_active);
    }

    public function test_active_scope_filters_active_languages(): void
    {
        Language::create([
            'language_code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Language::create([
            'language_code' => 'es',
            'name' => 'Spanish',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        Language::create([
            'language_code' => 'fr',
            'name' => 'French',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $activeLanguages = Language::active()->get();

        $this->assertCount(2, $activeLanguages);
        $this->assertEquals('en', $activeLanguages->first()->language_code);
        $this->assertEquals('fr', $activeLanguages->last()->language_code);
    }

    public function test_ordered_scope_orders_by_sort_order(): void
    {
        Language::create([
            'language_code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        Language::create([
            'language_code' => 'es',
            'name' => 'Spanish',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Language::create([
            'language_code' => 'fr',
            'name' => 'French',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $orderedLanguages = Language::ordered()->get();

        $this->assertEquals('es', $orderedLanguages->get(0)->language_code);
        $this->assertEquals('fr', $orderedLanguages->get(1)->language_code);
        $this->assertEquals('en', $orderedLanguages->get(2)->language_code);
    }

    public function test_language_code_is_fillable(): void
    {
        $language = new Language();

        $this->assertContains('language_code', $language->getFillable());
        $this->assertContains('name', $language->getFillable());
        $this->assertContains('native_name', $language->getFillable());
        $this->assertContains('is_active', $language->getFillable());
        $this->assertContains('sort_order', $language->getFillable());
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $language = Language::create([
            'language_code' => 'en',
            'name' => 'English',
            'is_active' => '1',
            'sort_order' => 1,
        ]);

        $this->assertIsBool($language->is_active);
        $this->assertTrue($language->is_active);
    }
}