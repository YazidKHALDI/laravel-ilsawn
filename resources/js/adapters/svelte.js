/**
 * ilsawn — Svelte adapter (Inertia.js)
 *
 * Requires @inertiajs/svelte. Reads translations from Inertia shared props
 * set by the SharesTranslations trait on the server.
 *
 * Usage:
 *   import { useLang } from '@/vendor/ilsawn/adapters/svelte';
 *
 *   const { __ } = useLang();
 *   $: label    = __('hello');
 *   $: greeting = __('welcome', { name: 'Ali' });
 */

import { page } from '@inertiajs/svelte';
import { derived } from 'svelte/store';
import { createTranslator } from '../ilsawn.js';

/**
 * A derived store that re-creates the translator whenever the page props change.
 *
 * @type {import('svelte/store').Readable<{ __: (key: string, replacements?: Record<string, string|number>) => string }>}
 */
export const lang = derived(page, ($page) =>
    createTranslator($page.props.translations ?? {})
);

/**
 * Convenience function — reads the current translator synchronously.
 * Use inside components or reactive statements.
 *
 * @returns {{ __: (key: string, replacements?: Record<string, string|number>) => string }}
 */
export function useLang() {
    let current;
    const unsubscribe = lang.subscribe((value) => { current = value; });
    unsubscribe();

    return current;
}
