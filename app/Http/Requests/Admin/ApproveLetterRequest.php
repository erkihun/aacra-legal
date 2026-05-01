<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Letter;
use Illuminate\Foundation\Http\FormRequest;

class ApproveLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $letter = $this->route('letter');

        return $letter instanceof Letter
            ? ($this->user()?->can('approve', $letter) ?? false)
            : false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
