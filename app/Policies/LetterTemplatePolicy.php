<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LetterTemplate;
use App\Models\User;

class LetterTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canAccessModule($user);
    }

    public function view(User $user, LetterTemplate $letterTemplate): bool
    {
        return $this->canAccessModule($user);
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

    private function canAccessModule(User $user): bool
    {
        return $user->canAnyPermissions([
            'letter_templates.view',
            'letter_templates.create',
            'letter_templates.update',
            'letter_templates.delete',
            'letter_templates.preview',
            'letter_templates.print',
        ]);
    }
}
