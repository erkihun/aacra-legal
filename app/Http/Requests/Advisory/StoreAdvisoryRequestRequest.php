<?php

declare(strict_types=1);

namespace App\Http\Requests\Advisory;

use App\Enums\AdvisoryRequestType;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAdvisoryRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('advisory.create')
            || $this->user()?->can('advisory-requests.create')
            || false;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'category_id' => ['required', 'uuid', 'exists:advisory_categories,id'],
            'subject' => ['required', 'string', 'max:255'],
            'request_type' => ['required', Rule::enum(AdvisoryRequestType::class)],
            'priority' => ['required', Rule::enum(PriorityLevel::class)],
            'description' => ['required', 'string', 'min:20'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'extensions:pdf,doc,docx,png,jpg,jpeg', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user === null || $user->isSuperAdmin() || ! $user->hasSystemRole(SystemRole::DEPARTMENT_REQUESTER)) {
                return;
            }

            if ((string) $this->input('department_id') !== (string) $user->department_id) {
                $validator->errors()->add('department_id', __('You may only submit advisory requests for your own department.'));
            }
        });
    }
}
