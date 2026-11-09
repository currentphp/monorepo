import { Link } from '@inertiajs/react';
import type { Breadcrumb } from '@/types';

export default function Breadcrumbs({ items }: { items: Breadcrumb[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <nav aria-label="Breadcrumb">
            <ol className="flex flex-wrap gap-2 text-sm">
                {items.map((crumb, index) => (
                    <li key={crumb.pageId} className="flex gap-2">
                        {index > 0 && <span aria-hidden="true">/</span>}
                        {crumb.url && !crumb.current ? (
                            <Link
                                href={crumb.url}
                                className="underline underline-offset-4"
                            >
                                {crumb.label}
                            </Link>
                        ) : (
                            <span
                                aria-current={
                                    crumb.current ? 'page' : undefined
                                }
                            >
                                {crumb.label}
                            </span>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}
