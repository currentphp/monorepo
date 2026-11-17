// The `subject` shared prop: WavyKit\Subjects\SubjectItem from Current::subject().

/** A subject such as a project or a task; the same in every reader. */
export type Subject = {
    kind: string;
    /** The object's route key; `kind:id` identifies the subject. */
    id: string;
    label: string;
    /** The subject's canonical page, or null when it has none. */
    url: string | null;
};
