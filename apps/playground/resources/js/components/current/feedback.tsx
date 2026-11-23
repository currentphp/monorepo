import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
export default function Feedback() {
    const { status, errors } = usePage().props;
    const ref = useRef<HTMLDivElement>(null);
    useEffect(() => {
        ref.current?.focus();
    }, [status, errors]);
    const messages = Object.values(errors ?? {}).filter(
        (value) => typeof value === 'string',
    );
    if (!status && messages.length === 0) return null;
    return (
        <div data-feedback="" tabIndex={-1} ref={ref}>
            {status ? (
                <p className="notice" role="status">
                    {status}
                </p>
            ) : null}
            {messages.length > 0 ? (
                <div className="notice error" role="alert">
                    {messages.map((message, index) => (
                        <p key={index}>{message}</p>
                    ))}
                </div>
            ) : null}
        </div>
    );
}
