<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdvisoryRequest;
use App\Models\Comment;
use App\Models\Complaint;
use App\Models\LegalCase;
use App\Models\User;

class CommentPolicy
{
    public function view(User $user, Comment $comment): bool
    {
        if (! $user->can('comments.view')) {
            return false;
        }

        $commentable = $comment->commentable;

        if ($commentable instanceof AdvisoryRequest || $commentable instanceof LegalCase || $commentable instanceof Complaint) {
            return $user->can('view', $commentable);
        }

        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->can('comments.create');
    }

    public function update(User $user, Comment $comment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $commentable = $comment->commentable;

        if (($commentable instanceof AdvisoryRequest || $commentable instanceof LegalCase || $commentable instanceof Complaint)
            && ! ($commentable instanceof LegalCase && $commentable->isClosed())
            && ! ($commentable instanceof Complaint && $commentable->isClosed())
            && $user->can('view', $commentable)
            && $comment->user_id === $user->getKey()) {
            return $user->can('comments.create');
        }

        return false;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->update($user, $comment);
    }
}
