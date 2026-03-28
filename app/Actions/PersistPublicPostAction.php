<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PublicPostStatus;
use App\Models\PublicPost;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersistPublicPostAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $author, ?PublicPost $publicPost = null): PublicPost
    {
        return DB::transaction(function () use ($attributes, $author, $publicPost): PublicPost {
            $publicPost ??= new PublicPost;
            $existingCover = $publicPost->cover_image_path;

            if (($attributes['cover_image'] ?? null) instanceof UploadedFile) {
                $attributes['cover_image_path'] = $this->storeCoverImage($attributes['cover_image'], $existingCover);
            }

            unset($attributes['cover_image']);

            $status = PublicPostStatus::from((string) $attributes['status']);
            $publishedAt = $attributes['published_at'] ?? null;

            if ($status === PublicPostStatus::PUBLISHED && blank($publishedAt)) {
                $attributes['published_at'] = now();
            }

            if ($status === PublicPostStatus::DRAFT) {
                $attributes['published_at'] = null;
            }

            $publicPost->fill([
                ...$attributes,
                'author_id' => $publicPost->exists ? $publicPost->author_id : $author->getKey(),
            ]);

            $publicPost->save();

            return $publicPost->fresh('author');
        });
    }

    private function storeCoverImage(UploadedFile $file, ?string $oldPath): string
    {
        if ($oldPath !== null) {
            Storage::disk('public')->delete($oldPath);
        }

        $extension = $file->extension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $sanitizedName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'cover';
        $storedName = "post-{$sanitizedName}-".Str::lower((string) Str::uuid()).".{$extension}";

        return $file->storePubliclyAs('public-posts', $storedName, 'public');
    }
}
