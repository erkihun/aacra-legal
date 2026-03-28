<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AdvisoryRequestStatus;
use App\Enums\CaseStatus;
use App\Enums\PriorityLevel;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.view') || $this->user()?->can('reports.export') || $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'department_id' => ['nullable', Rule::exists(Department::class, 'id')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'team_id' => ['nullable', Rule::exists(Team::class, 'id')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'status' => ['nullable', Rule::in([
                ...array_column(AdvisoryRequestStatus::cases(), 'value'),
                ...array_column(CaseStatus::cases(), 'value'),
            ])],
            'priority' => ['nullable', Rule::in(array_column(PriorityLevel::cases(), 'value'))],
            'expert_id' => ['nullable', Rule::exists(User::class, 'id')->where(fn ($query) => $query->whereNull('deleted_at'))],
        ];
    }
}
