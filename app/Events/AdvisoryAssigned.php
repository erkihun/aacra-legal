<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AdvisoryRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdvisoryAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AdvisoryRequest $advisoryRequest,
        public readonly User $assignee,
        public readonly User $assignedBy,
    ) {}
}
