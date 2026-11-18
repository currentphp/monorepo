/** Prototype (WK-008): an action as the app's own `actions` prop shares it. */
export type Action = {
    /** `task.archive` or `projects.tasks.create`. */
    id: string;
    name: string;
    label: string;
    url: string;
    method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    icon: string | null;
    destructive: boolean;
    enabled: boolean;
    /** Why a disabled action is disabled, when known. */
    reason: string | null;
    /** Text for a confirmation dialog before the request. */
    confirm: string | null;
};
