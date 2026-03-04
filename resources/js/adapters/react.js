/**
 * ilsawn — React adapter (Inertia.js)
 *
 * Requires @inertiajs/react. Reads translations from Inertia shared props
 * set by the SharesTranslations trait on the server.
 *
 * Usage:
 *   import { useLang } from '@/vendor/ilsawn/adapters/react';
 *
 *   const { __ } = useLang();
 *   __('hello')                     // 'Hello'
 *   __('welcome', { name: 'Ali' }) // 'Welcome Ali'
 */

import { usePage } from '@inertiajs/react';
import { createTranslator } from '../ilsawn.js';

/**
 * @returns {{ __: (key: string, replacements?: Record<string, string|number>) => string }}
 */
export function useLang() {
    const { translations = {} } = usePage().props;

    return createTranslator(translations);
}
