<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use LivewireTranslations\Traits\TranslatableTrait;

class TestModel extends Model
{
    use TranslatableTrait;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'description',
        'non_translatable'
    ];

    protected static array $translatable = [
        'name',
        'description'
    ];
}