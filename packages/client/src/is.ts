/** Anything that carries Current's page ID, such as the Inertia prop or the JSON output. */
export type HasPageId = { readonly pageId: string | null };

/**
 * Whether the current page matches any of the patterns, exactly as PHP's
 * `Current::is()` decides: `*` matches any characters, dots included; matching
 * is anchored at both ends and case-sensitive; and no page, or no patterns,
 * never matches.
 *
 * @example is(current, 'projects.*')
 */
export function is(current: HasPageId, ...patterns: string[]): boolean {
    const { pageId } = current;
    if (pageId === null || patterns.length === 0) {
        return false;
    }

    return patterns.some((pattern) => matches(pattern, pageId));
}

// Mirrors Laravel's Str::is(): a lone `*` or an exact match is a match;
// otherwise each `*` becomes `.*` in an anchored, dot-all, Unicode expression.
function matches(pattern: string, value: string): boolean {
    if (pattern === '*' || pattern === value) {
        return true;
    }

    const source = pattern.split('*').map(escape).join('.*');

    return new RegExp(`^${source}$`, 'su').test(value);
}

// Escapes the characters that are special in a Unicode-mode expression.
function escape(literal: string): string {
    return literal.replace(/[\\^$.*+?()[\]{}|/]/g, '\\$&');
}
