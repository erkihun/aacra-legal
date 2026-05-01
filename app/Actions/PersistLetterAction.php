<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Department;
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

            $documentLanguage = in_array(($payload['language'] ?? $letter->language ?? $template->language), ['en', 'am'], true)
                ? (string) ($payload['language'] ?? $letter->language ?? $template->language)
                : 'en';
            $normalizedRecipients = $this->snapshotRecipients($payload['recipients'] ?? []);
            $primaryRecipient = $normalizedRecipients[0] ?? null;
            $requestedStatus = (string) ($payload['status'] ?? $letter->status ?? 'draft');
            $resolvedStatus = (($letter->approval_status ?? 'draft') === 'approved' && $requestedStatus === 'archived')
                ? 'archived'
                : (($letter->approval_status ?? 'draft') === 'approved' ? 'final' : ($requestedStatus === 'final' ? 'draft' : $requestedStatus));
            $legacyRecipientName = $this->legacyRecipientName($primaryRecipient, $documentLanguage);
            $legacyRecipientOrganization = $this->legacyRecipientOrganization($primaryRecipient, $documentLanguage);

            $letter->fill([
                ...$payload,
                'template_id' => $template->getKey(),
                'recipients' => $normalizedRecipients,
                'recipient_name' => $legacyRecipientName ?? $letter->recipient_name ?? ($payload['recipient_name'] ?? null),
                'recipient_organization' => $legacyRecipientOrganization ?? $letter->recipient_organization ?? ($payload['recipient_organization'] ?? null),
                'language' => $payload['language'] ?? $letter->language ?? $template->language,
                'page_size' => $payload['page_size'] ?? $letter->page_size ?? $template->page_size,
                'orientation' => $payload['orientation'] ?? $letter->orientation ?? $template->orientation,
                'status' => $resolvedStatus,
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

    /**
     * @param  mixed  $value
     * @return array<int, array<string, string|null>>
     */
    private function snapshotRecipients(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $departmentIds = collect($value)
            ->map(fn (mixed $recipient): ?string => is_array($recipient) ? $this->normalizeOptionalString($recipient['recipient_department_id'] ?? null) : null)
            ->filter()
            ->values()
            ->all();

        $departments = Department::query()
            ->whereIn('id', $departmentIds)
            ->get(['id', 'name_en', 'name_am'])
            ->keyBy('id');

        return collect($value)
            ->map(function (mixed $recipient) use ($departments): ?array {
                if (! is_array($recipient)) {
                    return null;
                }

                $departmentId = $this->normalizeOptionalString($recipient['recipient_department_id'] ?? null);
                $name = $this->normalizeOptionalString($recipient['recipient_name'] ?? null);

                if (! filled($name) && ! filled($departmentId)) {
                    return null;
                }
                /** @var Department|null $department */
                $department = $departmentId ? $departments->get($departmentId) : null;
                $resolvedType = filled($department?->getKey()) && ! filled($name) ? 'department' : 'text';

                return [
                    'recipient_type' => $resolvedType,
                    'recipient_name' => $name,
                    'recipient_department_id' => $department?->getKey() ?? $departmentId,
                    'recipient_department_name_en' => $department?->name_en ?? $this->normalizeOptionalString($recipient['recipient_department_name_en'] ?? null),
                    'recipient_department_name_am' => $department?->name_am ?? $this->normalizeOptionalString($recipient['recipient_department_name_am'] ?? null),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string|null>|null  $recipient
     */
    private function legacyRecipientName(?array $recipient, string $language): ?string
    {
        if (! is_array($recipient)) {
            return null;
        }

        if (filled($recipient['recipient_name'] ?? null)) {
            return $recipient['recipient_name'];
        }

        return $this->legacyRecipientOrganization($recipient, $language);
    }

    /**
     * @param  array<string, string|null>|null  $recipient
     */
    private function legacyRecipientOrganization(?array $recipient, string $language): ?string
    {
        if (! is_array($recipient)) {
            return null;
        }

        return $language === 'am'
            ? ($recipient['recipient_department_name_am'] ?? $recipient['recipient_department_name_en'])
            : ($recipient['recipient_department_name_en'] ?? $recipient['recipient_department_name_am']);
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
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
