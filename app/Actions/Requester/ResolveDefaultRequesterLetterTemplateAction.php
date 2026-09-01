<?php

declare(strict_types=1);

namespace App\Actions\Requester;

use App\Models\LetterTemplate;
use RuntimeException;

final class ResolveDefaultRequesterLetterTemplateAction
{
    public function execute(): LetterTemplate
    {
        $defaultTemplate = LetterTemplate::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();

        if ($defaultTemplate instanceof LetterTemplate) {
            return $defaultTemplate;
        }

        $requestTemplate = LetterTemplate::query()
            ->where('is_active', true)
            ->whereIn('document_type', ['request', 'legal_request', 'advisory_request', 'lawsuit_request'])
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();

        if ($requestTemplate instanceof LetterTemplate) {
            return $requestTemplate;
        }

        $activeTemplates = LetterTemplate::query()
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        if ($activeTemplates->count() === 1) {
            return $activeTemplates->firstOrFail();
        }

        throw new RuntimeException(__('requester.formal_template_unavailable'));
    }
}
