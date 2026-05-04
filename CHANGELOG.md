# Changelog

All notable changes to `simple-blog` will be documented in this file.

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
