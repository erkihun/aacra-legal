<?php

declare(strict_types=1);

use App\Models\AdvisoryCategory;
use App\Models\CaseType;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\SequenceCounter;
use App\Models\User;
use Database\Seeders\DemoWorkflowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoWorkflowSeeder::class,
    ]);
});

it('generates the next advisory request number when demo data already seeded the first number', function (): void {
    SequenceCounter::query()->where('scope', 'ADV')->delete();

    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $department = Department::query()->where('code', 'HR')->firstOrFail();
    $category = AdvisoryCategory::query()->firstOrFail();

    $this->actingAs($requester)
        ->post(route('advisory.store'), [
            'department_id' => $department->id,
            'category_id' => $category->id,
            'subject' => 'Sequence recovery validation',
            'request_type' => 'written',
            'priority' => 'medium',
            'description' => str_repeat('Valid advisory description. ', 5),
        ])
        ->assertRedirect();

    $this->actingAs($requester)
        ->get(route('advisory.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Index')
            ->where('requests.data.0.request_number', 'ADV-2026-0002'));
});

it('stores a manually supplied legal case number through the case intake flow', function (): void {
    $registrar = User::query()->where('email', 'registrar@ldms.test')->firstOrFail();

    $this->actingAs($registrar)
        ->post(route('cases.store'), [
            'case_number' => 'CASE-2026-0002',
            'main_case_type' => 'civil-law',
            'court_id' => Court::query()->firstOrFail()->id,
            'case_type_id' => CaseType::query()->where('code', '!=', 'LAB')->firstOrFail()->id,
            'plaintiff' => 'Sequence Recovery Plaintiff',
            'defendant' => 'Institution',
            'claim_summary' => '<p>This filing validates that a manually supplied case number is accepted.</p>',
            'status' => 'under_director_review',
            'filing_date' => now()->subDay()->toDateString(),
            'priority' => 'medium',
        ])
        ->assertRedirect();

    expect(LegalCase::query()->where('case_number', 'CASE-2026-0002')->exists())->toBeTrue();
});
