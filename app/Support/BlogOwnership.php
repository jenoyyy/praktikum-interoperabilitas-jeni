<?php

namespace App\Support;

class BlogOwnership
{
    public static function allows(int $userId, ?int $ownerId): bool
    {
        return $userId === $ownerId;
    }
}
