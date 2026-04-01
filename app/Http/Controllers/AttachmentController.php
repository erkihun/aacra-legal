<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteAttachmentAction;
use App\Http\Requests\UpdateAttachmentRequest;
use App\Models\Attachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function show(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_INLINE,
                    $attachment->original_name,
                ),
            ],
        );
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function update(UpdateAttachmentRequest $request, Attachment $attachment): RedirectResponse
    {
        $this->authorize('update', $attachment);

        $attachment->update([
            'original_name' => $request->string('original_name')->trim()->toString(),
        ]);

        return back()->with('success', __('Attachment updated.'));
    }

    public function destroy(Attachment $attachment, DeleteAttachmentAction $action): RedirectResponse
    {
        $this->authorize('delete', $attachment);

        $action->execute($attachment);

        return back()->with('success', __('Attachment deleted.'));
    }
}
