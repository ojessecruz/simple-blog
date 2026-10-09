# Contributing to Simple Blog

Thanks for your interest. This package is maintained by one person, so these rules keep reviews short and the package focused. Pull requests that don't follow them may be closed without review.

## Before you open a pull request

- **Open an issue first** for anything other than a small, obvious bug fix or a typo. Wait for the maintainer to agree on the change before writing code. Unannounced features are usually declined.
- **One change per pull request.** No unrelated refactors, renames or reformatting.
- **No new dependencies** (runtime or dev) without agreement in the issue.
- Security problems are never reported in public issues: see [SECURITY.md](SECURITY.md).

## Scope of this package

A small, self-contained blog for Laravel apps: posts, categories, a Livewire admin and an opt-in JSON API. It stays small on purpose.

- **No authorization inside the package.** Host apps protect the admin and the API through the middleware in `config/blog.php`. Pull requests that add Gates or Policies will be closed.
- The host app supplies the author model through the `Author` contract; the package never assumes a `User` model or columns.
- Layouts are pluggable: the public side uses `@yield('content')`, the admin side `{{ $slot }}`, and pages push their SEO tags to `@stack('head')`. These conventions don't change.
- The Markdown defaults (`html_input: escape`, `allow_unsafe_links: false`) are the XSS boundary and are not loosened.
- Behavior comes from `config/blog.php`, never hardcoded values; every new key is documented in that file.
- When a view is added, moved or renamed, the publish maps in `SimpleBlogServiceProvider::registerViewPublishing()` are updated too.
- The public API is: config keys, route names, view names and their yield/slot/stack contract, the JSON API, the `Author` contract and the models' public methods. Breaking it only happens in a major version.

## Requirements for every pull request

- Tests with Pest for the change: the bug fix comes with a test that fails without it.
- `composer test` passes on PHP 8.3–8.5 × Laravel 11/12/13, on Linux and Windows, with `--prefer-lowest` too.
- `composer analyse` passes (PHPStan at the level in `phpstan.neon.dist`). Don't add baseline entries or `@phpstan-ignore` to make it pass.
- `composer format` was run (Laravel Pint, `pint.json`). CI checks the style but doesn't fix it for you.
- The README is updated when behavior or configuration changes.
- Don't edit `CHANGELOG.md` or the version: the maintainer does that at release time.

## Commits and merging

- Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/): `fix: …`, `feat: …`, `docs: …`, `test: …`, `refactor: …`, `chore: …`. A breaking change has `!` after the type and explains the migration path in the body.
- `main` is protected: changes only land through pull requests, with every check green and the maintainer's approval.
- Pull requests are squash-merged, so the title must be a valid Conventional Commit message.
- Releases (tags `v*`) are made only by the maintainer.

## Development

```bash
composer install
composer test
composer analyse
composer format
```

## License

By contributing, you agree that your contributions are licensed under the [MIT License](LICENSE.md).
