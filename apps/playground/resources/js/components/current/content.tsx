import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Breadcrumbs from './breadcrumbs';
import Feedback from './feedback';
export default function Content({ children }: { children: ReactNode }) {
    const { current } = usePage().props;
    return (
        <div data-example-content="">
            <Feedback />
            <Breadcrumbs items={current.breadcrumbs} />
            {children}
        </div>
    );
}
