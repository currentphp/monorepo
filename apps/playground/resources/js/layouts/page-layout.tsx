import type { ReactNode } from 'react';
import Content from '@/components/current/content';
import PlaygroundLayout from '@/layouts/playground-layout';

type Props = { title: string; description: string; children: ReactNode };
export default function PageLayout({ title, description, children }: Props) {
    return (
        <PlaygroundLayout title={title}>
            <Content>
                <section className="card max-w-lg">
                    <div className="page-heading">
                        <h1>{title}</h1>
                        <p>{description}</p>
                    </div>
                    {children}
                </section>
            </Content>
        </PlaygroundLayout>
    );
}
