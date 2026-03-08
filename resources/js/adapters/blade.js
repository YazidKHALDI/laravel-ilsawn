/**
 * ilsawn — Blade / Alpine.js adapter
 *
 * For projects that use Blade without Inertia.js.
 * Reads translations from window.__ilsawn, which is populated by the
 * @ilsawnTranslations Blade directive.
 *
 * Add the directive to your main layout (before Alpine initializes):
 *
 *   <head>
 *       @ilsawnTranslations
 *   </head>
 *
 * Then import and use in your JS:
 *
 *   import '@/vendor/ilsawn/adapters/blade';  // sets window.__
 *
 *   // In Alpine.js expressions it just works:
 *   // x-text="__('dashboard.title')"
 *
 * Or import the function explicitly:
 *
 *   import { __ } from '@/vendor/ilsawn/adapters/blade';
 *   __('welcome', { name: 'Ali' })  // 'Welcome Ali'
 */

import { createTranslator } from '../ilsawn.js';

const { __ } = createTranslator(window.__ilsawn ?? {});

// Expose as a global so Alpine.js inline expressions can call __() directly.
window.__ = __;

export { __ };
