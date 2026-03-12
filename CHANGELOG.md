# Changelog

## v1.0.1 - 2026-03-12

### Bug fixes

- **Renamed publish tag** `laravel-ilsawn-js` → `ilsawn-js` to match the convention Spatie uses for `ilsawn-config` and `ilsawn-views`. If you published JS assets manually, re-run:
  `php artisan vendor:publish --tag=ilsawn-js --force`
  
- **Fixed STDIN error in cleanup** — confirming unused-key deletion from the Livewire UI no longer crashes with `Undefined constant 'STDIN'`. The cleanup now calls the service directly instead of going through Artisan.
  
- **Fixed incorrect README guidance** — clarified that `lang/{locale}/breeze.json` is only loaded by the `SharesTranslations` trait (Inertia). For Blade/Livewire users, external package strings should be added to the CSV instead.
  

### What's changed

- Scan button now also removes framework duplicates in one step
- Cleanup button shows a dry-run preview modal before deleting any keys
- Missing-only toggle now shows a `missing/total` ratio badge

## v1.0.0 — 2026-03-08

### Added

- CSV-based translation management
- Livewire UI with inline editing, search, and "Missing only" filter
- `ilsawn:generate` — scan, cleanup, generate JSON files
- `ilsawn:install` — publishes config, CSV stub, Gate provider, JS adapters
- Inertia.js `SharesTranslations` trait with production caching
- JS adapters for React, Vue 3, Svelte, and Blade/Alpine
- AI auto-translation via `laravel/ai` (optional)
- Support for Laravel 11, 12, 13 and Livewire 3, 4
