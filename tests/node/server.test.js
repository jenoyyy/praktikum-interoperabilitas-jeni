import assert from 'node:assert/strict';
import { afterEach, beforeEach, test } from 'node:test';
import { createApp } from '../../server.js';

let server;
let baseUrl;

beforeEach(async () => {
    server = createApp().listen(0, '127.0.0.1');
    await new Promise((resolve, reject) => {
        server.once('listening', resolve);
        server.once('error', reject);
    });

    baseUrl = `http://127.0.0.1:${server.address().port}`;
});

afterEach(async () => {
    if (!server) return;

    await new Promise((resolve, reject) => {
        server.close((error) => error ? reject(error) : resolve());
    });

    server = undefined;
});

async function request(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...options.headers,
    };

    return fetch(`${baseUrl}${path}`, { ...options, headers });
}

function jsonRequest(method, payload) {
    return {
        method,
        body: JSON.stringify(payload),
    };
}

test('GET / menampilkan status server', async () => {
    const response = await request('/');
    const body = await response.json();

    assert.equal(response.status, 200);
    assert.match(body.message, /berhasil berjalan/);
});

test('GET /produk menampilkan daftar produk', async () => {
    const response = await request('/produk');
    const body = await response.json();

    assert.equal(response.status, 200);
    assert.equal(body.length, 5);
    assert.equal(body[0].id, 'P001');
});

test('GET /produk/:id menampilkan produk yang tersedia', async () => {
    const response = await request('/produk/P001');
    const body = await response.json();

    assert.equal(response.status, 200);
    assert.equal(body.nama, 'Laptop');
    assert.equal(body.harga, 7500000);
});

test('GET /produk/:id mengembalikan 404 untuk produk yang tidak ada', async () => {
    const response = await request('/produk/UNKNOWN');
    const body = await response.json();

    assert.equal(response.status, 404);
    assert.equal(body.message, 'Produk tidak ditemukan');
});

test('POST /produk menambahkan produk baru', async () => {
    const payload = { id: 'TST001', nama: 'Webcam', harga: 500000, stok: 4 };
    const response = await request('/produk', jsonRequest('POST', payload));
    const body = await response.json();

    assert.equal(response.status, 201);
    assert.deepEqual(body.data, payload);

    const detail = await request('/produk/TST001');
    assert.equal(detail.status, 200);
    assert.deepEqual(await detail.json(), payload);
});

test('POST /produk menolak data yang tidak valid', async () => {
    const response = await request('/produk', jsonRequest('POST', {
        id: 'TST002',
        nama: '',
        harga: -1,
        stok: 1.5,
    }));
    const body = await response.json();

    assert.equal(response.status, 422);
    assert.match(body.message, /wajib diisi/);
});

test('POST /produk menolak ID produk yang sudah digunakan', async () => {
    const response = await request('/produk', jsonRequest('POST', {
        id: 'P001',
        nama: 'Produk Duplikat',
        harga: 1000,
        stok: 1,
    }));
    const body = await response.json();

    assert.equal(response.status, 409);
    assert.match(body.message, /sudah digunakan/);
});

test('PATCH /produk/:id memperbarui field produk yang dikirim', async () => {
    const response = await request('/produk/P002', jsonRequest('PATCH', {
        nama: 'Mouse Wireless',
        harga: 175000,
    }));
    const body = await response.json();

    assert.equal(response.status, 200);
    assert.equal(body.data.nama, 'Mouse Wireless');
    assert.equal(body.data.harga, 175000);
    assert.equal(body.data.stok, 20);
});

test('PATCH /produk/:id menolak body kosong', async () => {
    const response = await request('/produk/P002', jsonRequest('PATCH', {}));

    assert.equal(response.status, 422);
});

test('PATCH /produk/:id menolak nilai harga yang tidak valid', async () => {
    const response = await request('/produk/P002', jsonRequest('PATCH', {
        harga: -500,
    }));

    assert.equal(response.status, 422);
});

test('PATCH /produk/:id mengembalikan 404 untuk produk yang tidak ada', async () => {
    const response = await request('/produk/UNKNOWN', jsonRequest('PATCH', {
        nama: 'Tidak ditemukan',
    }));

    assert.equal(response.status, 404);
});

test('DELETE /produk/:id menghapus produk yang tersedia', async () => {
    const response = await request('/produk/P003');
    assert.equal(response.status, 200);

    const deleted = await request('/produk/P003', { method: 'DELETE' });
    const body = await deleted.json();

    assert.equal(deleted.status, 200);
    assert.equal(body.data.id, 'P003');

    const detail = await request('/produk/P003');
    assert.equal(detail.status, 404);
});

test('DELETE /produk/:id mengembalikan 404 untuk produk yang tidak ada', async () => {
    const response = await request('/produk/UNKNOWN', { method: 'DELETE' });

    assert.equal(response.status, 404);
});
