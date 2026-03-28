<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly LegalCase $legalCase,
        public readonly User $assignee,
        public readonly User $assignedBy,
    ) {}
}
