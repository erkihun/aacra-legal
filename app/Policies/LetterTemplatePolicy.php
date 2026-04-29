<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LetterTemplate;
use App\Models\User;

class LetterTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('letter_templates.view');
    }

    public function view(User $user, LetterTemplate $letterTemplate): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('letter_templates.create');
    }

    public function update(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->can('letter_templates.update');
    }

    public function delete(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->can('letter_templates.delete');
    }

    public function preview(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->can('letter_templates.preview');
    }

    public function print(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->can('letter_templates.print');
    }

    public function duplicate(User $user, LetterTemplate $letterTemplate): bool
    {
        return $user->can('letter_templates.create');
    }
}
