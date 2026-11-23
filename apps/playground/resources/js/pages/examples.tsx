import { Link } from '@inertiajs/react';
import Content from '@/components/current/content';
import PlaygroundLayout from '@/layouts/playground-layout';

export default function Examples() {
    return (
        <PlaygroundLayout title="Examples">
            <Content>
                <div className="page-heading">
                    <h1>Current, in context.</h1>
                    <p>
                        Explore how one set of page, subject, navigation, and
                        action declarations shapes an application. Each example
                        offers Inertia, Blade, Livewire, and JSON views.
                    </p>
                </div>
                <div className="project-grid">
                    <Link className="card" href="/projects">
                        <h2>Projects</h2>
                        <p className="help">
                            Browse resources and nested tasks. Compare
                            breadcrumbs, active navigation, contextual actions,
                            and fresh data across renderers.
                        </p>
                        <p>Explore projects →</p>
                    </Link>
                    <Link className="card" href="/onboarding">
                        <h2>Simulated onboarding</h2>
                        <p className="help">
                            Try a signup and workspace setup with sample data.
                            Watch navigation, subject presentation, and
                            available actions change as you progress.
                        </p>
                        <p>Try onboarding →</p>
                    </Link>
                </div>
                <p className="help">
                    No account required. The onboarding simulation creates no
                    real account and sends no email.
                </p>
            </Content>
        </PlaygroundLayout>
    );
}
