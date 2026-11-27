<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Blog extends Model
{
    public const CREATED_AT = 'dt_created';

    public const UPDATED_AT = 'dt_updated';

    protected $table = 'blog';

    protected $primaryKey = 'blog_id';

    // Base content is authoritative for Ukrainian and is the fallback for other languages.
    protected $fillable = [
        'title',
        'description',
        'text',
        'category_id',
        'user_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BlogTranslation::class, 'blog_id', 'blog_id');
    }

    public function localizedContent(string $locale): array
    {
        $content = $this->only(['title', 'description', 'text']);

        if ($locale === 'uk') {
            return $content;
        }

        $translation = $this->translations->firstWhere('locale', $locale);

        if ($translation !== null) {
            foreach (array_keys($content) as $field) {
                $value = $translation->getAttribute($field);

                if (is_string($value) && trim($value) !== '') {
                    $content[$field] = $value;
                }
            }
        }

        return $content;
    }
}
