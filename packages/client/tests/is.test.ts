import { strict as assert } from 'node:assert';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { is } from '../src/is.ts';

type Case = { pageId: string | null; patterns: string[]; expected: boolean };

// The core's IsParityTest writes PHP's answers here (`composer test:types`).
const table = new URL('./parity/is-cases.json', import.meta.url);

test('is() matches PHP Current::is() for every shared case', () => {
    const cases: Case[] = JSON.parse(readFileSync(table, 'utf8'));
    assert.ok(cases.length > 0, 'no parity cases: run composer test:types from the monorepo');

    for (const { pageId, patterns, expected } of cases) {
        assert.equal(
            is({ pageId }, ...patterns),
            expected,
            `is({ pageId: ${JSON.stringify(pageId)} }, ${patterns.map((p) => JSON.stringify(p)).join(', ')})`,
        );
    }
});
