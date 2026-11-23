import type { InputHTMLAttributes } from 'react';
type Props = InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    name: string;
    error?: string;
};
export default function Field({
    label,
    name,
    error,
    type = 'text',
    ...props
}: Props) {
    const id = `field-${name}`;
    return (
        <div className="field">
            <label htmlFor={id}>{label}</label>
            <input
                {...props}
                id={id}
                name={name}
                type={type}
                aria-invalid={error ? 'true' : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
            />
            {error ? (
                <p id={`${id}-error`} className="error">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
