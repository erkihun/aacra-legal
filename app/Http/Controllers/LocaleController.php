<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request, SystemSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in($settings->supportedLocales())],
        ]);

        $request->session()->put('locale', $validated['locale']);

        if ($request->user() !== null) {
            $request->user()->update(['locale' => $validated['locale']]);
        }

        return back();
    }
}
