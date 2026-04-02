<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandingAssetController extends Controller
{
    public function show(string $path): StreamedResponse|Response
    {
        $normalizedPath = ltrim($path, '/');

        if (
            str_contains($normalizedPath, '..')
            || ! (
                str_starts_with($normalizedPath, 'branding/')
                || str_starts_with($normalizedPath, 'public-posts/')
                || str_starts_with($normalizedPath, 'users/')
            )
        ) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($normalizedPath)) {
            abort(404);
        }

        return Storage::disk('public')->response(
            $normalizedPath,
            null,
            [
                'Cache-Control' => 'public, max-age=3600',
            ],
        );
    }
}
