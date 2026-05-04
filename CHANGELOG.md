# Changelog

All notable changes to `simple-blog` will be documented in this file.

## Unreleased

### Added

- Dark mode support across all public and admin views via Tailwind `dark:` variants. The host app's Tailwind config controls the strategy (`media` or `class`).
- Translation support with built-in `en`, `pt_BR`, and `es` locales. New `locale` config key in `config/blog.php` — `null` follows `app()->getLocale()`, a string pins the blog to that locale. Custom translations can be added via `vendor:publish --tag="simple-blog-lang"`.

### Changed

- Medium-style cover image on the post page: edge-to-edge on mobile, breakout past the prose column on desktop, and a fixed 16:9 aspect ratio so covers render consistently regardless of source dimensions.
