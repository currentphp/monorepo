import { Form, usePage } from '@inertiajs/react';
import FormField from '@/components/app/form-field';
import SubmitButton from '@/components/app/submit-button';
import AppLayout from '@/layouts/app-layout';
import { focusFormError } from '@/lib/focus-form-error';
import { update } from '@/routes/profile';

export default function Profile({ status }: { status?: string }) {
    const { auth } = usePage().props;

    return (
        <AppLayout title="Profile">
            <section className="max-w-xl space-y-6 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                <p className="text-sm text-stone-600 dark:text-stone-400">
                    Choose the name displayed in your account.
                </p>
                {status && (
                    <p
                        role="status"
                        className="text-sm text-green-700 dark:text-green-400"
                    >
                        {status}
                    </p>
                )}
                <Form
                    {...update.form()}
                    onError={focusFormError}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="name"
                                name="name"
                                label="Display name"
                                autoComplete="name"
                                defaultValue={auth.user?.name}
                                required
                                maxLength={255}
                                error={errors.name}
                            />
                            <SubmitButton
                                processing={processing}
                                pendingLabel="Saving…"
                            >
                                Save name
                            </SubmitButton>
                        </>
                    )}
                </Form>
                <dl className="text-sm">
                    <dt className="text-stone-600 dark:text-stone-400">
                        Verified email
                    </dt>
                    <dd className="mt-1 break-all">{auth.user?.email}</dd>
                </dl>
            </section>
        </AppLayout>
    );
}
