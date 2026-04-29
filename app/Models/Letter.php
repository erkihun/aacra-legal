<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Support\SafeUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'layout_config',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'layout_config' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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
        return $this->assetUrl($this->signature_image_path_snapshot)
            ?? $this->creator?->signatureUrl();
    }

    public function signerFullName(): ?string
    {
        return filled($this->signer_full_name_snapshot)
            ? $this->signer_full_name_snapshot
            : $this->creator?->name;
    }

    public function signerTitle(): ?string
    {
        return filled($this->signer_title_snapshot)
            ? $this->signer_title_snapshot
            : $this->creator?->job_title;
    }

    public function previewPlaceholders(): array
    {
        return [
            'date' => $this->letter_date?->toDateString() ?? now()->toDateString(),
            'reference_number' => $this->reference_number,
            'recipient_name' => $this->recipient_name,
            'recipient_title' => $this->recipient_title ?? '',
            'recipient_organization' => $this->recipient_organization ?? '',
            'subject' => $this->subject ?? '',
            'sender_name' => $this->creator?->name ?? '',
            'sender_title' => $this->creator?->job_title ?? '',
            'department_name' => '',
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
}
