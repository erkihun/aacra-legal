<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use App\Models\LegalCase;

class UpdateLegalCaseRequest extends StoreLegalCaseRequest
{
    public function authorize(): bool
    {
        /** @var LegalCase|null $legalCase */
        $legalCase = $this->route('legalCase');

        return $this->user()?->can('update', $legalCase) ?? false;
    }
}
