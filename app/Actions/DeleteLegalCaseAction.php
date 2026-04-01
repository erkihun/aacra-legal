<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LegalCase;

class DeleteLegalCaseAction
{
    public function execute(LegalCase $legalCase): void
    {
        $legalCase->delete();
    }
}
