<?php

use App\Support\BlogOwnership;

test('memeriksa siapa pemilik blog', function (int $userId, ?int $ownerId, bool $expected) {
    expect(BlogOwnership::allows($userId, $ownerId))->toBe($expected);
})->with([
    'pemilik' => [7, 7, true],
    'pengguna lain' => [8, 7, false],
    'tanpa owner' => [7, null, false],
]);
