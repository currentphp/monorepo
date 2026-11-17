import { Head, Link, usePage } from '@inertiajs/react';
import Breadcrumbs from '@/components/wavykit/breadcrumbs';
import Menu from '@/components/wavykit/menu';
import { home } from '@/routes';
import type { Subject } from '@/types';

type Project = {
    id: number;
    name: string;
    url: string;
    editUrl: string;
};

type Props = {
    title: string;
    project: Pick<Project, 'id' | 'name'> | null;
    projects: Project[];
    tasks: Subject[];
};

export default function Projects({ title, project, projects, tasks }: Props) {
    const { auth, name, wavykit, subject } = usePage().props;
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
                    <Link href={home()} className="text-lg font-semibold">
                        {name}
                    </Link>
                    <nav aria-label="Primary">
                        <Menu items={wavykit.menus.primary} />
                    </nav>
                </div>
            </header>
            <main
                id="main-content"
                tabIndex={-1}
                className="mx-auto max-w-5xl space-y-8 px-6 py-10"
            >
                <Breadcrumbs items={wavykit.breadcrumbs} />
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
                        Explore a project, its tasks, and its edit page.
                        Breadcrumbs come from WavyKit Pages; the menus come from
                        WavyKit Navigation, where the primary menu keeps
                        Projects active without listing individual projects.
                    </p>
                    {subject ? (
                        <p className="text-sm text-stone-600 dark:text-stone-400">
                            Viewing {subject.kind}{' '}
                            <strong>{subject.label}</strong> ({subject.kind}:
                            {subject.id}).
                        </p>
                    ) : null}
                </div>
                {selected ? (
                    <section className="space-y-4 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                        <h2 className="text-xl font-semibold">
                            {selected.name}
                        </h2>
                        <p className="text-stone-600 dark:text-stone-400">
                            {wavykit.pageId === 'projects.edit'
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
                        {tasks.length > 0 ? (
                            <div className="space-y-2">
                                <h3 className="font-semibold">Tasks</h3>
                                <ul className="list-disc space-y-1 pl-5">
                                    {tasks.map((task) =>
                                        task.url ? (
                                            <li key={task.id}>
                                                <Link
                                                    href={task.url}
                                                    className="underline underline-offset-4"
                                                >
                                                    {task.label}
                                                </Link>
                                            </li>
                                        ) : null,
                                    )}
                                </ul>
                            </div>
                        ) : null}
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
                    <Menu items={wavykit.menus.footer} />
                </nav>
            </footer>
        </div>
    );
}
