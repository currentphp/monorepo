import type { Action } from '@/types/actions';
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
            actions: Action[];
            status: string | null;
            [key: string]: unknown;
        };
    }
}
