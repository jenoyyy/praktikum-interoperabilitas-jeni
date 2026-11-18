<?php

namespace App\Traits;

use App\Models\Blog;
use App\Support\BlogOwnership;
use Illuminate\Http\Request;

trait EnsureOwnerTrait
{
    private function ensureOwner(Request $request, Blog $blog): void
    {
        $userId = (int) $request->user()->getAuthIdentifier();
        $ownerId = $blog->user_id === null ? null : (int) $blog->user_id;

        abort_unless(
            BlogOwnership::allows($userId, $ownerId),
            403,
            'Blog ini hanya dapat diakses oleh pemiliknya.'
        );
    }
}
