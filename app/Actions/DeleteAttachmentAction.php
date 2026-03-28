<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAttachmentAction
{
    public function execute(Attachment $attachment): void
    {
        DB::transaction(function () use ($attachment): void {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        });
    }
}
