<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaints;

use App\Enums\PriorityLevel;
use App\Models\Complaint;
use App\Models\Department;
use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Complaint::class) ?? false;
    }

    public function rules(): array
    {
        $settings = app(SystemSettingsService::class);
        $maxSize = $settings->complaintMaxAttachmentSizeMb() * 1024;
        $extensions = implode(',', $settings->complaintAllowedAttachmentTypes());

        return [
            'complainant_name' => ['required', 'string', 'max:255'],
            'complainant_city' => ['nullable', 'string', 'max:255'],
            'complainant_sub_city' => ['nullable', 'string', 'max:255'],
            'complainant_woreda' => ['nullable', 'string', 'max:255'],
            'complainant_house_number' => ['nullable', 'string', 'max:255'],
            'complainant_phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\\-\\s()]{7,32}$/'],
            'complaint_essence' => ['required', 'string', 'min:10', 'max:5000'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'incident_sub_city' => ['nullable', 'string', 'max:255'],
            'incident_woreda' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'concerned_employee_name' => ['nullable', 'string', 'max:255'],
            'evidence_note' => ['nullable', 'string', 'max:1000'],
            'requested_resolution' => ['required', 'string', 'min:5', 'max:3000'],
            'category' => ['nullable', 'string', 'max:120'],
            'priority' => ['nullable', 'string', 'in:'.implode(',', array_column(PriorityLevel::cases(), 'value'))],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', "mimes:{$extensions}", "max:{$maxSize}"],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateDepartmentBranchConsistency($validator),
        ];
    }

    public function attributes(): array
    {
        return [
            'complainant_name' => __('complaints.validation_attributes.complainant_name'),
            'complainant_city' => __('complaints.validation_attributes.complainant_city'),
            'complainant_sub_city' => __('complaints.validation_attributes.complainant_sub_city'),
            'complainant_woreda' => __('complaints.validation_attributes.complainant_woreda'),
            'complainant_house_number' => __('complaints.validation_attributes.complainant_house_number'),
            'complainant_phone' => __('complaints.validation_attributes.complainant_phone'),
            'complaint_essence' => __('complaints.validation_attributes.complaint_essence'),
            'incident_date' => __('complaints.validation_attributes.incident_date'),
            'branch_id' => __('complaints.validation_attributes.branch'),
            'incident_sub_city' => __('complaints.validation_attributes.incident_sub_city'),
            'incident_woreda' => __('complaints.validation_attributes.incident_woreda'),
            'department_id' => __('complaints.validation_attributes.department'),
            'concerned_employee_name' => __('complaints.validation_attributes.concerned_employee_name'),
            'evidence_note' => __('complaints.validation_attributes.evidence_note'),
            'requested_resolution' => __('complaints.validation_attributes.requested_resolution'),
            'attachments' => __('complaints.validation_attributes.attachments'),
            'attachments.*' => __('complaints.validation_attributes.attachment_file'),
        ];
    }

    private function validateDepartmentBranchConsistency(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['branch_id', 'department_id'])) {
            return;
        }

        $departmentId = (string) $this->input('department_id');

        if ($departmentId === '') {
            return;
        }

        $department = Department::query()
            ->withTrashed()
            ->select(['id', 'branch_id'])
            ->find($departmentId);

        if ($department === null) {
            return;
        }

        $branchId = (string) $this->input('branch_id');

        if ($branchId === '') {
            $validator->errors()->add('branch_id', __('complaints.validation.branch_required_for_department'));

            return;
        }

        if ($department->branch_id !== $branchId) {
            $validator->errors()->add('department_id', __('complaints.validation.department_branch_mismatch'));
        }
    }
}
