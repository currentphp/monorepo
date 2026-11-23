import { useForm, usePage } from '@inertiajs/react';
import Content from '@/components/current/content';
import Field from '@/components/current/field';
import Actions from '@/components/current/actions';
import Menu from '@/components/current/menu';
import PlaygroundLayout from '@/layouts/playground-layout';
import type { MenuItem } from '@/types';

type Props = {
    step: string;
    profile: { name: string; email: string } | null;
    workspaceName: string;
    complete: boolean;
    steps: MenuItem[];
    resetUrl: string;
};
export default function Onboarding(props: Props) {
    return (
        <OnboardingFlow
            key={`${props.step}:${props.profile?.name ?? ''}:${props.workspaceName}:${props.complete}`}
            {...props}
        />
    );
}

function OnboardingFlow({
    step,
    profile,
    workspaceName,
    complete,
    steps,
    resetUrl,
}: Props) {
    const { current, errors } = usePage().props;
    const form = useForm({
        name: profile?.name ?? 'Alex Taylor',
        email: profile?.email ?? 'alex@example.test',
        workspaceName,
    });
    const submit = current.actions.find((action) => action.method === 'POST');
    const title = current.title ?? 'Onboarding';
    return (
        <PlaygroundLayout title={title}>
            <Content>
                <div className="page-heading">
                    <h1>{title}</h1>
                    <p>
                        {current.description ??
                            'A session-only signup simulation. Use the sample values; no account is created and no email is sent.'}
                    </p>
                </div>
                <nav aria-label="Onboarding steps" className="card">
                    <Menu items={steps} />
                </nav>
                <section className="card">
                    {step === 'intro' ? (
                        <>
                            <h2>A small flow with changing context.</h2>
                            <p>
                                Complete a demo profile, prepare a workspace,
                                and review the result. Watch steps appear and
                                action availability change. Reset whenever you
                                want to start again.
                            </p>
                        </>
                    ) : null}
                    {submit ? (
                        <form
                            className="task-form"
                            action={submit.url}
                            method="post"
                            aria-busy={form.processing ? 'true' : undefined}
                            onSubmit={(event) => {
                                event.preventDefault();
                                if (
                                    submit.confirm &&
                                    !window.confirm(submit.confirm)
                                )
                                    return;
                                const data = {
                                    name: form.data.name.trim(),
                                    email: form.data.email.trim(),
                                    workspaceName:
                                        form.data.workspaceName.trim(),
                                };
                                form.setData(data);
                                form.transform(() => data);
                                form.post(submit.url, {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            {step === 'profile' ? (
                                <>
                                    <Field
                                        label="Demo name"
                                        name="name"
                                        value={form.data.name}
                                        onChange={(event) =>
                                            form.setData(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                        required
                                        maxLength={80}
                                        autoComplete="off"
                                        readOnly={form.processing || undefined}
                                        error={errors.name}
                                    />
                                    <Field
                                        label="Example email"
                                        name="email"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(event) =>
                                            form.setData(
                                                'email',
                                                event.target.value,
                                            )
                                        }
                                        required
                                        maxLength={120}
                                        autoComplete="off"
                                        readOnly={form.processing || undefined}
                                        error={errors.email}
                                    />
                                </>
                            ) : null}
                            {step === 'workspace' ? (
                                <Field
                                    label="Workspace name"
                                    name="workspaceName"
                                    value={form.data.workspaceName}
                                    onChange={(event) =>
                                        form.setData(
                                            'workspaceName',
                                            event.target.value,
                                        )
                                    }
                                    required
                                    maxLength={80}
                                    autoComplete="off"
                                    readOnly={form.processing || undefined}
                                    error={errors.workspaceName}
                                />
                            ) : null}
                            {step === 'review' ? (
                                <>
                                    <h2>{current.subject?.title}</h2>
                                    <p className="help">
                                        {profile
                                            ? `Prepared for ${profile.name} (${profile.email}).`
                                            : 'Complete your demo profile first.'}
                                    </p>
                                    <p>
                                        {complete
                                            ? 'Your simulation is complete. Open Projects or reset to try another path.'
                                            : 'Finishing unlocks the Projects action below.'}
                                    </p>
                                </>
                            ) : null}
                            <button
                                type="submit"
                                disabled={form.processing || !submit.enabled}
                            >
                                {submit.label}
                            </button>
                            {submit.reason ? (
                                <p className="help">{submit.reason}</p>
                            ) : null}
                        </form>
                    ) : null}
                    <Actions
                        actions={current.actions.filter(
                            (action) => action.method === 'GET',
                        )}
                    />
                </section>
                <ResetSimulation url={resetUrl} />
            </Content>
        </PlaygroundLayout>
    );
}

function ResetSimulation({ url }: { url: string }) {
    const form = useForm({});
    return (
        <form
            action={url}
            method="post"
            aria-busy={form.processing ? 'true' : undefined}
            onSubmit={(event) => {
                event.preventDefault();
                form.post(url, { preserveScroll: true });
            }}
        >
            <button type="submit" disabled={form.processing}>
                Reset simulation
            </button>
        </form>
    );
}
