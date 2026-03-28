<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

it('allows attachment downloads only to users who can view the parent advisory request', function (): void {
    $owner = createAttachmentUser('owner@ldms.test', SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $otherRequester = createAttachmentUser('other@ldms.test', SystemRole::DEPARTMENT_REQUESTER, 'PROC');

    Storage::disk('local')->put('legal/AdvisoryRequest/test/evidence.pdf', 'legal attachment');

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-ATT-0001',
        'department_id' => $owner->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $owner->id,
        'subject' => 'Attachment security test',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'description' => 'Ensure unauthorized users cannot download files.',
        'date_submitted' => now()->toDateString(),
    ]);

    $attachment = $advisoryRequest->attachments()->create([
        'uploaded_by_id' => $owner->id,
        'disk' => 'local',
        'path' => 'legal/AdvisoryRequest/test/evidence.pdf',
        'original_name' => 'evidence.pdf',
        'stored_name' => 'evidence.pdf',
        'mime_type' => 'application/pdf',
        'size' => 32,
        'sha256' => hash('sha256', 'legal attachment'),
    ]);

    $this->actingAs($owner)
        ->get(route('attachments.download', $attachment))
        ->assertOk()
        ->assertDownload('evidence.pdf');

    $this->actingAs($otherRequester)
        ->get(route('attachments.download', $attachment))
        ->assertForbidden();
});

it('allows secure inline viewing and restricts unauthorized access', function (): void {
    $owner = createAttachmentUser('owner.view@ldms.test', SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $otherRequester = createAttachmentUser('other.view@ldms.test', SystemRole::DEPARTMENT_REQUESTER, 'PROC');

    Storage::disk('local')->put('legal/AdvisoryRequest/test/viewable.pdf', 'inline attachment');

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-ATT-0002',
        'department_id' => $owner->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $owner->id,
        'subject' => 'Attachment view security test',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'description' => 'Ensure unauthorized users cannot view files inline.',
        'date_submitted' => now()->toDateString(),
    ]);

    $attachment = $advisoryRequest->attachments()->create([
        'uploaded_by_id' => $owner->id,
        'disk' => 'local',
        'path' => 'legal/AdvisoryRequest/test/viewable.pdf',
        'original_name' => 'viewable.pdf',
        'stored_name' => 'viewable.pdf',
        'mime_type' => 'application/pdf',
        'size' => 24,
        'sha256' => hash('sha256', 'inline attachment'),
    ]);

    $this->actingAs($owner)
        ->get(route('attachments.view', $attachment))
        ->assertOk();

    $this->actingAs($otherRequester)
        ->get(route('attachments.view', $attachment))
        ->assertForbidden();
});

it('allows only authorized users to delete attachments and removes the file from storage', function (): void {
    $owner = createAttachmentUser('owner.delete@ldms.test', SystemRole::DEPARTMENT_REQUESTER, 'HR');
    $admin = createAttachmentUser('admin.delete@ldms.test', SystemRole::SUPER_ADMIN, 'HR');

    Storage::disk('local')->put('legal/AdvisoryRequest/test/delete-me.pdf', 'delete attachment');

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-ATT-0003',
        'department_id' => $owner->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $owner->id,
        'subject' => 'Attachment delete security test',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'description' => 'Ensure only authorized users can delete files.',
        'date_submitted' => now()->toDateString(),
    ]);

    $attachment = $advisoryRequest->attachments()->create([
        'uploaded_by_id' => $owner->id,
        'disk' => 'local',
        'path' => 'legal/AdvisoryRequest/test/delete-me.pdf',
        'original_name' => 'delete-me.pdf',
        'stored_name' => 'delete-me.pdf',
        'mime_type' => 'application/pdf',
        'size' => 28,
        'sha256' => hash('sha256', 'delete attachment'),
    ]);

    $this->actingAs($owner)
        ->delete(route('attachments.destroy', $attachment))
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('attachments.destroy', $attachment))
        ->assertRedirect();

    $this->assertSoftDeleted('attachments', [
        'id' => $attachment->id,
    ]);
    Storage::disk('local')->assertMissing('legal/AdvisoryRequest/test/delete-me.pdf');
});

function createAttachmentUser(string $email, SystemRole $role, string $departmentCode): User
{
    $department = Department::query()->where('code', $departmentCode)->firstOrFail();

    $user = User::factory()->create([
        'department_id' => $department->id,
        'email' => $email,
    ]);

    $user->assignRole($role->value);

    return $user;
}
