<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return array_values(array_unique([
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.manage',
            'departments.view',
            'departments.manage',
            'teams.view',
            'teams.manage',
            'advisory-categories.view',
            'advisory-categories.manage',
            'courts.view',
            'courts.manage',
            'legal-case-types.view',
            'legal-case-types.manage',
            'advisory-requests.view',
            'advisory-requests.create',
            'advisory-requests.review',
            'advisory-requests.assign',
            'advisory-requests.respond',
            'advisory-requests.close',
            'legal-cases.view',
            'legal-cases.create',
            'legal-cases.review',
            'legal-cases.assign',
            'legal-cases.update',
            'legal-cases.close',
            'attachments.view',
            'attachments.create',
            'attachments.delete',
            'comments.view',
            'comments.create',
            'reports.view',
            'audit-logs.view',
            'settings.manage',
            'public-posts.view',
            'public-posts.manage',
            'reports.export',
            'users.assign_roles',
            'references.view',
            'references.manage',
            'advisory.view_any',
            'advisory.view_own',
            'advisory.create',
            'advisory.submit',
            'advisory.review',
            'advisory.assign_team_leader',
            'advisory.assign_expert',
            'advisory.respond',
            'advisory.comment',
            'advisory.attach',
            'advisory.export',
            'cases.view_any',
            'cases.view_own',
            'cases.create',
            'cases.review',
            'cases.assign_team_leader',
            'cases.assign_expert',
            'cases.record_hearing',
            'cases.close',
            'cases.reopen',
            'case-reopen',
            'cases.comment',
            'cases.attach',
            'cases.export',
            'audit.view',
        ]));
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach ($this->rolePermissions() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(array_values(array_unique($permissions)));
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rolePermissions(): array
    {
        return [
            SystemRole::SUPER_ADMIN->value => self::permissions(),
            SystemRole::LEGAL_DIRECTOR->value => [
                'dashboard.view',
                'departments.view',
                'teams.view',
                'advisory-categories.view',
                'courts.view',
                'legal-case-types.view',
                'advisory-requests.view',
                'advisory-requests.review',
                'advisory-requests.assign',
                'advisory-requests.close',
                'legal-cases.view',
                'legal-cases.review',
                'legal-cases.assign',
                'legal-cases.update',
                'legal-cases.close',
                'attachments.view',
                'comments.view',
                'reports.view',
                'audit-logs.view',
                'public-posts.view',
                'public-posts.manage',
                'reports.export',
                'references.view',
                'advisory.view_any',
                'advisory.review',
                'advisory.assign_team_leader',
                'advisory.comment',
                'advisory.attach',
                'cases.view_any',
                'cases.review',
                'cases.assign_team_leader',
                'cases.comment',
                'cases.attach',
                'audit.view',
                'public-posts.view',
                'public-posts.manage',
            ],
            SystemRole::LITIGATION_TEAM_LEADER->value => [
                'dashboard.view',
                'legal-cases.view',
                'legal-cases.assign',
                'legal-cases.update',
                'legal-cases.close',
                'attachments.view',
                'attachments.create',
                'comments.view',
                'comments.create',
                'cases.view_any',
                'cases.assign_expert',
                'cases.record_hearing',
                'cases.close',
                'cases.comment',
                'cases.attach',
            ],
            SystemRole::ADVISORY_TEAM_LEADER->value => [
                'dashboard.view',
                'advisory-requests.view',
                'advisory-requests.assign',
                'advisory-requests.respond',
                'attachments.view',
                'attachments.create',
                'comments.view',
                'comments.create',
                'advisory.view_any',
                'advisory.assign_expert',
                'advisory.comment',
                'advisory.attach',
            ],
            SystemRole::LEGAL_EXPERT->value => [
                'dashboard.view',
                'advisory-requests.view',
                'advisory-requests.respond',
                'legal-cases.view',
                'legal-cases.update',
                'attachments.view',
                'attachments.create',
                'comments.view',
                'comments.create',
                'advisory.view_any',
                'advisory.respond',
                'advisory.comment',
                'advisory.attach',
                'cases.view_any',
                'cases.record_hearing',
                'cases.comment',
                'cases.attach',
            ],
            SystemRole::DEPARTMENT_REQUESTER->value => [
                'dashboard.view',
                'advisory-requests.view',
                'advisory-requests.create',
                'attachments.view',
                'attachments.create',
                'comments.view',
                'comments.create',
                'advisory.view_own',
                'advisory.create',
                'advisory.submit',
                'advisory.comment',
                'advisory.attach',
            ],
            SystemRole::REGISTRAR->value => [
                'dashboard.view',
                'legal-cases.view',
                'legal-cases.create',
                'attachments.view',
                'attachments.create',
                'comments.view',
                'comments.create',
                'cases.view_any',
                'cases.create',
                'cases.comment',
                'cases.attach',
            ],
            SystemRole::AUDITOR->value => [
                'dashboard.view',
                'advisory-requests.view',
                'legal-cases.view',
                'attachments.view',
                'comments.view',
                'reports.view',
                'audit-logs.view',
                'advisory.view_any',
                'cases.view_any',
                'audit.view',
            ],
        ];
    }
}
