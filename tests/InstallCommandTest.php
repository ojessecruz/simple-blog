<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::ensureDirectoryExists(resource_path('css'));
    File::put(resource_path('css/app.css'), "@import 'tailwindcss';\n");
});

afterEach(function () {
    File::delete(resource_path('css/app.css'));
});

it('registers the Tailwind source after the tailwindcss import', function () {
    $this->artisan('simple-blog:install')
        ->expectsConfirmation('Run the blog migrations now?', 'no')
        ->assertSuccessful();

    $css = File::get(resource_path('css/app.css'));

    expect($css)
        ->toContain("@import '../../vendor/ojessecruz/simple-blog/resources/css/simple-blog.css';")
        ->and($css)->toMatch('/@import\s+["\']tailwindcss["\'];\s*\n@import .*simple-blog\.css/');
});

it('is idempotent and does not duplicate the import', function () {
    $this->artisan('simple-blog:install')->expectsConfirmation('Run the blog migrations now?', 'no')->assertSuccessful();
    $this->artisan('simple-blog:install')->expectsConfirmation('Run the blog migrations now?', 'no')->assertSuccessful();

    $occurrences = substr_count(File::get(resource_path('css/app.css')), 'simple-blog/resources/css/simple-blog.css');

    expect($occurrences)->toBe(1);
});

it('prepends the import when no tailwindcss import is present', function () {
    File::put(resource_path('css/app.css'), "body { color: red; }\n");

    $this->artisan('simple-blog:install')
        ->expectsConfirmation('Run the blog migrations now?', 'no')
        ->assertSuccessful();

    expect(File::get(resource_path('css/app.css')))
        ->toStartWith("@import '../../vendor/ojessecruz/simple-blog/resources/css/simple-blog.css';");
});
