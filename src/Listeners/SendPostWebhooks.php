<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Listeners;

use Jessecruz\SimpleBlog\Events\PostPublished;
use Jessecruz\SimpleBlog\Http\Resources\PostResource;
use Jessecruz\SimpleBlog\Jobs\SendWebhook;

/**
 * Queues one webhook per URL in `blog.publishing.webhooks`.
 */
final class SendPostWebhooks
{
    public function handle(PostPublished $event): void
    {
        $urls = array_filter(
            (array) config('blog.publishing.webhooks', []),
            fn (mixed $url): bool => is_string($url) && $url !== '',
        );

        if ($urls === []) {
            return;
        }

        // Encoded once, so the signature covers the exact bytes every URL gets.
        $payload = json_encode([
            'event' => 'post.published',
            'post' => (new PostResource($event->post))->resolve(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        foreach ($urls as $url) {
            SendWebhook::dispatch($url, 'post.published', $payload);
        }
    }
}
