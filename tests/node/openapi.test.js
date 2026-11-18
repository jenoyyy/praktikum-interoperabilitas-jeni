import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';

const spec = JSON.parse(
    await readFile(new URL('../../openapi.json', import.meta.url), 'utf8')
);

test('OpenAPI document declares version 3.0.3', () => {
    assert.equal(spec.openapi, '3.0.3');
});

test('OpenAPI document contains authentication endpoints', () => {
    assert.ok(spec.paths['/register']?.post);
    assert.ok(spec.paths['/login']?.post);
    assert.ok(spec.paths['/profile']?.get);
    assert.ok(spec.paths['/logout']?.post);
});

test('OpenAPI document contains all BREAD operations', () => {
    assert.ok(spec.paths['/blogs']?.get);
    assert.ok(spec.paths['/blogs']?.post);
    assert.ok(spec.paths['/blog/{id}']?.get);
    assert.ok(spec.paths['/blog/{id}']?.put);
    assert.ok(spec.paths['/blog/{id}']?.delete);
});

test('protected blog endpoints document bearer authentication', () => {
    const operations = [
        spec.paths['/blogs'].get,
        spec.paths['/blogs'].post,
        spec.paths['/blog/{id}'].get,
        spec.paths['/blog/{id}'].put,
        spec.paths['/blog/{id}'].delete,
    ];

    for (const operation of operations) {
        assert.ok(operation.security?.some((scheme) => scheme.bearerAuth));
    }
});

test('BREAD endpoints document their required token abilities', () => {
    assert.equal(spec.paths['/blogs'].get['x-required-ability'], 'blogs:read');
    assert.equal(spec.paths['/blogs'].post['x-required-ability'], 'blogs:create');
    assert.equal(spec.paths['/blog/{id}'].get['x-required-ability'], 'blogs:read');
    assert.equal(spec.paths['/blog/{id}'].put['x-required-ability'], 'blogs:update');
    assert.equal(spec.paths['/blog/{id}'].delete['x-required-ability'], 'blogs:delete');
});
