<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AdvisoryRequest;

class DeleteAdvisoryRequestAction
{
    public function execute(AdvisoryRequest $advisoryRequest): void
    {
        $advisoryRequest->delete();
    }
}
