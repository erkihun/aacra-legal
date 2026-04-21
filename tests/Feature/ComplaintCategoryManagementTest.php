<?php

declare(strict_types=1);

use App\Enums\ComplaintComplainantType;
use App\Enums\ComplaintStatus;
use App\Models\Branch;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

it('allows an authorized user to view the complaint category list', function (): void {
    $user = createComplaintCategoryManager(['complaint-categories.view']);

    $this->actingAs($user)
        ->get(route('complaint-categories.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/ComplaintCategories/Index')
            ->where('can.create', false)
            ->has('categories.data')
        );
});

it('allows an authorized user to create a complaint category', function (): void {
    $user = createComplaintCategoryManager(['complaint-categories.view', 'complaint-categories.manage']);

    $this->actingAs($user)
        ->post(route('complaint-categories.store'), [
            'code' => 'PROC',
            'name_en' => 'Procurement',
            'name_am' => 'Procurement',
            'description' => 'Complaints related to procurement.',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('complaint_categories', [
        'code' => 'PROC',
        'name_en' => 'Procurement',
        'deleted_at' => null,
    ]);
});

it('allows an authorized user to update a complaint category', function (): void {
    $user = createComplaintCategoryManager(['complaint-categories.view', 'complaint-categories.manage']);
    $category = ComplaintCategory::query()->create([
        'code' => 'HR',
        'name_en' => 'Human Resources',
        'name_am' => 'Human Resources',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->patch(route('complaint-categories.update', $category), [
            'code' => 'HR',
            'name_en' => 'HR Complaints',
            'name_am' => 'HR Complaints',
            'description' => 'Updated description.',
            'is_active' => false,
        ])
        ->assertRedirect(route('complaint-categories.edit', $category));

    expect($category->fresh()?->name_en)->toBe('HR Complaints')
        ->and($category->fresh()?->is_active)->toBeFalse();
});

it('allows an authorized user to view complaint category details', function (): void {
    $user = createComplaintCategoryManager(['complaint-categories.view']);
    $category = ComplaintCategory::query()->create([
        'code' => 'FIN',
        'name_en' => 'Finance',
        'name_am' => 'Finance',
        'description' => 'Finance complaints.',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('complaint-categories.show', $category))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/ComplaintCategories/Show')
            ->where('categoryItem.id', $category->id)
            ->where('categoryItem.code', 'FIN')
        );
});

it('allows an authorized user to delete an unused complaint category', function (): void {
    $user = createComplaintCategoryManager(['complaint-categories.view', 'complaint-categories.manage']);
    $category = ComplaintCategory::query()->create([
        'code' => 'GEN',
        'name_en' => 'General',
        'name_am' => 'General',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('complaint-categories.destroy', $category))
        ->assertRedirect(route('complaint-categories.index'));

    $this->assertSoftDeleted('complaint_categories', ['id' => $category->id]);
});

it('denies unauthorized users from complaint category routes', function (): void {
    $user = User::factory()->create();
    $category = ComplaintCategory::query()->create([
        'code' => 'DENY',
        'name_en' => 'Denied',
        'name_am' => 'Denied',
        'is_active' => true,
    ]);

    $this->actingAs($user)->get(route('complaint-categories.index'))->assertForbidden();
    $this->actingAs($user)->post(route('complaint-categories.store'), [
        'code' => 'NEW',
        'name_en' => 'New Category',
        'name_am' => 'New Category',
        'is_active' => true,
    ])->assertForbidden();
    $this->actingAs($user)->patch(route('complaint-categories.update', $category), [
        'code' => 'DENY',
        'name_en' => 'Denied',
        'name_am' => 'Denied',
        'is_active' => true,
    ])->assertForbidden();
    $this->actingAs($user)->delete(route('complaint-categories.destroy', $category))->assertForbidden();
});

it('validates complaint category input and rejects duplicate codes', function (): void {
    $user = createComplaintCategoryManager(['complaint-categories.view', 'complaint-categories.manage']);
    ComplaintCategory::query()->create([
        'code' => 'DUP',
        'name_en' => 'Duplicate',
        'name_am' => 'Duplicate',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('complaint-categories.store'), [
            'code' => 'dup',
            'name_en' => '',
            'name_am' => '',
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['code', 'name_en', 'name_am']);
});

it('supports complaint category search and pagination', function (): void {
    $user = createComplaintCategoryManager(['complaint-categories.view']);

    foreach (range(1, 13) as $index) {
        ComplaintCategory::query()->create([
            'code' => sprintf('CAT-%02d', $index),
            'name_en' => $index === 13 ? 'Searchable Category' : "Category {$index}",
            'name_am' => "Category {$index}",
            'is_active' => true,
        ]);
    }

    $this->actingAs($user)
        ->get(route('complaint-categories.index', ['search' => 'Searchable']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/ComplaintCategories/Index')
            ->where('categories.data.0.name_en', 'Searchable Category')
            ->where('categories.total', 1)
        );

    $this->actingAs($user)
        ->get(route('complaint-categories.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/ComplaintCategories/Index')
            ->where('categories.per_page', 12)
        );
});

it('blocks complaint category deletion when complaints already reference it', function (): void {
    $user = createComplaintCategoryManager(['complaint-categories.view', 'complaint-categories.manage']);
    $category = ComplaintCategory::query()->create([
        'code' => 'PROC',
        'name_en' => 'Procurement',
        'name_am' => 'Procurement',
        'is_active' => true,
    ]);

    createComplaintUsingCategory('PROC');

    $this->actingAs($user)
        ->delete(route('complaint-categories.destroy', $category))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('complaint_categories', [
        'id' => $category->id,
        'deleted_at' => null,
    ]);
});

function createComplaintCategoryManager(array $permissions): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

function createComplaintUsingCategory(string $category): Complaint
{
    $user = User::factory()->create();
    $branch = Branch::query()->firstOrFail();
    $department = Department::query()->firstOrFail();

    return Complaint::query()->create([
        'complaint_number' => 'CMP-'.fake()->unique()->numerify('2026-####'),
        'complainant_user_id' => $user->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'complainant_type' => ComplaintComplainantType::BRANCH_EMPLOYEE,
        'complainant_name' => $user->name,
        'complainant_email' => $user->email,
        'complainant_phone' => $user->phone,
        'subject' => 'Complaint category dependency',
        'details' => '<p>Complaint details.</p>',
        'category' => $category,
        'submitted_at' => now(),
        'department_response_deadline_at' => now()->addDays(5),
        'status' => ComplaintStatus::ASSIGNED_TO_DEPARTMENT,
    ]);
}
