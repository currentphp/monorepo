import { Head, Link, usePage } from '@inertiajs/react';
import { home } from '@/routes';

type Breadcrumb = {
    pageId: string;
    label: string;
    url: string | null;
    current: boolean;
};

type MenuItem = {
    pageId: string | null;
    label: string;
    url: string | null;
    current: boolean;
    active: boolean;
    kind: 'page' | 'group';
    children: MenuItem[];
};

type Project = {
    id: number;
    name: string;
    url: string;
    editUrl: string;
};

type Props = {
    title: string;
    currentPageId: string;
    breadcrumbs: Breadcrumb[];
    primary: MenuItem[];
    footer: MenuItem[];
    project: Pick<Project, 'id' | 'name'> | null;
    projects: Project[];
};

function MenuItems({ items }: { items: MenuItem[] }) {
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
                            <MenuItems items={item.children} />
                        </div>
                    )}
                </li>
            ))}
        </ul>
    );
}

export default function PagesTrial({
    title,
    currentPageId,
    breadcrumbs,
    primary,
    footer,
    project,
    projects,
}: Props) {
    const { auth } = usePage().props;
    const selected = projects.find((item) => item.id === project?.id);

    return (
        <div className="min-h-screen bg-stone-50 text-stone-950 dark:bg-stone-950 dark:text-stone-100">
            <Head title={title} />
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:bg-white focus:p-4 focus:text-stone-950"
            >
                Skip to content
            </a>
            <header className="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-6 px-6 py-5">
                    <Link
                        href={primary[0]?.url ?? home()}
                        className="text-lg font-semibold"
                    >
                        Pages Trial
                    </Link>
                    <nav aria-label="Primary">
                        <MenuItems items={primary} />
                    </nav>
                </div>
            </header>
            <main
                id="main-content"
                tabIndex={-1}
                className="mx-auto max-w-5xl space-y-8 px-6 py-10"
            >
                <nav aria-label="Breadcrumb">
                    <ol className="flex flex-wrap gap-2 text-sm">
                        {breadcrumbs.map((crumb, index) => (
                            <li key={crumb.pageId} className="flex gap-2">
                                {index > 0 && <span aria-hidden="true">/</span>}
                                {crumb.url ? (
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
                <div className="space-y-3">
                    <p className="text-sm text-stone-600 dark:text-stone-400">
                        {auth.user
                            ? `Signed in as ${auth.user.name}`
                            : 'Browsing as a guest'}
                    </p>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="max-w-2xl text-stone-600 dark:text-stone-400">
                        Explore a project and its edit page. Breadcrumbs follow
                        the selected project while Projects stays active in the
                        primary navigation.
                    </p>
                </div>
                {selected ? (
                    <section className="space-y-4 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                        <h2 className="text-xl font-semibold">
                            {selected.name}
                        </h2>
                        <p className="text-stone-600 dark:text-stone-400">
                            {currentPageId === 'projects.edit'
                                ? 'This is a read-only edit-page example. No project changes are saved.'
                                : 'This example project has a details page and an edit page.'}
                        </p>
                        <div className="flex flex-wrap gap-4">
                            <Link
                                href={selected.url}
                                className="underline underline-offset-4"
                            >
                                View details
                            </Link>
                            <Link
                                href={selected.editUrl}
                                className="underline underline-offset-4"
                            >
                                Open edit page
                            </Link>
                        </div>
                    </section>
                ) : null}
                <section
                    className="grid gap-4 sm:grid-cols-2"
                    aria-label="Projects"
                >
                    {projects.map((item) => (
                        <Link
                            key={item.id}
                            href={item.url}
                            className="rounded-2xl border border-stone-200 bg-white p-6 focus-visible:outline-2 focus-visible:outline-offset-4 dark:border-stone-800 dark:bg-stone-900"
                        >
                            <h2 className="text-lg font-semibold">
                                {item.name}
                            </h2>
                            <p className="mt-2 text-sm text-stone-600 dark:text-stone-400">
                                Explore project
                            </p>
                        </Link>
                    ))}
                </section>
            </main>
            <footer className="mx-auto max-w-5xl border-t border-stone-200 px-6 py-8 dark:border-stone-800">
                <nav aria-label="Footer">
                    <MenuItems items={footer} />
                </nav>
            </footer>
        </div>
    );
}
