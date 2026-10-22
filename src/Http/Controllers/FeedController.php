<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Jessecruz\SimpleBlog\Models\Post;

/**
 * RSS 2.0 feed of the latest published posts.
 */
final class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()
            ->with('category', 'author')
            ->latest('published_at')
            ->latest('id')
            ->limit(max(1, (int) config('blog.feed.limit', 20)))
            ->get();

        return response()->view('blog::feed', [
            'posts' => $posts,
            'fullContent' => (bool) config('blog.feed.full_content', false),
        ], 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
