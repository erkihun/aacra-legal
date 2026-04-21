<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
    ]);
});

it('allows an authorized user to view the branch list', function (): void {
    $user = createBranchManager(['branches.view']);

    $this->actingAs($user)
        ->get(route('branches.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Branches/Index')
            ->where('can.create', false)
            ->has('branches.data')
        );
});

it('allows an authorized user to create a branch', function (): void {
    $user = createBranchManager(['branches.create', 'branches.view']);

    $this->actingAs($user)
        ->post(route('branches.store'), [
            'code' => 'ADM-01',
            'name_en' => 'Adama Branch',
            'name_am' => 'Adama Branch',
            'region' => 'Oromia',
            'city' => 'Adama',
            'address' => 'Main road',
            'phone' => '+251911111111',
            'email' => 'adama.branch@example.test',
            'manager_name' => 'Branch Manager',
            'notes' => 'Regional branch office.',
            'is_head_office' => false,
            'is_active' => true,
        ])
        ->assertRedirect();

    $branch = Branch::query()->where('code', 'ADM-01')->firstOrFail();

    expect($branch->city)->toBe('Adama')
        ->and($branch->email)->toBe('adama.branch@example.test');
});

it('allows an authorized user to update a branch', function (): void {
    $user = createBranchManager(['branches.update', 'branches.view']);
    $branch = Branch::query()->create([
        'code' => 'DIR-01',
        'name_en' => 'Dire Branch',
        'name_am' => 'Dire Branch',
        'is_head_office' => false,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->patch(route('branches.update', $branch), [
            'code' => 'DIR-01',
            'name_en' => 'Dire Dawa Branch',
            'name_am' => 'Dire Dawa Branch',
            'region' => 'Dire Dawa',
            'city' => 'Dire Dawa',
            'address' => 'Updated address',
            'phone' => '+251922222222',
            'email' => 'dire.branch@example.test',
            'manager_name' => 'Updated Manager',
            'notes' => 'Updated notes.',
            'is_head_office' => false,
            'is_active' => false,
        ])
        ->assertRedirect(route('branches.edit', $branch));

    expect($branch->fresh()?->name_en)->toBe('Dire Dawa Branch')
        ->and($branch->fresh()?->is_active)->toBeFalse();
});

it('allows an authorized user to view branch details', function (): void {
    $user = createBranchManager(['branches.view']);
    $branch = Branch::query()->create([
        'code' => 'BHR-01',
        'name_en' => 'Bahir Dar Branch',
        'name_am' => 'Bahir Dar Branch',
        'city' => 'Bahir Dar',
        'is_head_office' => false,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('branches.show', $branch))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Branches/Show')
            ->where('branchItem.id', $branch->id)
            ->where('branchItem.code', 'BHR-01')
        );
});

it('allows an authorized user to delete an unreferenced branch', function (): void {
    $user = createBranchManager(['branches.delete', 'branches.view']);
    $branch = Branch::query()->create([
        'code' => 'GON-01',
        'name_en' => 'Gondar Branch',
        'name_am' => 'Gondar Branch',
        'is_head_office' => false,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('branches.destroy', $branch))
        ->assertRedirect(route('branches.index'));

    $this->assertSoftDeleted('branches', ['id' => $branch->id]);
});

it('denies unauthorized users from branch management routes', function (): void {
    $user = User::factory()->create();
    $branch = Branch::query()->firstOrFail();

    $this->actingAs($user)->get(route('branches.index'))->assertForbidden();
    $this->actingAs($user)->post(route('branches.store'), [
        'code' => 'DENY-01',
        'name_en' => 'Denied Branch',
        'is_head_office' => false,
        'is_active' => true,
    ])->assertForbidden();
    $this->actingAs($user)->patch(route('branches.update', $branch), [
        'code' => $branch->code,
        'name_en' => $branch->name_en,
        'is_head_office' => $branch->is_head_office,
        'is_active' => $branch->is_active,
    ])->assertForbidden();
    $this->actingAs($user)->delete(route('branches.destroy', $branch))->assertForbidden();
});

it('validates branch input and rejects duplicate branch codes', function (): void {
    $user = createBranchManager(['branches.create', 'branches.view']);
    Branch::query()->create([
        'code' => 'DUP-01',
        'name_en' => 'Duplicate Branch',
        'name_am' => 'Duplicate Branch',
        'is_head_office' => false,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('branches.store'), [
            'code' => 'dup-01',
            'name_en' => '',
            'email' => 'invalid-email',
            'phone' => 'bad',
            'is_head_office' => false,
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['code', 'name_en', 'email', 'phone']);
});

it('supports branch search and pagination filters', function (): void {
    $user = createBranchManager(['branches.view']);

    foreach (range(1, 13) as $index) {
        Branch::query()->create([
            'code' => sprintf('BR-%02d', $index),
            'name_en' => $index === 13 ? 'Searchable Branch' : "Branch {$index}",
            'name_am' => "Branch {$index}",
            'city' => $index === 13 ? 'Adama' : 'Addis Ababa',
            'is_head_office' => false,
            'is_active' => true,
        ]);
    }

    $this->actingAs($user)
        ->get(route('branches.index', ['search' => 'Searchable', 'location' => 'Adama']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Branches/Index')
            ->where('branches.data.0.name_en', 'Searchable Branch')
            ->where('branches.total', 1)
        );

    $this->actingAs($user)
        ->get(route('branches.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Branches/Index')
            ->where('branches.per_page', 12)
        );
});

it('blocks branch deletion when the branch is still referenced', function (): void {
    $user = createBranchManager(['branches.delete', 'branches.view']);
    $branch = Branch::query()->create([
        'code' => 'REF-01',
        'name_en' => 'Referenced Branch',
        'name_am' => 'Referenced Branch',
        'is_head_office' => false,
        'is_active' => true,
    ]);

    User::factory()->create([
        'branch_id' => $branch->id,
        'email' => 'branch.ref@example.test',
    ]);

    $this->actingAs($user)
        ->delete(route('branches.destroy', $branch))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);
});

function createBranchManager(array $permissions): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}
