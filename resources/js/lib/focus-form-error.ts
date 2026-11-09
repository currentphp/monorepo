export function focusFormError(errors: Record<string, string>) {
    const firstField = Object.keys(errors)[0];

    if (firstField) {
        document.getElementById(firstField)?.focus();
    }
}
