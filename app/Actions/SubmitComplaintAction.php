<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ComplaintComplainantType;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintAssignedToDepartmentNotification;
use App\Notifications\ComplaintSubmittedNotification;
use App\Services\SystemSettingsService;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmitComplaintAction
{
    public function __construct(
        private readonly GenerateSequenceNumberAction $generateSequenceNumberAction,
        private readonly StoreAttachmentAction $storeAttachmentAction,
        private readonly SystemSettingsService $settings,
        private readonly RichTextSanitizer $richTextSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, \Illuminate\Http\UploadedFile>  $attachments
     */
    public function execute(array $attributes, User $complainant, array $attachments = []): Complaint
    {
        return DB::transaction(function () use ($attributes, $complainant, $attachments): Complaint {
            $submittedAt = now();
            $complaintEssence = trim((string) ($attributes['complaint_essence'] ?? ''));
            $requestedResolution = trim((string) ($attributes['requested_resolution'] ?? ''));
            $subject = Str::limit(Str::of($complaintEssence)->replaceMatches('/\s+/', ' ')->trim()->value(), 255, '');

            $complaint = Complaint::query()->create([
                'complaint_number' => $this->generateSequenceNumberAction->execute($this->settings->complaintCodePrefix()),
                'complainant_user_id' => $complainant->getKey(),
                'branch_id' => $attributes['branch_id'] ?? $complainant->branch_id,
                'department_id' => $attributes['department_id'],
                'concerned_employee_name' => $this->nullableString($attributes['concerned_employee_name'] ?? null),
                'complainant_type' => $this->resolveComplainantType($complainant)->value,
                'complainant_name' => trim((string) ($attributes['complainant_name'] ?? $complainant->name)),
                'complainant_email' => $complainant->email,
                'complainant_phone' => trim((string) ($attributes['complainant_phone'] ?? $complainant->phone)),
                'complainant_city' => $this->nullableString($attributes['complainant_city'] ?? null),
                'complainant_sub_city' => $this->nullableString($attributes['complainant_sub_city'] ?? null),
                'complainant_woreda' => $this->nullableString($attributes['complainant_woreda'] ?? null),
                'complainant_house_number' => $this->nullableString($attributes['complainant_house_number'] ?? null),
                'subject' => $subject,
                'complaint_essence' => $complaintEssence,
                'details' => $this->richTextSanitizer->sanitize($complaintEssence),
                'category' => $attributes['category'] ?? null,
                'evidence_note' => $this->nullableString($attributes['evidence_note'] ?? null),
                'requested_resolution' => $requestedResolution,
                'priority' => $attributes['priority'] ?? null,
                'incident_date' => $attributes['incident_date'],
                'incident_sub_city' => $this->nullableString($attributes['incident_sub_city'] ?? null),
                'incident_woreda' => $this->nullableString($attributes['incident_woreda'] ?? null),
                'submitted_at' => $submittedAt,
                'department_response_deadline_at' => $submittedAt->copy()->addDays($this->settings->complaintDeadlineDays()),
                'status' => ComplaintStatus::ASSIGNED_TO_DEPARTMENT,
            ]);

            $complaint->histories()->create([
                'actor_id' => $complainant->getKey(),
                'from_status' => null,
                'to_status' => ComplaintStatus::SUBMITTED,
                'action' => 'submitted',
                'notes' => 'Complaint submitted by complainant.',
                'acted_at' => $submittedAt,
            ]);

            $complaint->histories()->create([
                'actor_id' => $complainant->getKey(),
                'from_status' => ComplaintStatus::SUBMITTED,
                'to_status' => ComplaintStatus::ASSIGNED_TO_DEPARTMENT,
                'action' => 'assigned_to_department',
                'notes' => 'Complaint routed to the responsible department.',
                'acted_at' => $submittedAt,
            ]);

            if ($attachments !== []) {
                $this->storeAttachmentAction->execute($complaint, $attachments, $complainant);
            }

            $departmentRecipients = User::query()
                ->where('is_active', true)
                ->where('department_id', $complaint->department_id)
                ->withAnyPermission(['complaints.view_department', 'complaints.respond_department'])
                ->get()
                ->unique('id')
                ->values();

            DB::afterCommit(function () use ($complainant, $complaint, $departmentRecipients): void {
                $complainant->notify(new ComplaintSubmittedNotification($complaint));

                foreach ($departmentRecipients as $recipient) {
                    $recipient->notify(new ComplaintAssignedToDepartmentNotification($complaint));
                }
            });

            return $complaint->fresh(['complainant', 'branch', 'department']);
        });
    }

    private function resolveComplainantType(User $complainant): ComplaintComplainantType
    {
        if ($complainant->hasRole('Complaint Client')) {
            return ComplaintComplainantType::CLIENT;
        }

        return $complainant->branch_id !== null
            ? ComplaintComplainantType::BRANCH_EMPLOYEE
            : ComplaintComplainantType::HEAD_OFFICE_EMPLOYEE;
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
