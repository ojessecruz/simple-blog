<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;

final class BlogController extends Controller
{
    public function index(): View
    {
        $posts = Post::published()
            ->with('category')
            ->latest('published_at')
            ->get();

        $categories = PostCategory::orderBy('name')->get();

        return view('blog::index', compact('posts', 'categories'));
    }

    public function show(Post $post): View
    {
        if (! $post->isPublished()) {
            abort(404);
        }

        $post->increment('views_count');

        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->with('category')
            ->latest('published_at')
            ->limit(3)
            ->get();

        $post->load('category', 'author');

        return view('blog::show', compact('post', 'relatedPosts'));
    }

    public function category(PostCategory $category): View
    {
        $posts = Post::published()
            ->where('blog_category_id', $category->id)
            ->with('category')
            ->latest('published_at')
            ->get();

        $categories = PostCategory::orderBy('name')->get();
        $currentCategory = $category;

        return view('blog::index', compact('posts', 'categories', 'currentCategory'));
    }

    public function preview(Post $post): View
    {
        $relatedPosts = Post::query()
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->with('category')
            ->latest('published_at')
            ->limit(3)
            ->get();

        $post->load('category', 'author');

        return view('blog::show', compact('post', 'relatedPosts'));
    }
}
