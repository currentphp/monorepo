import AppLayout from '@/layouts/app-layout';

export default function Dashboard() {
    return (
        <AppLayout title="Overview">
            <section className="space-y-3 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                <h2 className="text-lg font-semibold">Your account is ready</h2>
                <p className="text-sm leading-6 text-stone-600 dark:text-stone-400">
                    Your email is verified. There’s nothing else to manage here
                    yet.
                </p>
            </section>
        </AppLayout>
    );
}
