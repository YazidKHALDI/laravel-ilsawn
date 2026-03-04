/**
 * ilsawn — Vue 3 adapter (Inertia.js)
 *
 * Requires @inertiajs/vue3. Reads translations from Inertia shared props
 * set by the SharesTranslations trait on the server.
 *
 * Usage:
 *   import { useLang } from '@/vendor/ilsawn/adapters/vue';
 *
 *   const { __ } = useLang();
 *   __('hello')                     // 'Hello'
 *   __('welcome', { name: 'Ali' }) // 'Welcome Ali'
 */

import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { createTranslator } from '../ilsawn.js';

/**
 * @returns {{ __: (key: string, replacements?: Record<string, string|number>) => string }}
 */
export function useLang() {
    const page = usePage();

    // Computed so translations stay reactive when the locale changes mid-session.
    const translator = computed(() =>
        createTranslator(page.props.translations ?? {})
    );

    function __(key, replacements = {}) {
        return translator.value.__(key, replacements);
    }

    return { __ };
}
