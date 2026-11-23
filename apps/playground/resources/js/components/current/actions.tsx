import { Link, useForm } from '@inertiajs/react';
import type { Action } from '@/types';
export default function Actions({ actions }: { actions: Action[] }) {
    const visible = actions.filter(
        (action) => !action.current && action.method !== 'HEAD',
    );
    if (visible.length === 0) return null;
    return (
        <ul className="actions">
            {visible.map((action) => (
                <li data-action-id={action.id} key={action.id}>
                    <ActionControl action={action} />
                    {action.reason ? (
                        <p id={`${action.id}-reason`} className="help">
                            {action.reason}
                        </p>
                    ) : null}
                    {action.confirm ? (
                        <p className="help">{action.confirm}</p>
                    ) : null}
                </li>
            ))}
        </ul>
    );
}
function ActionControl({ action }: { action: Action }) {
    const form = useForm({});
    const describedBy = action.reason ? `${action.id}-reason` : undefined;
    const destructive = action.destructive ? 'true' : undefined;
    if (action.method === 'GET') {
        return action.enabled ? (
            <Link
                className="button-link"
                href={action.url}
                data-destructive={destructive}
                aria-describedby={describedBy}
                onBefore={() =>
                    !action.confirm || window.confirm(action.confirm)
                }
            >
                {action.label}
            </Link>
        ) : (
            <button
                type="button"
                className="button-link"
                disabled
                data-destructive={destructive}
                aria-describedby={describedBy}
            >
                {action.label}
            </button>
        );
    }
    const method = action.method.toLowerCase() as
        | 'post'
        | 'put'
        | 'patch'
        | 'delete';
    return (
        <form
            action={action.url}
            method="post"
            aria-busy={form.processing ? 'true' : undefined}
            onSubmit={(event) => {
                event.preventDefault();
                if (action.confirm && !window.confirm(action.confirm)) return;
                form.submit(method, action.url, { preserveScroll: true });
            }}
        >
            <button
                type="submit"
                disabled={!action.enabled || form.processing}
                data-destructive={destructive}
                aria-describedby={describedBy}
            >
                {action.label}
            </button>
        </form>
    );
}
