import { Link } from '@inertiajs/react';
import type { MenuItem } from '@/types';

export default function Menu({ items }: { items: MenuItem[] }) {
    return (
        <ul className="flex flex-wrap gap-4">
            {items.map((item) => (
                <li key={`${item.kind}:${item.pageId ?? item.label}`}>
                    {item.url ? (
                        <Link
                            href={item.url}
                            aria-current={item.current ? 'page' : undefined}
                            className={`rounded underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 ${item.active ? 'font-semibold text-teal-700 dark:text-teal-300' : ''}`}
                        >
                            {item.label}
                        </Link>
                    ) : (
                        <span className="font-semibold">{item.label}</span>
                    )}
                    {item.children.length > 0 && (
                        <div className="mt-3 text-sm">
                            <Menu items={item.children} />
                        </div>
                    )}
                </li>
            ))}
        </ul>
    );
}
