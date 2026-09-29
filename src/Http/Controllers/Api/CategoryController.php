<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Jessecruz\SimpleBlog\Http\Requests\Api\StoreCategoryRequest;
use Jessecruz\SimpleBlog\Models\PostCategory;

final class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'data' => PostCategory::orderBy('name')->get(['slug', 'name', 'description']),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = PostCategory::create($request->validated());

        return new JsonResponse([
            'data' => $category->only(['slug', 'name', 'description']),
        ], 201);
    }
}
