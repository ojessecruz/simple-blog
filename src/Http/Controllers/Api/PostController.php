<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Controllers\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Jessecruz\SimpleBlog\Http\Requests\Api\PostRequest;
use Jessecruz\SimpleBlog\Http\Resources\PostResource;
use Jessecruz\SimpleBlog\Models\Post;

final class PostController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PostResource::collection(
            Post::with('category')->latest()->paginate(config('blog.api.per_page', 50))
        );
    }

    public function show(Post $post): PostResource
    {
        return new PostResource($post->load('category'));
    }

    public function store(PostRequest $request): PostResource
    {
        $post = Post::create($request->postAttributes());

        return new PostResource($post->load('category'));
    }

    public function update(PostRequest $request, Post $post): PostResource
    {
        $post->update($request->postAttributes());

        return new PostResource($post->load('category'));
    }
}
