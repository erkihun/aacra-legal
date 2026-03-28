<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PublicPostStatus;
use App\Models\PublicPost;
use Illuminate\Support\Carbon;

class UpdatePublicPostStatusAction
{
    public function publish(PublicPost $publicPost, ?string $publishedAt = null): PublicPost
    {
        $publicPost->update([
            'status' => PublicPostStatus::PUBLISHED,
            'published_at' => $publishedAt ? Carbon::parse($publishedAt) : ($publicPost->published_at ?? now()),
        ]);

        return $publicPost->fresh('author');
    }

    public function unpublish(PublicPost $publicPost): PublicPost
    {
        $publicPost->update([
            'status' => PublicPostStatus::DRAFT,
            'published_at' => null,
        ]);

        return $publicPost->fresh('author');
    }
}
