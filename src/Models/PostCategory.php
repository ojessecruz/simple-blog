<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read int|null $posts_count
 */
final class PostCategory extends Model
{
    protected $table = 'blog_categories';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'blog_category_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
