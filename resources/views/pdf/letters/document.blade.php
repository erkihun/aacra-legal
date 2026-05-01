<!DOCTYPE html>
<html lang="{{ $letter->language }}">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }}</title>
    <style>
        @page {
            size: A4 {{ $orientation }};
            margin: {{ $pageTopMarginMm }}mm {{ $rightMarginMm }}mm {{ $pageBottomMarginMm }}mm {{ $leftMarginMm }}mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.65;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        header {
            position: fixed;
            top: -{{ $pageTopMarginMm }}mm;
            left: 0;
            right: 0;
            height: {{ $headerSlotHeightMm }}mm;
            border-bottom: 1px solid #cbd5e1;
        }

        header .header-inner {
            box-sizing: border-box;
            height: 100%;
            padding-top: {{ $headerTopMarginMm }}mm;
            padding-bottom: {{ $headerBottomSpacingMm }}mm;
        }

        header img {
            display: block;
            width: 100%;
            height: {{ $headerHeightMm }}mm;
            object-fit: contain;
            object-position: top center;
        }

        footer {
            position: fixed;
            bottom: -{{ $pageBottomMarginMm }}mm;
            left: -{{ $leftMarginMm }}mm;
            right: -{{ $rightMarginMm }}mm;
            height: {{ $footerSlotHeightMm }}mm;
            border-top: 1px solid #cbd5e1;
        }

        footer .footer-inner {
            box-sizing: border-box;
            height: 100%;
            padding-top: {{ $footerTopSpacingMm }}mm;
            padding-right: {{ $footerRightMarginMm }}mm;
            padding-bottom: {{ $footerBottomMarginMm }}mm;
            padding-left: {{ $footerLeftMarginMm }}mm;
        }

        footer img {
            display: block;
            width: 100%;
            height: {{ $footerHeightMm }}mm;
            object-fit: contain;
            object-position: bottom center;
        }

        .reference-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .reference-table td {
            vertical-align: top;
            font-size: 12px;
        }

        .reference-table td:last-child {
            text-align: right;
        }

        .meta-label {
            margin: 0;
            font-weight: 700;
            color: #334155;
        }

        .meta-value {
            margin: 4px 0 0;
            color: #0f172a;
        }

        .recipient-block,
        .salutation-block,
        .closing-block {
            white-space: pre-line;
        }

        .recipient-block,
        .subject-block,
        .salutation-block,
        .body-block,
        .closing-block,
        .signature-block,
        .cc-block,
        .enclosure-block {
            margin-bottom: 18px;
        }

        .subject-block {
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            padding: 12px 0;
            text-align: center;
        }

        .subject-block .subject-label {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
        }

        .subject-block .subject-value {
            margin: 8px 0 0;
            font-size: 14px;
            font-weight: 700;
            color: #020617;
        }

        .body-block p,
        .closing-block p,
        .signature-note p,
        .enclosure-block p {
            margin: 0 0 12px;
        }

        .body-block ul,
        .body-block ol,
        .cc-block ul,
        .enclosure-block ul,
        .signature-note ul {
            margin-top: 0;
        }

        .signature-block {
            margin-top: 24px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 240px;
            margin-left: auto;
            text-align: right;
        }

        .signature-image {
            display: block;
            max-width: 140px;
            max-height: 88px;
            margin: 0 0 12px auto;
        }

        .signer-name {
            margin: 0;
            font-weight: 700;
            color: #020617;
        }

        .signer-title {
            margin: 4px 0 0;
            color: #334155;
        }

        .signature-note {
            margin-top: 12px;
        }

        .section-label {
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #64748b;
        }

        .cc-list {
            margin: 0;
            padding-left: 20px;
        }

        .cc-list li {
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
@if ($headerImage)
    <header>
        <div class="header-inner">
            <img src="{{ $headerImage }}" alt="">
        </div>
    </header>
@endif

@if ($footerImage)
    <footer>
        <div class="footer-inner">
            <img src="{{ $footerImage }}" alt="">
        </div>
    </footer>
@endif

<main>
    <table class="reference-table">
        <tr>
            <td>
                <p class="meta-label">{{ $referenceLabel }}</p>
                <p class="meta-value">{{ $referenceNumber ?: '-' }}</p>
            </td>
            <td>
                <p class="meta-label">{{ $dateLabel }}</p>
                <p class="meta-value">{{ $letterDate ?: '-' }}</p>
            </td>
        </tr>
    </table>

    @if ($recipientBlock !== '')
        <section class="recipient-block">
            {!! nl2br(e($recipientBlock)) !!}
        </section>
    @endif

    @if (filled($subject))
        <section class="subject-block">
            <p class="subject-label">{{ $subjectLabel }}</p>
            <p class="subject-value">{{ $subject }}</p>
        </section>
    @endif

    @if ($salutationHtml)
        <section class="salutation-block">{!! $salutationHtml !!}</section>
    @endif

    <section class="body-block">{!! $bodyHtml !!}</section>

    @if ($closingHtml)
        <section class="closing-block">{!! $closingHtml !!}</section>
    @endif

    @if ($signatureImage || filled($signerName) || filled($signerTitle) || $signatureBlockHtml)
        <section class="signature-block">
            <div class="signature-box">
                @if ($signatureImage)
                    <img class="signature-image" src="{{ $signatureImage }}" alt="">
                @endif

                @if (filled($signerName))
                    <p class="signer-name">{{ $signerName }}</p>
                @endif

                @if (filled($signerTitle))
                    <p class="signer-title">{{ $signerTitle }}</p>
                @endif

                @if ($signatureBlockHtml)
                    <div class="signature-note">{!! $signatureBlockHtml !!}</div>
                @endif
            </div>
        </section>
    @endif

    @if ($ccItems !== [])
        <section class="cc-block">
            <p class="section-label">{{ $ccLabel }}</p>
            <ul class="cc-list">
                @foreach ($ccItems as $ccItem)
                    <li>{{ $ccItem }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($enclosureHtml)
        <section class="enclosure-block">
            <p class="section-label">{{ $enclosureLabel }}</p>
            {!! $enclosureHtml !!}
        </section>
    @endif
</main>
</body>
</html>
