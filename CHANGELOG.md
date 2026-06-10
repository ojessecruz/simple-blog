# Changelog

All notable changes to `simple-blog` will be documented in this file.

## 0.6.1 - 2026-06-10

### Documentation

- Republished release carrying the `0.6.0` changelog entry. The `0.6.0` feature code (the `simple-blog:install` command) is unchanged; this patch only adds its changelog entry, which landed after `0.6.0` was already tagged. No functional changes.

## 0.6.0 - 2026-06-09

### Added

- `simple-blog:install` command. One idempotent command publishes the config and migrations, registers the Tailwind v4 source by injecting `@import '../../vendor/ojessecruz/simple-blog/resources/css/simple-blog.css';` into `resources/css/app.css` (right after the `@import "tailwindcss";` line, or prepended when absent), and offers to run the migrations. A clean install is now `composer require` → `php artisan simple-blog:install` → `npm run build` instead of hand-editing CSS. Re-running is safe — it never duplicates the import — and when `resources/css/app.css` is missing it prints the line to add manually.

## 0.5.1 - 2026-06-09

### Added

- Tailwind v4 CSS source entrypoint at `resources/css/simple-blog.css`. It registers the package views via `@source`, so a consuming app enables the package's styling with a single `@import '../../vendor/ojessecruz/simple-blog/resources/css/simple-blog.css';` from its main stylesheet instead of hardcoding the vendor path. Without it, Tailwind v4 never scans the package and the blog renders unstyled. The `@source` path is resolved relative to the package file, so it survives host-app restructuring.

### Documentation

- The Tailwind setup step moved into the Installation section (was buried under "Customizing the layout"), since it affects every install — the default styling renders broken without it. The README now documents both the Tailwind v4 (`@import`) and v3 (`tailwind.config.js` `content`) wiring.

## 0.5.0 - 2026-06-08

### Added

- Livewire 4 support. The `livewire/livewire` constraint widens to `^3.5||^4.0`, so the package installs cleanly alongside either major version. The full test suite passes against the Livewire 3.5 floor (with Laravel 11) and against Livewire 4.3 (with Laravel 12).

### Fixed

- Admin Livewire components are now registered under dotted names (`blog.admin.post-index`, `blog.admin.post-form`, `blog.admin.category-index`) instead of `blog::admin.*`. Livewire 4 reserves `::` for namespace resolution and would fail to resolve the `::`-style names, throwing `ComponentNotFoundException`. The new names resolve identically on Livewire 3 and 4. Routing is unaffected (admin routes reference the component classes directly).
- `Post::published()` scope now uses the `scopePublished()` naming convention instead of the `#[Scope]` attribute. The attribute (`Illuminate\Database\Eloquent\Attributes\Scope`) only exists in Laravel 12+, so on Laravel 11 the scope threw `ArgumentCountError`. This is the version range declared in `composer.json` (`illuminate/contracts: ^11.0`) but was never exercised in CI, which only ran Laravel 12/13. The conventional `scopePublished()` works across Laravel 11/12/13.

## 0.4.3 - 2026-05-04

### Fixed

- "Mark as draft" checkbox and label now follow dark mode: explicit `text-zinc-900 dark:text-zinc-100` on the label and `bg-white dark:bg-zinc-900` on the checkbox so it stops rendering as a bright white square against the dark card.

## 0.4.2 - 2026-05-04

### Changed

- Post form now uses `max-w-7xl` to match the post index, giving the split-view editor and preview panes enough horizontal room on wide screens.

## 0.4.1 - 2026-05-04

### Fixed

- Dark mode text color on form controls and ghost-style buttons across the admin views (status filter, search, post form fields, "Categorias" / "Ver" / "Cancel" buttons). User-agent stylesheets and inconsistent preflight loading were leaving these elements with black text on the dark background.

## 0.4.0 - 2026-05-04

### Added

- Dark mode support across all public and admin views via Tailwind `dark:` variants. The host app's Tailwind config controls the strategy (`media` or `class`).
- Translation support with built-in `en`, `pt_BR`, and `es` locales. New `locale` config key in `config/blog.php` — `null` follows `app()->getLocale()`, a string pins the blog to that locale. Custom translations can be added via `vendor:publish --tag="simple-blog-lang"`.
- Split-view toggle in the post form: two icon buttons switch between Markdown-only editing and a side-by-side editor + live preview. The preview renders through the same `Str::markdown()` pipeline as the published post and uses inline styles, so it does not depend on the `@tailwindcss/typography` plugin in the host app.

### Changed

- Medium-style cover image on the post page: edge-to-edge on mobile, breakout past the prose column on desktop, and a fixed 16:9 aspect ratio so covers render consistently regardless of source dimensions.
