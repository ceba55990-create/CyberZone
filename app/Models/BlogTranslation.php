<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogTranslation extends Model
{
    protected $fillable = [
        'blog_id',
        'locale',
        'title',
        'description',
        'text',
    ];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class, 'blog_id', 'blog_id');
    }
}
