import { Form, Link } from '@inertiajs/react';
import FormField from '@/components/app/form-field';
import SubmitButton from '@/components/app/submit-button';
import AuthLayout from '@/layouts/auth-layout';
import { focusFormError } from '@/lib/focus-form-error';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <AuthLayout
            title="Forgot password?"
            description="Enter your email to request a password reset link."
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
                {...email.form()}
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
                            autoComplete="email"
                            required
                            maxLength={255}
                            error={errors.email}
                        />
                        <SubmitButton
                            processing={processing}
                            pendingLabel="Requesting link…"
                        >
                            Send reset link
                        </SubmitButton>
                    </>
                )}
            </Form>
            <Link
                href={login()}
                className="block rounded text-center text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
            >
                Back to sign in
            </Link>
        </AuthLayout>
    );
}
