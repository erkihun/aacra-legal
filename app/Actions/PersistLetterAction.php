<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersistLetterAction
{
    public function __construct(
        private readonly GenerateLetterReferenceNumberAction $generateReferenceNumber,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, User $actor, ?Letter $letter = null): Letter
    {
        return DB::transaction(function () use ($attributes, $actor, $letter): Letter {
            $letter ??= new Letter;

            if (! $letter->exists && ! $letter->getKey()) {
                $letter->{$letter->getKeyName()} = (string) Str::uuid7();
            }

            $template = $letter->exists
                ? $letter->template()->firstOrFail()
                : LetterTemplate::query()->findOrFail((string) $attributes['template_id']);

            if (! $letter->exists && ! $template->is_active) {
                abort(422, __('The selected letter template is inactive.'));
            }

            $payload = $letter->exists
                ? $attributes
                : $this->withTemplateDefaults($attributes, $template);

            $letter->fill([
                ...$payload,
                'template_id' => $template->getKey(),
                'language' => $payload['language'] ?? $letter->language ?? $template->language,
                'page_size' => $payload['page_size'] ?? $letter->page_size ?? $template->page_size,
                'orientation' => $payload['orientation'] ?? $letter->orientation ?? $template->orientation,
                'layout_config' => $payload['layout_config'] ?? $letter->layout_config ?? $template->layout_config,
            ]);

            if (! $letter->exists) {
                $letter->reference_number = $this->resolveReferenceNumber($attributes, $template);
                $letter->header_image_path_snapshot = $this->snapshotAsset($template->header_image_path, (string) $letter->getKey(), 'header');
                $letter->footer_image_path_snapshot = $this->snapshotAsset($template->footer_image_path, (string) $letter->getKey(), 'footer');
                $letter->signature_image_path_snapshot = $this->snapshotAsset($actor->signature_path, (string) $letter->getKey(), 'signature');
                $letter->signer_full_name_snapshot = filled($actor->name) ? $actor->name : null;
                $letter->signer_title_snapshot = filled($actor->job_title) ? $actor->job_title : null;
                $letter->created_by = $actor->getKey();
            }

            $letter->updated_by = $actor->getKey();
            $letter->save();

            return $letter->refresh()->loadMissing(['template', 'creator', 'updater']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveReferenceNumber(array $attributes, LetterTemplate $template): string
    {
        $referenceNumber = trim((string) ($attributes['reference_number'] ?? ''));
        $referencePreview = trim((string) ($attributes['reference_number_preview'] ?? ''));

        if ($referenceNumber === '' || $referenceNumber === $referencePreview) {
            return $this->generateReferenceNumber->execute($template);
        }

        return $referenceNumber;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withTemplateDefaults(array $attributes, LetterTemplate $template): array
    {
        return [
            ...$attributes,
            'subject' => $this->valueOrDefault($attributes['subject'] ?? null, $template->subject_template),
            'salutation' => $this->valueOrDefault($attributes['salutation'] ?? null, $template->salutation_template),
            'body_content' => $this->valueOrDefault($attributes['body_content'] ?? null, $template->body_content),
            'closing_content' => $this->valueOrDefault($attributes['closing_content'] ?? null, $template->closing_content),
            'signature_block_content' => $this->valueOrDefault($attributes['signature_block_content'] ?? null, $template->signature_block_content),
            'cc_content' => $this->valueOrDefault($attributes['cc_content'] ?? null, $template->cc_content),
            'enclosure_content' => $this->valueOrDefault($attributes['enclosure_content'] ?? null, $template->enclosure_content),
        ];
    }

    private function valueOrDefault(mixed $value, ?string $default): ?string
    {
        $normalized = is_string($value) ? trim($value) : null;

        if ($normalized !== null && $normalized !== '') {
            return $value;
        }

        return $default;
    }

    private function snapshotAsset(?string $sourcePath, string $letterId, string $prefix): ?string
    {
        if (! is_string($sourcePath) || trim($sourcePath) === '') {
            return null;
        }

        $normalizedPath = ltrim(trim($sourcePath), '/');

        if (
            ! str_starts_with($normalizedPath, 'letter-templates/')
            && ! str_starts_with($normalizedPath, 'users/')
            && ! str_starts_with($normalizedPath, 'letters/')
        ) {
            return $normalizedPath;
        }

        if (! Storage::disk('public')->exists($normalizedPath)) {
            return $normalizedPath;
        }

        $extension = pathinfo($normalizedPath, PATHINFO_EXTENSION) ?: 'png';
        $copiedPath = "letters/{$letterId}/{$prefix}-".Str::lower((string) Str::uuid()).".{$extension}";
        Storage::disk('public')->copy($normalizedPath, $copiedPath);

        return $copiedPath;
    }
}
