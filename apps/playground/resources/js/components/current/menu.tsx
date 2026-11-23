import { Link } from '@inertiajs/react';
import type { MenuItem } from '@/types';
export default function Menu({ items }: { items: MenuItem[] }) {
    return (
        <ul className="menu">
            {items.map((item) => (
                <li key={`${item.kind}:${item.pageId ?? item.label}`}>
                    {item.url ? (
                        <Link
                            href={item.url}
                            data-active={item.active ? 'true' : undefined}
                            aria-current={item.current ? 'page' : undefined}
                        >
                            {item.label}
                        </Link>
                    ) : (
                        <strong>{item.label}</strong>
                    )}
                    {item.children.length > 0 ? (
                        <Menu items={item.children} />
                    ) : null}
                </li>
            ))}
        </ul>
    );
}
