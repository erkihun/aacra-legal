<?php

declare(strict_types=1);

namespace App\Http\Requests\Cases;

use App\Enums\CaseStatus;
use App\Enums\LegalCaseMainType;
use App\Enums\PriorityLevel;
use App\Models\CaseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'case_number' => ['required', 'string', 'max:255', Rule::unique('legal_cases', 'case_number')->ignore($this->route('legalCase'))],
            'main_case_type' => ['required', Rule::enum(LegalCaseMainType::class)],
            'court_id' => ['nullable', 'uuid', 'exists:courts,id'],
            'case_type_id' => ['nullable', 'uuid', 'exists:case_types,id'],
            'plaintiff' => ['nullable', 'string', 'max:255'],
            'defendant' => ['nullable', 'string', 'max:255'],
            'claim_summary' => ['nullable', 'string', 'min:20'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'crime_scene' => ['nullable', 'string', 'max:255'],
            'police_station' => ['nullable', 'string', 'max:255'],
            'stolen_property_type' => ['nullable', 'string', 'max:255'],
            'stolen_property_estimated_value' => ['nullable', 'numeric', 'min:0'],
            'suspect_names' => ['nullable', 'string'],
            'statement_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in([CaseStatus::INTAKE->value, CaseStatus::UNDER_DIRECTOR_REVIEW->value])],
            'filing_date' => ['nullable', 'date'],
            'next_hearing_date' => ['nullable', 'date', 'after_or_equal:today'],
            'priority' => ['required', Rule::enum(PriorityLevel::class)],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'extensions:pdf,doc,docx,png,jpg,jpeg', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mainType = LegalCaseMainType::tryFrom((string) $this->input('main_case_type'));

            if ($mainType === null) {
                return;
            }

            match ($mainType) {
                LegalCaseMainType::CIVIL_LAW => $this->validateCivilLaw($validator),
                LegalCaseMainType::CRIME => $this->validateCrime($validator),
                LegalCaseMainType::LABOUR_DISPUTE => $this->validateLabourDispute($validator),
            };
        });
    }

    private function validateCivilLaw(Validator $validator): void
    {
        foreach ([
            'court_id',
            'case_type_id',
            'plaintiff',
            'defendant',
            'claim_summary',
            'status',
        ] as $field) {
            if (blank($this->input($field))) {
                $validator->errors()->add($field, __('validation.required', ['attribute' => $field]));
            }
        }

        if ($this->filled('case_type_id')) {
            $caseType = CaseType::query()->find($this->input('case_type_id'));

            if ($caseType !== null && strtoupper((string) $caseType->code) === 'LAB') {
                $validator->errors()->add('case_type_id', __('The selected civil-law type is invalid.'));
            }
        }
    }

    private function validateCrime(Validator $validator): void
    {
        foreach ([
            'crime_scene',
            'police_station',
            'stolen_property_type',
            'suspect_names',
            'statement_date',
            'claim_summary',
            'status',
        ] as $field) {
            if (blank($this->input($field))) {
                $validator->errors()->add($field, __('validation.required', ['attribute' => $field]));
            }
        }
    }

    private function validateLabourDispute(Validator $validator): void
    {
        foreach ([
            'court_id',
            'plaintiff',
            'defendant',
            'claim_summary',
            'status',
        ] as $field) {
            if (blank($this->input($field))) {
                $validator->errors()->add($field, __('validation.required', ['attribute' => $field]));
            }
        }
    }
}
