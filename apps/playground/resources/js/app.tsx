import { createInertiaApp } from '@inertiajs/react';

const appName =
    (typeof document !== 'undefined'
        ? document
              .querySelector('meta[name="application-name"]')
              ?.getAttribute('content')
        : null) ?? 'Current Examples';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: false,
});
