<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

// Blade only compiles the parts of a template that PHP's tokenizer reads as
// inline HTML. With short_open_tag=On, any "<" followed by "?" starts PHP code,
// so the rest of that line is left uncompiled and the view fails with a
// ParseError (e.g. a literal XML declaration in feed.blade.php).
it('has no view that short_open_tag would read as PHP code', function () {
    $views = collect(File::allFiles(__DIR__.'/../../resources/views'))
        ->map(fn ($file) => $file->getRealPath())
        ->values()
        ->all();

    $result = Process::run([PHP_BINARY, '-d', 'short_open_tag=On', __DIR__.'/../Stubs/find-php-tags.php', ...$views])->throw();

    expect(trim($result->output()))->toBe('');
});
