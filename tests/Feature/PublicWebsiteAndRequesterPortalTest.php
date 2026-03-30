<?php

declare(strict_types=1);

use App\Enums\PublicPostStatus;
use App\Enums\SystemRole;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\Department;
use App\Models\PublicPost;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
        DemoUserSeeder::class,
    ]);
});

it('shows the public homepage with published legal updates', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();

    PublicPost::factory()->for($director, 'author')->create([
        'title' => 'Legal update one',
        'slug' => 'legal-update-one',
        'status' => PublicPostStatus::PUBLISHED,
        'published_at' => now()->subDay(),
    ]);

    PublicPost::factory()->draft()->for($director, 'author')->create([
        'title' => 'Draft update',
        'slug' => 'draft-update',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Home')
            ->has('featuredPosts', 1)
            ->where('featuredPosts.0.slug', 'legal-update-one'));
});

it('shows only published public posts and denies draft detail access', function (): void {
    $director = User::query()->where('email', 'director@ldms.test')->firstOrFail();

    $published = PublicPost::factory()->for($director, 'author')->create([
        'title' => 'Published legal notice',
        'slug' => 'published-legal-notice',
        'status' => PublicPostStatus::PUBLISHED,
        'published_at' => now()->subHour(),
    ]);

    $draft = PublicPost::factory()->draft()->for($director, 'author')->create([
        'title' => 'Internal draft note',
        'slug' => 'internal-draft-note',
    ]);

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Posts/Index')
            ->has('posts.data', 1)
            ->where('posts.data.0.slug', 'published-legal-notice'));

    $this->get(route('posts.show', $published))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Posts/Show')
            ->where('post.slug', 'published-legal-notice'));

    $this->get(route('posts.show', $draft))->assertNotFound();
});

it('keeps public pages available when the public posts table is missing', function (): void {
    Schema::drop('public_posts');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Home')
            ->has('featuredPosts', 0));

    $this->get(route('posts.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/Posts/Index')
            ->has('posts.data', 0));
});

it('returns not found for public post detail when the public posts table is missing', function (): void {
    Schema::drop('public_posts');

    $this->get(route('posts.show', ['slug' => 'missing-post']))->assertNotFound();
});

it('registers a department requester account with the correct default role', function (): void {
    $department = Department::query()->where('code', 'HR')->firstOrFail();

    $this->post(route('register'), [
        'name' => 'Public Requester',
        'email' => 'public.requester@example.test',
        'phone' => '+251911234567',
        'department_id' => $department->id,
        'job_title' => 'HR Officer',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'public.requester@example.test')->firstOrFail();

    expect($user->department_id)->toBe($department->id)
        ->and($user->hasRole(SystemRole::DEPARTMENT_REQUESTER->value))->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('allows requester portal access and denies direct admin user management access', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();

    $this->actingAs($requester)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('role_context.key', 'department_requester'));

    $this->actingAs($requester)
        ->get(route('users.index'))
        ->assertForbidden();
});

it('allows authorized admins to open the public post edit page by slug route key', function (): void {
    $admin = User::query()->where('email', 'admin@ldms.test')->firstOrFail();

    $post = PublicPost::factory()->for($admin, 'author')->create([
        'title' => 'Deployment Notice',
        'slug' => 'deployment-notice',
        'status' => PublicPostStatus::DRAFT,
    ]);

    $this->actingAs($admin)
        ->get(route('public-posts.edit', $post->getRouteKey()))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/PublicPosts/Form')
            ->where('postItem.route_key', 'deployment-notice'));
});

it('allows a requester to create and track an advisory request', function (): void {
    $requester = User::query()->where('email', 'requester@ldms.test')->firstOrFail();
    $category = AdvisoryCategory::query()->where('is_active', true)->firstOrFail();

    $this->actingAs($requester)
        ->post(route('advisory.store'), [
            'department_id' => $requester->department_id,
            'category_id' => $category->id,
            'subject' => 'Need contract interpretation support',
            'request_type' => 'written',
            'priority' => 'medium',
            'description' => 'The HR department needs legal interpretation support for a contract clause and its operational implications.',
            'due_date' => now()->addDays(5)->toDateString(),
        ])
        ->assertRedirect();

    $advisoryRequest = AdvisoryRequest::query()
        ->where('requester_user_id', $requester->id)
        ->latest('created_at')
        ->firstOrFail();

    $this->actingAs($requester)
        ->get(route('advisory.show', $advisoryRequest))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Advisory/Show')
            ->has('requestItem')
            ->has('can')
            ->has('teamLeaders')
            ->has('experts'));
});
