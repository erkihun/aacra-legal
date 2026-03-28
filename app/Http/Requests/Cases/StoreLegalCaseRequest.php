<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use App\Enums\PriorityLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegalCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cases.create')
            || $this->user()?->can('legal-cases.create')
            || false;
    }

    public function rules(): array
    {
        return [
            'external_court_file_number' => ['nullable', 'string', 'max:255'],
            'court_id' => ['required', 'uuid', 'exists:courts,id'],
            'case_type_id' => ['required', 'uuid', 'exists:case_types,id'],
            'plaintiff' => ['required', 'string', 'max:255'],
            'defendant' => ['required', 'string', 'max:255'],
            'bench_or_chamber' => ['nullable', 'string', 'max:255'],
            'claim_summary' => ['required', 'string', 'min:20'],
            'institution_position' => ['nullable', 'string'],
            'filing_date' => ['nullable', 'date'],
            'next_hearing_date' => ['nullable', 'date', 'after_or_equal:today'],
            'priority' => ['required', Rule::enum(PriorityLevel::class)],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'extensions:pdf,doc,docx,png,jpg,jpeg', 'max:10240'],
        ];
    }
}
