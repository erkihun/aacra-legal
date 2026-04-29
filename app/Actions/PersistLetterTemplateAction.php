<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersistLetterTemplateAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $actor, ?LetterTemplate $letterTemplate = null): LetterTemplate
    {
        return DB::transaction(function () use ($attributes, $actor, $letterTemplate): LetterTemplate {
            $letterTemplate ??= new LetterTemplate;
            $headerImage = $attributes['header_image'] ?? null;
            $footerImage = $attributes['footer_image'] ?? null;

            unset($attributes['header_image'], $attributes['footer_image']);

            $letterTemplate->fill($attributes);
            $letterTemplate->created_by ??= $actor->getKey();
            $letterTemplate->updated_by = $actor->getKey();

            if (! $letterTemplate->exists && ! isset($attributes['current_reference_number'])) {
                $letterTemplate->current_reference_number = max(0, (int) $letterTemplate->reference_start_number - 1);
            }

            $letterTemplate->save();

            $storedAttributes = [];

            if ($headerImage instanceof UploadedFile) {
                $storedAttributes['header_image_path'] = $this->storeImage(
                    $letterTemplate,
                    $headerImage,
                    'header',
                    $letterTemplate->header_image_path,
                );
            }

            if ($footerImage instanceof UploadedFile) {
                $storedAttributes['footer_image_path'] = $this->storeImage(
                    $letterTemplate,
                    $footerImage,
                    'footer',
                    $letterTemplate->footer_image_path,
                );
            }

            if ($storedAttributes !== []) {
                $letterTemplate->fill($storedAttributes);
                $letterTemplate->updated_by = $actor->getKey();
                $letterTemplate->save();
            }

            if ($letterTemplate->is_default) {
                LetterTemplate::query()
                    ->whereKeyNot($letterTemplate->getKey())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            return $letterTemplate->refresh()->loadMissing(['creator', 'updater']);
        });
    }

    private function storeImage(LetterTemplate $letterTemplate, UploadedFile $file, string $prefix, ?string $oldPath): string
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'png');
        $sanitizedName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $prefix;
        $storedName = "{$prefix}-{$sanitizedName}-".Str::lower((string) Str::uuid()).".{$extension}";
        $directory = 'letter-templates/'.$letterTemplate->getKey();
        $path = $file->storePubliclyAs($directory, $storedName, 'public');

        if ($oldPath !== null && $oldPath !== '' && str_starts_with($oldPath, 'letter-templates/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }
}
