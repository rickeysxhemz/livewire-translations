<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Support\Models;

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

    protected static array $translatable = [
        'title',
        'content',
        'description',
    ];
}