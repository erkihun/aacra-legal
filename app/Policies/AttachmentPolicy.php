<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdvisoryRequest;
use App\Models\AdvisoryResponse;
use App\Models\Attachment;
use App\Models\LegalCase;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('attachments.view')) {
            return false;
        }

        return $this->canAccessAttachable($user, $attachment);
    }

    public function create(User $user): bool
    {
        return $user->can('attachments.create');
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can('attachments.delete')) {
            return false;
        }

        if (! $this->canAccessAttachable($user, $attachment)) {
            return false;
        }

        if ($attachment->uploaded_by_id === $user->getKey()) {
            return ! ($attachment->attachable instanceof LegalCase && $attachment->attachable->isClosed());
        }

        $attachable = $attachment->attachable;

        if ($attachable instanceof AdvisoryRequest || $attachable instanceof LegalCase) {
            if ($attachable instanceof LegalCase && $attachable->isClosed()) {
                return false;
            }

            return $user->can('attach', $attachable);
        }

        if ($attachable instanceof AdvisoryResponse) {
            return $user->can('respond', $attachable->advisoryRequest);
        }

        return false;
    }

    public function update(User $user, Attachment $attachment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $this->canAccessAttachable($user, $attachment)) {
            return false;
        }

        if ($attachment->uploaded_by_id === $user->getKey()) {
            return $user->can('attachments.create')
                && ! ($attachment->attachable instanceof LegalCase && $attachment->attachable->isClosed());
        }

        $attachable = $attachment->attachable;

        if ($attachable instanceof AdvisoryRequest || $attachable instanceof LegalCase) {
            if ($attachable instanceof LegalCase && $attachable->isClosed()) {
                return false;
            }

            return $user->can('attach', $attachable);
        }

        if ($attachable instanceof AdvisoryResponse) {
            return $user->can('respond', $attachable->advisoryRequest);
        }

        return false;
    }

    private function canAccessAttachable(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof AdvisoryRequest || $attachable instanceof LegalCase) {
            return $user->can('view', $attachable);
        }

        if ($attachable instanceof AdvisoryResponse) {
            return $user->can('view', $attachable->advisoryRequest);
        }

        return false;
    }
}
