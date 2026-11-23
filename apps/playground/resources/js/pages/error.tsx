import { Link } from '@inertiajs/react';
import PageLayout from '@/layouts/page-layout';
import { home } from '@/routes';

export default function ErrorPage({
    status,
    requestId,
}: {
    status: number;
    requestId?: string;
}) {
    const messages: Record<number, [string, string]> = {
        403: [
            'Access denied',
            'This link may be invalid or expired, or you may not have permission to open this page.',
        ],
        404: ['Page not found', 'The page you requested could not be found.'],
        419: ['Your session expired', 'Reload the page and try again.'],
        429: ['Too many requests', 'Please wait a minute and try again.'],
        500: [
            'Something went wrong',
            'Please try again. If the problem continues, share the request ID with support.',
        ],
        503: ['Temporarily unavailable', 'Please try again shortly.'],
    };
    const [title, description] = messages[status] ?? messages[500];
    return (
        <PageLayout title={title} description={description}>
            {requestId && (
                <p className="text-xs break-all text-stone-600 dark:text-stone-400">
                    Request ID: {requestId}
                </p>
            )}
            <Link
                href={home()}
                className="inline-block rounded px-2 py-3 text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4"
            >
                Return home
            </Link>
        </PageLayout>
    );
}
