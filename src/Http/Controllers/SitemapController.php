<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;

/**
 * XML sitemap of the blog: the index, every category with published posts
 * and every published post.
 */
final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()
            ->select(['id', 'slug', 'blog_category_id', 'published_at', 'updated_at'])
            ->latest('published_at')
            ->latest('id')
            ->get();

        // Empty categories would be thin pages, so only those with published posts.
        $categories = PostCategory::whereIn('id', $posts->pluck('blog_category_id')->unique())
            ->orderBy('name')
            ->get();

        return response()->view('blog::sitemap', [
            'posts' => $posts,
            'categories' => $categories,
        ], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
