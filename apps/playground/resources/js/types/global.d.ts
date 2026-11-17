import type { Auth } from '@/types/auth';
import type { Current } from '@current/inertia';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            current: Current<'primary' | 'footer'>;
            [key: string]: unknown;
        };
    }
}
