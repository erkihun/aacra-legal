<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LocaleCode;
use App\Enums\SystemRole;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $legalDepartment = Department::query()->where('code', 'LEG')->firstOrFail();
        $hrDepartment = Department::query()->where('code', 'HR')->firstOrFail();
        $procurementDepartment = Department::query()->where('code', 'PROC')->firstOrFail();

        $litigationTeam = Team::query()->where('code', 'LIT')->firstOrFail();
        $advisoryTeam = Team::query()->where('code', 'ADV')->firstOrFail();
        $administrationTeam = Team::query()->where('code', 'ADM')->firstOrFail();

        $users = [
            'super_admin' => $this->makeUser('admin@ldms.test', 'System Administrator', $legalDepartment, $administrationTeam, 'Platform Administrator', SystemRole::SUPER_ADMIN),
            'director' => $this->makeUser('director@ldms.test', 'Legal Director', $legalDepartment, $administrationTeam, 'Legal Department Director', SystemRole::LEGAL_DIRECTOR),
            'litigation_lead' => $this->makeUser('litigation.lead@ldms.test', 'Litigation Team Lead', $legalDepartment, $litigationTeam, 'Litigation Team Leader', SystemRole::LITIGATION_TEAM_LEADER),
            'advisory_lead' => $this->makeUser('advisory.lead@ldms.test', 'Advisory Team Lead', $legalDepartment, $advisoryTeam, 'Advisory Team Leader', SystemRole::ADVISORY_TEAM_LEADER),
            'expert_one' => $this->makeUser('expert.one@ldms.test', 'Senior Legal Expert', $legalDepartment, $advisoryTeam, 'Senior Legal Expert', SystemRole::LEGAL_EXPERT),
            'expert_two' => $this->makeUser('expert.two@ldms.test', 'Litigation Legal Expert', $legalDepartment, $litigationTeam, 'Legal Expert', SystemRole::LEGAL_EXPERT),
            'requester' => $this->makeUser('requester@ldms.test', 'HR Department Requester', $hrDepartment, null, 'HR Officer', SystemRole::DEPARTMENT_REQUESTER),
            'registrar' => $this->makeUser('registrar@ldms.test', 'Case Registrar', $legalDepartment, $administrationTeam, 'Case Intake Registrar', SystemRole::REGISTRAR),
            'auditor' => $this->makeUser('auditor@ldms.test', 'Internal Auditor', $procurementDepartment, null, 'Internal Auditor', SystemRole::AUDITOR),
        ];

        $litigationTeam->update(['leader_user_id' => $users['litigation_lead']->id]);
        $advisoryTeam->update(['leader_user_id' => $users['advisory_lead']->id]);
    }

    private function makeUser(
        string $email,
        string $name,
        Department $department,
        ?Team $team,
        string $jobTitle,
        SystemRole $role,
    ): User {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'department_id' => $department->id,
                'team_id' => $team?->id,
                'employee_number' => strtoupper(str_replace(['@', '.'], ['', ''], strtok($email, '@'))).'01',
                'name' => $name,
                'phone' => '+251911000000',
                'job_title' => $jobTitle,
                'locale' => LocaleCode::ENGLISH,
                'email_verified_at' => now(),
                'is_active' => true,
                'password' => Hash::make('password'),
            ],
        );

        $user->syncRoles([$role->value]);

        return $user;
    }
}
