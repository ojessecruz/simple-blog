<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class InstallCommand extends Command
{
    protected $signature = 'simple-blog:install';

    protected $description = 'Install Simple Blog: publish config & migrations and register the Tailwind source';

    /**
     * The import that registers the package views as a Tailwind v4 source.
     */
    private const CSS_IMPORT = "@import '../../vendor/ojessecruz/simple-blog/resources/css/simple-blog.css';";

    /**
     * Substring used to detect an existing registration (quote-agnostic).
     */
    private const CSS_MARKER = 'ojessecruz/simple-blog/resources/css/simple-blog.css';

    public function handle(): int
    {
        $this->components->info('Installing Simple Blog…');

        $this->callSilently('vendor:publish', ['--tag' => 'simple-blog-config']);
        $this->components->task('Published config (config/blog.php)');

        $this->callSilently('vendor:publish', ['--tag' => 'simple-blog-migrations']);
        $this->components->task('Published migrations');

        $this->registerTailwindSource();

        if ($this->confirm('Run the blog migrations now?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->components->info('Simple Blog installed. Next steps:');
        $this->components->bulletList([
            'Protect the admin: set `admin_middleware` in config/blog.php (e.g. add an auth/role middleware).',
            'Build your CSS so the blog styles compile: `npm run build` (or `npm run dev`).',
        ]);

        return self::SUCCESS;
    }

    /**
     * Idempotently add the package's Tailwind source `@import` to the app's
     * main stylesheet, right after the `tailwindcss` import when present.
     */
    private function registerTailwindSource(): void
    {
        $cssPath = resource_path('css/app.css');

        if (! File::exists($cssPath)) {
            $this->components->warn('Could not find [resources/css/app.css]. Add this line to your main CSS manually:');
            $this->line('    '.self::CSS_IMPORT);

            return;
        }

        $contents = File::get($cssPath);

        if (str_contains($contents, self::CSS_MARKER)) {
            $this->components->task('Tailwind source already registered');

            return;
        }

        if (preg_match('/^@import\s+["\']tailwindcss["\'];.*$/m', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            $position = $matches[0][1] + strlen($matches[0][0]);
            $contents = substr($contents, 0, $position)."\n".self::CSS_IMPORT.substr($contents, $position);
        } else {
            $contents = self::CSS_IMPORT."\n".$contents;
        }

        File::put($cssPath, $contents);
        $this->components->task('Registered Tailwind source in resources/css/app.css');
    }
}
