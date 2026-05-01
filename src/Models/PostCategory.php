<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
