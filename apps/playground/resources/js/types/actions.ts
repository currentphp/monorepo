/** An action from Current\Actions, as the app's own `actions` prop shares it. */
export type Action = {
    /** `task.archive` or `projects.tasks.create`. */
    id: string;
    name: string;
    label: string;
    url: string;
    method: 'GET' | 'HEAD' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    icon: string | null;
    destructive: boolean;
    enabled: boolean;
    /** Why a disabled action is disabled, when known. */
    reason: string | null;
    /** Text for a confirmation dialog before the request. */
    confirm: string | null;
    /** A GET action whose target is the current page, such as Edit on the edit page. */
    current: boolean;
};
