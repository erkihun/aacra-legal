<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Support\SafeUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Letter extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'template_id',
        'reference_number',
        'letter_date',
        'recipient_name',
        'recipient_title',
        'recipient_organization',
        'recipient_address',
        'recipients',
        'subject',
        'salutation',
        'body_content',
        'closing_content',
        'signature_block_content',
        'cc_content',
        'enclosure_content',
        'header_image_path_snapshot',
        'footer_image_path_snapshot',
        'signature_image_path_snapshot',
        'signer_full_name_snapshot',
        'signer_title_snapshot',
        'language',
        'page_size',
        'orientation',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'approved_signature_path_snapshot',
        'approved_signer_name_snapshot',
        'approved_signer_title_snapshot',
        'layout_config',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'approved_at' => 'datetime',
            'layout_config' => 'array',
            'recipients' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'template_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function headerImageUrl(): ?string
    {
        return $this->assetUrl($this->header_image_path_snapshot)
            ?? $this->template?->headerImageUrl();
    }

    public function footerImageUrl(): ?string
    {
        return $this->assetUrl($this->footer_image_path_snapshot)
            ?? $this->template?->footerImageUrl();
    }

    public function signatureImageUrl(): ?string
    {
        if (($this->approval_status ?? 'draft') === 'approved') {
            return $this->assetUrl($this->approved_signature_path_snapshot)
                ?? $this->approver?->signatureUrl();
        }

        return $this->assetUrl($this->signature_image_path_snapshot)
            ?? $this->creator?->signatureUrl();
    }

    public function signerFullName(): ?string
    {
        if (($this->approval_status ?? 'draft') === 'approved') {
            return filled($this->approved_signer_name_snapshot)
                ? $this->approved_signer_name_snapshot
                : $this->approver?->name;
        }

        return filled($this->signer_full_name_snapshot)
            ? $this->signer_full_name_snapshot
            : $this->creator?->name;
    }

    public function signerTitle(): ?string
    {
        if (($this->approval_status ?? 'draft') === 'approved') {
            return filled($this->approved_signer_title_snapshot)
                ? $this->approved_signer_title_snapshot
                : $this->approver?->job_title;
        }

        return filled($this->signer_title_snapshot)
            ? $this->signer_title_snapshot
            : $this->creator?->job_title;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function resolvedRecipients(): array
    {
        $storedRecipients = is_array($this->recipients) ? $this->recipients : [];
        $normalizedRecipients = collect($storedRecipients)
            ->map(function (mixed $recipient): ?array {
                if (! is_array($recipient)) {
                    return null;
                }

                $name = trim((string) Arr::get($recipient, 'recipient_name', ''));
                $departmentNameEn = $this->normalizeOptionalString(Arr::get($recipient, 'recipient_department_name_en'));
                $departmentNameAm = $this->normalizeOptionalString(Arr::get($recipient, 'recipient_department_name_am'));

                if ($name === '' && ! filled($departmentNameEn) && ! filled($departmentNameAm)) {
                    return null;
                }

                return [
                    'recipient_type' => $this->normalizeOptionalString(Arr::get($recipient, 'recipient_type')) ?? ($name === '' ? 'department' : 'text'),
                    'recipient_name' => $name !== '' ? $name : null,
                    'recipient_department_id' => $this->normalizeOptionalString(Arr::get($recipient, 'recipient_department_id')),
                    'recipient_department_name_en' => $departmentNameEn,
                    'recipient_department_name_am' => $departmentNameAm,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($normalizedRecipients !== []) {
            return $normalizedRecipients;
        }

        if (! filled($this->recipient_name)) {
            return [];
        }

        return [[
            'recipient_type' => 'text',
            'recipient_name' => $this->recipient_name,
            'recipient_department_id' => null,
            'recipient_department_name_en' => $this->normalizeOptionalString($this->recipient_organization),
            'recipient_department_name_am' => $this->normalizeOptionalString($this->recipient_organization),
        ]];
    }

    /**
     * @return array<int, string>
     */
    public function recipientDisplayLines(?string $language = null): array
    {
        $documentLanguage = in_array($language, ['en', 'am'], true)
            ? $language
            : $this->language;

        return collect($this->resolvedRecipients())
            ->map(function (array $recipient) use ($documentLanguage): string {
                $departmentName = $documentLanguage === 'am'
                    ? ($recipient['recipient_department_name_am'] ?? $recipient['recipient_department_name_en'])
                    : ($recipient['recipient_department_name_en'] ?? $recipient['recipient_department_name_am']);

                if (filled($recipient['recipient_name'] ?? null)) {
                    return (string) $recipient['recipient_name'];
                }

                return (string) ($departmentName ?? '');
            })
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function previewPlaceholders(): array
    {
        $primaryRecipient = $this->resolvedRecipients()[0] ?? null;
        $recipientDepartmentName = $this->language === 'am'
            ? ($primaryRecipient['recipient_department_name_am'] ?? $primaryRecipient['recipient_department_name_en'] ?? '')
            : ($primaryRecipient['recipient_department_name_en'] ?? $primaryRecipient['recipient_department_name_am'] ?? '');

        return [
            'date' => $this->letter_date?->toDateString() ?? now()->toDateString(),
            'reference_number' => $this->reference_number,
            'recipient_name' => $primaryRecipient['recipient_name'] ?? $this->recipient_name,
            'recipient_title' => $this->recipient_title ?? '',
            'recipient_organization' => $recipientDepartmentName,
            'subject' => $this->subject ?? '',
            'sender_name' => $this->signerFullName() ?? $this->creator?->name ?? '',
            'sender_title' => $this->signerTitle() ?? $this->creator?->job_title ?? '',
            'department_name' => $recipientDepartmentName,
            'organization_name' => config('app.name'),
            'signature_name' => $this->signerFullName() ?? '',
            'signature_title' => $this->signerTitle() ?? '',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    private function assetUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Str::startsWith($path, '/')) {
            return url($path);
        }

        $storagePath = SafeUrl::storageAssetPath($path, ['letter-templates/', 'letters/', 'users/']);

        if ($storagePath !== null && ! Storage::disk('public')->exists($storagePath)) {
            return null;
        }

        return $storagePath !== null
            ? route('branding-assets.show', ['path' => $storagePath])
            : null;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
