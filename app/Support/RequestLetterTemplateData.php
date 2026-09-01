<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\LetterTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class RequestLetterTemplateData
{
    /**
     * @return array{
     *     template_id: string,
     *     template_name: string,
     *     language: string,
     *     header_image_path: string|null,
     *     footer_image_path: string|null,
     *     subject_template: string|null,
     *     recipient_block_template: string|null,
     *     salutation_template: string|null,
     *     body_content: string|null,
     *     closing_content: string|null,
     *     signature_block_content: string|null,
     *     layout_config: array<string, mixed>|null
     * }
     */
    public function snapshot(LetterTemplate $template): array
    {
        return [
            'template_id' => (string) $template->getKey(),
            'template_name' => $template->name,
            'language' => $template->language,
            'header_image_path' => $template->header_image_path,
            'footer_image_path' => $template->footer_image_path,
            'subject_template' => $template->subject_template,
            'recipient_block_template' => $template->recipient_block_template,
            'salutation_template' => $template->salutation_template,
            'body_content' => $template->body_content,
            'closing_content' => $template->closing_content,
            'signature_block_content' => $template->signature_block_content,
            'layout_config' => is_array($template->layout_config) ? $template->layout_config : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array{
     *     id: string,
     *     name: string,
     *     language: string,
     *     header_image_url: string|null,
     *     footer_image_url: string|null,
     *     subject_template: string|null,
     *     recipient_block_template: string|null,
     *     salutation_template: string|null,
     *     body_content: string|null,
     *     closing_content: string|null,
     *     signature_block_content: string|null,
     *     layout_config: array<string, mixed>|null
     * }|null
     */
    public function previewPayload(?array $snapshot = null, ?LetterTemplate $template = null): ?array
    {
        $resolved = $this->resolvedSnapshot($snapshot, $template);

        if ($resolved === null) {
            return null;
        }

        return [
            'id' => (string) ($resolved['template_id'] ?? ''),
            'name' => (string) ($resolved['template_name'] ?? ''),
            'language' => (string) ($resolved['language'] ?? 'en'),
            'header_image_url' => $this->assetUrl($resolved['header_image_path'] ?? null),
            'footer_image_url' => $this->assetUrl($resolved['footer_image_path'] ?? null),
            'subject_template' => $this->stringOrNull($resolved['subject_template'] ?? null),
            'recipient_block_template' => $this->stringOrNull($resolved['recipient_block_template'] ?? null),
            'salutation_template' => $this->stringOrNull($resolved['salutation_template'] ?? null),
            'body_content' => $this->stringOrNull($resolved['body_content'] ?? null),
            'closing_content' => $this->stringOrNull($resolved['closing_content'] ?? null),
            'signature_block_content' => $this->stringOrNull($resolved['signature_block_content'] ?? null),
            'layout_config' => is_array($resolved['layout_config'] ?? null) ? $resolved['layout_config'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @param  array{name?: string|null, name_en?: string|null, name_am?: string|null}|null  $department
     * @return array{
     *     template_name: string|null,
     *     language: string,
     *     header_image_url: string|null,
     *     footer_image_url: string|null,
     *     subject_template: string|null,
     *     recipient_block_template: string|null,
     *     salutation_template: string|null,
     *     body_content: string,
     *     closing_content: string|null,
     *     signature_block_content: string|null,
     *     layout_config: array<string, mixed>|null,
     *     reference_number: string|null,
     *     subject: string|null,
     *     date_submitted: string|null,
     *     department_name: string|null
     * }
     */
    public function renderPayload(
        ?array $snapshot,
        ?LetterTemplate $template,
        string $bodyContent,
        ?string $referenceNumber,
        ?string $subject,
        ?string $dateSubmitted,
        ?array $department,
        string $locale = 'en',
    ): array {
        $resolved = $this->resolvedSnapshot($snapshot, $template);

        return [
            'template_name' => $this->stringOrNull($resolved['template_name'] ?? null),
            'language' => (string) ($resolved['language'] ?? 'en'),
            'header_image_url' => $this->assetUrl($resolved['header_image_path'] ?? null),
            'footer_image_url' => $this->assetUrl($resolved['footer_image_path'] ?? null),
            'subject_template' => $this->stringOrNull($resolved['subject_template'] ?? null),
            'recipient_block_template' => $this->stringOrNull($resolved['recipient_block_template'] ?? null),
            'salutation_template' => $this->stringOrNull($resolved['salutation_template'] ?? null),
            'body_content' => $bodyContent,
            'closing_content' => $this->stringOrNull($resolved['closing_content'] ?? null),
            'signature_block_content' => $this->stringOrNull($resolved['signature_block_content'] ?? null),
            'layout_config' => is_array($resolved['layout_config'] ?? null) ? $resolved['layout_config'] : null,
            'reference_number' => $referenceNumber,
            'subject' => $subject,
            'date_submitted' => $dateSubmitted,
            'department_name' => $this->departmentName($department, $locale),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    private function resolvedSnapshot(?array $snapshot, ?LetterTemplate $template): ?array
    {
        if (is_array($snapshot) && $snapshot !== []) {
            return $snapshot;
        }

        if ($template instanceof LetterTemplate) {
            return $this->snapshot($template);
        }

        return null;
    }

    private function assetUrl(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $normalizedPath = trim($path);

        if (Str::startsWith($normalizedPath, '/')) {
            return url($normalizedPath);
        }

        $storagePath = SafeUrl::storageAssetPath($normalizedPath, ['letter-templates/']);

        if ($storagePath === null) {
            return null;
        }

        if (! Storage::disk('public')->exists($storagePath)) {
            return null;
        }

        return route('branding-assets.show', ['path' => $storagePath]);
    }

    /**
     * @param  array{name?: string|null, name_en?: string|null, name_am?: string|null}|null  $department
     */
    private function departmentName(?array $department, string $locale): ?string
    {
        if (! is_array($department)) {
            return null;
        }

        if (isset($department['name']) && is_string($department['name']) && trim($department['name']) !== '') {
            return trim($department['name']);
        }

        $am = $this->stringOrNull($department['name_am'] ?? null);
        $en = $this->stringOrNull($department['name_en'] ?? null);

        return $locale === 'am' ? ($am ?? $en) : ($en ?? $am);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
