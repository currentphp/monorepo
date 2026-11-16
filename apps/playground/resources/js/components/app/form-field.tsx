import type { ComponentProps } from 'react';

type Props = ComponentProps<'input'> & {
    id: string;
    label: string;
    error?: string;
};

export default function FormField({ id, label, error, ...props }: Props) {
    return (
        <div className="space-y-2">
            <label htmlFor={id} className="block text-sm font-medium">
                {label}
            </label>
            <input
                {...props}
                id={id}
                aria-invalid={Boolean(error)}
                aria-describedby={error ? `${id}-error` : undefined}
                className="block min-h-11 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-base text-stone-950 shadow-xs outline-none placeholder:text-stone-400 focus-visible:border-stone-700 focus-visible:ring-2 focus-visible:ring-stone-500/30 disabled:opacity-60 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-100 dark:focus-visible:border-stone-400"
            />
            {error && (
                <p
                    id={`${id}-error`}
                    role="alert"
                    className="text-sm text-red-700 dark:text-red-400"
                >
                    {error}
                </p>
            )}
        </div>
    );
}
