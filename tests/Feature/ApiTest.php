<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('registers a standard user and does not accept a client supplied role', function () {
    $this->postJson('/api/register', [
        'name' => 'Jeni',
        'email' => 'jeni@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ])
        ->assertCreated()
        ->assertJsonPath('message', 'User registered successfully')
        ->assertJsonPath('user.name', 'Jeni')
        ->assertJsonPath('user.role', 'user')
        ->assertJsonMissingPath('user.password');

    $user = User::query()->where('email', 'jeni@example.com')->firstOrFail();

    expect(Hash::check('password123', $user->password))->toBeTrue();
});

it('validates registration input', function () {
    $this->postJson('/api/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('issues a read token by default', function () {
    User::factory()->create([
        'email' => 'reader@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'reader@example.com',
        'password' => 'password123',
    ])
        ->assertOk()
        ->assertJsonPath('mode', 'read')
        ->assertJsonPath('abilities', ['blogs:read'])
        ->assertJsonPath('token_type', 'Bearer');

    expect($response->json('token'))->toBeString()
        ->and(PersonalAccessToken::query()->firstOrFail()->abilities)->toBe(['blogs:read']);
});

it('issues the expected abilities for each login mode', function (string $mode, array $abilities) {
    User::factory()->create([
        'email' => 'mode@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => 'mode@example.com',
        'password' => 'password123',
        'mode' => $mode,
    ])
        ->assertOk()
        ->assertJsonPath('mode', $mode)
        ->assertJsonPath('abilities', $abilities);
})->with([
    'read mode' => ['read', ['blogs:read']],
    'edit mode' => ['edit', ['blogs:read', 'blogs:update']],
    'delete mode' => ['delete', ['blogs:read', 'blogs:delete']],
    'manage mode' => ['manage', ['blogs:read', 'blogs:update', 'blogs:create', 'blogs:delete']],
]);

it('rejects unsupported login modes and invalid credentials', function () {
    User::factory()->create([
        'email' => 'mode@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/login', [
        'email' => 'mode@example.com',
        'password' => 'password123',
        'mode' => 'superuser',
    ])->assertUnprocessable()->assertJsonValidationErrors(['mode']);

    $this->postJson('/api/login', [
        'email' => 'mode@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('returns the profile and revokes only the current token on logout', function () {
    $user = User::factory()->create();
    $firstToken = $user->createToken('first-token', ['blogs:read'])->plainTextToken;
    $secondToken = $user->createToken('second-token', ['blogs:read'])->plainTextToken;

    $firstRecord = PersonalAccessToken::findToken($firstToken);
    $secondRecord = PersonalAccessToken::findToken($secondToken);

    $this->withToken($firstToken)
        ->getJson('/api/profile')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email);

    $this->withToken($firstToken)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logout berhasil.');

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $firstRecord->id]);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $secondRecord->id]);
});

it('protects profile and logout routes from unauthenticated requests', function () {
    $this->getJson('/api/profile')->assertUnauthorized();
    $this->postJson('/api/logout')->assertUnauthorized();
});

it('browses blogs and includes their owners for a read token', function () {
    $owner = User::factory()->create();
    $blog = $owner->blogs()->create([
        'title' => 'Belajar Interoperabilitas',
        'content' => 'Belajar membuat API dengan Laravel.',
    ]);

    Sanctum::actingAs($owner, ['blogs:read']);

    $this->getJson('/api/blogs')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $blog->id,
            'title' => 'Belajar Interoperabilitas',
            'user' => ['id' => $owner->id, 'name' => $owner->name],
        ]);
});

it('blocks blog requests when the token does not have the required ability', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, []);

    $this->getJson('/api/blogs')->assertForbidden();
});

it('creates a blog and assigns the authenticated user as its owner', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['blogs:create']);

    $this->postJson('/api/blogs', [
        'title' => 'Blog Baru',
        'content' => 'Konten blog baru.',
        'user_id' => 9999,
    ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Blog Baru')
        ->assertJsonPath('data.user_id', $user->id);

    $this->assertDatabaseHas('blogs', [
        'title' => 'Blog Baru',
        'user_id' => $user->id,
    ]);
});

it('validates blog creation input', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['blogs:create']);

    $this->postJson('/api/blogs', [
        'content' => 'Tanpa judul',
    ])->assertUnprocessable()->assertJsonValidationErrors(['title']);
});

it('allows the owner to read and update their own blog', function () {
    $owner = User::factory()->create();
    $blog = $owner->blogs()->create([
        'title' => 'Judul Awal',
        'content' => 'Konten awal.',
    ]);

    Sanctum::actingAs($owner, ['blogs:read']);

    $this->getJson('/api/blog/' . $blog->id)
        ->assertOk()
        ->assertJsonPath('data.id', $blog->id)
        ->assertJsonPath('data.title', 'Judul Awal');

    Sanctum::actingAs($owner, ['blogs:update']);

    $this->putJson('/api/blog/' . $blog->id, [
        'title' => 'Judul Setelah Diubah',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Judul Setelah Diubah');

    $this->assertDatabaseHas('blogs', [
        'id' => $blog->id,
        'title' => 'Judul Setelah Diubah',
    ]);
});

it('prevents another user from reading updating or deleting a blog', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $blog = $owner->blogs()->create([
        'title' => 'Blog Milik Orang Lain',
        'content' => 'Tidak boleh diubah pengguna lain.',
    ]);

    Sanctum::actingAs($otherUser, ['blogs:read']);
    $this->getJson('/api/blog/' . $blog->id)->assertForbidden();

    Sanctum::actingAs($otherUser, ['blogs:update']);
    $this->putJson('/api/blog/' . $blog->id, [
        'title' => 'Percobaan Mengubah',
    ])->assertForbidden();

    Sanctum::actingAs($otherUser, ['blogs:delete']);
    $this->deleteJson('/api/blog/' . $blog->id)->assertForbidden();

    $this->assertDatabaseHas('blogs', [
        'id' => $blog->id,
        'title' => 'Blog Milik Orang Lain',
        'user_id' => $owner->id,
    ]);
});

it('allows the owner to delete their blog with the delete ability', function () {
    $owner = User::factory()->create();
    $blog = $owner->blogs()->create([
        'title' => 'Blog yang Akan Dihapus',
        'content' => 'Konten.',
    ]);

    Sanctum::actingAs($owner, ['blogs:delete']);

    $this->deleteJson('/api/blog/' . $blog->id)
        ->assertOk()
        ->assertJsonPath('data.id', $blog->id);

    $this->assertDatabaseMissing('blogs', ['id' => $blog->id]);
});

it('requires the matching ability for create update and delete endpoints', function () {
    $user = User::factory()->create();
    $blog = $user->blogs()->create([
        'title' => 'Blog Uji',
        'content' => 'Konten.',
    ]);

    Sanctum::actingAs($user, ['blogs:read']);

    $this->postJson('/api/blogs', ['title' => 'Tidak Boleh Dibuat'])->assertForbidden();
    $this->putJson('/api/blog/' . $blog->id, ['title' => 'Tidak Boleh Diubah'])->assertForbidden();
    $this->deleteJson('/api/blog/' . $blog->id)->assertForbidden();
});
