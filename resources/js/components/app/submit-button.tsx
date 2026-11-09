import type { ReactNode } from 'react';

type Props = {
    processing: boolean;
    pendingLabel: string;
    children: ReactNode;
};

export default function SubmitButton({
    processing,
    pendingLabel,
    children,
}: Props) {
    return (
        <button
            type="submit"
            disabled={processing}
            aria-busy={processing}
            className="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-stone-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-stone-500 disabled:cursor-wait disabled:opacity-60 dark:bg-stone-100 dark:text-stone-950 dark:hover:bg-white"
        >
            <span role="status">{processing ? pendingLabel : children}</span>
        </button>
    );
}
