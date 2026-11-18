// Theme toggle: follows the system until the reader chooses.
const root = document.documentElement;

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const dark = !root.classList.contains('dark');
        root.classList.toggle('dark', dark);
        try {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        } catch {
            // Storage may be unavailable; the choice then lasts for this page.
        }
    });
});

// Copy buttons on code blocks.
document.querySelectorAll('.prose pre.phiki').forEach((pre) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'code-block';
    pre.replaceWith(wrapper);
    wrapper.append(pre);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'copy-button';
    button.textContent = 'Copy';
    button.addEventListener('click', async () => {
        await navigator.clipboard.writeText(pre.innerText);
        button.textContent = 'Copied';
        setTimeout(() => (button.textContent = 'Copy'), 1500);
    });
    wrapper.append(button);
});

// Search, once Pagefind has indexed the exported site.
const search = document.getElementById('search');

if (search && window.PagefindUI) {
    new window.PagefindUI({ element: search, showSubResults: true, resetStyles: false });
}
