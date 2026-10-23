<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\ViewCounter;

final class BlogController extends Controller
{
    public function index(): View
    {
        $posts = $this->paginate(Post::published());

        $categories = PostCategory::orderBy('name')->get();

        return view('blog::index', compact('posts', 'categories'));
    }

    public function show(Post $post, Request $request): View
    {
        if (! $post->isPublished()) {
            abort(404);
        }

        ViewCounter::record($post, $request);

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
        $posts = $this->paginate(Post::published()->where('blog_category_id', $category->id));

        $categories = PostCategory::orderBy('name')->get();
        $currentCategory = $category;

        return view('blog::index', compact('posts', 'categories', 'currentCategory'));
    }

    public function preview(Post $post): Response
    {
        $relatedPosts = Post::query()
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->with('category')
            ->latest('published_at')
            ->limit(3)
            ->get();

        $post->load('category', 'author');

        // Header rather than a meta tag alone, so it holds with any host layout.
        return response()
            ->view('blog::show', compact('post', 'relatedPosts'))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * @param  Builder<Post>  $query
     * @return LengthAwarePaginator<int, Post>
     */
    private function paginate(Builder $query): LengthAwarePaginator
    {
        $posts = $query
            ->with('category', 'author')
            ->latest('published_at')
            ->latest('id')
            ->paginate(max(1, (int) config('blog.per_page', 12)));

        // Pages past the end would render an empty listing search engines can index.
        abort_if($posts->isEmpty() && $posts->currentPage() > 1, 404);

        return $posts;
    }
}
