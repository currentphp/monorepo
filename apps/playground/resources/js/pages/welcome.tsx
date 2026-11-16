import { Link, usePage } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <AuthLayout
            title="Welcome"
            description="Sign in to your account to get started."
        >
            <nav aria-label="Account" className="flex flex-wrap gap-3">
                <Link
                    href={auth.user ? dashboard() : login()}
                    className="inline-flex min-h-11 items-center justify-center rounded-lg bg-stone-900 px-5 py-2 text-sm font-medium text-white hover:bg-stone-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-stone-500 dark:bg-stone-100 dark:text-stone-950"
                >
                    {auth.user ? 'Your account' : 'Sign in'}
                </Link>
                {!auth.user && auth.canRegister && (
                    <Link
                        href={register()}
                        className="inline-flex min-h-11 items-center justify-center rounded-lg border border-stone-300 px-5 py-2 text-sm font-medium hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-stone-500 dark:border-stone-700 dark:hover:bg-stone-800"
                    >
                        Create an account
                    </Link>
                )}
            </nav>
        </AuthLayout>
    );
}
