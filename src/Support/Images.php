<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jessecruz\SimpleBlog\Models\Post;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Cover and social images uploaded to the disk in `config('blog.images')`.
 * A post stores an upload as its path on the disk, not as a URL, so a new
 * disk URL (APP_URL, CDN) applies to every image; the URL is built on read.
 */
final class Images
{
    /** Post columns that hold an image URL or an uploaded image's path. */
    public const FIELDS = ['cover_image', 'og_image'];

    /** Upload formats. SVG is left out: it can carry scripts. */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public static function enabled(): bool
    {
        return self::disk() !== null;
    }

    /**
     * The disk uploads go to: `images.disk`, else the app's default disk —
     * `public` in place of Laravel's private `local` one. Null when uploads
     * are off or the disk isn't in config/filesystems.php.
     */
    public static function disk(): ?string
    {
        if (! config('blog.images.enabled', true)) {
            return null;
        }

        $disk = config('blog.images.disk');

        if (! is_string($disk) || $disk === '') {
            $disk = config('filesystems.default');
            $disk = $disk === 'local' ? 'public' : $disk;
        }

        return is_string($disk) && config('filesystems.disks.'.$disk) !== null ? $disk : null;
    }

    public static function directory(): string
    {
        return trim((string) config('blog.images.directory', 'blog'), '/');
    }

    /** Largest upload, in kilobytes. */
    public static function maxSize(): int
    {
        return max(1, (int) config('blog.images.max_size', 2048));
    }

    /**
     * Validation rules for an uploaded image.
     *
     * @return list<string>
     */
    public static function uploadRules(): array
    {
        return ['nullable', 'file', 'mimes:'.implode(',', self::EXTENSIONS), 'max:'.self::maxSize()];
    }

    /**
     * Validation rules for an image field: an absolute URL, or the URL of an
     * uploaded image even when the disk serves relative ones (`/storage/…`).
     *
     * @return list<string|Closure>
     */
    public static function urlRules(): array
    {
        return ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && ! Str::isUrl($value, ['http', 'https']) && self::path($value) === $value) {
                $fail('validation.url')->translate();
            }
        }];
    }

    /**
     * Stores an upload as "{name}-{random}.{ext}" in the images directory and
     * returns its path, or null when the disk refused it.
     */
    public static function store(UploadedFile $file, string $name): ?string
    {
        $path = self::prefixed((Str::slug($name) ?: 'image').'-'.Str::lower(Str::random(8)).'.'.$file->extension());

        // Livewire's own storeAs() reports success even when the disk refuses the write.
        $stream = $file instanceof TemporaryUploadedFile ? $file->readStream() : fopen($file->getRealPath(), 'r');

        if (! is_resource($stream)) {
            return null;
        }

        $stored = Storage::disk(self::disk())->put($path, $stream);

        // Some adapters (S3) close the stream themselves.
        if (get_resource_type($stream) === 'stream') {
            fclose($stream);
        }

        return $stored ? $path : null;
    }

    /** Public URL of a stored value: an uploaded image's path or, unchanged, a URL. */
    public static function url(?string $value): ?string
    {
        if (! self::isPath($value) || ! self::enabled()) {
            return $value;
        }

        return Storage::disk(self::disk())->url($value);
    }

    /**
     * Value to store for an image: the path of an uploaded image when given
     * its URL, so a URL read from a post and saved back keeps the upload;
     * anything else unchanged.
     */
    public static function path(?string $value): ?string
    {
        if (! is_string($value) || self::isPath($value) || ! self::enabled()) {
            return $value;
        }

        $base = self::baseUrl();

        if (! str_starts_with($value, $base)) {
            return $value;
        }

        $path = self::prefixed(substr($value, strlen($base)));

        return self::isUpload($path) ? $path : $value;
    }

    /**
     * Paths of the uploaded images a Markdown text links to.
     *
     * @return list<string>
     */
    public static function pathsIn(?string $text): array
    {
        if (! is_string($text) || $text === '' || ! self::enabled()) {
            return [];
        }

        preg_match_all('#'.preg_quote(self::baseUrl(), '#').'([\w./-]+)#', $text, $matches);

        $paths = array_map(fn (string $rest): string => self::prefixed(rtrim($rest, '.')), $matches[1]);

        return array_values(array_unique(array_filter($paths, self::isUpload(...))));
    }

    /**
     * Deletes the uploads a post stopped using in its last save (cover,
     * social image or body), or all of its uploads once it's deleted.
     */
    public static function deleteDropped(Post $post): void
    {
        if ($post->exists && ! $post->wasChanged([...self::FIELDS, 'body'])) {
            return;
        }

        $paths = [
            ...array_map(fn (string $field): mixed => $post->getRawOriginal($field), self::FIELDS),
            ...self::pathsIn($post->getRawOriginal('body')),
        ];

        foreach (array_unique(array_filter($paths, 'is_string')) as $path) {
            self::deleteUnused($path);
        }
    }

    /**
     * Deletes an uploaded image no post uses any more — as its cover or
     * social image, or in its body — so copies of its URL keep it alive.
     */
    public static function deleteUnused(?string $value): void
    {
        if (! self::isUpload($value)) {
            return;
        }

        $used = Post::query()
            ->where('cover_image', $value)
            ->orWhere('og_image', $value)
            ->orWhere('body', 'like', '%'.$value.'%')
            ->exists();

        if (! $used) {
            Storage::disk(self::disk())->delete((string) $value);
        }
    }

    /**
     * Whether a value is the path of a file in the images directory. Every
     * segment must be a plain name, so a crafted value never reaches another
     * folder of the disk ("..", "//", backslashes).
     */
    public static function isUpload(?string $value): bool
    {
        if (! self::isPath($value) || ! self::enabled()) {
            return false;
        }

        $directory = self::directory();

        if ($directory !== '' && ! str_starts_with((string) $value, $directory.'/')) {
            return false;
        }

        return preg_match('#^[\w-][\w.-]*(/[\w-][\w.-]*)*$#', (string) $value) === 1;
    }

    /** Whether a value is a path rather than a URL (scheme, "//host" or "/path"). */
    private static function isPath(?string $value): bool
    {
        return is_string($value) && $value !== '' && preg_match('#^([a-z][a-z0-9+.-]*:|/)#i', $value) !== 1;
    }

    /** URL of the images directory, with a trailing slash. */
    private static function baseUrl(): string
    {
        return rtrim(Storage::disk(self::disk())->url(self::directory()), '/').'/';
    }

    private static function prefixed(string $path): string
    {
        $directory = self::directory();

        return $directory === '' ? $path : $directory.'/'.$path;
    }
}
