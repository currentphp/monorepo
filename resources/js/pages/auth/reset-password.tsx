import { Form } from '@inertiajs/react';
import FormField from '@/components/app/form-field';
import SubmitButton from '@/components/app/submit-button';
import AuthLayout from '@/layouts/auth-layout';
import { focusFormError } from '@/lib/focus-form-error';
import { request, update } from '@/routes/password';

type ResetPasswordProps = { email: string; token: string };

export default function ResetPassword({ email, token }: ResetPasswordProps) {
    return (
        <AuthLayout
            title="Reset your password"
            description="Choose a new password for your account."
        >
            <Form
                {...update.form()}
                resetOnError={['password', 'password_confirmation']}
                resetOnSuccess={['password', 'password_confirmation']}
                onError={focusFormError}
                className="space-y-5"
            >
                {({ errors, processing }) => (
                    <>
                        <input type="hidden" name="token" value={token} />
                        {errors.token && (
                            <p
                                id="token"
                                tabIndex={-1}
                                role="alert"
                                className="text-sm text-red-700 dark:text-red-400"
                            >
                                {errors.token}
                            </p>
                        )}
                        <FormField
                            id="email"
                            name="email"
                            type="email"
                            label="Email address"
                            autoComplete="username"
                            defaultValue={email}
                            required
                            maxLength={255}
                            error={errors.email}
                        />
                        <FormField
                            id="password"
                            name="password"
                            type="password"
                            label="New password"
                            autoComplete="new-password"
                            required
                            error={errors.password}
                        />
                        <FormField
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            label="Confirm new password"
                            autoComplete="new-password"
                            required
                            error={errors.password_confirmation}
                        />
                        <SubmitButton
                            processing={processing}
                            pendingLabel="Resetting password…"
                        >
                            Reset password
                        </SubmitButton>
                    </>
                )}
            </Form>
            <a
                href={request.url()}
                className="block rounded text-center text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
            >
                Request a new reset link
            </a>
        </AuthLayout>
    );
}
