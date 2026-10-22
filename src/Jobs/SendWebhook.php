<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

/**
 * POSTs a JSON payload to one webhook URL, signed with
 * `blog.publishing.webhook_secret` when one is set.
 */
final class SendWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(
        public readonly string $url,
        public readonly string $event,
        public readonly string $payload,
    ) {}

    public function handle(): void
    {
        $headers = ['X-Simple-Blog-Event' => $this->event];

        // Read at send time so the secret never sits in the queue payload.
        $secret = config('blog.publishing.webhook_secret');

        if (is_string($secret) && $secret !== '') {
            $headers['X-Simple-Blog-Signature'] = 'sha256='.hash_hmac('sha256', $this->payload, $secret);
        }

        Http::timeout(10)
            ->withHeaders($headers)
            ->withBody($this->payload, 'application/json')
            ->post($this->url)
            ->throw();
    }
}
