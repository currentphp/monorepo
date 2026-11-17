import type { Auth } from '@/types/auth';
import type { Subject } from '@/types/subject';
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
            subject: Subject | null;
            [key: string]: unknown;
        };
    }
}
