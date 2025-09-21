<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

class TestModelTranslation extends Model
{
    protected $table = 'test_model_translations';

    protected $fillable = [
        'test_model_id',
        'language_code',
        'name',
        'description'
    ];

    public function testModel()
    {
        return $this->belongsTo(TestModel::class);
    }
}