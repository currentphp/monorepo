import { Link } from '@inertiajs/react';
import type { Breadcrumb } from '@/types';
export default function Breadcrumbs({ items }: { items: Breadcrumb[] }) {
    if (items.length === 0) return null;
    return (
        <nav aria-label="Breadcrumb" className="breadcrumbs">
            <ol>
                {items.map((crumb, index) => (
                    <li key={crumb.pageId}>
                        {index > 0 ? <span aria-hidden="true">/</span> : null}
                        {crumb.url && !crumb.current ? (
                            <Link href={crumb.url}>{crumb.label}</Link>
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
