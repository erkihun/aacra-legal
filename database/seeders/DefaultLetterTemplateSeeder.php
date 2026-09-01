<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DefaultLetterTemplateSeeder extends Seeder
{
    private const CODE = 'DEFAULT-REQUEST-LETTER';

    public function run(): void
    {
        $now = now();

        $existingDefault = DB::table('letter_templates')
            ->whereNull('deleted_at')
            ->where('is_default', true)
            ->orderByDesc('updated_at')
            ->first();

        $existingByCode = DB::table('letter_templates')
            ->whereNull('deleted_at')
            ->where('code', self::CODE)
            ->first();

        $target = $existingDefault ?? $existingByCode;

        if ($target === null) {
            $activeTemplates = DB::table('letter_templates')
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->orderByDesc('updated_at')
                ->get();

            if ($activeTemplates->count() === 1) {
                $target = $activeTemplates->first();
            }
        }

        if ($target !== null) {
            $this->updateExistingTemplate((string) $target->id, $target, $now);

            return;
        }

        DB::table('letter_templates')->insert(array_merge(
            [
                'id' => (string) Str::uuid7(),
                'code' => self::CODE,
                'created_at' => $now,
            ],
            $this->baselineAttributes(),
            ['updated_at' => $now],
        ));
    }

    /**
     * @param  object  $template
     */
    private function updateExistingTemplate(string $id, object $template, mixed $now): void
    {
        DB::table('letter_templates')
            ->whereNull('deleted_at')
            ->where('id', '!=', $id)
            ->where('is_default', true)
            ->update([
                'is_default' => false,
                'updated_at' => $now,
            ]);

        DB::table('letter_templates')
            ->where('id', $id)
            ->update(array_merge(
                $this->baselineAttributes($template),
                ['updated_at' => $now],
            ));
    }

    /**
     * @return array<string, mixed>
     */
    private function baselineAttributes(?object $template = null): array
    {
        return [
            'name' => $this->valueOrDefault($template?->name ?? null, 'Default Request Letter Template'),
            'document_type' => $this->valueOrDefault($template?->document_type ?? null, 'request'),
            'language' => $this->valueOrDefault($template?->language ?? null, 'am'),
            'page_size' => $this->valueOrDefault($template?->page_size ?? null, 'A4'),
            'orientation' => $this->valueOrDefault($template?->orientation ?? null, 'portrait'),
            'reference_label' => $this->valueOrDefault($template?->reference_label ?? null, 'Reference No'),
            'reference_prefix' => $this->valueOrDefault($template?->reference_prefix ?? null, 'REQ'),
            'reference_start_number' => (int) ($template?->reference_start_number ?? 1),
            'current_reference_number' => (int) ($template?->current_reference_number ?? 0),
            'recipient_block_template' => $this->valueOrDefault(
                $template?->recipient_block_template ?? null,
                '<p>ለሕግ ጉዳዮች ዳይሬክቶሬት</p>',
            ),
            'salutation_template' => $this->valueOrDefault(
                $template?->salutation_template ?? null,
                '<p>እንደሚመለከተው፣</p>',
            ),
            'body_content' => $this->valueOrDefault(
                $template?->body_content ?? null,
                '<p>የጥያቄውን ዝርዝር እዚህ ያስገቡ።</p>',
            ),
            'closing_content' => $this->valueOrDefault(
                $template?->closing_content ?? null,
                '<p>ከአክብሮት ጋር፣</p>',
            ),
            'signature_block_content' => $this->valueOrDefault(
                $template?->signature_block_content ?? null,
                '<p>[የጠያቂው ስም]</p>',
            ),
            'layout_config' => $template?->layout_config ?? json_encode([
                'margin_top_mm' => 20,
                'margin_right_mm' => 18,
                'margin_bottom_mm' => 20,
                'margin_left_mm' => 18,
                'content_top_margin_mm' => 20,
                'content_bottom_margin_mm' => 20,
            ], JSON_THROW_ON_ERROR),
            'numbering_config' => $template?->numbering_config ?? json_encode([
                'pad_length' => 4,
                'separator' => '-',
                'include_year' => true,
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'is_default' => true,
        ];
    }

    private function valueOrDefault(mixed $value, string $default): string
    {
        return is_string($value) && trim($value) !== ''
            ? $value
            : $default;
    }
}
