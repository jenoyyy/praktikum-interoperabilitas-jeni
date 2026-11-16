<?php

namespace App\Traits;

use App\Models\Blog;
use Illuminate\Http\Request;

trait EnsureOwnerTrait
{
    private function ensureOwner(Request $request, Blog $blog): void
    {
        $userId = (int) $request->user()->getAuthIdentifier();

        abort_unless(
            $blog->user_id !== null && (int) $blog->user_id === $userId,
            403,
            'Blog ini hanya dapat diakses oleh pemiliknya.'
        );
    }
}
