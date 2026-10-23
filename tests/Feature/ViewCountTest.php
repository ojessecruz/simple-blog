<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Carbon;
use Jessecruz\SimpleBlog\Models\Post;
use Jessecruz\SimpleBlog\Models\PostCategory;
use Jessecruz\SimpleBlog\Support\Seo;
use Jessecruz\SimpleBlog\Support\ViewCounter;

beforeEach(function () {
    $this->category = PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
});

afterEach(fn () => Carbon::setTestNow());

function countedPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'slug' => 'p-'.uniqid(),
        'title' => 'Post',
        'excerpt' => 'r',
        'body' => 'c',
        'blog_category_id' => PostCategory::first()->id,
        'published_at' => now()->subDay(),
    ], $attributes));
}

it('counts a view without changing the post\'s last-modified date', function () {
    Carbon::setTestNow('2026-01-10 09:00:00');
    $post = countedPost();
    Carbon::setTestNow('2026-03-01 18:30:00');

    $this->get(route('blog.show', $post))->assertOk();

    $post = $post->fresh();
    expect($post->views_count)->toBe(1)
        ->and($post->updated_at->toDateTimeString())->toBe('2026-01-10 09:00:00')
        ->and(Seo::lastModified($post)->toDateTimeString())->toBe('2026-01-10 09:00:00');

    $this->get(route('blog.sitemap'))->assertSee('2026-01-10T09:00:00', false);
});

it('fires no model events when counting a view', function () {
    $post = countedPost();
    $fired = [];
    Post::updating(function () use (&$fired) {
        $fired[] = 'updating';
    });
    Post::updated(function () use (&$fired) {
        $fired[] = 'updated';
    });

    $this->get(route('blog.show', $post))->assertOk();

    expect($fired)->toBe([])->and($post->fresh()->views_count)->toBe(1);
});

it('counts each post once per visitor session', function () {
    $post = countedPost();
    $other = countedPost();

    $this->get(route('blog.show', $post))->assertOk();
    $this->get(route('blog.show', $post))->assertOk();
    $this->get(route('blog.show', $other))->assertOk();

    expect($post->fresh()->views_count)->toBe(1)
        ->and($other->fresh()->views_count)->toBe(1);

    $this->flushSession();
    $this->get(route('blog.show', $post))->assertOk();

    expect($post->fresh()->views_count)->toBe(2);
});

it('remembers only the latest posts in the session', function () {
    $post = countedPost();
    $this->get(route('blog.show', $post));

    foreach (range(1, 50) as $n) {
        $this->get(route('blog.show', countedPost()));
    }

    expect(session(ViewCounter::SESSION_KEY))->toHaveCount(50);

    $this->get(route('blog.show', $post));

    expect($post->fresh()->views_count)->toBe(2);
});

it('counts every reload when once_per_session is off', function () {
    config()->set('blog.views.once_per_session', false);
    $post = countedPost();

    $this->get(route('blog.show', $post));
    $this->get(route('blog.show', $post));

    expect($post->fresh()->views_count)->toBe(2);
});

it('ignores bots, link previews and scripts', function (string $userAgent) {
    $post = countedPost();

    $this->withHeader('User-Agent', $userAgent)->get(route('blog.show', $post))->assertOk();

    expect($post->fresh()->views_count)->toBe(0);
})->with([
    'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Googlebot smartphone' => 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.126 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Bing' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
    'Google inspection tool' => 'Mozilla/5.0 (compatible; Google-InspectionTool/1.0;)',
    'Facebook preview' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'WhatsApp preview' => 'WhatsApp/2.23.20.0 A',
    'Slack preview' => 'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
    'Skype preview' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) SkypeUriPreview Preview/0.5',
    'GPTBot' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)',
    'ChatGPT user' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot',
    'Lighthouse' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Chrome-Lighthouse',
    'headless Chrome' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/126.0.0.0 Safari/537.36',
    'UptimeRobot' => 'Mozilla/5.0+(compatible; UptimeRobot/2.0; http://www.uptimerobot.com/)',
    'curl' => 'curl/8.7.1',
    'Guzzle' => 'GuzzleHttp/7',
    'Python' => 'python-requests/2.32.3',
    'empty' => '',
]);

it('counts browsers, in-app ones included', function (string $userAgent) {
    $post = countedPost();

    $this->withHeader('User-Agent', $userAgent)->get(route('blog.show', $post))->assertOk();

    expect($post->fresh()->views_count)->toBe(1);
})->with([
    'Chrome desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
    'Chrome Android' => 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36',
    'Safari iPhone' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
    'Firefox' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.7; rv:131.0) Gecko/20100101 Firefox/131.0',
    'Edge' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0',
    'Samsung Internet' => 'Mozilla/5.0 (Linux; Android 14; SAMSUNG SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/26.0 Chrome/122.0.0.0 Mobile Safari/537.36',
    'Instagram in-app' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 340.0.0.22.109 (iPhone15,3; iOS 17_5; pt_BR; pt; scale=3.00; 1290x2796; 624245385)',
    'Facebook in-app' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [FBAN/FBIOS;FBAV/470.0.0.40.97;FBBV/621025123;FBDV/iPhone15,3;FBMD/iPhone;FBSN/iOS;FBSV/17.5;FBSS/3;FBID/phone;FBLC/pt_BR;FBOP/5]',
    'LinkedIn in-app' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [LinkedInApp]/9.30.1220',
]);

it('counts bots when ignore_bots is off', function () {
    config()->set('blog.views.ignore_bots', false);
    $post = countedPost();

    $this->withHeader('User-Agent', 'Googlebot/2.1')->get(route('blog.show', $post))->assertOk();

    expect($post->fresh()->views_count)->toBe(1);
});

it('counts nothing when view counting is disabled', function () {
    config()->set('blog.views.enabled', false);
    $post = countedPost(['views_count' => 7]);

    $this->get(route('blog.show', $post))->assertOk();

    expect($post->fresh()->views_count)->toBe(7);
});

it('does not count HEAD requests', function () {
    $post = countedPost();

    $this->call('HEAD', route('blog.show', $post))->assertOk();

    expect($post->fresh()->views_count)->toBe(0);
});

it('counts every request when the public middleware has no session', function () {
    $this->bootConfig = ['public_middleware' => [SubstituteBindings::class]];
    $this->refreshApplication();
    PostCategory::create(['slug' => 'g', 'name' => 'Gestão']);
    $post = countedPost();

    $this->get(route('blog.show', $post))->assertOk();
    $this->get(route('blog.show', $post))->assertOk();

    expect($post->fresh()->views_count)->toBe(2);
});
