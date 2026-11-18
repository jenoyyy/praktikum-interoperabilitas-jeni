<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('menambahkan dan menyimpan blog milik pengguna yang sedang login', function () {
    $owner = User::factory()->create();
    Sanctum::actingAs($owner, ['blogs:create']);

    $payload = [
        'title' => 'Catatan baru',
        'content' => 'Isi blog',
    ];

    $response = $this->postJson('/api/blogs', $payload)
        ->assertCreated()
        ->assertJsonPath('data.title', $payload['title'])
        ->assertJsonPath('data.user_id', $owner->id);

    $this->assertDatabaseHas('blogs', [
        'id' => $response->json('data.id'),
        ...$payload,
        'user_id' => $owner->id,
    ]);
});

it('menampilkan daftar blog kepada pengguna yang memiliki izin membaca', function () {
    $owner = User::factory()->create();
    $blog = $owner->blogs()->create([
        'title' => 'Catatan',
        'content' => 'Isi blog',
    ]);

    Sanctum::actingAs($owner, ['blogs:read']);

    $this->getJson('/api/blogs')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $blog->id)
        ->assertJsonPath('data.0.user.id', $owner->id)
        ->assertJsonPath('data.0.user.name', $owner->name);
});

it('menampilkan detail blog kepada pemilik yang memiliki izin membaca', function () {
    $owner = User::factory()->create();
    $blog = $owner->blogs()->create([
        'title' => 'Catatan pribadi',
        'content' => 'Isi catatan pribadi',
    ]);

    Sanctum::actingAs($owner, ['blogs:read']);

    $this->getJson("/api/blog/{$blog->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $blog->id)
        ->assertJsonPath('data.user_id', $owner->id)
        ->assertJsonPath('data.title', 'Catatan pribadi');
});

it('mengubah judul dan isi blog milik sendiri dengan izin edit', function () {
    $owner = User::factory()->create();
    $blog = $owner->blogs()->create([
        'title' => 'Judul awal',
        'content' => 'Isi awal',
    ]);

    Sanctum::actingAs($owner, ['blogs:update']);

    $payload = [
        'title' => 'Judul baru',
        'content' => 'Isi baru',
    ];

    $this->putJson("/api/blog/{$blog->id}", $payload)
        ->assertOk()
        ->assertJsonPath('data.title', $payload['title'])
        ->assertJsonPath('data.content', $payload['content']);

    $this->assertDatabaseHas('blogs', [
        'id' => $blog->id,
        'user_id' => $owner->id,
        ...$payload,
    ]);
});

it('menghapus blog milik sendiri dengan izin delete', function () {
    $owner = User::factory()->create();
    $blog = $owner->blogs()->create([
        'title' => 'Catatan lama',
        'content' => 'Isi catatan lama',
    ]);

    Sanctum::actingAs($owner, ['blogs:delete']);

    $this->deleteJson("/api/blog/{$blog->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $blog->id);

    $this->assertDatabaseMissing('blogs', [
        'id' => $blog->id,
    ]);
});
