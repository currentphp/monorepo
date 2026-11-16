import { Form, Link, usePage } from '@inertiajs/react';
import FormField from '@/components/app/form-field';
import SubmitButton from '@/components/app/submit-button';
import AuthLayout from '@/layouts/auth-layout';
import { focusFormError } from '@/lib/focus-form-error';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

export default function Login({ status }: { status?: string }) {
    const { auth } = usePage().props;

    return (
        <AuthLayout
            title="Sign in"
            description="Enter your email and password to access your account."
        >
            {status && (
                <p
                    role="status"
                    className="text-sm text-green-700 dark:text-green-400"
                >
                    {status}
                </p>
            )}
            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                resetOnError={['password']}
                onError={focusFormError}
                className="space-y-5"
            >
                {({ errors, processing }) => (
                    <>
                        <FormField
                            id="email"
                            name="email"
                            type="email"
                            label="Email address"
                            autoComplete="username"
                            required
                            maxLength={255}
                            error={errors.email}
                        />
                        <FormField
                            id="password"
                            name="password"
                            type="password"
                            label="Password"
                            autoComplete="current-password"
                            required
                            error={errors.password}
                        />
                        <label className="flex min-h-11 items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                className="size-4 rounded border-stone-300 accent-stone-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-stone-500 dark:accent-stone-100"
                            />
                            Keep me signed in
                        </label>
                        <SubmitButton
                            processing={processing}
                            pendingLabel="Signing in…"
                        >
                            Sign in
                        </SubmitButton>
                    </>
                )}
            </Form>
            <Link
                href={request()}
                className="block rounded text-center text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
            >
                Forgot password?
            </Link>
            {auth.canRegister && (
                <p className="text-center text-sm text-stone-600 dark:text-stone-400">
                    New here?{' '}
                    <Link
                        href={register()}
                        className="rounded font-medium text-stone-950 underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 dark:text-stone-100"
                    >
                        Create an account
                    </Link>
                </p>
            )}
        </AuthLayout>
    );
}
