<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\SendTelegramTestMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTelegramTestMessageRequest;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TelegramTestMessageController extends Controller
{
    public function __invoke(
        SendTelegramTestMessageRequest $request,
        SendTelegramTestMessageAction $action,
    ): RedirectResponse {
        $this->authorize('update', SystemSetting::class);

        try {
            $action->execute($request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Telegram test message sent successfully.');
    }
}
