<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Support;

use Illuminate\Http\Request;
use Jessecruz\SimpleBlog\Models\Post;

/**
 * Counts reads of a public post in `views_count`, per `config('blog.views')`:
 * skips bots and, through the session, reloads of a post already counted.
 */
final class ViewCounter
{
    /** Session key with the ids of the posts already counted for the visitor. */
    public const SESSION_KEY = 'simple_blog.viewed_posts';

    /**
     * How many post ids the session remembers. Bounded because the session
     * may live in a cookie; reading older posts again counts them again.
     */
    private const REMEMBERED = 50;

    /**
     * User-Agent fragments of self-identified crawlers, link previews, AI
     * agents, uptime checkers and HTTP libraries. Browsers' UAs, in-app ones
     * included (Instagram, Facebook, LinkedIn), contain none of them.
     */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|scrap|fetch|archiver|preview|facebookexternalhit|meta-external|embedly|whatsapp|google-|mediapartners|lighthouse|headless|pingdom|uptime|monitor|chatgpt|claude-|anthropic|perplexity|cohere|curl|wget|python|java\/|okhttp|go-http|httpclient|guzzle|axios|libwww|ruby/i';

    /**
     * Counts the request as a view of the post unless counting is off, it
     * isn't a GET, it comes from a bot, or this session already counted it.
     */
    public static function record(Post $post, Request $request): bool
    {
        if (! config('blog.views.enabled', true) || ! $request->isMethod('GET')) {
            return false;
        }

        if (config('blog.views.ignore_bots', true) && self::isBot($request->userAgent())) {
            return false;
        }

        if (config('blog.views.once_per_session', true) && $request->hasSession()) {
            $session = $request->session();
            $viewed = (array) $session->get(self::SESSION_KEY, []);

            if (in_array($post->getKey(), $viewed, true)) {
                return false;
            }

            $session->put(self::SESSION_KEY, array_slice([...$viewed, $post->getKey()], -self::REMEMBERED));
        }

        // Through the base query: a read isn't an edit, so updated_at (the
        // sitemap's lastmod) stays put and no model events fire.
        $post->newQuery()->whereKey($post->getKey())->toBase()->increment('views_count');
        $post->views_count++;
        $post->syncOriginalAttribute('views_count');

        return true;
    }

    /** Whether a User-Agent (or its absence) belongs to a bot rather than a browser. */
    public static function isBot(?string $userAgent): bool
    {
        return $userAgent === null || trim($userAgent) === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1;
    }
}
