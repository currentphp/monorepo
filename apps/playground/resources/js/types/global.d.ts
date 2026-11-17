import type { Auth } from '@/types/auth';
import type { WavyKit } from '@wavykit/inertia';

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
            wavykit: WavyKit<'primary' | 'footer'>;
            [key: string]: unknown;
        };
    }
}
