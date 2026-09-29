<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default guard for the blog API: requires `Authorization: Bearer {blog.api.token}`.
 * Fails closed — every request is rejected while no token is configured.
 */
final class ValidateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('blog.api.token');

        if (! is_string($token) || $token === '' || ! hash_equals($token, (string) $request->bearerToken())) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
