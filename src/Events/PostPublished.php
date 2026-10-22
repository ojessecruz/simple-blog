<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Jessecruz\SimpleBlog\Models\Post;

/**
 * A post went live. Dispatched once per post by `simple-blog:announce`, on
 * its first run after the post's publish date — for posts published right
 * away and for scheduled ones alike.
 */
final class PostPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Post $post) {}
}
