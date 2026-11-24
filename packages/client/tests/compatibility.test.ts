import { strict as assert } from 'node:assert';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';

const read = (path: string): string => readFileSync(new URL(path, import.meta.url), 'utf8');
const minor = (version: string): string => version.split('.').slice(0, 2).join('.');

// The client versions independently; its README's compatibility table must name
// the core's current development line for this client version.
test('the README names the current core minor for this client version', () => {
    const client = minor(JSON.parse(read('../package.json')).version);
    const root = JSON.parse(read('../../../composer.json'));
    const core = minor(root.repositories[0].options.versions['current/current'].replace('.x-dev', '.0'));

    assert.match(
        read('../README.md'),
        new RegExp(`^\\| ${client.replace('.', '\\.')} \\| ${core.replace('.', '\\.')} \\|`, 'm'),
        `README.md needs a compatibility row "| ${client} | ${core} |"`,
    );
});
