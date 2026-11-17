// WK-019 prototype: the `subject` shared prop (App\WavyKit\Subjects\Subject).

/** A subject such as a project or a task; the same in every reader. */
export type Subject = {
    kind: string;
    /** The object's route key; `kind:id` identifies the subject. */
    id: string;
    label: string;
    /** The subject's canonical page, or null when it has none. */
    url: string | null;
};
