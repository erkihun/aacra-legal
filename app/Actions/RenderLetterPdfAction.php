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
    private static array $fontDataUriCache = [];

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
        App::setLocale($this->resolvedLocale($letter));

        try {
            $dompdf = new Dompdf($this->options());
            $dompdf->loadHtml($this->html($letter), 'UTF-8');
            $dompdf->setPaper('a4', $letter->orientation === 'landscape' ? 'landscape' : 'portrait');
            $dompdf->render();

            return $dompdf->output();
        } finally {
            App::setLocale($previousLocale);
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
        $letter->loadMissing(['template', 'creator']);

        $layout = is_array($letter->layout_config) ? $letter->layout_config : [];
        $locale = $this->resolvedLocale($letter);
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

        $headerImage = $this->assetDataUri($letter->header_image_path_snapshot ?: $letter->template?->header_image_path);
        $footerImage = $this->assetDataUri($letter->footer_image_path_snapshot ?: $letter->template?->footer_image_path);
        $signatureImage = $this->assetDataUri($letter->signature_image_path_snapshot ?: $letter->creator?->signature_path);

        $headerHeight = $headerImage ? 30 : 0;
        $footerHeight = $footerImage ? 22 : 0;
        $headerSlotHeight = $headerTopMargin + $headerHeight + $headerBottomSpacing;
        $footerSlotHeight = $footerTopSpacing + $footerHeight + $footerBottomMargin;
        $pageTopMargin = $headerSlotHeight + $contentTopMargin;
        $pageBottomMargin = $footerSlotHeight + $contentBottomMargin;

        $recipientLines = array_values(array_filter([
            $letter->recipient_name,
            $letter->recipient_title,
            $letter->recipient_organization,
            $letter->recipient_address,
        ], static fn (?string $value): bool => filled($value)));

        return [
            'letter' => $letter,
            'documentLanguage' => $locale,
            'isAmharicDocument' => $isAmharicDocument,
            'documentTitle' => $letter->reference_number ?: __('letters.detail_title'),
            'referenceLabel' => $letter->template?->reference_label ?: __('letters.preview.reference'),
            'dateLabel' => __('letters.preview.date'),
            'ccLabel' => __('letters.preview.cc'),
            'enclosureLabel' => __('letters.preview.enclosure'),
            'subjectLabel' => __('letters.preview.subject'),
            'embeddedFontCss' => $this->embeddedFontCss(),
            'pdfBodyClass' => $isAmharicDocument ? 'pdf-amharic' : 'pdf-latin',
            'bodyFontSizePx' => $isAmharicDocument ? 12.5 : 12,
            'bodyLineHeight' => $isAmharicDocument ? 1.85 : 1.65,
            'bodyWordSpacingEm' => $isAmharicDocument ? 0.03 : 0,
            'bodyLetterSpacingEm' => $isAmharicDocument ? 0.01 : 0,
            'subjectLabelLetterSpacingEm' => $isAmharicDocument ? 0.03 : 0.12,
            'sectionLabelLetterSpacingEm' => $isAmharicDocument ? 0.04 : 0.18,
            'labelTextTransform' => $isAmharicDocument ? 'none' : 'uppercase',
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
            'recipientBlock' => implode("\n", $recipientLines),
            'bodyHtml' => $this->richContentHtml($letter->body_content),
            'salutationHtml' => $this->textOrHtml($letter->salutation),
            'closingHtml' => $this->textOrHtml($letter->closing_content),
            'signatureBlockHtml' => $this->textOrHtml($letter->signature_block_content),
            'enclosureHtml' => $this->textOrHtml($letter->enclosure_content),
            'ccItems' => $this->bulletItems($letter->cc_content),
            'subject' => $letter->subject,
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
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 96);
        $options->set('isFontSubsettingEnabled', true);

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
        $regularFont = $this->fontDataUri('NotoSansEthiopic-Regular.ttf');
        $boldFont = $this->fontDataUri('NotoSansEthiopic-Bold.ttf');

        if ($regularFont === null || $boldFont === null) {
            return '';
        }

        return <<<CSS
@font-face {
    font-family: 'LDMSPdfEthiopic';
    font-style: normal;
    font-weight: 400;
    src: url('{$regularFont}') format('truetype');
}

@font-face {
    font-family: 'LDMSPdfEthiopic';
    font-style: normal;
    font-weight: 700;
    src: url('{$boldFont}') format('truetype');
}
CSS;
    }

    private function fontDataUri(string $fileName): ?string
    {
        if (array_key_exists($fileName, self::$fontDataUriCache)) {
            return self::$fontDataUriCache[$fileName];
        }

        $path = resource_path('fonts/pdf/'.$fileName);

        if (! is_file($path)) {
            self::$fontDataUriCache[$fileName] = null;

            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            self::$fontDataUriCache[$fileName] = null;

            return null;
        }

        $mimeType = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'woff' => 'font/woff',
            default => mime_content_type($path) ?: 'application/octet-stream',
        };
        self::$fontDataUriCache[$fileName] = sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));

        return self::$fontDataUriCache[$fileName];
    }

    private function assetDataUri(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $normalizedPath = ltrim(trim($path), '/');

        if (Storage::disk('public')->exists($normalizedPath)) {
            $contents = Storage::disk('public')->get($normalizedPath);
            $mimeType = Storage::disk('public')->mimeType($normalizedPath) ?: 'application/octet-stream';

            return sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));
        }

        $publicPath = public_path($normalizedPath);

        if (is_file($publicPath)) {
            $contents = file_get_contents($publicPath);

            if ($contents === false) {
                return null;
            }

            $mimeType = mime_content_type($publicPath) ?: 'application/octet-stream';

            return sprintf('data:%s;base64,%s', $mimeType, base64_encode($contents));
        }

        return null;
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
