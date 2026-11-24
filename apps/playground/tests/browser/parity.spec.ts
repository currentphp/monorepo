import { test, expect, type Page, type BrowserContext } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';

const modes = ['inertia', 'blade', 'livewire'] as const;
type Mode = (typeof modes)[number];
const database = resolve('tests/http/.runtime/playground.sqlite');
function projects(readonly = false) {
    execFileSync('python3', [
        '-c',
        `import sqlite3\nwith sqlite3.connect(${JSON.stringify(database)}) as db:\n db.execute("UPDATE projects SET name = CASE id WHEN 42 THEN 'Apollo' ELSE 'Artemis' END, is_visible = 1, is_editable = ?", (${readonly ? 0 : 1},))`,
    ]);
}
async function visit(page: Page, path: string, mode: Mode) {
    await page.goto(`${path}${path.includes('?') ? '&' : '?'}view=${mode}`);
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
}
async function capture(page: Page) {
    // These are transport differences, not presentation exclusions. All other
    // elements, attributes, values, semantics and content remain in the contract.
    return page.locator('.shell').evaluate((root) => {
        function url(value: string) {
            const parsed = new URL(value, location.href);
            if (parsed.origin !== location.origin) return parsed.toString();
            parsed.searchParams.delete('view');
            return `${parsed.pathname}${parsed.search}${parsed.hash}`;
        }
        function node(element: Element): unknown {
            if (
                element.matches(
                    '[data-renderer-probe], script, input[name="_token"], input[name="_method"]',
                )
            )
                return null;
            const attrs = Object.fromEntries(
                Array.from(element.attributes)
                    .filter(
                        ({ name }) =>
                            !name.startsWith('wire:') &&
                            !name.startsWith('x-') &&
                            name !== 'data-confirm' &&
                            name !== 'value',
                    )
                    .filter(
                        ({ name }) =>
                            !(
                                name === 'aria-current' &&
                                element.closest('.rendering-switcher')
                            ),
                    )
                    .map(({ name, value }) => [
                        name,
                        ['href', 'action'].includes(name) ? url(value) : value,
                    ])
                    .sort(([a], [b]) => a.localeCompare(b)),
            );
            if (element instanceof HTMLInputElement) {
                attrs.value = element.value;
                if (element.disabled) attrs.disabled = '';
                if (element.readOnly) attrs.readonly = '';
            }
            if (element instanceof HTMLButtonElement && element.disabled)
                attrs.disabled = '';
            // Whitespace text nodes vary between JSX and Blade formatting; text
            // itself is compared as words within each actual text node.
            const children: unknown[] = [];
            let text = '';
            const flush = () => {
                const value = text
                    .replace(/[ \t\r\n\f]+/g, ' ')
                    .replace(/^ | $/g, '');
                if (value) children.push(value);
                text = '';
            };
            for (const child of element.childNodes) {
                if (child instanceof Element) {
                    flush();
                    const result = node(child);
                    if (result !== null) children.push(result);
                } else if (child.nodeType === Node.TEXT_NODE)
                    text += child.textContent ?? '';
            }
            flush();
            return { tag: element.tagName.toLowerCase(), attrs, children };
        }
        return node(root);
    });
}
async function state(page: Page, path: string, mode: Mode, scenario: string) {
    if (scenario.startsWith('onboarding-')) {
        await visit(page, '/onboarding/profile', mode);
        if (scenario !== 'onboarding-error') {
            const token = await page
                .locator('meta[name=csrf-token]')
                .getAttribute('content');
            await page.request.post('/onboarding/profile', {
                form: {
                    _token: token ?? '',
                    name: 'Alex Taylor',
                    email: 'alex@example.test',
                },
            });
            if (scenario !== 'onboarding-workspace')
                await page.request.post('/onboarding/workspace', {
                    form: { _token: token ?? '', workspaceName: 'Acme studio' },
                });
            if (scenario === 'onboarding-complete')
                await page.request.post('/onboarding/workspaces/demo/finish', {
                    form: { _token: token ?? '' },
                });
        }
    }
    await visit(page, path, mode);
    if (scenario.endsWith('-error')) {
        const field = page.getByRole('textbox').first();
        page.on('dialog', (dialog) => dialog.accept());
        await field.fill('   ');
        // Wait for the submission's own response, whichever transport sends it (a
        // native form post, Inertia, or a Livewire update), so a slow server
        // response cannot use up the alert assertion's timeout. Track the
        // request rather than responses: a redirected post's final response
        // is a GET.
        const sent = page.waitForRequest(
            (request) => request.method() !== 'GET',
        );
        await page
            .locator('form')
            .filter({ has: field })
            .getByRole('button', { name: /Continue|Rename|Save/ })
            .click();
        await (await sent).response();
        await page.waitForLoadState('networkidle');
        await expect(page.getByRole('alert')).toBeVisible();
        await expect(page.locator('[data-feedback]')).toBeFocused();
    }
}
const cases = [
    ['projects', '/projects'],
    ['project', '/projects/42'],
    ['project-edit', '/projects/42/edit'],
    ['project-readonly', '/projects/42/edit'],
    ['project-error', '/projects/42/edit'],
    ['tasks', '/projects/42/tasks'],
    ['task', '/projects/42/tasks/1'],
    ['task-create', '/projects/42/tasks/create'],
    ['task-edit', '/projects/42/tasks/1/edit'],
    ['task-error', '/projects/42/tasks/create'],
    ['intro', '/onboarding'],
    ['profile', '/onboarding/profile'],
    ['workspace-blocked', '/onboarding/workspace'],
    ['review-blocked', '/onboarding/workspaces/demo'],
    ['onboarding-error', '/onboarding/profile'],
    ['onboarding-workspace', '/onboarding/workspace'],
    ['onboarding-review', '/onboarding/workspaces/demo'],
    ['onboarding-complete', '/onboarding/workspaces/demo'],
] as const;
for (const width of [1280, 390]) {
    for (const [scenario, path] of cases) {
        test(`${scenario} at ${width}px: semantic DOM and visuals match`, async ({
            browser,
        }, info) => {
            projects(scenario === 'project-readonly');
            let reference: unknown;
            for (const mode of modes) {
                const context: BrowserContext = await browser.newContext({
                    viewport: { width, height: 1000 },
                });
                try {
                    const page = await context.newPage();
                    await state(page, path, mode, scenario);
                    const selected = page
                        .getByRole('navigation', { name: 'Rendering choices' })
                        .getByRole('link', {
                            name:
                                mode === 'inertia'
                                    ? 'Inertia'
                                    : mode === 'blade'
                                      ? 'Blade'
                                      : 'Livewire',
                            exact: true,
                        });
                    await expect(selected).toHaveAttribute(
                        'aria-current',
                        'page',
                    );
                    const dom = await capture(page);
                    if (mode === 'inertia') reference = dom;
                    else expect(dom, `${mode} semantic DOM`).toEqual(reference);
                    await page.evaluate(() => {
                        if (document.activeElement instanceof HTMLElement)
                            document.activeElement.blur();
                    });
                    await page.mouse.move(0, 0);
                    await page.addStyleTag({
                        content:
                            '.rendering-switcher a[aria-current=page] { background: #fff !important; border-color: #d6dfd5 !important; font-weight: 400 !important; }',
                    });
                    const image = await page.screenshot({
                        animations: 'disabled',
                        caret: 'hide',
                        fullPage: true,
                    });
                    if (mode === 'inertia') {
                        const file = info.snapshotPath('inertia-reference.png');
                        await mkdir(dirname(file), { recursive: true });
                        await writeFile(file, image);
                    } else
                        expect(
                            image,
                            `${mode} visual rendering`,
                        ).toMatchSnapshot('inertia-reference.png', {
                            maxDiffPixels: 0,
                        });
                    expect(
                        await page.evaluate(
                            () =>
                                document.documentElement.scrollWidth <=
                                innerWidth,
                        ),
                    ).toBe(true);
                } finally {
                    await context.close();
                }
            }
        });
    }
}

for (const mode of modes) {
    test(`${mode}: pending forms expose the same locked controls`, async ({
        page,
    }) => {
        projects();
        await visit(page, '/onboarding/profile', mode);
        let release = () => {};
        let began = () => {};
        const waiting = new Promise<void>((resolve) => {
            release = resolve;
        });
        const started = new Promise<void>((resolve) => {
            began = resolve;
        });
        let pendingState: unknown;
        await page.exposeBinding(
            'recordParityPending',
            (_source, value: unknown) => {
                pendingState = value;
            },
        );
        await page.route('**/*', async (route) => {
            const request = route.request();
            if (
                request.method() === 'POST' &&
                (new URL(request.url()).pathname === '/onboarding/profile' ||
                    request.headers()['x-livewire'])
            ) {
                began();
                await waiting;
            }
            await route.continue();
        });
        try {
            const form = page.locator('form').filter({
                has: page.getByRole('textbox', { name: 'Demo name' }),
            });
            await form.evaluate((element: HTMLFormElement) => {
                const record: (value: unknown) => Promise<void> = Reflect.get(
                    window,
                    'recordParityPending',
                );
                const read = () => {
                    void record({
                        busy: element.getAttribute('aria-busy'),
                        disabled:
                            element.querySelector<HTMLButtonElement>('button')
                                ?.disabled,
                        nameReadonly:
                            element.querySelector<HTMLInputElement>(
                                'input[name=name]',
                            )?.readOnly,
                        emailReadonly:
                            element.querySelector<HTMLInputElement>(
                                'input[name=email]',
                            )?.readOnly,
                    });
                };
                new MutationObserver(read).observe(element, {
                    attributes: true,
                    subtree: true,
                    attributeFilter: ['aria-busy', 'disabled', 'readonly'],
                });
                // Observe after application submit handlers, before a native
                // navigation makes its previous document unavailable to locators.
                document.addEventListener('submit', (event) => {
                    if (event.target === element) read();
                });
                element.requestSubmit();
            });
            await started;
            await expect
                .poll(() => pendingState)
                .toEqual({
                    busy: 'true',
                    disabled: true,
                    nameReadonly: true,
                    emailReadonly: true,
                });
        } finally {
            release();
        }
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(
            'Prepare a workspace for Alex Taylor',
        );
    });

    test(`${mode}: rename recovers from validation with consistent feedback, focus and title`, async ({
        page,
    }) => {
        projects();
        await visit(page, '/projects/42/edit', mode);
        page.on('dialog', (dialog) => dialog.accept());
        const name = page.getByRole('textbox', { name: 'Project name' });
        await name.fill('   ');
        await page.getByRole('button', { name: 'Rename', exact: true }).click();
        await expect(page.getByRole('alert')).toBeVisible();
        await expect(name).toHaveAttribute('aria-invalid', 'true');
        await name.fill('Shared result');
        await page.getByRole('button', { name: 'Rename', exact: true }).click();
        await expect(page.getByRole('alert')).toHaveCount(0);
        await expect(name).not.toHaveAttribute('aria-invalid', 'true');
        await expect(page).toHaveTitle('Edit Shared result - Current Examples');
        await expect(page.getByRole('status')).toHaveText('Project renamed.');
        await expect(page.locator('[data-feedback]')).toBeFocused();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(
            'Edit Shared result',
        );
        await expect(
            page.getByRole('textbox', { name: 'Project name' }),
        ).toHaveValue('Shared result');
        const token = await page
            .locator('meta[name=csrf-token]')
            .getAttribute('content');
        await page.request.post('/projects/42/rename', {
            form: { _token: token ?? '', name: 'Apollo' },
        });
    });

    test(`${mode}: onboarding validates, confirms, completes and resets`, async ({
        page,
    }) => {
        projects();
        await visit(page, '/onboarding', mode);
        await page
            .getByRole('button', { name: 'Try simulated signup' })
            .or(page.getByRole('link', { name: 'Try simulated signup' }))
            .click();
        await expect(
            page.getByRole('textbox', { name: 'Demo name' }),
        ).toHaveValue('Alex Taylor');
        await page
            .getByRole('button', { name: 'Continue to workspace' })
            .click();
        await page
            .getByRole('textbox', { name: 'Workspace name' })
            .fill('Shared studio');
        await page.getByRole('button', { name: 'Review workspace' }).click();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(
            'Review Shared studio',
        );
        const finish = page.getByRole('button', { name: 'Finish simulation' });
        page.once('dialog', async (dialog) => {
            expect(dialog.message()).toBe('Finish setup for Shared studio?');
            await dialog.dismiss();
        });
        await finish.click();
        await expect(finish).toBeEnabled();
        await expect(
            page.getByRole('link', { name: 'Open Projects' }),
        ).toHaveCount(0);
        page.once('dialog', async (dialog) => {
            expect(dialog.message()).toBe('Finish setup for Shared studio?');
            await dialog.accept();
        });
        await finish.click();
        await expect(page.getByRole('status')).toHaveText(
            'Simulation complete. No account was created.',
        );
        await expect(page.locator('[data-feedback]')).toBeFocused();
        await expect(
            page.getByRole('link', { name: 'Open Projects' }),
        ).toBeVisible();
        await expect(finish).toBeDisabled();
        await page.getByRole('button', { name: 'Reset simulation' }).click();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(
            'Try simulated onboarding',
        );
        await expect(page.getByRole('status')).toHaveText(
            'Onboarding simulation reset.',
        );
        await expect(page.locator('[data-feedback]')).toBeFocused();
    });
}

test('keyboard navigation order is identical in all three renderers', async ({
    browser,
}) => {
    let expected: unknown;
    for (const mode of modes) {
        const context = await browser.newContext();
        try {
            const page = await context.newPage();
            await visit(page, '/onboarding/profile', mode);
            const order = [];
            for (let i = 0; i < 17; i++) {
                await page.keyboard.press('Tab');
                order.push(
                    await page.evaluate(() => {
                        const el = document.activeElement;
                        if (!(el instanceof HTMLElement)) return null;
                        const href = el.getAttribute('href');
                        const url = href ? new URL(href, location.href) : null;
                        url?.searchParams.delete('view');
                        return {
                            tag: el.tagName,
                            id: el.id,
                            name: el.getAttribute('name'),
                            text: el.innerText.trim(),
                            href: url
                                ? `${url.pathname}${url.search}${url.hash}`
                                : null,
                        };
                    }),
                );
            }
            if (mode === 'inertia') expected = order;
            else expect(order, `${mode} keyboard order`).toEqual(expected);
        } finally {
            await context.close();
        }
    }
});

for (const mode of modes) {
    test(`${mode}: shared action controls preserve denial and confirmation behavior`, async ({
        page,
    }) => {
        projects();
        await visit(page, '/projects/42/tasks', mode);
        const protectedRow = page.locator('.task-row').filter({
            has: page.getByRole('link', {
                name: 'Write release notes',
                exact: true,
            }),
        });
        await expect(
            protectedRow.getByRole('button', { name: 'Archive', exact: true }),
        ).toBeDisabled();
        await expect(protectedRow).toContainText(
            'This demo task is protected from archiving.',
        );
        const row = page.locator('.task-row').filter({
            has: page.getByRole('link', { name: 'Fix login', exact: true }),
        });
        const archive = row.getByRole('button', {
            name: 'Archive',
            exact: true,
        });
        page.once('dialog', async (dialog) => {
            expect(dialog.message()).toBe('Archive “Fix login”?');
            await dialog.dismiss();
        });
        await archive.click();
        await expect(archive).toBeEnabled();
        await expect(archive).toBeFocused();
        page.once('dialog', async (dialog) => {
            expect(dialog.message()).toBe('Archive “Fix login”?');
            await dialog.accept();
        });
        await archive.click();
        await expect(page.getByRole('status')).toHaveText(
            'Demo: “Fix login” was not archived.',
        );
        await expect(page.locator('[data-feedback]')).toBeFocused();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(
            'Tasks',
        );
    });
}
