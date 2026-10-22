<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Converts dates between the app timezone (what the database stores) and the
 * blog timezone (`config('blog.timezone')`, what editors type and readers
 * see), so a UTC app can still schedule and show posts in local time.
 */
final class Dates
{
    /**
     * The blog timezone: `blog.timezone`, then `app.timezone`.
     */
    public static function timezone(): string
    {
        $configured = config('blog.timezone');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $app = config('app.timezone');

        return is_string($app) && $app !== '' ? $app : 'UTC';
    }

    /**
     * A copy of the date in the blog timezone and the current locale.
     */
    public static function local(CarbonInterface $date): CarbonInterface
    {
        return $date->copy()->setTimezone(self::timezone())->locale(app()->getLocale());
    }

    /**
     * Formats a date for display with translated month and day names.
     */
    public static function format(CarbonInterface $date, string $format): string
    {
        return self::local($date)->translatedFormat($format);
    }

    /**
     * Value for a datetime-local input, in the blog timezone.
     */
    public static function toInput(?CarbonInterface $date): ?string
    {
        return $date === null ? null : self::local($date)->format('Y-m-d\TH:i');
    }

    /**
     * Parses a datetime-local value typed in the blog timezone. The Post model
     * converts it to the app timezone when it is saved.
     */
    public static function fromInput(string $value): CarbonInterface
    {
        return Carbon::parse($value, self::timezone());
    }

    /**
     * The blog timezone with its current UTC offset, e.g.
     * "America/Sao_Paulo (UTC-03:00)".
     */
    public static function timezoneLabel(): string
    {
        $timezone = self::timezone();
        $offset = Carbon::now($timezone)->format('P');

        return $timezone === 'UTC' ? 'UTC' : $timezone.' (UTC'.$offset.')';
    }
}
