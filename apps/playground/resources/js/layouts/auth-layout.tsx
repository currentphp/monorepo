import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { home } from '@/routes';

type Props = {
    title: string;
    description: string;
    children: ReactNode;
};

export default function AuthLayout({ title, description, children }: Props) {
    const { name } = usePage().props;

    return (
        <main className="flex min-h-screen items-center justify-center bg-stone-50 px-4 py-12 text-stone-950 dark:bg-stone-950 dark:text-stone-100">
            <Head title={title} />
            <div className="w-full max-w-md space-y-8">
                <Link
                    href={home()}
                    className="inline-block rounded text-sm font-semibold tracking-wide focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-stone-500"
                >
                    {name}
                </Link>
                <section
                    aria-labelledby="page-title"
                    className="space-y-6 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900"
                >
                    <header className="space-y-2">
                        <h1
                            id="page-title"
                            className="text-2xl font-semibold tracking-tight"
                        >
                            {title}
                        </h1>
                        <p className="text-sm leading-6 text-stone-600 dark:text-stone-400">
                            {description}
                        </p>
                    </header>
                    {children}
                </section>
            </div>
        </main>
    );
}
