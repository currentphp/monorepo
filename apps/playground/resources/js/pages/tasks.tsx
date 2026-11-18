import { Form, Head, Link, usePage } from '@inertiajs/react';
import Actions from '@/components/current/actions';
import Breadcrumbs from '@/components/current/breadcrumbs';
import Menu from '@/components/current/menu';
import { home } from '@/routes';
import type { Action, Subject } from '@/types';

type Row = { subject: Subject; actions: Action[] };

type Props = {
    title: string;
    rows: Row[];
    form: { url: string; method: 'post' | 'put'; value: string } | null;
};

/** Task admin for the actions prototype: every button comes from the action description. */
export default function Tasks({ title, rows, form }: Props) {
    const { name, current, actions, status } = usePage().props;

    return (
        <div className="min-h-screen bg-stone-50 text-stone-950 dark:bg-stone-950 dark:text-stone-100">
            <Head title={title} />
            <header className="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-6 px-6 py-5">
                    <Link href={home()} className="text-lg font-semibold">
                        {name}
                    </Link>
                    <nav aria-label="Primary">
                        <Menu items={current.menus.primary} />
                    </nav>
                </div>
            </header>
            <main
                id="main-content"
                tabIndex={-1}
                className="mx-auto max-w-5xl space-y-8 px-6 py-10"
            >
                <Breadcrumbs items={current.breadcrumbs} />
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <Actions actions={actions} />
                </div>
                {status ? (
                    <p
                        role="status"
                        className="rounded-lg border border-teal-300 bg-teal-50 px-4 py-3 text-sm dark:border-teal-800 dark:bg-teal-950"
                    >
                        {status}
                    </p>
                ) : null}
                {form ? (
                    <Form
                        action={form.url}
                        method={form.method}
                        className="max-w-md space-y-4"
                    >
                        <label className="block space-y-1">
                            <span className="text-sm font-medium">Title</span>
                            <input
                                name="title"
                                defaultValue={form.value}
                                required
                                className="w-full rounded-lg border border-stone-300 px-3 py-2 dark:border-stone-700 dark:bg-stone-900"
                            />
                        </label>
                        <button className="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">
                            Save
                        </button>
                    </Form>
                ) : (
                    <ul className="divide-y divide-stone-200 rounded-2xl border border-stone-200 bg-white dark:divide-stone-800 dark:border-stone-800 dark:bg-stone-900">
                        {rows.map((row) => (
                            <li
                                key={row.subject.id}
                                className="flex flex-wrap items-center justify-between gap-4 px-6 py-4"
                            >
                                {row.subject.url ? (
                                    <Link
                                        href={row.subject.url}
                                        className="underline underline-offset-4"
                                    >
                                        {row.subject.label}
                                    </Link>
                                ) : (
                                    <span>{row.subject.label}</span>
                                )}
                                <Actions actions={row.actions} />
                            </li>
                        ))}
                    </ul>
                )}
            </main>
        </div>
    );
}
