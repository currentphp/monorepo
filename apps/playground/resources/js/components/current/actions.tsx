import type { Method } from '@inertiajs/core';
import { Link } from '@inertiajs/react';
import type { Action } from '@/types';

const methods = {
    GET: 'get',
    HEAD: null,
    POST: 'post',
    PUT: 'put',
    PATCH: 'patch',
    DELETE: 'delete',
} satisfies Record<Action['method'], Method | null>;

/**
 * Renders described actions as buttons. Current only describes them; the
 * request goes to the app's own route, which authorizes and performs it.
 */
export default function Actions({ actions }: { actions: Action[] }) {
    if (actions.length === 0) {
        return null;
    }

    return (
        <ul className="flex flex-wrap gap-2">
            {actions
                .filter((action) => !action.current)
                .map((action) => {
                    // HEAD describes a valid HTTP action but cannot render an Inertia page.
                    const method = methods[action.method];
                    if (method === null) {
                        return null;
                    }

                    return (
                        <li key={action.id}>
                            <Link
                                href={action.url}
                                method={method}
                                as="button"
                                disabled={!action.enabled}
                                title={action.reason ?? undefined}
                                onBefore={() =>
                                    action.confirm === null ||
                                    window.confirm(action.confirm)
                                }
                                className={`rounded-lg border px-3 py-1.5 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${action.destructive ? 'border-red-300 text-red-700 dark:border-red-800 dark:text-red-300' : 'border-stone-300 dark:border-stone-700'}`}
                            >
                                {action.label}
                            </Link>
                            {!action.enabled && action.reason ? (
                                <span className="sr-only">
                                    {' '}
                                    ({action.reason})
                                </span>
                            ) : null}
                        </li>
                    );
                })}
        </ul>
    );
}
