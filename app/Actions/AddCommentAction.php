<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AddCommentAction
{
    public function execute(Model $commentable, User $user, string $body, bool $isInternal = true): void
    {
        $commentable->comments()->create([
            'user_id' => $user->getKey(),
            'body' => $body,
            'is_internal' => $isInternal,
        ]);
    }
}
