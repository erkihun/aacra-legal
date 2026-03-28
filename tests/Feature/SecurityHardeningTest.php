<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\CaseStatus;
use App\Enums\DirectorDecision;
use App\Enums\PriorityLevel;
use App\Enums\SystemRole;
use App\Enums\WorkflowStage;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\Team;
use App\Models\User;
use App\Services\SystemSettingsService;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('local');

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('restricts case visibility to the assigned litigation leader and originating registrar', function (): void {
    $assignedLeader = User::query()->where('email', 'litigation.lead@ldms.test')->firstOrFail();
    $originRegistrar = User::query()->where('email', 'registrar@ldms.test')->firstOrFail();
    $otherLeader = User::factory()->create([
        'department_id' => Department::query()->where('code', 'LEG')->firstOrFail()->id,
        'team_id' => Team::query()->where('code', 'LIT')->firstOrFail()->id,
        'email' => 'other.litigation.lead@ldms.test',
    ]);
    $otherLeader->assignRole(SystemRole::LITIGATION_TEAM_LEADER->value);

    $otherRegistrar = User::factory()->create([
        'department_id' => Department::query()->where('code', 'LEG')->firstOrFail()->id,
        'email' => 'other.registrar@ldms.test',
    ]);
    $otherRegistrar->assignRole(SystemRole::REGISTRAR->value);

    $legalCase = LegalCase::query()->create([
        'case_number' => 'CASE-SEC-0001',
        'court_id' => Court::query()->firstOrFail()->id,
        'case_type_id' => CaseType::query()->firstOrFail()->id,
        'registered_by_id' => $originRegistrar->id,
        'assigned_team_leader_id' => $assignedLeader->id,
        'plaintiff' => 'Security Review Plaintiff',
        'defendant' => 'Security Review Defendant',
        'status' => CaseStatus::ASSIGNED_TO_TEAM_LEADER,
        'workflow_stage' => WorkflowStage::TEAM_LEADER,
        'priority' => PriorityLevel::HIGH,
        'director_decision' => DirectorDecision::APPROVED,
        'claim_summary' => 'The scope of case visibility should be limited.',
        'filing_date' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($assignedLeader)
        ->get(route('cases.show', $legalCase))
        ->assertOk();

    $this->actingAs($originRegistrar)
        ->get(route('cases.show', $legalCase))
        ->assertOk();

    $this->actingAs($otherLeader)
        ->get(route('cases.show', $legalCase))
        ->assertForbidden();

    $this->actingAs($otherRegistrar)
        ->get(route('cases.show', $legalCase))
        ->assertForbidden();
});

it('prevents viewers with attachment delete permission from deleting attachments they cannot manage', function (): void {
    $owner = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();
    $director->givePermissionTo('attachments.delete');

    Storage::disk('local')->put('legal/AdvisoryRequest/test/security-delete.pdf', 'security delete check');

    $advisoryRequest = AdvisoryRequest::query()->create([
        'request_number' => 'ADV-SEC-0001',
        'department_id' => $owner->department_id,
        'category_id' => AdvisoryCategory::query()->firstOrFail()->id,
        'requester_user_id' => $owner->id,
        'subject' => 'Attachment delete boundary',
        'request_type' => AdvisoryRequestType::WRITTEN,
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW,
        'workflow_stage' => WorkflowStage::DIRECTOR,
        'priority' => PriorityLevel::MEDIUM,
        'description' => 'A viewer with delete permission alone should still be blocked.',
        'date_submitted' => now()->toDateString(),
    ]);

    $attachment = $advisoryRequest->attachments()->create([
        'uploaded_by_id' => $owner->id,
        'disk' => 'local',
        'path' => 'legal/AdvisoryRequest/test/security-delete.pdf',
        'original_name' => 'security-delete.pdf',
        'stored_name' => 'security-delete.pdf',
        'mime_type' => 'application/pdf',
        'size' => 64,
        'sha256' => hash('sha256', 'security delete check'),
    ]);

    $this->actingAs($director)
        ->delete(route('attachments.destroy', $attachment))
        ->assertForbidden();
});

it('rejects unsafe public website slide targets and keeps homepage slide links internal', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();
    $payload = array_merge(
        app(SystemSettingsService::class)->group('public_website'),
        [
            'hero_slides' => [
                [
                    'title' => 'Secure slide',
                    'subtitle' => 'Only internal navigation should be allowed.',
                    'button_label' => 'Open',
                    'button_url' => 'javascript:alert(1)',
                    'display_order' => 1,
                    'is_active' => true,
                ],
            ],
        ],
    );

    $this->actingAs($admin)
        ->put(route('settings.update', 'public_website'), $payload)
        ->assertSessionHasErrors('hero_slides.0.button_url');

    app(SystemSettingsService::class)->updateGroup('public_website', [
        ...app(SystemSettingsService::class)->group('public_website'),
        'hero_slides' => [
            [
                'title' => 'Unsafe',
                'subtitle' => 'Unsafe URL should be stripped.',
                'button_label' => 'Open',
                'button_url' => 'javascript:alert(1)',
                'display_order' => 1,
                'is_active' => true,
                'image_path' => '/images/home/hero-slide-1.svg',
            ],
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Home')
            ->where('slides.0.button_url', ''));
});

it('sanitizes notification links before exposing them to the frontend', function (): void {
    $user = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\UnsafeNotification',
        'data' => [
            'title' => 'Unsafe link',
            'url' => 'javascript:alert(1)',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Notifications/Index')
            ->where('notifications.data.0.url', null));
});

it('applies hardened browser response headers to public pages', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeader('Content-Security-Policy');
});
