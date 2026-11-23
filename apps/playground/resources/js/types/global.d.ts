import type { Current } from '@current/inertia';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            renderingChoices: {
                label: string;
                url: string;
                current: boolean;
            }[];
            current: Current<
                'primary' | 'footer',
                'pagePresentation' | 'subjectPresentation' | 'actions'
            >;
            status: string | null;
            [key: string]: unknown;
        };
    }
}
