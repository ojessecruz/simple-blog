<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetBlogLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = config('blog.locale');

        if (is_string($locale) && $locale !== '') {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
