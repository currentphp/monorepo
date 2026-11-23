import { Link, usePage, useForm } from '@inertiajs/react';
import Actions from '@/components/current/actions';
import Content from '@/components/current/content';
import Field from '@/components/current/field';
import PlaygroundLayout from '@/layouts/playground-layout';
import type { Subject } from '@/types';
type Project = { id: number; name: string; url: string; editUrl: string };
type Props = {
    project: Pick<Project, 'id' | 'name'> | null;
    projects: Project[];
    tasks: Subject[];
};
export default function Projects({ project, projects, tasks }: Props) {
    const { name, current } = usePage().props;
    const subject = current.subject;
    const selected = projects.find((item) => item.id === project?.id);
    const title = current.title ?? name;
    return (
        <PlaygroundLayout title={title}>
            <Content>
                <div className="page-heading">
                    <h1>{title}</h1>
                    <p>
                        {current.description ??
                            'Explore your projects and their tasks.'}
                    </p>
                    {subject ? (
                        <p>
                            Viewing {subject.kind}{' '}
                            <strong>{subject.label}</strong> ({subject.kind}:
                            {subject.id}).
                        </p>
                    ) : null}
                    <Actions
                        actions={current.actions.filter(
                            (action) => action.id !== 'project.rename',
                        )}
                    />
                </div>
                {selected ? (
                    <section className="card">
                        <h2>{selected.name}</h2>
                        <p className="help">
                            {current.pageId === 'projects.edit'
                                ? 'Update the project name. Changes appear in every view.'
                                : 'View project details, manage its name, or explore its tasks.'}
                        </p>
                        <div className="actions">
                            <Link href={selected.url}>View project</Link>
                            <Link href={selected.editUrl}>Edit project</Link>
                        </div>
                        {current.pageId === 'projects.edit' ? (
                            <RenameProject
                                key={`${selected.id}:${selected.name}`}
                                project={selected}
                            />
                        ) : null}
                        {tasks.length > 0 ? (
                            <div className="project-tasks">
                                <h3>Tasks</h3>
                                <ul className="task-links">
                                    {tasks.map((task) =>
                                        task.url ? (
                                            <li key={task.id}>
                                                <Link href={task.url}>
                                                    {task.label}
                                                </Link>
                                            </li>
                                        ) : null,
                                    )}
                                </ul>
                            </div>
                        ) : null}
                    </section>
                ) : null}
                <section className="project-grid" aria-label="Projects">
                    {projects.map((item) => (
                        <Link key={item.id} href={item.url} className="card">
                            <h2>{item.name}</h2>
                            <p className="help">Explore project</p>
                        </Link>
                    ))}
                </section>
            </Content>
        </PlaygroundLayout>
    );
}
function RenameProject({ project }: { project: Project }) {
    const { current, errors } = usePage().props;
    const action = current.actions.find((item) => item.id === 'project.rename');
    const form = useForm({ name: project.name });
    if (!action) return null;
    return (
        <>
            <form
                className="rename-form"
                action={action.url}
                method="post"
                aria-busy={form.processing ? 'true' : undefined}
                onSubmit={(event) => {
                    event.preventDefault();
                    if (action.confirm && !window.confirm(action.confirm))
                        return;
                    const data = { name: form.data.name.trim() };
                    form.setData(data);
                    form.transform(() => data);
                    form.post(action.url, {
                        preserveScroll: true,
                    });
                }}
            >
                <Field
                    label="Project name"
                    name="name"
                    value={form.data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                    required
                    maxLength={100}
                    readOnly={form.processing || undefined}
                    error={errors.name}
                />
                <button
                    type="submit"
                    disabled={form.processing || !action.enabled}
                >
                    Rename
                </button>
            </form>
            {action.reason ? <p className="help">{action.reason}</p> : null}
            {action.confirm ? <p className="help">{action.confirm}</p> : null}
        </>
    );
}
