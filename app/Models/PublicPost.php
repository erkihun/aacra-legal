<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\PublicPostStatus;
use App\Support\SafeUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PublicPost extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'summary',
        'body',
        'cover_image_path',
        'status',
        'locale',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublicPostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', PublicPostStatus::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function coverImageUrl(): ?string
    {
        if ($this->cover_image_path === null || $this->cover_image_path === '') {
            return null;
        }

        if (Str::startsWith($this->cover_image_path, '/')) {
            return url($this->cover_image_path);
        }

        $storagePath = SafeUrl::storageAssetPath($this->cover_image_path, ['public-posts/']);

        return $storagePath !== null
            ? route('branding-assets.show', ['path' => $storagePath])
            : null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'author_id',
                'title',
                'slug',
                'summary',
                'status',
                'locale',
                'published_at',
            ])
            ->logOnlyDirty();
    }
}
