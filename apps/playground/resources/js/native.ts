declare global {
    interface Window {
        Livewire?: {
            hook(
                name: 'morphed',
                callback: (event: { el: HTMLElement }) => void,
            ): void;
        };
    }
}
const focusFeedback = (root: ParentNode = document) =>
    root.querySelector<HTMLElement>('[data-feedback]')?.focus();
const readonlyBeforeSubmit = new WeakMap<HTMLInputElement, boolean>();
const disabledBeforeSubmit = new WeakMap<HTMLButtonElement, boolean>();

document.addEventListener('click', (event) => {
    const link =
        event.target instanceof Element
            ? event.target.closest<HTMLAnchorElement>('a[data-confirm]')
            : null;
    if (link?.dataset.confirm && !window.confirm(link.dataset.confirm))
        event.preventDefault();
});
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (
        !(form instanceof HTMLFormElement) ||
        event.defaultPrevented ||
        form.hasAttribute('wire:submit')
    )
        return;
    const message = form.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
    }
    form.setAttribute('aria-busy', 'true');
    form.querySelectorAll<HTMLInputElement>('input:not([type=hidden])').forEach(
        (input) => {
            readonlyBeforeSubmit.set(input, input.readOnly);
            input.readOnly = true;
        },
    );
    form.querySelectorAll<HTMLButtonElement>('button[type=submit]').forEach(
        (button) => {
            disabledBeforeSubmit.set(button, button.disabled);
            button.disabled = true;
        },
    );
});
window.addEventListener('pageshow', () => {
    document
        .querySelectorAll<HTMLFormElement>('form[aria-busy=true]')
        .forEach((form) => {
            form.removeAttribute('aria-busy');
            form.querySelectorAll<HTMLInputElement>('input').forEach(
                (input) => {
                    if (readonlyBeforeSubmit.has(input))
                        input.readOnly =
                            readonlyBeforeSubmit.get(input) ?? false;
                },
            );
            form.querySelectorAll<HTMLButtonElement>('button').forEach(
                (button) => {
                    if (disabledBeforeSubmit.has(button))
                        button.disabled =
                            disabledBeforeSubmit.get(button) ?? false;
                },
            );
        });
});
if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', () => focusFeedback());
else focusFeedback();
const watchLivewire = () =>
    window.Livewire?.hook('morphed', ({ el }) => focusFeedback(el));
if (window.Livewire) watchLivewire();
else document.addEventListener('livewire:init', watchLivewire, { once: true });

export {};
