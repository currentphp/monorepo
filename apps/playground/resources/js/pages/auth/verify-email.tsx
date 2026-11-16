import { Form, usePage } from '@inertiajs/react';
import SubmitButton from '@/components/app/submit-button';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { auth } = usePage().props;

    return (
        <AuthLayout
            title="Verify your email"
            description="Check your inbox and follow the verification link to continue."
        >
            <p className="text-sm break-all">
                We sent a link to {auth.user?.email}.
            </p>
            {status === 'verification-link-sent' && (
                <p
                    role="status"
                    className="text-sm text-green-700 dark:text-green-400"
                >
                    A new verification email has been requested. Please check
                    your inbox shortly.
                </p>
            )}
            <Form {...send.form()}>
                {({ processing, errors }) => (
                    <div className="space-y-3">
                        {errors.email && (
                            <p
                                role="alert"
                                className="text-sm text-red-700 dark:text-red-400"
                            >
                                {errors.email}
                            </p>
                        )}
                        <SubmitButton
                            processing={processing}
                            pendingLabel="Requesting email…"
                        >
                            Resend verification email
                        </SubmitButton>
                    </div>
                )}
            </Form>
            <Form {...logout.form()}>
                {({ processing }) => (
                    <button
                        disabled={processing}
                        className="min-h-11 rounded text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
                    >
                        Sign out
                    </button>
                )}
            </Form>
        </AuthLayout>
    );
}
