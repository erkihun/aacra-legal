<?php

declare(strict_types=1);

use App\Enums\LocaleCode;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('public');

    $this->seed([
        PermissionSeeder::class,
    ]);
});

it('allows an authorized user to view the letter template list', function (): void {
    $user = createLetterTemplateUser(['letter_templates.view']);

    $this->actingAs($user)
        ->get(route('letter-templates.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/LetterTemplates/Index')
            ->where('can.create', false)
            ->has('templates.data')
        );
});

it('allows an authorized user to create a letter template with png header and footer assets', function (): void {
    $user = createLetterTemplateUser(['letter_templates.view', 'letter_templates.create']);

    $this->actingAs($user)
        ->post(route('letter-templates.store'), letterTemplatePayload([
            'code' => 'LTR-001',
            'name' => 'Outgoing Letter Template',
            'header_image' => UploadedFile::fake()->image('header.png', 1200, 180),
            'footer_image' => UploadedFile::fake()->image('footer.png', 1200, 120),
        ]))
        ->assertRedirect();

    $template = LetterTemplate::query()->where('code', 'LTR-001')->firstOrFail();

    expect($template->reference_prefix)->toBe('LTR')
        ->and($template->reference_start_number)->toBe(1)
        ->and($template->current_reference_number)->toBe(0)
        ->and($template->header_image_path)->not->toBeNull()
        ->and($template->footer_image_path)->not->toBeNull();

    Storage::disk('public')->assertExists((string) $template->header_image_path);
    Storage::disk('public')->assertExists((string) $template->footer_image_path);
});

it('allows an authorized user to update template numbering settings', function (): void {
    $user = createLetterTemplateUser(['letter_templates.view', 'letter_templates.update']);
    $template = createLetterTemplate();

    $this->actingAs($user)
        ->patch(route('letter-templates.update', $template), letterTemplatePayload([
            'name' => 'Updated Letter Template',
            'reference_prefix' => 'LEG',
            'reference_start_number' => 50,
            'numbering_separator' => '-',
            'numbering_include_year' => false,
            'numbering_pad_length' => 5,
            'header_top_margin_mm' => 6,
            'header_bottom_spacing_mm' => 8,
            'footer_top_spacing_mm' => 7,
            'footer_bottom_margin_mm' => 5,
            'content_top_margin_mm' => 18,
            'content_bottom_margin_mm' => 16,
            'is_active' => false,
        ], $template->code))
        ->assertRedirect(route('letter-templates.edit', $template));

    $template->refresh();

    expect($template->name)->toBe('Updated Letter Template')
        ->and($template->reference_prefix)->toBe('LEG')
        ->and($template->reference_start_number)->toBe(50)
        ->and($template->numbering_config['separator'])->toBe('-')
        ->and($template->numbering_config['include_year'])->toBeFalse()
        ->and($template->numbering_config['pad_length'])->toBe(5)
        ->and($template->layout_config['header_top_margin_mm'])->toBe(6)
        ->and($template->layout_config['header_bottom_spacing_mm'])->toBe(8)
        ->and($template->layout_config['footer_top_spacing_mm'])->toBe(7)
        ->and($template->layout_config['footer_bottom_margin_mm'])->toBe(5)
        ->and($template->layout_config['content_top_margin_mm'])->toBe(18)
        ->and($template->layout_config['content_bottom_margin_mm'])->toBe(16)
        ->and($template->is_active)->toBeFalse();
});

it('renders the template preview and print pages for authorized users', function (): void {
    $user = createLetterTemplateUser(['letter_templates.view', 'letter_templates.preview', 'letter_templates.print']);
    $template = createLetterTemplate([
        'header_image_path' => 'letter-templates/preview/header.png',
        'footer_image_path' => 'letter-templates/preview/footer.png',
    ]);

    $this->actingAs($user)
        ->get(route('letter-templates.preview', $template))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/LetterTemplates/Preview')
            ->where('templateItem.id', $template->id)
            ->where('templateItem.header_image_url', route('branding-assets.show', ['path' => 'letter-templates/preview/header.png']))
            ->where('templateItem.footer_image_url', route('branding-assets.show', ['path' => 'letter-templates/preview/footer.png']))
        );

    $this->actingAs($user)
        ->get(route('letter-templates.print', $template))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/LetterTemplates/Print')
            ->where('templateItem.id', $template->id)
        );
});

it('serves template header and footer assets through the branding asset route', function (): void {
    $template = createLetterTemplate([
        'header_image_path' => 'letter-templates/assets/header.png',
        'footer_image_path' => 'letter-templates/assets/footer.png',
    ]);

    $this->get(route('branding-assets.show', ['path' => $template->header_image_path]))
        ->assertOk();

    $this->get(route('branding-assets.show', ['path' => $template->footer_image_path]))
        ->assertOk();
});

it('blocks deleting a template that is already used by letters', function (): void {
    $user = createLetterTemplateUser(['letter_templates.view', 'letter_templates.delete', 'letters.create', 'letters.view']);
    $template = createLetterTemplate();

    Letter::query()->create([
        'template_id' => $template->id,
        'reference_number' => 'LTR/2026/0001',
        'letter_date' => now()->toDateString(),
        'recipient_name' => 'Aster Tadesse',
        'body_content' => '<p>Body</p>',
        'language' => 'en',
        'page_size' => 'A4',
        'orientation' => 'portrait',
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->delete(route('letter-templates.destroy', $template))
        ->assertRedirect(route('letter-templates.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('letter_templates', [
        'id' => $template->id,
        'deleted_at' => null,
    ]);
});

it('denies unauthorized users from letter template routes', function (): void {
    $user = User::factory()->create();
    $template = createLetterTemplate();

    $this->actingAs($user)->get(route('letter-templates.index'))->assertForbidden();
    $this->actingAs($user)->post(route('letter-templates.store'), letterTemplatePayload())->assertForbidden();
    $this->actingAs($user)->patch(route('letter-templates.update', $template), letterTemplatePayload(code: $template->code))->assertForbidden();
    $this->actingAs($user)->delete(route('letter-templates.destroy', $template))->assertForbidden();
});

it('validates template input including duplicate code and png restrictions', function (): void {
    $user = createLetterTemplateUser(['letter_templates.view', 'letter_templates.create']);
    createLetterTemplate(['code' => 'LTR-DUP']);

    $this->actingAs($user)
        ->post(route('letter-templates.store'), letterTemplatePayload([
            'code' => 'ltr-dup',
            'name' => '',
            'body_content' => '',
            'language' => 'fr',
            'header_image' => UploadedFile::fake()->create('header.jpg', 50, 'image/jpeg'),
        ]))
        ->assertSessionHasErrors(['code', 'name', 'body_content', 'language', 'header_image']);
});

it('duplicates a template for authorized users without reusing template assets', function (): void {
    $user = createLetterTemplateUser(['letter_templates.view', 'letter_templates.create']);
    $template = createLetterTemplate([
        'code' => 'LTR-ORIG',
        'name' => 'Original Template',
        'header_image_path' => 'letter-templates/original/header.png',
        'footer_image_path' => 'letter-templates/original/footer.png',
    ]);

    $this->actingAs($user)
        ->post(route('letter-templates.duplicate', $template))
        ->assertRedirect();

    $copy = LetterTemplate::query()->where('code', 'LTR-ORIG-COPY')->firstOrFail();

    expect($copy->name)->toBe('Original Template copy')
        ->and($copy->header_image_path)->toBeNull()
        ->and($copy->footer_image_path)->toBeNull();
});

it('keeps the shared letter renderer contracts for repeated pages and first-last page rules', function (): void {
    $renderer = file_get_contents(base_path('resources/js/Pages/Admin/LetterTemplates/shared.tsx'));

    expect($renderer)
        ->toContain('data-letter-page')
        ->toContain('pageBreakAfter')
        ->toContain('breakAfter')
        ->toContain('index === 0')
        ->toContain('index === pages.length - 1')
        ->toContain('header_top_margin_mm')
        ->toContain('footer_bottom_margin_mm')
        ->toContain('content_top_margin_mm')
        ->toContain('content_bottom_margin_mm');
});

it('shares localized template labels when amharic is active', function (): void {
    $user = createLetterTemplateUser(['letter_templates.view', 'letter_templates.create'], LocaleCode::AMHARIC);

    $this->actingAs($user)
        ->get(route('letter-templates.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/LetterTemplates/Form')
            ->where('locale', LocaleCode::AMHARIC->value)
            ->where('translations', letterTranslationSubset([
                'letter_templates.create_title' => __('letter_templates.create_title', locale: LocaleCode::AMHARIC->value),
                'navigation.letter_templates' => __('navigation.letter_templates', locale: LocaleCode::AMHARIC->value),
            ]))
        );
});

function createLetterTemplateUser(array $permissions, LocaleCode $locale = LocaleCode::ENGLISH): User
{
    $user = User::factory()->create([
        'locale' => $locale->value,
    ]);
    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createLetterTemplate(array $overrides = []): LetterTemplate
{
    $template = LetterTemplate::query()->create(array_merge([
        'name' => 'Institution Letter Template',
        'code' => 'LTR-100',
        'document_type' => 'Outgoing correspondence',
        'language' => 'en',
        'page_size' => 'A4',
        'orientation' => 'portrait',
        'reference_label' => 'Ref.',
        'reference_prefix' => 'LTR',
        'reference_start_number' => 1,
        'current_reference_number' => 0,
        'numbering_config' => [
            'separator' => '/',
            'include_year' => true,
            'pad_length' => 4,
        ],
        'subject_template' => 'Subject: {subject}',
        'recipient_block_template' => "{recipient_name}\n{recipient_title}\n{recipient_organization}",
        'salutation_template' => 'Dear {recipient_name},',
        'body_content' => '<p>This is an official communication regarding {subject}.</p>',
        'closing_content' => "With regards,\n{sender_name}",
        'signature_block_content' => "{signature_name}\n{signature_title}",
        'cc_content' => 'CC: Archive Office',
        'enclosure_content' => 'Enclosure: Supporting memo',
        'layout_config' => [
            'margin_top_mm' => 20,
            'margin_right_mm' => 18,
            'margin_bottom_mm' => 20,
            'margin_left_mm' => 18,
            'header_top_margin_mm' => 0,
            'header_bottom_spacing_mm' => 4,
            'footer_top_spacing_mm' => 4,
            'footer_bottom_margin_mm' => 0,
            'content_top_margin_mm' => 20,
            'content_bottom_margin_mm' => 20,
        ],
        'is_active' => true,
        'is_default' => false,
        'notes' => 'Reusable institutional letter template.',
    ], $overrides));

    if (is_string($template->header_image_path) && $template->header_image_path !== '') {
        Storage::disk('public')->put($template->header_image_path, 'template-header');
    }

    if (is_string($template->footer_image_path) && $template->footer_image_path !== '') {
        Storage::disk('public')->put($template->footer_image_path, 'template-footer');
    }

    return $template;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function letterTemplatePayload(array $overrides = [], string $code = 'LTR-NEW'): array
{
    return array_merge([
        'name' => 'Official Letter Template',
        'code' => $code,
        'document_type' => 'General correspondence',
        'language' => 'en',
        'page_size' => 'A4',
        'orientation' => 'portrait',
        'reference_label' => 'Ref.',
        'reference_prefix' => 'LTR',
        'reference_start_number' => 1,
        'numbering_separator' => '/',
        'numbering_include_year' => true,
        'numbering_pad_length' => 4,
        'subject_template' => 'Subject: {subject}',
        'recipient_block_template' => "{recipient_name}\n{recipient_organization}",
        'salutation_template' => 'Dear {recipient_name},',
        'body_content' => '<p>Official body content.</p>',
        'closing_content' => 'Sincerely,',
        'signature_block_content' => "{signature_name}\n{signature_title}",
        'cc_content' => 'CC: Records Office',
        'enclosure_content' => 'Enclosure: Attachment list',
        'margin_top_mm' => 20,
        'margin_right_mm' => 18,
        'margin_bottom_mm' => 20,
        'margin_left_mm' => 18,
        'header_top_margin_mm' => 0,
        'header_bottom_spacing_mm' => 4,
        'footer_top_spacing_mm' => 4,
        'footer_bottom_margin_mm' => 0,
        'content_top_margin_mm' => 20,
        'content_bottom_margin_mm' => 20,
        'is_active' => true,
        'is_default' => false,
        'notes' => 'Template notes.',
    ], $overrides);
}

function letterTranslationSubset(array $expected): \Closure
{
    return function ($translations) use ($expected): bool {
        $values = collect($translations)->all();

        foreach ($expected as $key => $value) {
            if (($values[$key] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    };
}
