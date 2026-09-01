<?php

declare(strict_types=1);

use App\Enums\AdvisoryRequestStatus;
use App\Enums\LawsuitRequestStatus;
use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\Department;
use App\Models\LetterTemplate;
use App\Models\LawsuitFilingRequest;
use App\Models\RequesterAccount;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        ReferenceDataSeeder::class,
    ]);
});

// ────────────────────────────────────────────────────────────────
// Helpers
// ────────────────────────────────────────────────────────────────

function rpDepartment(): Department
{
    return Department::query()->firstOrFail();
}

function rpRequester(?Department $dept = null): RequesterAccount
{
    return RequesterAccount::factory()->create([
        'department_id' => ($dept ?? rpDepartment())->id,
        'is_active' => true,
    ]);
}

function rpCategory(): AdvisoryCategory
{
    return AdvisoryCategory::query()->where('is_active', true)->firstOrFail();
}

function rpTemplate(): LetterTemplate
{
    return LetterTemplate::factory()->create([
        'is_active' => true,
        'is_default' => true,
        'name' => 'Default Test Template',
        'subject_template' => '<p>Template subject block</p>',
        'recipient_block_template' => '<p>Template recipient block</p>',
        'body_content' => '<p>Formal request body</p>',
        'salutation_template' => '<p>To whom it may concern,</p>',
        'closing_content' => '<p>Sincerely,</p>',
        'signature_block_content' => '<p>Template signature block</p>',
    ]);
}

// ────────────────────────────────────────────────────────────────
// Auth guard separation
// ────────────────────────────────────────────────────────────────

it('internal login page does not authenticate a requester account', function (): void {
    $requester = rpRequester();

    $this->post(route('login'), [
        'email' => $requester->email,
        'password' => 'password',
    ])->assertRedirect();

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('requester login page does not authenticate an internal user', function (): void {
    $user = \App\Models\User::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('password')]);

    $this->post(route('requester.login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors();

    expect(Auth::guard('requester')->check())->toBeFalse();
});

// ────────────────────────────────────────────────────────────────
// Register
// ────────────────────────────────────────────────────────────────

it('renders the requester register page', function (): void {
    $this->get(route('requester.register'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Requester/Auth/Register')
                 ->has('departments')
        );
});

it('allows a new requester to register', function (): void {
    $dept = rpDepartment();

    $this->post(route('requester.register'), [
        'full_name' => 'Test Requester',
        'email' => 'portal@example.com',
        'department_id' => $dept->id,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect(route('requester.dashboard'));

    expect(Auth::guard('requester')->check())->toBeTrue();
    $this->assertDatabaseHas('requester_accounts', ['email' => 'portal@example.com']);
});

it('blocks registration with duplicate email', function (): void {
    $existing = rpRequester();
    $dept = rpDepartment();

    $this->post(route('requester.register'), [
        'full_name' => 'Duplicate',
        'email' => $existing->email,
        'department_id' => $dept->id,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors(['email']);
});

// ────────────────────────────────────────────────────────────────
// Login / logout
// ────────────────────────────────────────────────────────────────

it('allows a requester to log in and out', function (): void {
    $requester = rpRequester();

    $this->post(route('requester.login'), [
        'email' => $requester->email,
        'password' => 'password',
    ])->assertRedirect(route('requester.dashboard'));

    expect(Auth::guard('requester')->check())->toBeTrue();

    $this->post(route('requester.logout'))
        ->assertRedirect(route('requester.login'));

    expect(Auth::guard('requester')->check())->toBeFalse();
});

it('blocks an inactive requester from logging in', function (): void {
    $requester = RequesterAccount::factory()->inactive()->create([
        'department_id' => rpDepartment()->id,
    ]);

    $this->post(route('requester.login'), [
        'email' => $requester->email,
        'password' => 'password',
    ])->assertSessionHasErrors();
});

// ────────────────────────────────────────────────────────────────
// Dashboard
// ────────────────────────────────────────────────────────────────

it('renders the requester dashboard for an authenticated requester', function (): void {
    $requester = rpRequester();

    $this->actingAs($requester, 'requester')
        ->get(route('requester.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Requester/Dashboard')
                 ->has('stats')
                 ->has('recentAdvisory')
                 ->has('recentLawsuit')
        );
});

it('redirects unauthenticated users from the dashboard to requester login', function (): void {
    $this->get(route('requester.dashboard'))
        ->assertRedirect(route('requester.login'));
});

it('blocks requester accounts from the internal legal dashboard', function (): void {
    $requester = rpRequester();

    $this->actingAs($requester, 'requester')
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('blocks requester accounts from internal letter template management', function (): void {
    $requester = rpRequester();

    $this->actingAs($requester, 'requester')
        ->get(route('letter-templates.index'))
        ->assertRedirect(route('login'));
});

// ────────────────────────────────────────────────────────────────
// Advisory — submit
// ────────────────────────────────────────────────────────────────

it('allows a requester to submit an advisory request', function (): void {
    $requester = rpRequester();
    $category = rpCategory();
    $template = rpTemplate();

    $this->actingAs($requester, 'requester')
        ->post(route('requester.advisory.store'), [
            'category_id' => $category->id,
            'subject' => 'Labour law interpretation',
            'description' => 'We need guidance on a dismissal procedure.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('advisory_requests', [
        'requester_account_id' => $requester->id,
        'subject' => 'Labour law interpretation',
        'letter_template_id' => $template->id,
        'status' => AdvisoryRequestStatus::SUBMITTED->value,
    ]);

    $advisory = AdvisoryRequest::query()->firstOrFail();

    expect($advisory->letter_snapshot)
        ->toBeArray()
        ->and($advisory->letter_snapshot['template_id'])->toBe((string) $template->id)
        ->and($advisory->letter_snapshot['subject_template'])->toBe('<p>Template subject block</p>')
        ->and($advisory->letter_snapshot['recipient_block_template'])->toBe('<p>Template recipient block</p>')
        ->and($advisory->letter_snapshot['signature_block_content'])->toBe('<p>Template signature block</p>');
});

it('validates required fields when submitting an advisory request', function (): void {
    $requester = rpRequester();

    $this->actingAs($requester, 'requester')
        ->post(route('requester.advisory.store'), [])
        ->assertSessionHasErrors(['category_id', 'subject', 'description']);
});

// ────────────────────────────────────────────────────────────────
// Advisory — visibility scoping
// ────────────────────────────────────────────────────────────────

it('shows only own advisory requests in the index', function (): void {
    $requesterA = rpRequester();
    $requesterB = rpRequester();
    $category = rpCategory();

    AdvisoryRequest::factory()->create([
        'requester_account_id' => $requesterA->id,
        'category_id' => $category->id,
        'subject' => 'Requester A Request',
        'status' => AdvisoryRequestStatus::SUBMITTED->value,
    ]);

    AdvisoryRequest::factory()->create([
        'requester_account_id' => $requesterB->id,
        'category_id' => $category->id,
        'subject' => 'Requester B Request',
        'status' => AdvisoryRequestStatus::SUBMITTED->value,
    ]);

    $this->actingAs($requesterA, 'requester')
        ->get(route('requester.advisory.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Requester/Advisory/Index')
                 ->where('requests.data.0.subject', 'Requester A Request')
                 ->where('requests.total', 1)
        );
});

it('blocks a requester from viewing another requester\'s advisory request', function (): void {
    $requesterA = rpRequester();
    $requesterB = rpRequester();
    $category = rpCategory();

    $advisory = AdvisoryRequest::factory()->create([
        'requester_account_id' => $requesterB->id,
        'category_id' => $category->id,
        'subject' => 'Private',
        'status' => AdvisoryRequestStatus::SUBMITTED->value,
    ]);

    $this->actingAs($requesterA, 'requester')
        ->get(route('requester.advisory.show', $advisory))
        ->assertForbidden();
});

// ────────────────────────────────────────────────────────────────
// Advisory — edit
// ────────────────────────────────────────────────────────────────

it('allows editing a submitted advisory request', function (): void {
    $requester = rpRequester();
    $category = rpCategory();
    $template = rpTemplate();

    $advisory = AdvisoryRequest::factory()->create([
        'requester_account_id' => $requester->id,
        'category_id' => $category->id,
        'department_id' => $requester->department_id,
        'letter_template_id' => $template->id,
        'letter_snapshot' => [
            'template_id' => (string) $template->id,
            'template_name' => $template->name,
            'language' => $template->language,
            'header_image_path' => null,
            'footer_image_path' => null,
            'salutation_template' => '<p>To whom it may concern,</p>',
            'body_content' => '<p>Formal request body</p>',
            'closing_content' => '<p>Sincerely,</p>',
            'layout_config' => null,
        ],
        'subject' => 'Original',
        'description' => 'Original description.',
        'status' => AdvisoryRequestStatus::SUBMITTED->value,
    ]);

    $this->actingAs($requester, 'requester')
        ->patch(route('requester.advisory.update', $advisory), [
            'category_id' => $category->id,
            'subject' => 'Updated subject',
            'description' => 'Updated description.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('advisory_requests', [
        'id' => $advisory->id,
        'subject' => 'Updated subject',
    ]);
});

it('blocks editing an advisory request that is not submitted or returned', function (): void {
    $requester = rpRequester();
    $category = rpCategory();

    $advisory = AdvisoryRequest::factory()->create([
        'requester_account_id' => $requester->id,
        'category_id' => $category->id,
        'subject' => 'Under review',
        'status' => AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW->value,
    ]);

    $this->actingAs($requester, 'requester')
        ->patch(route('requester.advisory.update', $advisory), [
            'category_id' => $category->id,
            'subject' => 'Hacked',
            'description' => 'Attempt',
        ])
        ->assertForbidden();
});

// ────────────────────────────────────────────────────────────────
// Lawsuit — submit
// ────────────────────────────────────────────────────────────────

it('allows a requester to submit a lawsuit filing request', function (): void {
    $requester = rpRequester();
    $template = rpTemplate();

    $this->actingAs($requester, 'requester')
        ->post(route('requester.lawsuit-requests.store'), [
            'subject' => 'Contract breach',
            'description' => 'We need to initiate legal proceedings against Vendor X.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('lawsuit_filing_requests', [
        'requester_account_id' => $requester->id,
        'subject' => 'Contract breach',
        'letter_template_id' => $template->id,
        'status' => LawsuitRequestStatus::SUBMITTED->value,
    ]);

    $lawsuit = LawsuitFilingRequest::query()->firstOrFail();

    expect($lawsuit->letter_snapshot)
        ->toBeArray()
        ->and($lawsuit->letter_snapshot['template_id'])->toBe((string) $template->id)
        ->and($lawsuit->letter_snapshot['subject_template'])->toBe('<p>Template subject block</p>')
        ->and($lawsuit->letter_snapshot['recipient_block_template'])->toBe('<p>Template recipient block</p>')
        ->and($lawsuit->letter_snapshot['signature_block_content'])->toBe('<p>Template signature block</p>');
});

it('validates required fields when submitting a lawsuit request', function (): void {
    $requester = rpRequester();

    $this->actingAs($requester, 'requester')
        ->post(route('requester.lawsuit-requests.store'), [])
        ->assertSessionHasErrors(['subject', 'description']);
});

// ────────────────────────────────────────────────────────────────
// Lawsuit — visibility scoping
// ────────────────────────────────────────────────────────────────

it('blocks a requester from viewing another requester\'s lawsuit request', function (): void {
    $requesterA = rpRequester();
    $requesterB = rpRequester();

    $lawsuit = LawsuitFilingRequest::factory()->create([
        'requester_account_id' => $requesterB->id,
        'requesting_department_id' => $requesterB->department_id,
    ]);

    $this->actingAs($requesterA, 'requester')
        ->get(route('requester.lawsuit-requests.show', $lawsuit))
        ->assertForbidden();
});

// ────────────────────────────────────────────────────────────────
// Default letter template
// ────────────────────────────────────────────────────────────────

it('passes the default template to the advisory create page', function (): void {
    $requester = rpRequester();
    $template = rpTemplate();

    $this->actingAs($requester, 'requester')
        ->get(route('requester.advisory.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Requester/Advisory/Create')
                 ->has('defaultTemplate')
                 ->where('defaultTemplate.name', 'Default Test Template')
                 ->where('defaultTemplate.id', (string) $template->id)
                 ->where('defaultTemplate.subject_template', '<p>Template subject block</p>')
                 ->where('defaultTemplate.recipient_block_template', '<p>Template recipient block</p>')
                 ->where('defaultTemplate.body_content', '<p>Formal request body</p>')
                 ->where('defaultTemplate.signature_block_content', '<p>Template signature block</p>')
                 ->where('requestingDepartment.id', (string) $requester->department_id)
        );
});

it('passes the default template to the lawsuit create page', function (): void {
    $requester = rpRequester();
    $template = rpTemplate();

    $this->actingAs($requester, 'requester')
        ->get(route('requester.lawsuit-requests.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Requester/Lawsuit/Create')
                 ->has('defaultTemplate')
                 ->where('defaultTemplate.id', (string) $template->id)
                 ->where('defaultTemplate.subject_template', '<p>Template subject block</p>')
                 ->where('defaultTemplate.recipient_block_template', '<p>Template recipient block</p>')
                 ->where('defaultTemplate.body_content', '<p>Formal request body</p>')
                 ->where('defaultTemplate.signature_block_content', '<p>Template signature block</p>')
                 ->where('requestingDepartment.id', (string) $requester->department_id)
        );
});

it('uses the existing active template when no template is marked default without creating duplicates', function (): void {
    $requester = rpRequester();
    $category = rpCategory();

    $template = LetterTemplate::factory()->create([
        'is_active' => true,
        'is_default' => false,
        'name' => 'Only Active Request Template',
        'body_content' => '<p>Existing active template body</p>',
    ]);

    $this->actingAs($requester, 'requester')
        ->post(route('requester.advisory.store'), [
            'category_id' => $category->id,
            'subject' => 'Use existing active template',
            'description' => 'The resolver must not create a new template.',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('letter_templates', 1);
    $this->assertDatabaseHas('advisory_requests', [
        'requester_account_id' => $requester->id,
        'letter_template_id' => $template->id,
    ]);
});

it('renders advisory requester detail with formal letter content', function (): void {
    $requester = rpRequester();
    $category = rpCategory();
    $template = rpTemplate();

    $advisory = AdvisoryRequest::factory()->create([
        'requester_account_id' => $requester->id,
        'department_id' => $requester->department_id,
        'category_id' => $category->id,
        'letter_template_id' => $template->id,
        'letter_snapshot' => [
            'template_id' => (string) $template->id,
            'template_name' => $template->name,
            'language' => $template->language,
            'header_image_path' => null,
            'footer_image_path' => null,
            'salutation_template' => '<p>To whom it may concern,</p>',
            'body_content' => '<p>Formal request body</p>',
            'closing_content' => '<p>Sincerely,</p>',
            'layout_config' => null,
        ],
        'subject' => 'Formal advisory request',
        'description' => '<p>Rendered inside the formal template.</p>',
        'status' => AdvisoryRequestStatus::SUBMITTED->value,
    ]);

    $this->actingAs($requester, 'requester')
        ->get(route('requester.advisory.show', $advisory))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Requester/Advisory/Show')
            ->where('requestItem.formal_letter.template_name', 'Default Test Template')
            ->where('requestItem.formal_letter.subject', 'Formal advisory request')
            ->where('requestItem.formal_letter.body_content', '<p>Rendered inside the formal template.</p>')
        );
});

it('renders lawsuit requester detail with formal letter content', function (): void {
    $requester = rpRequester();
    $template = rpTemplate();

    $lawsuit = LawsuitFilingRequest::factory()->create([
        'requester_account_id' => $requester->id,
        'requesting_department_id' => $requester->department_id,
        'letter_template_id' => $template->id,
        'letter_snapshot' => [
            'template_id' => (string) $template->id,
            'template_name' => $template->name,
            'language' => $template->language,
            'header_image_path' => null,
            'footer_image_path' => null,
            'salutation_template' => '<p>To whom it may concern,</p>',
            'body_content' => '<p>Formal request body</p>',
            'closing_content' => '<p>Sincerely,</p>',
            'layout_config' => null,
        ],
        'subject' => 'Formal lawsuit request',
        'description' => '<p>Rendered inside the formal template.</p>',
        'status' => LawsuitRequestStatus::SUBMITTED->value,
    ]);

    $this->actingAs($requester, 'requester')
        ->get(route('requester.lawsuit-requests.show', $lawsuit))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Requester/Lawsuit/Show')
            ->where('requestItem.formal_letter.template_name', 'Default Test Template')
            ->where('requestItem.formal_letter.subject', 'Formal lawsuit request')
            ->where('requestItem.formal_letter.body_content', '<p>Rendered inside the formal template.</p>')
        );
});

it('loads legacy requester requests without a template snapshot safely', function (): void {
    $requester = rpRequester();
    $category = rpCategory();

    $advisory = AdvisoryRequest::factory()->create([
        'requester_account_id' => $requester->id,
        'department_id' => $requester->department_id,
        'category_id' => $category->id,
        'letter_template_id' => null,
        'letter_snapshot' => null,
        'subject' => 'Legacy advisory request',
        'description' => 'Legacy plain description.',
        'status' => AdvisoryRequestStatus::SUBMITTED->value,
    ]);

    $this->actingAs($requester, 'requester')
        ->get(route('requester.advisory.show', $advisory))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Requester/Advisory/Show')
            ->where('requestItem.formal_letter.body_content', 'Legacy plain description.')
            ->where('requestItem.formal_letter.template_name', null)
        );
});

it('fails safely when the default template is missing for requester create pages', function (): void {
    $requester = rpRequester();

    $this->actingAs($requester, 'requester')
        ->get(route('requester.advisory.create'))
        ->assertRedirect(route('requester.dashboard'))
        ->assertSessionHas('error');

    $this->actingAs($requester, 'requester')
        ->get(route('requester.lawsuit-requests.create'))
        ->assertRedirect(route('requester.dashboard'))
        ->assertSessionHas('error');
});

it('keeps requester advisory attachments working with the template-backed flow', function (): void {
    Storage::fake('public');

    $requester = rpRequester();
    $category = rpCategory();
    rpTemplate();

    $this->actingAs($requester, 'requester')
        ->post(route('requester.advisory.store'), [
            'category_id' => $category->id,
            'subject' => 'Template-backed request with file',
            'description' => 'Attachment should still be stored.',
            'attachments' => [
                UploadedFile::fake()->create('supporting-note.pdf', 24, 'application/pdf'),
            ],
        ])
        ->assertRedirect();

    $advisory = AdvisoryRequest::query()->with('attachments')->firstOrFail();

    expect($advisory->attachments)->toHaveCount(1);
});
