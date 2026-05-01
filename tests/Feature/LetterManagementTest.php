<?php

declare(strict_types=1);

use App\Actions\RenderLetterPdfAction;
use App\Actions\GenerateLetterReferenceNumberAction;
use App\Enums\LocaleCode;
use App\Models\Department;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('public');

    $this->seed([
        PermissionSeeder::class,
    ]);
});

it('shows the letter list and create action for authorized users', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create']);
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template);

    $this->actingAs($user)
        ->get(route('letters.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Letters/Index')
            ->where('can.create', true)
            ->has('letters.data', 1)
            ->where('letters.data.0.id', $letter->id)
        );
});

it('loads the letter create page for authorized users', function (): void {
    $user = createLetterUser(['letters.create']);
    $template = createLetterTemplateForLetters();
    $department = createLetterDepartment();

    $this->actingAs($user)
        ->get(route('letters.create', ['template_id' => $template->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Letters/Form')
            ->where('selectedTemplate.id', $template->id)
            ->has('departments', 1)
            ->where('departments.0.id', $department->id)
        );
});

it('allows an authorized user to create a letter from a selected template', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create', 'letter_templates.view']);
    $template = createLetterTemplateForLetters([
        'reference_prefix' => 'LEG',
        'reference_start_number' => 10,
        'subject_template' => 'Subject: Legal Review',
        'salutation_template' => 'Dear {recipient_name},',
        'body_content' => '<p>Template body content.</p>',
        'closing_content' => 'Regards,',
        'signature_block_content' => 'Director of Legal Affairs',
        'header_image_path' => 'letter-templates/template-1/header.png',
        'footer_image_path' => 'letter-templates/template-1/footer.png',
        'layout_config' => [
            'margin_top_mm' => 20,
            'margin_right_mm' => 18,
            'margin_bottom_mm' => 20,
            'margin_left_mm' => 18,
            'header_top_margin_mm' => 3,
            'header_bottom_spacing_mm' => 7,
            'footer_top_spacing_mm' => 6,
            'footer_left_margin_mm' => 9,
            'footer_right_margin_mm' => 11,
            'footer_bottom_margin_mm' => 4,
            'content_top_margin_mm' => 17,
            'content_bottom_margin_mm' => 14,
        ],
    ]);

    $preview = app(GenerateLetterReferenceNumberAction::class)->preview($template);

    $this->actingAs($user)
        ->post(route('letters.store'), [
            'template_id' => $template->id,
            'reference_number' => $preview,
            'reference_number_preview' => $preview,
            'letter_date' => '2026-04-28',
            'recipient_name' => 'Aster Tadesse',
            'recipient_title' => 'Director',
            'recipient_organization' => 'Public Service Office',
            'recipient_address' => 'Addis Ababa',
            'subject' => '',
            'salutation' => '',
            'body_content' => '',
            'closing_content' => '',
            'signature_block_content' => '',
            'cc_content' => '',
            'enclosure_content' => '',
            'status' => 'draft',
            'language' => 'en',
            'page_size' => 'A4',
            'orientation' => 'portrait',
            'margin_top_mm' => 20,
            'margin_right_mm' => 18,
            'margin_bottom_mm' => 20,
            'margin_left_mm' => 18,
        ])
        ->assertRedirect();

    $letter = Letter::query()->latest('created_at')->firstOrFail();

    expect($letter->template_id)->toBe($template->id)
        ->and($letter->reference_number)->toStartWith('LEG/')
        ->and($letter->subject)->toBe('Subject: Legal Review')
        ->and($letter->salutation)->toBe('Dear {recipient_name},')
        ->and($letter->body_content)->toBe('<p>Template body content.</p>')
        ->and($letter->closing_content)->toBe('Regards,')
        ->and($letter->signature_block_content)->toBe('Director of Legal Affairs')
        ->and($letter->header_image_path_snapshot)->toStartWith("letters/{$letter->id}/header-")
        ->and($letter->footer_image_path_snapshot)->toStartWith("letters/{$letter->id}/footer-")
        ->and($letter->signature_image_path_snapshot)->toStartWith("letters/{$letter->id}/signature-")
        ->and($letter->signer_full_name_snapshot)->toBe($user->name)
        ->and($letter->layout_config['header_top_margin_mm'])->toBe(3)
        ->and($letter->layout_config['header_bottom_spacing_mm'])->toBe(7)
        ->and($letter->layout_config['footer_top_spacing_mm'])->toBe(6)
        ->and($letter->layout_config['footer_left_margin_mm'])->toBe(9)
        ->and($letter->layout_config['footer_right_margin_mm'])->toBe(11)
        ->and($letter->layout_config['footer_bottom_margin_mm'])->toBe(4)
        ->and($letter->layout_config['content_top_margin_mm'])->toBe(17)
        ->and($letter->layout_config['content_bottom_margin_mm'])->toBe(14);

    Storage::disk('public')->assertExists((string) $letter->header_image_path_snapshot);
    Storage::disk('public')->assertExists((string) $letter->footer_image_path_snapshot);
    Storage::disk('public')->assertExists((string) $letter->signature_image_path_snapshot);
});

it('supports creating a letter with multiple recipient names entered by newline in the textarea', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create']);
    $template = createLetterTemplateForLetters();
    $preview = app(GenerateLetterReferenceNumberAction::class)->preview($template);

    $this->actingAs($user)
        ->post(route('letters.store'), letterPayload($template, [
            'reference_number' => $preview,
            'reference_number_preview' => $preview,
            'recipient_names_text' => "Ato Abebe Kebede\nW/ro Hana Tadesse",
        ]))
        ->assertRedirect();

    $letter = Letter::query()->latest('created_at')->firstOrFail();

    expect($letter->recipientDisplayLines())->toBe(['Ato Abebe Kebede', 'W/ro Hana Tadesse'])
        ->and($letter->recipient_name)->toBe('Ato Abebe Kebede');
});

it('supports creating a letter with multiple selected recipient departments', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create']);
    $template = createLetterTemplateForLetters();
    $legal = createLetterDepartment([
        'name_en' => 'Legal Directorate',
        'name_am' => 'የህግ ዳይሬክቶሬት',
    ]);
    $finance = createLetterDepartment([
        'name_en' => 'Finance Department',
        'name_am' => 'የፋይናንስ መምሪያ',
    ]);
    $preview = app(GenerateLetterReferenceNumberAction::class)->preview($template);

    $this->actingAs($user)
        ->post(route('letters.store'), letterPayload($template, [
            'reference_number' => $preview,
            'reference_number_preview' => $preview,
            'recipient_department_ids' => [$legal->id, $finance->id],
        ]))
        ->assertRedirect();

    $letter = Letter::query()->latest('created_at')->firstOrFail();

    expect($letter->recipientDisplayLines())->toBe(['Legal Directorate', 'Finance Department'])
        ->and($letter->recipient_name)->toBe('Legal Directorate')
        ->and($letter->resolvedRecipients()[0]['recipient_department_id'])->toBe($legal->id)
        ->and($letter->resolvedRecipients()[1]['recipient_department_id'])->toBe($finance->id);
});

it('supports creating a letter with recipient textarea names and selected departments together', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create', 'letters.preview']);
    $template = createLetterTemplateForLetters();
    $legal = createLetterDepartment([
        'code' => 'LEG',
        'name_en' => 'Legal Directorate',
        'name_am' => 'የህግ ዳይሬክቶሬት',
    ]);
    $finance = createLetterDepartment([
        'code' => 'FIN',
        'name_en' => 'Finance Department',
        'name_am' => 'የፋይናንስ መምሪያ',
    ]);
    $preview = app(GenerateLetterReferenceNumberAction::class)->preview($template);

    $this->actingAs($user)
        ->post(route('letters.store'), letterPayload($template, [
            'reference_number' => $preview,
            'reference_number_preview' => $preview,
            'recipient_names_text' => 'Ato Abebe Kebede',
            'recipient_department_ids' => [$legal->id, $finance->id],
        ]))
        ->assertRedirect();

    $letter = Letter::query()->latest('created_at')->firstOrFail();

    expect($letter->recipientDisplayLines())->toBe([
        'Ato Abebe Kebede',
        'Legal Directorate',
        'Finance Department',
    ]);

    $this->actingAs($user)
        ->get(route('letters.preview', $letter))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('letterItem.recipients.0.recipient_name', 'Ato Abebe Kebede')
            ->where('letterItem.recipients.1.recipient_department_name_en', 'Legal Directorate')
            ->where('letterItem.recipients.2.recipient_department_name_en', 'Finance Department')
        );

    $html = app(RenderLetterPdfAction::class)->html($letter);

    expect($html)
        ->toContain('<ul class="recipient-list">')
        ->toContain('<li>Ato Abebe Kebede</li>')
        ->toContain('<li>Legal Directorate</li>')
        ->toContain('<li>Finance Department</li>');
});

it('ignores blank lines in the recipient textarea safely', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create']);
    $template = createLetterTemplateForLetters();
    $preview = app(GenerateLetterReferenceNumberAction::class)->preview($template);

    $this->actingAs($user)
        ->post(route('letters.store'), letterPayload($template, [
            'reference_number' => $preview,
            'reference_number_preview' => $preview,
            'recipient_names_text' => "Ato Abebe Kebede\n\n  \nW/ro Hana Tadesse\n",
        ]))
        ->assertRedirect();

    $letter = Letter::query()->latest('created_at')->firstOrFail();

    expect($letter->recipientDisplayLines())->toBe(['Ato Abebe Kebede', 'W/ro Hana Tadesse']);
});

it('fails validation when both recipient textarea and department selections are empty', function (): void {
    $user = createLetterUser(['letters.create']);
    $template = createLetterTemplateForLetters();

    $this->actingAs($user)
        ->post(route('letters.store'), letterPayload($template, [
            'recipient_names_text' => '',
            'recipient_department_ids' => [],
            'recipient_name' => '',
        ]))
        ->assertSessionHasErrors(['recipients']);
});

it('reloads saved recipient textarea and selected departments on the edit form', function (): void {
    $user = createLetterUser(['letters.view', 'letters.update']);
    $template = createLetterTemplateForLetters();
    $legal = createLetterDepartment([
        'name_en' => 'Legal Directorate',
        'name_am' => 'የህግ ዳይሬክቶሬት',
    ]);
    $finance = createLetterDepartment([
        'name_en' => 'Finance Department',
        'name_am' => 'የፋይናንስ መምሪያ',
    ]);
    $letter = createLetterForTesting($template, [
        'recipients' => [
            [
                'recipient_type' => 'text',
                'recipient_name' => 'Ato Abebe Kebede',
                'recipient_department_id' => null,
                'recipient_department_name_en' => null,
                'recipient_department_name_am' => null,
            ],
            [
                'recipient_type' => 'department',
                'recipient_name' => null,
                'recipient_department_id' => $legal->id,
                'recipient_department_name_en' => 'Legal Directorate',
                'recipient_department_name_am' => 'የህግ ዳይሬክቶሬት',
            ],
            [
                'recipient_type' => 'department',
                'recipient_name' => null,
                'recipient_department_id' => $finance->id,
                'recipient_department_name_en' => 'Finance Department',
                'recipient_department_name_am' => 'የፋይናንስ መምሪያ',
            ],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('letters.edit', $letter))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('letterItem.id', $letter->id)
            ->where('letterItem.recipients.0.recipient_name', 'Ato Abebe Kebede')
            ->where('letterItem.recipients.1.recipient_department_id', $legal->id)
            ->where('letterItem.recipients.2.recipient_department_id', $finance->id)
            ->has('departments', 2)
        );
});

it('removes the add recipient button from the letter create and edit ui', function (): void {
    $form = file_get_contents(base_path('resources/js/Pages/Admin/Letters/Form.tsx'));

    expect($form)
        ->not->toContain("letters.actions.add_recipient")
        ->toContain("recipient_names_text")
        ->toContain("recipient_department_ids")
        ->toContain("textarea-ui min-h-40");
});

it('inherits low template margin values into saved letters and pdf rendering', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create', 'letters.preview']);
    $template = createLetterTemplateForLetters([
        'reference_prefix' => 'LOW',
        'layout_config' => [
            'margin_top_mm' => 3,
            'margin_right_mm' => 2,
            'margin_bottom_mm' => 4,
            'margin_left_mm' => 1,
            'header_top_margin_mm' => 1,
            'header_bottom_spacing_mm' => 2,
            'footer_top_spacing_mm' => 3,
            'footer_left_margin_mm' => 1,
            'footer_right_margin_mm' => 2,
            'footer_bottom_margin_mm' => 4,
            'content_top_margin_mm' => 3,
            'content_bottom_margin_mm' => 4,
        ],
    ]);

    $preview = app(GenerateLetterReferenceNumberAction::class)->preview($template);

    $this->actingAs($user)
        ->post(route('letters.store'), letterPayload($template, [
            'reference_number' => $preview,
            'reference_number_preview' => $preview,
            'margin_top_mm' => 3,
            'margin_right_mm' => 2,
            'margin_bottom_mm' => 4,
            'margin_left_mm' => 1,
        ]))
        ->assertRedirect();

    $letter = Letter::query()->latest('created_at')->firstOrFail();

    expect($letter->layout_config['margin_top_mm'])->toBe(3)
        ->and($letter->layout_config['margin_right_mm'])->toBe(2)
        ->and($letter->layout_config['margin_bottom_mm'])->toBe(4)
        ->and($letter->layout_config['margin_left_mm'])->toBe(1)
        ->and($letter->layout_config['header_top_margin_mm'])->toBe(1)
        ->and($letter->layout_config['header_bottom_spacing_mm'])->toBe(2)
        ->and($letter->layout_config['footer_top_spacing_mm'])->toBe(3)
        ->and($letter->layout_config['footer_left_margin_mm'])->toBe(1)
        ->and($letter->layout_config['footer_right_margin_mm'])->toBe(2)
        ->and($letter->layout_config['footer_bottom_margin_mm'])->toBe(4)
        ->and($letter->layout_config['content_top_margin_mm'])->toBe(3)
        ->and($letter->layout_config['content_bottom_margin_mm'])->toBe(4);

    $this->actingAs($user)
        ->get(route('letters.preview', $letter))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Letters/Preview')
            ->where('letterItem.layout_config.margin_left_mm', 1)
            ->where('letterItem.layout_config.footer_left_margin_mm', 1)
            ->where('letterItem.layout_config.footer_right_margin_mm', 2)
            ->where('letterItem.layout_config.footer_bottom_margin_mm', 4)
        );

    $html = app(RenderLetterPdfAction::class)->html($letter);

    expect($html)
        ->toContain('margin: 36mm 2mm 33mm 1mm;')
        ->toContain('padding-right: 2mm;')
        ->toContain('padding-bottom: 4mm;')
        ->toContain('padding-left: 1mm;');
});

it('increments template numbering safely when letters are created', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create']);
    $template = createLetterTemplateForLetters([
        'reference_prefix' => 'HR',
        'reference_start_number' => 3,
        'current_reference_number' => 2,
        'numbering_config' => [
            'separator' => '/',
            'include_year' => false,
            'pad_length' => 3,
        ],
    ]);

    $preview = app(GenerateLetterReferenceNumberAction::class)->preview($template);

    $this->actingAs($user)->post(route('letters.store'), letterPayload($template, [
        'reference_number' => $preview,
        'reference_number_preview' => $preview,
    ]))->assertRedirect();

    $template->refresh();
    $letter = Letter::query()->latest('created_at')->firstOrFail();

    expect($letter->reference_number)->toBe('HR/003')
        ->and($template->current_reference_number)->toBe(3);
});

it('enforces reference number uniqueness when manual override is used', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create']);
    $template = createLetterTemplateForLetters();

    Letter::query()->create([
        'template_id' => $template->id,
        'reference_number' => 'LEG/2026/0001',
        'letter_date' => now()->toDateString(),
        'recipient_name' => 'Existing Recipient',
        'body_content' => '<p>Existing body</p>',
        'language' => 'en',
        'page_size' => 'A4',
        'orientation' => 'portrait',
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->post(route('letters.store'), letterPayload($template, [
            'reference_number' => 'LEG/2026/0001',
            'reference_number_preview' => 'LEG/2026/0002',
        ]))
        ->assertSessionHasErrors(['reference_number']);
});

it('keeps saved letters stable after the template is edited later', function (): void {
    $user = createLetterUser(['letters.view', 'letters.create', 'letter_templates.update']);
    $template = createLetterTemplateForLetters([
        'body_content' => '<p>Original body content.</p>',
        'header_image_path' => 'letter-templates/stable/header-v1.png',
        'footer_image_path' => 'letter-templates/stable/footer-v1.png',
    ]);

    $preview = app(GenerateLetterReferenceNumberAction::class)->preview($template);

    $this->actingAs($user)->post(route('letters.store'), letterPayload($template, [
        'reference_number' => $preview,
        'reference_number_preview' => $preview,
        'subject' => '',
        'salutation' => '',
        'body_content' => '',
        'closing_content' => '',
        'signature_block_content' => '',
        'cc_content' => '',
        'enclosure_content' => '',
    ]))->assertRedirect();

    $letter = Letter::query()->latest('created_at')->firstOrFail();

    $template->update([
        'body_content' => '<p>Changed template body.</p>',
        'header_image_path' => 'letter-templates/stable/header-v2.png',
        'footer_image_path' => 'letter-templates/stable/footer-v2.png',
    ]);

    Storage::disk('public')->put('letter-templates/stable/header-v2.png', 'template-header-v2');
    Storage::disk('public')->put('letter-templates/stable/footer-v2.png', 'template-footer-v2');

    $letter->refresh();

    expect($letter->body_content)->toBe('<p>Original body content.</p>')
        ->and($letter->header_image_path_snapshot)->toStartWith("letters/{$letter->id}/header-")
        ->and($letter->footer_image_path_snapshot)->toStartWith("letters/{$letter->id}/footer-");

    Storage::disk('public')->assertExists((string) $letter->header_image_path_snapshot);
    Storage::disk('public')->assertExists((string) $letter->footer_image_path_snapshot);
});

it('loads the saved letter show page for authorized users', function (): void {
    $user = createLetterUser(['letters.view']);
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template);

    $this->actingAs($user)
        ->get(route('letters.show', $letter))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Letters/Show')
            ->where('letterItem.id', $letter->id)
            ->where('letterItem.header_image_url', route('branding-assets.show', ['path' => $template->header_image_path]))
            ->where('letterItem.footer_image_url', route('branding-assets.show', ['path' => $template->footer_image_path]))
            ->where('letterItem.signature_image_url', route('branding-assets.show', ['path' => 'letters/saved/signature.png']))
            ->where('letterItem.signer_full_name', 'Meseret Kebede')
            ->where('can.download', false)
        );
});

it('creates a letter even when the current user has no signature image', function (): void {
    $user = createLetterUser(['letters.create'], LocaleCode::ENGLISH, [
        'signature_path' => null,
    ]);
    $template = createLetterTemplateForLetters();
    $preview = app(GenerateLetterReferenceNumberAction::class)->preview($template);

    $this->actingAs($user)
        ->post(route('letters.store'), letterPayload($template, [
            'reference_number' => $preview,
            'reference_number_preview' => $preview,
        ]))
        ->assertRedirect();

    $letter = Letter::query()->latest('created_at')->firstOrFail();

    expect($letter->signature_image_path_snapshot)->toBeNull()
        ->and($letter->signer_full_name_snapshot)->toBe($user->name);
});

it('renders old letters safely without signer snapshots', function (): void {
    $user = createLetterUser(['letters.view']);
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template, [
        'signature_image_path_snapshot' => null,
        'signer_full_name_snapshot' => null,
        'signer_title_snapshot' => null,
    ]);

    $this->actingAs($user)
        ->get(route('letters.show', $letter))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Letters/Show')
            ->where('letterItem.id', $letter->id)
        );
});

it('renders the letter preview page and exposes real PDF endpoints for authorized users', function (): void {
    $user = createLetterUser(['letters.view', 'letters.preview', 'letters.print']);
    $template = createLetterTemplateForLetters([
        'layout_config' => [
            'margin_top_mm' => 20,
            'margin_right_mm' => 18,
            'margin_bottom_mm' => 20,
            'margin_left_mm' => 18,
            'header_top_margin_mm' => 2,
            'header_bottom_spacing_mm' => 5,
            'footer_top_spacing_mm' => 9,
            'footer_left_margin_mm' => 10,
            'footer_right_margin_mm' => 14,
            'footer_bottom_margin_mm' => 7,
            'content_top_margin_mm' => 18,
            'content_bottom_margin_mm' => 14,
        ],
    ]);
    $letter = createLetterForTesting($template, [
        'layout_config' => [
            'margin_top_mm' => 20,
            'margin_right_mm' => 18,
            'margin_bottom_mm' => 20,
            'margin_left_mm' => 18,
            'header_top_margin_mm' => 2,
            'header_bottom_spacing_mm' => 5,
            'footer_top_spacing_mm' => 9,
            'footer_left_margin_mm' => 10,
            'footer_right_margin_mm' => 14,
            'footer_bottom_margin_mm' => 7,
            'content_top_margin_mm' => 18,
            'content_bottom_margin_mm' => 14,
        ],
    ]);

    $this->actingAs($user)
        ->get(route('letters.preview', $letter))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Letters/Preview')
            ->where('letterItem.id', $letter->id)
            ->where('letterItem.header_image_url', route('branding-assets.show', ['path' => $template->header_image_path]))
            ->where('letterItem.footer_image_url', route('branding-assets.show', ['path' => $template->footer_image_path]))
            ->where('pdfUrl', route('letters.pdf', $letter))
            ->where('downloadUrl', route('letters.download-pdf', $letter))
            ->where('printUrl', route('letters.print', $letter))
            ->where('canPrint', true)
            ->where('letterItem.layout_config.footer_top_spacing_mm', 9)
            ->where('letterItem.layout_config.footer_left_margin_mm', 10)
            ->where('letterItem.layout_config.footer_right_margin_mm', 14)
            ->where('letterItem.layout_config.footer_bottom_margin_mm', 7)
        );
});

it('renders amharic letter pdf html with embedded ethiopic fonts', function (): void {
    $user = createLetterUser(['letters.preview'], LocaleCode::AMHARIC, [
        'name' => 'መሰረት ከበደ',
        'job_title' => 'የሕግ ጉዳይ ኃላፊ',
    ]);
    $template = createLetterTemplateForLetters([
        'language' => 'am',
        'reference_label' => 'መ/ቁ',
        'subject_template' => 'ጉዳይ: አስፈላጊ ጉዳይ',
        'salutation_template' => 'ክቡር ተቀባይ፣',
        'body_content' => '<p>ይህ የአማርኛ ደብዳቤ የሙከራ ይዘት ነው።</p>',
        'closing_content' => 'ከአክብሮት ጋር፣',
        'signature_block_content' => 'የሕግ ጉዳይ ክፍል',
        'cc_content' => "ግልባጭ: መዝገብ ቤት\nግልባጭ: አስተዳደር",
    ]);
    $letter = createLetterForTesting($template, [
        'language' => 'am',
        'recipient_name' => 'አስቴር ታደሰ',
        'recipient_title' => 'ዳይሬክተር',
        'recipient_organization' => 'የህዝብ አገልግሎት ቢሮ',
        'recipient_address' => 'አዲስ አበባ',
        'subject' => 'ጉዳይ: አስፈላጊ ጉዳይ',
        'salutation' => 'ክቡር አስቴር ታደሰ፣',
        'body_content' => '<p>ይህ የአማርኛ ፒዲኤፍ ሙከራ ይዘት ነው።</p>',
        'closing_content' => 'ከአክብሮት ጋር፣',
        'signature_block_content' => 'የሕግ ጉዳይ ክፍል',
        'cc_content' => "ግልባጭ: መዝገብ ቤት\nግልባጭ: አስተዳደር",
        'signer_full_name_snapshot' => 'መሰረት ከበደ',
        'signer_title_snapshot' => 'የሕግ ጉዳይ ኃላፊ',
    ]);

    $html = app(RenderLetterPdfAction::class)->html($letter);

    expect($html)
        ->toContain('<html lang="am">')
        ->toContain('class="pdf-nyala"')
        ->toContain("font-family: 'Nyala', serif;")
        ->toContain("font-family: 'Nyala';")
        ->toContain("src: url('file://")
        ->not->toContain('data:font/ttf;base64,')
        ->toContain('ይህ የአማርኛ ፒዲኤፍ ሙከራ ይዘት ነው።')
        ->toContain('መሰረት ከበደ');

    $this->actingAs($user)
        ->get(route('letters.pdf', $letter))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('renders letter pdf subject without automatic label or divider lines', function (): void {
    $template = createLetterTemplateForLetters([
        'subject_template' => 'Subject: Official Matter',
    ]);
    $letter = createLetterForTesting($template, [
        'subject' => 'Subject: Official Matter',
    ]);

    $html = app(RenderLetterPdfAction::class)->html($letter);

    expect($html)
        ->toContain('<p class="subject-value">Official Matter</p>')
        ->not->toContain('class="subject-label"')
        ->not->toContain('<p class="subject-value">Subject: Official Matter</p>')
        ->not->toContain('border-bottom: 1px solid #cbd5e1;')
        ->not->toContain('border-top: 1px solid #cbd5e1;');
});

it('renders letter pdf safely when the subject is empty', function (): void {
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template, [
        'subject' => null,
    ]);

    $html = app(RenderLetterPdfAction::class)->html($letter);

    expect($html)
        ->not->toContain('class="subject-block"')
        ->toContain('Saved letter body.');
});

it('allows editing and previewing letters whose template was soft deleted later', function (): void {
    $user = createLetterUser(['letters.view', 'letters.update', 'letters.preview', 'letters.print']);
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template);

    $template->delete();

    $this->actingAs($user)
        ->get(route('letters.edit', $letter))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Letters/Form')
            ->where('letterItem.id', $letter->id)
            ->where('selectedTemplate.id', $template->id)
            ->where('selectedTemplate.name', $template->name)
        );

    $this->actingAs($user)
        ->get(route('letters.preview', $letter))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('letters.pdf', $letter))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('returns inline and downloadable pdf responses for authorized users', function (): void {
    $user = createLetterUser(['letters.view', 'letters.preview', 'letters.print']);
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template);

    $this->actingAs($user)
        ->get(route('letters.pdf', $letter))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename="leg-2026-0001.pdf"')
        ->assertHeader('x-frame-options', 'SAMEORIGIN');

    expect($this->actingAs($user)->get(route('letters.pdf', $letter))->headers->get('content-security-policy'))
        ->toContain("frame-ancestors 'self'");

    expect($this->actingAs($user)->get(route('letters.pdf', $letter))->getContent())
        ->toStartWith('%PDF');

    $this->actingAs($user)
        ->get(route('letters.print', $letter))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename="leg-2026-0001.pdf"');

    $this->actingAs($user)
        ->get(route('letters.download-pdf', $letter))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename="leg-2026-0001.pdf"');
});

it('generates pdf safely for letters with invalid legacy language values', function (): void {
    $user = createLetterUser(['letters.preview']);
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template, [
        'language' => 'fr',
    ]);

    $this->actingAs($user)
        ->get(route('letters.pdf', $letter))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('serves saved letter signature and template snapshot assets through the branding asset route', function (): void {
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template);

    $this->get(route('branding-assets.show', ['path' => $template->header_image_path]))
        ->assertOk();

    $this->get(route('branding-assets.show', ['path' => $template->footer_image_path]))
        ->assertOk();

    $this->get(route('branding-assets.show', ['path' => $letter->signature_image_path_snapshot]))
        ->assertOk();
});

it('keeps the shared letter renderer contracts for centered subject, right signature, and bulleted cc', function (): void {
    $renderer = file_get_contents(base_path('resources/js/Pages/Admin/LetterTemplates/shared.tsx'));

    expect($renderer)
        ->toContain("const LETTER_NYALA_FONT_FAMILY = \"'LetterNyala', 'Nyala', serif\";")
        ->toContain('function formatSubjectDisplayValue(value: string)')
        ->toContain("trimmed.replace(/^(subject|ጉዳይ|ርዕስ)\\s*[:\\-–—]+\\s*/iu, '')")
        ->toContain("data-letter-font={useNyala ? 'nyala' : 'default'}")
        ->toContain('sanitizeRichTextHtml(value, { allowFontSize: true, allowLineHeight: true })')
        ->toContain('data-letter-slot="header"')
        ->toContain('data-letter-slot="footer"')
        ->toContain('data-letter-page')
        ->toContain('index === 0')
        ->toContain('index === pages.length - 1')
        ->toContain('pageBreakAfter')
        ->toContain('height: metrics.pageMinHeightStyle')
        ->toContain('footerSlotHeightMm')
        ->toContain('footerLeftMarginMm')
        ->toContain('footerRightMarginMm')
        ->toContain('gridTemplateRows: `${metrics.footerTopSpacingMm}mm ${metrics.footerHeightMm}mm ${metrics.footerBottomMarginMm}mm`')
        ->toContain('paddingLeft: `${metrics.footerLeftMarginMm}mm`')
        ->toContain('paddingRight: `${metrics.footerRightMarginMm}mm`')
        ->toContain('text-base font-bold text-slate-950 text-center underline')
        ->toContain('items-end space-y-4 pt-6 text-right')
        ->toContain('list-disc space-y-0.5 pl-6 text-sm leading-5 marker:text-slate-500')
        ->toContain('recipient_items')
        ->not->toContain('border-y border-slate-300 py-3 text-center')
        ->not->toContain('className="grid border-b border-slate-200"')
        ->not->toContain('className="grid border-t border-slate-200"');
});

it('configures the letter main body editor with a font size control and nyala content style', function (): void {
    $form = file_get_contents(base_path('resources/js/Pages/Admin/Letters/Form.tsx'));
    $editor = file_get_contents(base_path('resources/js/Components/Ui/RichTextEditor.tsx'));

    expect($form)
        ->toContain('fontsizeinput')
        ->toContain('lineheight')
        ->toContain("font-family: 'LetterNyala', 'Nyala', serif;")
        ->toContain("import nyalaFontUrl from '../../../../fonts/pdf/Nyala.ttf?url';")
        ->toContain("contentStyle={buildLetterEditorContentStyle(form.data.language as 'en' | 'am')}");

    expect($editor)
        ->toContain('toolbar?: string;')
        ->toContain('font_size_formats: fontSizeFormats')
        ->toContain('font_size_input_default_unit: fontSizeDefaultUnit')
        ->toContain('line_height_formats: lineHeightFormats');
});

it('generates multi-page letter pdf output without crashing', function (): void {
    $user = createLetterUser(['letters.preview']);
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template, [
        'body_content' => collect(range(1, 80))
            ->map(fn (int $index): string => "<p>Paragraph {$index} for a long institutional letter body.</p>")
            ->implode(''),
    ]);

    $response = $this->actingAs($user)->get(route('letters.pdf', $letter));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

it('generates pdf safely for older letters with missing snapshot assets', function (): void {
    $user = createLetterUser(['letters.preview']);
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template, [
        'header_image_path_snapshot' => null,
        'footer_image_path_snapshot' => null,
        'signature_image_path_snapshot' => null,
        'signer_full_name_snapshot' => null,
        'signer_title_snapshot' => null,
    ]);

    $response = $this->actingAs($user)->get(route('letters.pdf', $letter));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

it('denies unauthorized users from letter routes', function (): void {
    $user = User::factory()->create();
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template);

    $this->actingAs($user)->get(route('letters.index'))->assertForbidden();
    $this->actingAs($user)->get(route('letters.create'))->assertForbidden();
    $this->actingAs($user)->post(route('letters.store'), letterPayload($template))->assertForbidden();
    $this->actingAs($user)->get(route('letters.show', $letter))->assertForbidden();
    $this->actingAs($user)->get(route('letters.preview', $letter))->assertForbidden();
    $this->actingAs($user)->get(route('letters.pdf', $letter))->assertForbidden();
    $this->actingAs($user)->get(route('letters.download-pdf', $letter))->assertForbidden();
    $this->actingAs($user)->get(route('letters.print', $letter))->assertForbidden();
    $this->actingAs($user)->patch(route('letters.update', $letter), [
        'letter_date' => '2026-04-28',
        'recipient_name' => 'Changed',
        'body_content' => '<p>Changed</p>',
        'status' => 'draft',
        'language' => 'en',
        'page_size' => 'A4',
        'orientation' => 'portrait',
    ])->assertForbidden();
});

it('allows editing a saved letter even if its source template is later inactive', function (): void {
    $user = createLetterUser(['letters.view', 'letters.update']);
    $template = createLetterTemplateForLetters(['is_active' => false]);
    $letter = createLetterForTesting($template);

    $this->actingAs($user)
        ->patch(route('letters.update', $letter), [
            'template_id' => $template->id,
            'reference_number' => $letter->reference_number,
            'reference_number_preview' => $letter->reference_number,
            'letter_date' => '2026-04-29',
            'recipient_name' => 'Updated Recipient',
            'recipient_title' => 'Director',
            'recipient_organization' => 'Public Service Office',
            'recipient_address' => 'Addis Ababa',
            'subject' => 'Updated subject',
            'salutation' => 'Dear Recipient,',
            'body_content' => '<p>Updated body</p>',
            'closing_content' => 'Regards,',
            'signature_block_content' => 'Director',
            'cc_content' => '',
            'enclosure_content' => '',
            'status' => 'final',
            'language' => 'en',
            'page_size' => 'A4',
            'orientation' => 'portrait',
            'margin_top_mm' => 20,
            'margin_right_mm' => 18,
            'margin_bottom_mm' => 20,
            'margin_left_mm' => 18,
        ])
        ->assertRedirect(route('letters.show', $letter));

    $letter->refresh();

    expect($letter->recipient_name)->toBe('Updated Recipient')
        ->and($letter->status)->toBe('draft');
});

it('allows deleting a saved letter for authorized users', function (): void {
    $user = createLetterUser(['letters.view', 'letters.delete']);
    $template = createLetterTemplateForLetters();
    $letter = createLetterForTesting($template);

    $this->actingAs($user)
        ->delete(route('letters.destroy', $letter))
        ->assertRedirect(route('letters.index'));

    $this->assertSoftDeleted('letters', [
        'id' => $letter->id,
    ]);
});

function createLetterUser(array $permissions, LocaleCode $locale = LocaleCode::ENGLISH, array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'locale' => $locale->value,
        'name' => 'Meseret Kebede',
        'job_title' => 'Head, Legal Affairs',
        'signature_path' => 'users/default-user/signature.png',
    ], $overrides));

    if (is_string($user->signature_path) && $user->signature_path !== '') {
        Storage::disk('public')->put($user->signature_path, 'user-signature');
    }

    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createLetterTemplateForLetters(array $overrides = []): LetterTemplate
{
    $template = LetterTemplate::query()->create(array_merge([
        'name' => 'Legal Letter Template',
        'code' => 'LTR-200',
        'document_type' => 'Official letter',
        'language' => 'en',
        'page_size' => 'A4',
        'orientation' => 'portrait',
        'reference_label' => 'Ref.',
        'reference_prefix' => 'LEG',
        'reference_start_number' => 1,
        'current_reference_number' => 0,
        'numbering_config' => [
            'separator' => '/',
            'include_year' => true,
            'pad_length' => 4,
        ],
        'subject_template' => 'Subject: Official Matter',
        'recipient_block_template' => "{recipient_name}\n{recipient_title}\n{recipient_organization}",
        'salutation_template' => 'Dear {recipient_name},',
        'body_content' => '<p>Template letter body.</p>',
        'closing_content' => 'With regards,',
        'signature_block_content' => 'Head of Department',
        'cc_content' => 'CC: Records Office',
        'enclosure_content' => 'Enclosure: Annex',
        'header_image_path' => 'letter-templates/default/header.png',
        'footer_image_path' => 'letter-templates/default/footer.png',
        'layout_config' => [
            'margin_top_mm' => 20,
            'margin_right_mm' => 18,
            'margin_bottom_mm' => 20,
            'margin_left_mm' => 18,
            'header_top_margin_mm' => 1,
            'header_bottom_spacing_mm' => 4,
            'footer_top_spacing_mm' => 4,
            'footer_left_margin_mm' => 18,
            'footer_right_margin_mm' => 18,
            'footer_bottom_margin_mm' => 1,
            'content_top_margin_mm' => 20,
            'content_bottom_margin_mm' => 20,
        ],
        'is_active' => true,
        'is_default' => false,
        'notes' => 'Letter template notes.',
    ], $overrides));

    if (is_string($template->header_image_path) && $template->header_image_path !== '') {
        Storage::disk('public')->put($template->header_image_path, 'template-header');
    }

    if (is_string($template->footer_image_path) && $template->footer_image_path !== '') {
        Storage::disk('public')->put($template->footer_image_path, 'template-footer');
    }

    return $template;
}

function createLetterForTesting(LetterTemplate $template, array $overrides = []): Letter
{
    $letter = Letter::query()->create(array_merge([
        'template_id' => $template->id,
        'reference_number' => 'LEG/2026/0001',
        'letter_date' => '2026-04-28',
        'recipient_name' => 'Aster Tadesse',
        'recipient_title' => 'Director',
        'recipient_organization' => 'Public Service Office',
        'recipient_address' => 'Addis Ababa',
        'subject' => 'Subject: Official Matter',
        'salutation' => 'Dear Aster Tadesse,',
        'body_content' => '<p>Saved letter body.</p>',
        'closing_content' => 'With regards,',
        'signature_block_content' => 'Head of Department',
        'cc_content' => 'CC: Records Office',
        'enclosure_content' => 'Enclosure: Annex',
        'header_image_path_snapshot' => $template->header_image_path,
        'footer_image_path_snapshot' => $template->footer_image_path,
        'signature_image_path_snapshot' => 'letters/saved/signature.png',
        'signer_full_name_snapshot' => 'Meseret Kebede',
        'signer_title_snapshot' => 'Head, Legal Affairs',
        'language' => 'en',
        'page_size' => 'A4',
        'orientation' => 'portrait',
        'status' => 'draft',
        'layout_config' => [
            'margin_top_mm' => 20,
            'margin_right_mm' => 18,
            'margin_bottom_mm' => 20,
            'margin_left_mm' => 18,
            'header_top_margin_mm' => 1,
            'header_bottom_spacing_mm' => 4,
            'footer_top_spacing_mm' => 4,
            'footer_left_margin_mm' => 18,
            'footer_right_margin_mm' => 18,
            'footer_bottom_margin_mm' => 1,
            'content_top_margin_mm' => 20,
            'content_bottom_margin_mm' => 20,
        ],
    ], $overrides));

    if (is_string($letter->signature_image_path_snapshot) && $letter->signature_image_path_snapshot !== '') {
        Storage::disk('public')->put($letter->signature_image_path_snapshot, 'saved-letter-signature');
    }

    return $letter;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createLetterDepartment(array $overrides = []): Department
{
    static $sequence = 1;

    $department = Department::query()->create(array_merge([
        'code' => 'DPT-'.$sequence,
        'name_en' => 'Department '.$sequence,
        'name_am' => 'መምሪያ '.$sequence,
        'is_active' => true,
    ], $overrides));

    $sequence++;

    return $department;
}

/**
 * @return array<string, mixed>
 */
function letterPayload(LetterTemplate $template, array $overrides = []): array
{
    return array_merge([
        'template_id' => $template->id,
        'reference_number' => 'LEG/2026/0001',
        'reference_number_preview' => 'LEG/2026/0001',
        'letter_date' => '2026-04-28',
        'recipient_name' => 'Aster Tadesse',
        'recipient_title' => 'Director',
        'recipient_organization' => 'Public Service Office',
        'recipient_address' => 'Addis Ababa',
        'subject' => 'Official subject',
        'salutation' => 'Dear Aster Tadesse,',
        'body_content' => '<p>Actual body content.</p>',
        'closing_content' => 'With regards,',
        'signature_block_content' => 'Head of Department',
        'cc_content' => 'CC: Records Office',
        'enclosure_content' => 'Enclosure: Annex',
        'status' => 'draft',
        'language' => 'en',
        'page_size' => 'A4',
        'orientation' => 'portrait',
        'margin_top_mm' => 20,
        'margin_right_mm' => 18,
        'margin_bottom_mm' => 20,
        'margin_left_mm' => 18,
        'notes' => 'Letter notes.',
    ], $overrides);
}
