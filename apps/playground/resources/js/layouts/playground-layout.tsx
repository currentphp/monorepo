import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Menu from '@/components/current/menu';

export default function PlaygroundLayout({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    const { name, current, renderingChoices } = usePage().props;
    return (
        <div className="playground">
            <Head title={title} />
            <a href="#main-content" className="skip-link">
                Skip to content
            </a>
            <div className="shell">
                <header className="masthead">
                    <Link href="/" className="brand">
                        {name}
                    </Link>
                    <span className="help">
                        Demo mode · no account required
                    </span>
                </header>
                <nav aria-label="Primary">
                    <Menu items={current.menus.primary} />
                </nav>
                {renderingChoices.length > 0 ? (
                    <nav
                        className="rendering-switcher"
                        aria-label="Rendering choices"
                    >
                        {renderingChoices.map((choice) => (
                            <a
                                key={choice.label}
                                href={choice.url}
                                aria-current={
                                    choice.current ? 'page' : undefined
                                }
                            >
                                {choice.label}
                            </a>
                        ))}
                    </nav>
                ) : null}
                <main id="main-content" tabIndex={-1}>
                    {children}
                </main>
                <footer>
                    <nav aria-label="Footer">
                        <Menu items={current.menus.footer} />
                    </nav>
                </footer>
            </div>
        </div>
    );
}
