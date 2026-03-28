<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreAttachmentAction
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function execute(Model $attachable, array $files, User $uploadedBy): void
    {
        foreach ($files as $file) {
            $originalName = basename((string) $file->getClientOriginalName());
            $extension = strtolower((string) ($file->extension() ?: $file->getClientOriginalExtension()));
            $path = $file->store(
                path: sprintf('legal/%s/%s', class_basename($attachable), $attachable->getKey()),
                options: ['disk' => 'local'],
            );

            $attachable->attachments()->create([
                'uploaded_by_id' => $uploadedBy->getKey(),
                'disk' => 'local',
                'path' => $path,
                'original_name' => $originalName,
                'stored_name' => Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).($extension !== '' ? ".{$extension}" : ''),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'sha256' => hash_file('sha256', Storage::disk('local')->path($path)),
            ]);
        }
    }
}
