import { Link, useForm, usePage } from '@inertiajs/react';
import Actions from '@/components/current/actions';
import Content from '@/components/current/content';
import Field from '@/components/current/field';
import PlaygroundLayout from '@/layouts/playground-layout';
import type { Action, Subject } from '@/types';
type Row = { subject: Subject; actions: Action[] };
type Props = {
    rows: Row[];
    form: { url: string; method: 'post' | 'put'; value: string } | null;
};
export default function Tasks(props: Props) {
    return <TaskPage key={props.form?.url ?? 'list'} {...props} />;
}
function TaskPage({ rows, form }: Props) {
    const { name, current, errors } = usePage().props;
    const state = useForm({ title: form?.value ?? '' });
    const title = current.title ?? name;
    return (
        <PlaygroundLayout title={title}>
            <Content>
                <div className="page-heading">
                    <h1>{title}</h1>
                    <Actions actions={current.actions} />
                </div>
                {form ? (
                    <form
                        action={form.url}
                        method="post"
                        className="card task-form"
                        aria-busy={state.processing ? 'true' : undefined}
                        onSubmit={(event) => {
                            event.preventDefault();
                            const data = { title: state.data.title.trim() };
                            state.setData(data);
                            state.transform(() => data);
                            state.submit(form.method, form.url, {
                                preserveScroll: true,
                            });
                        }}
                    >
                        <Field
                            label="Title"
                            name="title"
                            value={state.data.title}
                            onChange={(event) =>
                                state.setData('title', event.target.value)
                            }
                            required
                            maxLength={100}
                            readOnly={state.processing || undefined}
                            error={errors.title}
                        />
                        <button type="submit" disabled={state.processing}>
                            Save
                        </button>
                    </form>
                ) : (
                    <ul className="card task-links">
                        {rows.map((row) => (
                            <li key={row.subject.id} className="task-row">
                                {row.subject.url ? (
                                    <Link href={row.subject.url}>
                                        {row.subject.label}
                                    </Link>
                                ) : (
                                    <span>{row.subject.label}</span>
                                )}
                                <Actions actions={row.actions} />
                            </li>
                        ))}
                    </ul>
                )}
            </Content>
        </PlaygroundLayout>
    );
}
