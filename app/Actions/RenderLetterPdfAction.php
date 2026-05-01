<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Letter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RenderLetterPdfAction
{
    /**
     * @var array<string, string|null>
     */
    private static array $fontSourceCache = [];

    /**
     * @var array<string, string|null>
     */
    private static array $assetSourceCache = [];

    public function __construct(
        private readonly ViewFactory $view,
    ) {}

    public function inlineResponse(Letter $letter): Response
    {
        return $this->response($letter, 'inline');
    }

    public function downloadResponse(Letter $letter): Response
    {
        return $this->response($letter, 'attachment');
    }

    public function filename(Letter $letter): string
    {
        $base = $letter->reference_number
            ?: $letter->subject
            ?: __('letters.detail_title');

        $filename = Str::of($base)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/i', '-')
            ->trim('-')
            ->value();

        return ($filename !== '' ? $filename : 'letter').'.pdf';
    }

    public function html(Letter $letter): string
    {
        $viewData = $this->viewData($letter);

        return $this->view->make('pdf.letters.document', $viewData)->render();
    }

    public function render(Letter $letter): string
    {
        $previousLocale = App::currentLocale();
        $previousMemoryLimit = ini_get('memory_limit');
        App::setLocale($this->resolvedLocale($letter));

        try {
            $this->increaseMemoryLimit('512M');

            $dompdf = new Dompdf($this->options());
            $dompdf->loadHtml($this->html($letter), 'UTF-8');
            $dompdf->setPaper('a4', $letter->orientation === 'landscape' ? 'landscape' : 'portrait');
            $dompdf->render();

            return $dompdf->output();
        } finally {
            App::setLocale($previousLocale);

            if (is_string($previousMemoryLimit) && $previousMemoryLimit !== '') {
                @ini_set('memory_limit', $previousMemoryLimit);
            }
        }
    }

    private function response(Letter $letter, string $disposition): Response
    {
        $filename = $this->filename($letter);

        return response($this->render($letter), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Letter $letter): array
    {
        $letter->loadMissing(['template', 'creator', 'approver']);

        $layout = is_array($letter->layout_config) ? $letter->layout_config : [];
        $locale = $this->resolvedLocale($letter);
        $usesNyalaTypography = $this->usesNyalaTypography($letter, $locale);
        $isAmharicDocument = $locale === 'am';
        $leftMargin = (int) ($layout['margin_left_mm'] ?? 18);
        $rightMargin = (int) ($layout['margin_right_mm'] ?? 18);
        $contentTopMargin = (int) ($layout['content_top_margin_mm'] ?? $layout['margin_top_mm'] ?? 20);
        $contentBottomMargin = (int) ($layout['content_bottom_margin_mm'] ?? $layout['margin_bottom_mm'] ?? 20);
        $headerTopMargin = (int) ($layout['header_top_margin_mm'] ?? 0);
        $headerBottomSpacing = (int) ($layout['header_bottom_spacing_mm'] ?? 4);
        $footerTopSpacing = (int) ($layout['footer_top_spacing_mm'] ?? 4);
        $footerLeftMargin = (int) ($layout['footer_left_margin_mm'] ?? $layout['margin_left_mm'] ?? 18);
        $footerRightMargin = (int) ($layout['footer_right_margin_mm'] ?? $layout['margin_right_mm'] ?? 18);
        $footerBottomMargin = (int) ($layout['footer_bottom_margin_mm'] ?? 0);

        $headerImage = $this->assetSource($letter->header_image_path_snapshot ?: $letter->template?->header_image_path);
        $footerImage = $this->assetSource($letter->footer_image_path_snapshot ?: $letter->template?->footer_image_path);
        $signatureImage = $this->assetSource(
            (($letter->approval_status ?? 'draft') === 'approved'
                ? ($letter->approved_signature_path_snapshot ?: $letter->approver?->signature_path)
                : ($letter->signature_image_path_snapshot ?: $letter->creator?->signature_path))
        );

        $headerHeight = $headerImage ? 30 : 0;
        $footerHeight = $footerImage ? 22 : 0;
        $headerSlotHeight = $headerTopMargin + $headerHeight + $headerBottomSpacing;
        $footerSlotHeight = $footerTopSpacing + $footerHeight + $footerBottomMargin;
        $pageTopMargin = $headerSlotHeight + $contentTopMargin;
        $pageBottomMargin = $footerSlotHeight + $contentBottomMargin;

        $recipientItems = $letter->recipientDisplayLines($letter->language);
        $recipientBlock = count($recipientItems) > 1
            ? ''
            : ($recipientItems[0] ?? implode("\n", array_values(array_filter([
                $letter->recipient_name,
                $letter->recipient_title,
                $letter->recipient_organization,
                $letter->recipient_address,
            ], static fn (?string $value): bool => filled($value)))));

        return [
            'letter' => $letter,
            'documentLanguage' => $locale,
            'isAmharicDocument' => $isAmharicDocument,
            'usesNyalaTypography' => $usesNyalaTypography,
            'documentTitle' => $letter->reference_number ?: __('letters.detail_title'),
            'referenceLabel' => $letter->template?->reference_label ?: __('letters.preview.reference'),
            'dateLabel' => __('letters.preview.date'),
            'ccLabel' => __('letters.preview.cc'),
            'enclosureLabel' => __('letters.preview.enclosure'),
            'embeddedFontCss' => $this->embeddedFontCss(),
            'pdfBodyClass' => $usesNyalaTypography ? 'pdf-nyala' : 'pdf-latin',
            'bodyFontSizePx' => $usesNyalaTypography ? 12.5 : 12,
            'bodyLineHeight' => $usesNyalaTypography ? 1.85 : 1.65,
            'bodyWordSpacingEm' => $usesNyalaTypography ? 0.03 : 0,
            'bodyLetterSpacingEm' => $usesNyalaTypography ? 0.01 : 0,
            'sectionLabelLetterSpacingEm' => $usesNyalaTypography ? 0.04 : 0.18,
            'labelTextTransform' => $usesNyalaTypography ? 'none' : 'uppercase',
            'orientation' => $letter->orientation === 'landscape' ? 'landscape' : 'portrait',
            'pageTopMarginMm' => $pageTopMargin,
            'pageBottomMarginMm' => $pageBottomMargin,
            'leftMarginMm' => $leftMargin,
            'rightMarginMm' => $rightMargin,
            'contentTopMarginMm' => $contentTopMargin,
            'contentBottomMarginMm' => $contentBottomMargin,
            'headerTopMarginMm' => $headerTopMargin,
            'headerBottomSpacingMm' => $headerBottomSpacing,
            'footerTopSpacingMm' => $footerTopSpacing,
            'footerLeftMarginMm' => $footerLeftMargin,
            'footerRightMarginMm' => $footerRightMargin,
            'footerBottomMarginMm' => $footerBottomMargin,
            'headerSlotHeightMm' => $headerSlotHeight,
            'footerSlotHeightMm' => $footerSlotHeight,
            'headerHeightMm' => $headerHeight,
            'footerHeightMm' => $footerHeight,
            'headerImage' => $headerImage,
            'footerImage' => $footerImage,
            'signatureImage' => $signatureImage,
            'recipientBlock' => $recipientBlock,
            'recipientItems' => count($recipientItems) > 1 ? $recipientItems : [],
            'bodyHtml' => $this->richContentHtml($letter->body_content),
            'salutationHtml' => $this->textOrHtml($letter->salutation),
            'closingHtml' => $this->textOrHtml($letter->closing_content),
            'signatureBlockHtml' => $this->textOrHtml($letter->signature_block_content),
            'enclosureHtml' => $this->textOrHtml($letter->enclosure_content),
            'ccItems' => $this->bulletItems($letter->cc_content),
            'subject' => $this->displaySubject($letter->subject),
            'referenceNumber' => $letter->reference_number,
            'letterDate' => $letter->letter_date?->toDateString(),
            'signerName' => $letter->signerFullName(),
            'signerTitle' => $letter->signerTitle(),
        ];
    }

    private function options(): Options
    {
        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Nyala');
        $options->set('dpi', 96);
        $options->set('isFontSubsettingEnabled', true);
        $options->setChroot([
            base_path(),
            public_path(),
            storage_path(),
            resource_path('fonts/pdf'),
        ]);

        return $options;
    }

    private function resolvedLocale(Letter $letter): string
    {
        $locale = is_string($letter->language) ? strtolower(trim($letter->language)) : '';

        return in_array($locale, ['en', 'am'], true)
            ? $locale
            : (string) config('app.locale', 'en');
    }

    private function embeddedFontCss(): string
    {
        $regularFont = $this->fontSource('Nyala.ttf');

        if ($regularFont === null) {
            return '';
        }

        return <<<CSS
@font-face {
    font-family: 'Nyala';
    font-style: normal;
    font-weight: 400;
    src: url('{$regularFont}') format('truetype');
}

@font-face {
    font-family: 'Nyala';
    font-style: normal;
    font-weight: 700;
    src: url('{$regularFont}') format('truetype');
}
CSS;
    }

    private function usesNyalaTypography(Letter $letter, string $locale): bool
    {
        if ($locale === 'am') {
            return true;
        }

        return collect([
            $letter->template?->reference_label,
            $letter->reference_number,
            ...$letter->recipientDisplayLines($letter->language),
            $letter->subject,
            $letter->salutation,
            $letter->body_content,
            $letter->closing_content,
            $letter->signature_block_content,
            $letter->cc_content,
            $letter->enclosure_content,
            $letter->signerFullName(),
            $letter->signerTitle(),
        ])->contains(static fn (mixed $value): bool => is_string($value) && preg_match('/[\x{1200}-\x{137F}\x{1380}-\x{139F}\x{2D80}-\x{2DDF}\x{AB00}-\x{AB2F}]/u', $value) === 1);
    }

    private function fontSource(string $fileName): ?string
    {
        if (array_key_exists($fileName, self::$fontSourceCache)) {
            return self::$fontSourceCache[$fileName];
        }

        $path = resource_path('fonts/pdf/'.$fileName);

        if (! is_file($path)) {
            self::$fontSourceCache[$fileName] = null;

            return null;
        }

        self::$fontSourceCache[$fileName] = $this->localFileUri($path);

        return self::$fontSourceCache[$fileName];
    }

    private function assetSource(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $normalizedPath = ltrim(trim($path), '/');

        if (array_key_exists($normalizedPath, self::$assetSourceCache)) {
            return self::$assetSourceCache[$normalizedPath];
        }

        if (Storage::disk('public')->exists($normalizedPath)) {
            $resolvedPath = Storage::disk('public')->path($normalizedPath);

            if (is_file($resolvedPath)) {
                self::$assetSourceCache[$normalizedPath] = $this->localFileUri($resolvedPath);

                return self::$assetSourceCache[$normalizedPath];
            }
        }

        $publicPath = public_path($normalizedPath);

        if (is_file($publicPath)) {
            self::$assetSourceCache[$normalizedPath] = $this->localFileUri($publicPath);

            return self::$assetSourceCache[$normalizedPath];
        }

        self::$assetSourceCache[$normalizedPath] = null;

        return null;
    }

    private function localFileUri(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);

        if (preg_match('/^[A-Za-z]:\//', $normalizedPath) === 1) {
            return 'file://'.$normalizedPath;
        }

        return 'file://'.$normalizedPath;
    }

    private function increaseMemoryLimit(string $target): void
    {
        $current = ini_get('memory_limit');

        if (! is_string($current) || $current === '' || $current === '-1') {
            return;
        }

        if ($this->bytesFromIni($current) >= $this->bytesFromIni($target)) {
            return;
        }

        @ini_set('memory_limit', $target);
    }

    private function bytesFromIni(string $value): int
    {
        $trimmed = trim($value);

        if ($trimmed === '' || $trimmed === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($trimmed, -1));
        $number = (int) $trimmed;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $trimmed,
        };
    }

    private function containsHtml(?string $value): bool
    {
        return is_string($value) && preg_match('/<\/?[a-z][\s\S]*>/i', $value) === 1;
    }

    private function textOrHtml(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if ($this->containsHtml($value)) {
            return $value;
        }

        return nl2br(e(trim($value)));
    }

    private function richContentHtml(?string $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '<p></p>';
        }

        if ($this->containsHtml($value)) {
            return $value;
        }

        $paragraphs = preg_split('/\R{2,}/', trim($value)) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), static fn (string $item): bool => $item !== ''));

        if ($paragraphs === []) {
            return '<p></p>';
        }

        return collect($paragraphs)
            ->map(fn (string $paragraph): string => '<p>'.nl2br(e($paragraph)).'</p>')
            ->implode('');
    }

    private function displaySubject(?string $subject): ?string
    {
        if (! is_string($subject) || trim($subject) === '') {
            return null;
        }

        $trimmed = trim($subject);
        $normalized = preg_replace('/^(subject|ጉዳይ|ርዕስ)\s*[:\-–—]+\s*/iu', '', $trimmed);
        $normalized = is_string($normalized) ? trim($normalized) : '';

        return $normalized !== '' ? $normalized : $trimmed;
    }

    /**
     * @return array<int, string>
     */
    private function bulletItems(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $normalized = preg_replace('/^(cc|copy to)\s*:\s*/i', '', trim($value)) ?? trim($value);

        return array_values(array_filter(array_map(
            static fn (string $item): string => trim(preg_replace('/^[\x{2022}\*\-]\s*/u', '', $item) ?? $item),
            preg_split('/\R+|;\s*/', $normalized) ?: [],
        ), static fn (string $item): bool => $item !== ''));
    }
}
