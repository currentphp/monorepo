import { Form, Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { dashboard, logout } from '@/routes';
import { edit } from '@/routes/profile';

type Props = { title: string; children: ReactNode };

export default function AppLayout({ title, children }: Props) {
    const { name, auth } = usePage().props;

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
                <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
                    <Link
                        href={dashboard()}
                        className="rounded font-semibold focus-visible:outline-2 focus-visible:outline-offset-4"
                    >
                        {name}
                    </Link>
                    <nav
                        aria-label="Account"
                        className="flex flex-wrap items-center gap-4 text-sm"
                    >
                        <Link
                            href={dashboard()}
                            className="rounded px-2 py-3 underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
                        >
                            Overview
                        </Link>
                        <Link
                            href={edit()}
                            className="rounded px-2 py-3 underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
                        >
                            Profile
                        </Link>
                        <Form {...logout.form()}>
                            {({ processing }) => (
                                <button
                                    disabled={processing}
                                    className="rounded px-2 py-3 underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
                                >
                                    {processing ? 'Signing out…' : 'Sign out'}
                                </button>
                            )}
                        </Form>
                    </nav>
                </div>
            </header>
            <main
                id="main-content"
                tabIndex={-1}
                className="mx-auto max-w-5xl space-y-6 px-4 py-10 sm:px-6"
            >
                <div className="space-y-2">
                    <p className="text-sm break-words text-stone-600 dark:text-stone-400">
                        Signed in as {auth.user?.name}
                    </p>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {title}
                    </h1>
                </div>
                {children}
            </main>
        </div>
    );
}
