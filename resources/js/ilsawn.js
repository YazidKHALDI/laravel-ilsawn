/**
 * ilsawn — framework-agnostic translation core
 *
 * Usage:
 *   import { createTranslator } from 'ilsawn';
 *
 *   const { __ } = createTranslator(translations);
 *   __('hello')                     // 'Bonjour'
 *   __('welcome', { name: 'Ali' }) // 'Bienvenue Ali' (replaces :name)
 */

/**
 * Replace :placeholder tokens in a string.
 *
 * @param {string} value
 * @param {Record<string, string|number>} replacements
 * @returns {string}
 */
function applyReplacements(value, replacements) {
    return Object.entries(replacements).reduce(
        (str, [k, v]) => str.replaceAll(`:${k}`, String(v)),
        value
    );
}

/**
 * Build a translator from a flat key→value translations object.
 *
 * @param {Record<string, string>} translations  Flat map of translation keys to strings.
 * @param {{ fallback?: (key: string) => string }} [options]
 * @returns {{ __: (key: string, replacements?: Record<string, string|number>) => string }}
 */
export function createTranslator(translations, options = {}) {
    const { fallback = (key) => key } = options;

    /**
     * @param {string} key
     * @param {Record<string, string|number>} [replacements]
     * @returns {string}
     */
    function __(key, replacements = {}) {
        const value = Object.prototype.hasOwnProperty.call(translations, key)
            ? translations[key]
            : fallback(key);

        if (!value) return key;

        return Object.keys(replacements).length
            ? applyReplacements(value, replacements)
            : value;
    }

    return { __ };
}
