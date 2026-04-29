<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Support\SafeUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LetterTemplate extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'document_type',
        'language',
        'page_size',
        'orientation',
        'header_image_path',
        'footer_image_path',
        'reference_label',
        'reference_prefix',
        'reference_start_number',
        'current_reference_number',
        'subject_template',
        'recipient_block_template',
        'salutation_template',
        'body_content',
        'closing_content',
        'signature_block_content',
        'cc_content',
        'enclosure_content',
        'layout_config',
        'numbering_config',
        'is_active',
        'is_default',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'layout_config' => 'array',
            'numbering_config' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'reference_start_number' => 'integer',
            'current_reference_number' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class, 'template_id');
    }

    public function headerImageUrl(): ?string
    {
        return $this->assetUrl($this->header_image_path);
    }

    public function footerImageUrl(): ?string
    {
        return $this->assetUrl($this->footer_image_path);
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

        $storagePath = SafeUrl::storageAssetPath($path, ['letter-templates/']);

        if ($storagePath !== null && ! Storage::disk('public')->exists($storagePath)) {
            return null;
        }

        return $storagePath !== null
            ? route('branding-assets.show', ['path' => $storagePath])
            : null;
    }
}
