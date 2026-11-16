import { Form, Link } from '@inertiajs/react';
import FormField from '@/components/app/form-field';
import SubmitButton from '@/components/app/submit-button';
import AuthLayout from '@/layouts/auth-layout';
import { focusFormError } from '@/lib/focus-form-error';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    return (
        <AuthLayout
            title="Create your account"
            description="A few details to get started."
        >
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                resetOnError={['password', 'password_confirmation']}
                onError={focusFormError}
                className="space-y-5"
            >
                {({ errors, processing }) => (
                    <>
                        <FormField
                            id="name"
                            name="name"
                            label="Name"
                            autoComplete="name"
                            required
                            maxLength={255}
                            error={errors.name}
                        />
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
                            autoComplete="new-password"
                            required
                            error={errors.password}
                        />
                        <FormField
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            label="Confirm password"
                            autoComplete="new-password"
                            required
                            error={errors.password_confirmation}
                        />
                        <SubmitButton
                            processing={processing}
                            pendingLabel="Creating account…"
                        >
                            Create account
                        </SubmitButton>
                    </>
                )}
            </Form>
            <p className="text-center text-sm text-stone-600 dark:text-stone-400">
                Already have an account?{' '}
                <Link
                    href={login()}
                    className="rounded font-medium text-stone-950 underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 dark:text-stone-100"
                >
                    Sign in
                </Link>
            </p>
        </AuthLayout>
    );
}
