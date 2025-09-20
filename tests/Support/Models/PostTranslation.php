<?php

declare(strict_types=1);

namespace LivewireTranslations\Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTranslation extends Model
{
    protected $fillable = [
        'title',
        'content',
        'description',
        'language_code',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}