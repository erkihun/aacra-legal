<?php

declare(strict_types=1);

use App\Enums\SystemRole;
use App\Enums\SystemSettingGroup;
use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'dashboard.view',
            'attachments.view',
            'attachments.create',
            'attachments.delete',
            'complaints.view',
            'complaints.create',
            'complaints.view_own',
            'complaints.view_department',
            'complaints.respond_department',
            'complaints.forward_to_committee',
            'complaints.committee.review',
            'complaints.committee.decide',
            'complaints.settings.manage',
            'complaints.reports.view',
            'complaints.view_all',
            'complaints.attachments.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate(SystemRole::SUPER_ADMIN->value, 'web');
        $superAdmin->givePermissionTo($permissions);

        $committeeRole = Role::findOrCreate(SystemRole::COMPLAINT_COMMITTEE->value, 'web');
        $committeeRole->givePermissionTo([
            'dashboard.view',
            'complaints.view',
            'complaints.committee.review',
            'complaints.committee.decide',
            'complaints.reports.view',
            'attachments.view',
            'attachments.create',
            'attachments.delete',
        ]);

        $complaintClientRole = Role::findOrCreate(SystemRole::COMPLAINT_CLIENT->value, 'web');
        $complaintClientRole->givePermissionTo([
            'dashboard.view',
            'complaints.view',
            'complaints.create',
            'complaints.view_own',
            'complaints.forward_to_committee',
            'attachments.view',
            'attachments.create',
        ]);

        $departmentPermissions = [
            'complaints.view',
            'complaints.view_department',
            'complaints.respond_department',
            'complaints.attachments.manage',
            'attachments.view',
            'attachments.create',
            'attachments.delete',
        ];

        foreach ([
            SystemRole::LEGAL_DIRECTOR->value,
            SystemRole::LITIGATION_TEAM_LEADER->value,
            SystemRole::ADVISORY_TEAM_LEADER->value,
            SystemRole::LEGAL_EXPERT->value,
            SystemRole::DEPARTMENT_REQUESTER->value,
            SystemRole::REGISTRAR->value,
        ] as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->givePermissionTo($departmentPermissions);
        }

        $defaults = [
            'default_response_deadline_days' => 5,
            'auto_escalation_enabled' => true,
            'reminder_interval_hours' => 24,
            'committee_notification_user_ids' => [],
            'allow_client_self_registration' => true,
            'complaint_code_prefix' => 'CMP',
            'allowed_attachment_types' => ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'],
            'max_attachment_size_mb' => 10,
        ];

        foreach ($defaults as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                [
                    'setting_group' => SystemSettingGroup::COMPLAINTS->value,
                    'setting_key' => $key,
                ],
                [
                    'value' => $value,
                ],
            );
        }
    }

    public function down(): void
    {
        // Non-destructive by design.
    }
};
