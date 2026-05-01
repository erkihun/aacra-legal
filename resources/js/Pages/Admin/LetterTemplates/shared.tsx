import { sanitizeRichTextHtml } from '@/lib/sanitize-rich-text';
import { ReactNode, useEffect, useMemo, useState } from 'react';

export type LayoutConfig = {
    margin_top_mm?: number | null;
    margin_right_mm?: number | null;
    margin_bottom_mm?: number | null;
    margin_left_mm?: number | null;
    header_top_margin_mm?: number | null;
    header_bottom_spacing_mm?: number | null;
    footer_top_spacing_mm?: number | null;
    footer_left_margin_mm?: number | null;
    footer_right_margin_mm?: number | null;
    footer_bottom_margin_mm?: number | null;
    content_top_margin_mm?: number | null;
    content_bottom_margin_mm?: number | null;
};

export type NumberingConfig = {
    separator?: string | null;
    include_year?: boolean;
    pad_length?: number | null;
};

export type LetterTemplateItem = {
    id?: string;
    name: string;
    code: string;
    document_type?: string | null;
    language: 'en' | 'am';
    page_size: 'A4';
    orientation: 'portrait' | 'landscape';
    reference_label?: string | null;
    reference_prefix?: string | null;
    reference_start_number?: number | null;
    current_reference_number?: number | null;
    numbering_config?: NumberingConfig | null;
    subject_template?: string | null;
    header_image_path?: string | null;
    footer_image_path?: string | null;
    header_image_url?: string | null;
    footer_image_url?: string | null;
    recipient_block_template?: string | null;
    salutation_template?: string | null;
    body_content?: string | null;
    closing_content?: string | null;
    signature_block_content?: string | null;
    cc_content?: string | null;
    enclosure_content?: string | null;
    layout_config?: LayoutConfig | null;
    next_reference_number_preview?: string | null;
    letters_count?: number;
    is_active: boolean;
    is_default: boolean;
    notes?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    creator?: { name: string } | null;
    updater?: { name: string } | null;
};

export type LetterItem = {
    id?: string;
    template_id?: string | null;
    reference_number?: string | null;
    letter_date?: string | null;
    recipient_name?: string | null;
    recipient_title?: string | null;
    recipient_organization?: string | null;
    recipient_address?: string | null;
    subject?: string | null;
    salutation?: string | null;
    body_content?: string | null;
    closing_content?: string | null;
    signature_block_content?: string | null;
    cc_content?: string | null;
    enclosure_content?: string | null;
    signature_image_path_snapshot?: string | null;
    header_image_url?: string | null;
    footer_image_url?: string | null;
    signature_image_url?: string | null;
    signer_full_name?: string | null;
    signer_title?: string | null;
    language: 'en' | 'am';
    page_size: 'A4';
    orientation: 'portrait' | 'landscape';
    status?: 'draft' | 'final' | 'archived' | string | null;
    layout_config?: LayoutConfig | null;
    notes?: string | null;
    template?: { id: string; name: string; code: string } | null;
    created_at?: string | null;
    updated_at?: string | null;
    creator?: { name: string } | null;
    updater?: { name: string } | null;
};

export type LetterTemplateSelection = {
    id: string;
    name: string;
    code: string;
    language: 'en' | 'am';
    page_size: 'A4';
    orientation: 'portrait' | 'landscape';
    reference_label?: string | null;
    reference_prefix?: string | null;
    reference_start_number?: number | null;
    current_reference_number?: number | null;
    numbering_config?: NumberingConfig | null;
    subject_template?: string | null;
    recipient_block_template?: string | null;
    salutation_template?: string | null;
    body_content?: string | null;
    closing_content?: string | null;
    signature_block_content?: string | null;
    cc_content?: string | null;
    enclosure_content?: string | null;
    layout_config?: LayoutConfig | null;
    header_image_url?: string | null;
    footer_image_url?: string | null;
    reference_number_preview?: string | null;
};

export type PlaceholderField = {
    token: string;
    description: string;
};

export type PreviewData = Record<string, string>;

export type LetterRenderable = {
    page_size: 'A4';
    orientation: 'portrait' | 'landscape';
    language: 'en' | 'am';
    layout_config?: LayoutConfig | null;
    reference_label?: string | null;
    reference_number?: string | null;
    date?: string | null;
    recipient_block?: string | null;
    subject?: string | null;
    salutation?: string | null;
    body_content?: string | null;
    closing_content?: string | null;
    signature_block_content?: string | null;
    signature_image_url?: string | null;
    signer_full_name?: string | null;
    signer_title?: string | null;
    cc_content?: string | null;
    enclosure_content?: string | null;
    header_image_url?: string | null;
    footer_image_url?: string | null;
};

type LetterLabels = {
    subject: string;
    cc: string;
    enclosure: string;
    reference: string;
    date: string;
};

const previewLabelSets: Record<'en' | 'am', LetterLabels> = {
    en: {
        subject: 'Subject',
        cc: 'CC',
        enclosure: 'Enclosure',
        reference: 'Reference Number',
        date: 'Date',
    },
    am: {
        subject: 'ጉዳይ',
        cc: 'ግልባጭ',
        enclosure: 'አባሪ',
        reference: 'የማጣቀሻ ቁጥር',
        date: 'ቀን',
    },
};

type PageBlock =
    | { key: string; kind: 'recipient'; value: string }
    | { key: string; kind: 'subject'; value: string; label: string }
    | { key: string; kind: 'salutation'; value: string }
    | { key: string; kind: 'body'; value: string }
    | { key: string; kind: 'closing'; value: string }
    | { key: string; kind: 'signature'; value?: string | null; signerName?: string | null; signerTitle?: string | null; signatureImageUrl?: string | null }
    | { key: string; kind: 'cc'; value: string; title: string }
    | { key: string; kind: 'enclosure'; value: string; title: string };

type LetterPage = {
    blocks: PageBlock[];
};

type LetterLayoutMetrics = {
    sheetWidthMm: number;
    sheetHeightMm: number;
    leftMarginMm: number;
    rightMarginMm: number;
    contentTopMarginMm: number;
    contentBottomMarginMm: number;
    headerTopMarginMm: number;
    headerBottomSpacingMm: number;
    footerTopSpacingMm: number;
    footerLeftMarginMm: number;
    footerRightMarginMm: number;
    footerBottomMarginMm: number;
    headerHeightMm: number;
    footerHeightMm: number;
    headerSlotHeightMm: number;
    footerSlotHeightMm: number;
    pageWidthStyle: string;
    pageMinHeightStyle: string;
    pageContentWidthPx: number;
    pageContentHeightPx: number;
    blockGapPx: number;
};

const LETTER_NYALA_FONT_FAMILY = "'LetterNyala', 'Nyala', serif";

export const defaultPreviewData: PreviewData = {
    date: '2026-04-28',
    reference_number: 'LDMS/2026/0001',
    recipient_name: 'Aster Tadesse',
    recipient_title: 'Director',
    recipient_organization: 'Public Service Office',
    subject: 'Official communication',
    sender_name: 'Meseret Kebede',
    sender_title: 'Head, Legal Affairs',
    department_name: 'Legal Affairs Department',
    organization_name: 'Institution Name',
    signature_name: 'Meseret Kebede',
    signature_title: 'Head, Legal Affairs',
};

export function previewDocumentLabels(language: string | null | undefined): LetterLabels {
    return language === 'am' ? previewLabelSets.am : previewLabelSets.en;
}

export function mergeTemplatePlaceholders(value: string | null | undefined, previewData: PreviewData) {
    let output = value ?? '';

    for (const [key, replacement] of Object.entries(previewData)) {
        output = output.replaceAll(`{${key}}`, replacement);
    }

    return output.trim();
}

export function appendPlaceholder(currentValue: string, token: string) {
    if (currentValue.trim() === '') {
        return token;
    }

    return `${currentValue}${currentValue.endsWith(' ') || currentValue.endsWith('\n') ? '' : ' '}${token}`;
}

export function buildReferencePreview({
    referencePrefix,
    currentReferenceNumber,
    referenceStartNumber,
    numberingConfig,
}: {
    referencePrefix?: string | null;
    currentReferenceNumber?: number | null;
    referenceStartNumber?: number | null;
    numberingConfig?: NumberingConfig | null;
}) {
    const separator = numberingConfig?.separator?.trim() || '/';
    const includeYear = numberingConfig?.include_year ?? true;
    const padLength = Math.max(1, numberingConfig?.pad_length ?? 4);
    const sequence = Math.max((currentReferenceNumber ?? 0) + 1, referenceStartNumber ?? 1);
    const number = String(sequence).padStart(padLength, '0');
    const prefix = referencePrefix?.trim();

    return [prefix, includeYear ? String(new Date().getFullYear()) : null, number].filter(Boolean).join(separator);
}

export function buildTemplateRenderable(templateItem: LetterTemplateItem, previewData: PreviewData): LetterRenderable {
    return {
        page_size: templateItem.page_size,
        orientation: templateItem.orientation,
        language: templateItem.language,
        layout_config: templateItem.layout_config,
        reference_label: templateItem.reference_label,
        reference_number: previewData.reference_number || templateItem.next_reference_number_preview || '',
        date: previewData.date,
        recipient_block: mergeTemplatePlaceholders(templateItem.recipient_block_template, previewData),
        subject: mergeTemplatePlaceholders(templateItem.subject_template, previewData),
        salutation: mergeTemplatePlaceholders(templateItem.salutation_template, previewData),
        body_content: mergeTemplatePlaceholders(templateItem.body_content, previewData),
        closing_content: mergeTemplatePlaceholders(templateItem.closing_content, previewData),
        signature_block_content: mergeTemplatePlaceholders(templateItem.signature_block_content, previewData),
        signer_full_name: previewData.signature_name || '',
        signer_title: previewData.signature_title || '',
        cc_content: mergeTemplatePlaceholders(templateItem.cc_content, previewData),
        enclosure_content: mergeTemplatePlaceholders(templateItem.enclosure_content, previewData),
        header_image_url: templateItem.header_image_url,
        footer_image_url: templateItem.footer_image_url,
    };
}

export function buildLetterRenderable(letterItem: LetterItem): LetterRenderable {
    const recipientLines = [
        letterItem.recipient_name,
        letterItem.recipient_title,
        letterItem.recipient_organization,
        letterItem.recipient_address,
    ].filter((value): value is string => Boolean(value && value.trim()));

    return {
        page_size: letterItem.page_size,
        orientation: letterItem.orientation,
        language: letterItem.language,
        layout_config: letterItem.layout_config,
        reference_number: letterItem.reference_number,
        date: letterItem.letter_date,
        recipient_block: recipientLines.join('\n'),
        subject: letterItem.subject,
        salutation: letterItem.salutation,
        body_content: letterItem.body_content,
        closing_content: letterItem.closing_content,
        signature_block_content: letterItem.signature_block_content,
        signature_image_url: letterItem.signature_image_url,
        signer_full_name: letterItem.signer_full_name,
        signer_title: letterItem.signer_title,
        cc_content: letterItem.cc_content,
        enclosure_content: letterItem.enclosure_content,
        header_image_url: letterItem.header_image_url,
        footer_image_url: letterItem.footer_image_url,
    };
}

function contentIsHtml(value: string) {
    return /<\/?[a-z][\s\S]*>/i.test(value);
}

function escapeHtml(value: string) {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function splitPlainTextParagraphs(value: string) {
    return value
        .replace(/\r\n/g, '\n')
        .split(/\n{2,}/)
        .map((item) => item.trim())
        .filter((item) => item !== '');
}

function splitRichTextBlocks(value: string) {
    const trimmed = value.trim();

    if (trimmed === '') {
        return [];
    }

    if (! contentIsHtml(trimmed) || typeof window === 'undefined') {
        return [trimmed];
    }

    const parser = new window.DOMParser();
    const parsed = parser.parseFromString(`<div>${trimmed}</div>`, 'text/html');
    const wrapper = parsed.body.firstElementChild;

    if (! wrapper) {
        return [trimmed];
    }

    const blocks = Array.from(wrapper.childNodes)
        .map((node) => {
            if (node.nodeType === Node.TEXT_NODE) {
                const text = node.textContent?.trim() ?? '';

                return text === '' ? null : `<p>${escapeHtml(text)}</p>`;
            }

            if (node.nodeType === Node.ELEMENT_NODE) {
                return (node as HTMLElement).outerHTML.trim();
            }

            return null;
        })
        .filter((item): item is string => Boolean(item && item.trim() !== ''));

    return blocks.length > 0 ? blocks : [trimmed];
}

function parseBulletListItems(value: string) {
    return value
        .replace(/\r\n/g, '\n')
        .replace(/^(cc|copy to)\s*:\s*/i, '')
        .split(/\n+|;\s*/)
        .map((item) => item.replace(/^[\u2022*-]\s*/, '').trim())
        .filter((item) => item !== '');
}

function containsEthiopicText(value?: string | null) {
    return typeof value === 'string' && /[\u1200-\u137F\u1380-\u139F\u2D80-\u2DDF\uAB00-\uAB2F]/u.test(value);
}

function usesNyalaTypography(renderable: LetterRenderable) {
    if (renderable.language === 'am') {
        return true;
    }

    return [
        renderable.reference_label,
        renderable.reference_number,
        renderable.recipient_block,
        renderable.subject,
        renderable.salutation,
        renderable.body_content,
        renderable.closing_content,
        renderable.signature_block_content,
        renderable.signer_full_name,
        renderable.signer_title,
        renderable.cc_content,
        renderable.enclosure_content,
    ].some((value) => containsEthiopicText(value ?? null));
}

function ContentBlock({
    value,
    className,
    allowFontSize = false,
}: {
    value: string;
    className?: string;
    allowFontSize?: boolean;
}) {
    if (value.trim() === '') {
        return null;
    }

    if (contentIsHtml(value)) {
        return <div className={className} dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(value, { allowFontSize }) }} />;
    }

    return <div className={className ?? 'whitespace-pre-line'}>{value}</div>;
}

function BulletListBlock({ value, useNyala }: { value: string; useNyala: boolean }) {
    if (value.trim() === '') {
        return null;
    }

    if (contentIsHtml(value)) {
        return (
            <div
                className="prose prose-slate max-w-none text-sm leading-7"
                style={useNyala ? { fontFamily: LETTER_NYALA_FONT_FAMILY } : undefined}
                dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(value, { allowFontSize: true }) }}
            />
        );
    }

    const items = parseBulletListItems(value);

    if (items.length === 0) {
        return null;
    }

    return (
        <ul className="list-disc space-y-2 pl-6 text-sm leading-7 marker:text-slate-500">
            {items.map((item, index) => (
                <li key={`${item}-${index}`}>{item}</li>
            ))}
        </ul>
    );
}

function Section({ title, children, useNyala = false }: { title?: string; children?: ReactNode; useNyala?: boolean }) {
    if (! children) {
        return null;
    }

    return (
        <section className="space-y-2">
            {title ? (
                <h3
                    className={useNyala ? 'text-[11px] font-semibold text-slate-500' : 'text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500'}
                    style={useNyala ? { fontFamily: LETTER_NYALA_FONT_FAMILY } : undefined}
                >
                    {title}
                </h3>
            ) : null}
            {children}
        </section>
    );
}

function normalizeLayoutConfig(config?: LayoutConfig | null) {
    return {
        leftMarginMm: config?.margin_left_mm ?? 18,
        rightMarginMm: config?.margin_right_mm ?? 18,
        contentTopMarginMm: config?.content_top_margin_mm ?? config?.margin_top_mm ?? 20,
        contentBottomMarginMm: config?.content_bottom_margin_mm ?? config?.margin_bottom_mm ?? 20,
        headerTopMarginMm: config?.header_top_margin_mm ?? 0,
        headerBottomSpacingMm: config?.header_bottom_spacing_mm ?? 4,
        footerTopSpacingMm: config?.footer_top_spacing_mm ?? 4,
        footerLeftMarginMm: config?.footer_left_margin_mm ?? config?.margin_left_mm ?? 18,
        footerRightMarginMm: config?.footer_right_margin_mm ?? config?.margin_right_mm ?? 18,
        footerBottomMarginMm: config?.footer_bottom_margin_mm ?? 0,
    };
}

function buildLayoutMetrics(renderable: LetterRenderable): LetterLayoutMetrics {
    const sheetWidthMm = renderable.orientation === 'landscape' ? 297 : 210;
    const sheetHeightMm = renderable.orientation === 'landscape' ? 210 : 297;
    const normalized = normalizeLayoutConfig(renderable.layout_config);
    const hasHeader = Boolean(renderable.header_image_url);
    const hasFooter = Boolean(renderable.footer_image_url);
    const headerTopMarginMm = hasHeader ? normalized.headerTopMarginMm : 0;
    const headerBottomSpacingMm = hasHeader ? normalized.headerBottomSpacingMm : 0;
    const footerTopSpacingMm = hasFooter ? normalized.footerTopSpacingMm : 0;
    const footerLeftMarginMm = hasFooter ? normalized.footerLeftMarginMm : normalized.leftMarginMm;
    const footerRightMarginMm = hasFooter ? normalized.footerRightMarginMm : normalized.rightMarginMm;
    const footerBottomMarginMm = hasFooter ? normalized.footerBottomMarginMm : 0;
    const headerHeightMm = renderable.header_image_url ? 30 : 0;
    const footerHeightMm = renderable.footer_image_url ? 22 : 0;
    const headerSlotHeightMm = headerTopMarginMm + headerHeightMm + headerBottomSpacingMm;
    const footerSlotHeightMm = footerTopSpacingMm + footerHeightMm + footerBottomMarginMm;
    const pageContentWidthPx = mmToPx(sheetWidthMm - normalized.leftMarginMm - normalized.rightMarginMm);
    const pageContentHeightPx = mmToPx(
        sheetHeightMm
            - headerSlotHeightMm
            - footerSlotHeightMm
            - normalized.contentTopMarginMm
            - normalized.contentBottomMarginMm,
    );

    return {
        sheetWidthMm,
        sheetHeightMm,
        leftMarginMm: normalized.leftMarginMm,
        rightMarginMm: normalized.rightMarginMm,
        contentTopMarginMm: normalized.contentTopMarginMm,
        contentBottomMarginMm: normalized.contentBottomMarginMm,
        headerTopMarginMm,
        headerBottomSpacingMm,
        footerTopSpacingMm,
        footerLeftMarginMm,
        footerRightMarginMm,
        footerBottomMarginMm,
        headerHeightMm,
        footerHeightMm,
        headerSlotHeightMm,
        footerSlotHeightMm,
        pageWidthStyle: `${sheetWidthMm}mm`,
        pageMinHeightStyle: `${sheetHeightMm}mm`,
        pageContentWidthPx,
        pageContentHeightPx,
        blockGapPx: 24,
    };
}

function mmToPx(value: number) {
    return (value * 96) / 25.4;
}

function buildRegularBlocks(renderable: LetterRenderable, labels: LetterLabels): PageBlock[] {
    const blocks: PageBlock[] = [];

    if (renderable.recipient_block?.trim()) {
        blocks.push({
            key: 'recipient-block',
            kind: 'recipient',
            value: renderable.recipient_block,
        });
    }

    if (renderable.subject?.trim()) {
        blocks.push({
            key: 'subject-block',
            kind: 'subject',
            value: renderable.subject,
            label: labels.subject,
        });
    }

    if (renderable.salutation?.trim()) {
        blocks.push({
            key: 'salutation-block',
            kind: 'salutation',
            value: renderable.salutation,
        });
    }

    const bodySegments = renderable.body_content
        ? (contentIsHtml(renderable.body_content) ? splitRichTextBlocks(renderable.body_content) : splitPlainTextParagraphs(renderable.body_content))
        : [];

    bodySegments.forEach((segment, index) => {
        blocks.push({
            key: `body-block-${index}`,
            kind: 'body',
            value: segment,
        });
    });

    if (renderable.closing_content?.trim()) {
        blocks.push({
            key: 'closing-block',
            kind: 'closing',
            value: renderable.closing_content,
        });
    }

    return blocks;
}

function buildLastPageBlocks(renderable: LetterRenderable, labels: LetterLabels): PageBlock[] {
    const blocks: PageBlock[] = [];

    if (
        renderable.signature_image_url
        || renderable.signer_full_name?.trim()
        || renderable.signer_title?.trim()
        || renderable.signature_block_content?.trim()
    ) {
        blocks.push({
            key: 'signature-block',
            kind: 'signature',
            value: renderable.signature_block_content,
            signerName: renderable.signer_full_name,
            signerTitle: renderable.signer_title,
            signatureImageUrl: renderable.signature_image_url,
        });
    }

    if (renderable.cc_content?.trim()) {
        blocks.push({
            key: 'cc-block',
            kind: 'cc',
            value: renderable.cc_content,
            title: labels.cc,
        });
    }

    if (renderable.enclosure_content?.trim()) {
        blocks.push({
            key: 'enclosure-block',
            kind: 'enclosure',
            value: renderable.enclosure_content,
            title: labels.enclosure,
        });
    }

    return blocks;
}

function measureReferenceHeight(labels: LetterLabels, renderable: LetterRenderable, widthPx: number, useNyala: boolean) {
    if ((! renderable.reference_number || renderable.reference_number.trim() === '') && (! renderable.date || renderable.date.trim() === '')) {
        return 0;
    }

    return measureHtmlHeight(
        `
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;font-size:14px;line-height:1.5;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : ''}">
            <div>
                <p style="margin:0;font-weight:600;color:#334155;">${escapeHtml(renderable.reference_label?.trim() || labels.reference)}</p>
                <p style="margin:4px 0 0;color:#0f172a;">${escapeHtml(renderable.reference_number || '-')}</p>
            </div>
            <div style="text-align:right;">
                <p style="margin:0;font-weight:600;color:#334155;">${escapeHtml(labels.date)}</p>
                <p style="margin:4px 0 0;color:#0f172a;">${escapeHtml(renderable.date || '-')}</p>
            </div>
        </div>
        `,
        widthPx,
    );
}

function measureHtmlHeight(html: string, widthPx: number) {
    if (typeof window === 'undefined') {
        return 0;
    }

    const host = window.document.createElement('div');
    host.style.position = 'absolute';
    host.style.left = '-10000px';
    host.style.top = '0';
    host.style.width = `${widthPx}px`;
    host.style.visibility = 'hidden';
    host.style.pointerEvents = 'none';
    host.style.background = '#ffffff';
    host.innerHTML = html;
    window.document.body.appendChild(host);
    const height = host.getBoundingClientRect().height;
    host.remove();

    return Math.ceil(height);
}

function measureBlockHeight(block: PageBlock, widthPx: number, useNyala: boolean) {
    switch (block.kind) {
        case 'recipient':
        case 'salutation':
        case 'closing':
            return measureHtmlHeight(
                `<section><div style="white-space:pre-line;font-size:14px;line-height:28px;color:#0f172a;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : ''}">${escapeHtml(block.value)}</div></section>`,
                widthPx,
            );
        case 'subject':
            return measureHtmlHeight(
                `
                <section>
                    <div style="border-top:1px solid #cbd5e1;border-bottom:1px solid #cbd5e1;padding:12px 0;text-align:center;">
                        <p style="margin:0;font-size:14px;font-weight:600;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : 'letter-spacing:0.12em;text-transform:uppercase;'}color:#64748b;">${escapeHtml(block.label)}</p>
                        <p style="margin:8px 0 0;font-size:16px;font-weight:600;color:#020617;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : ''}">${escapeHtml(block.value)}</p>
                    </div>
                </section>
                `,
                widthPx,
            );
        case 'body':
            return measureHtmlHeight(
                `<section><div style="font-size:14px;line-height:28px;color:#0f172a;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : ''}">${contentIsHtml(block.value) ? sanitizeRichTextHtml(block.value, { allowFontSize: true }) : escapeHtml(block.value)}</div></section>`,
                widthPx,
            );
        case 'signature':
            return measureHtmlHeight(
                `
                <section>
                    <div style="margin-left:auto;display:flex;max-width:240px;flex-direction:column;align-items:flex-end;gap:16px;padding-top:24px;text-align:right;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : ''}">
                        ${block.signatureImageUrl ? '<div style="height:88px;width:140px;"></div>' : ''}
                        ${(block.signerName?.trim() || block.signerTitle?.trim()) ? `
                            <div style="text-align:right;">
                                ${block.signerName?.trim() ? `<p style="margin:0;font-size:14px;font-weight:600;color:#020617;">${escapeHtml(block.signerName)}</p>` : ''}
                                ${block.signerTitle?.trim() ? `<p style="margin:4px 0 0;font-size:14px;color:#334155;">${escapeHtml(block.signerTitle)}</p>` : ''}
                            </div>
                        ` : ''}
                        ${block.value?.trim() ? `<div style="white-space:pre-line;font-size:14px;line-height:28px;color:#0f172a;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : ''}">${escapeHtml(block.value)}</div>` : ''}
                    </div>
                </section>
                `,
                widthPx,
            );
        case 'cc': {
            const items = parseBulletListItems(block.value);

            return measureHtmlHeight(
                `
                <section>
                    <h3 style="margin:0 0 8px;font-size:11px;font-weight:600;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : 'letter-spacing:0.18em;text-transform:uppercase;'}color:#64748b;">${escapeHtml(block.title)}</h3>
                    <ul style="margin:0;padding-left:24px;font-size:14px;line-height:28px;color:#0f172a;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : ''}">
                        ${items.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}
                    </ul>
                </section>
                `,
                widthPx,
            );
        }
        case 'enclosure':
            return measureHtmlHeight(
                `
                <section>
                    <h3 style="margin:0 0 8px;font-size:11px;font-weight:600;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : 'letter-spacing:0.18em;text-transform:uppercase;'}color:#64748b;">${escapeHtml(block.title)}</h3>
                    <div style="white-space:pre-line;font-size:14px;line-height:28px;color:#0f172a;${useNyala ? `font-family:${LETTER_NYALA_FONT_FAMILY};` : ''}">${contentIsHtml(block.value) ? sanitizeRichTextHtml(block.value, { allowFontSize: true }) : escapeHtml(block.value)}</div>
                </section>
                `,
                widthPx,
            );
    }
}

function blockHeights(blocks: PageBlock[], widthPx: number, gapPx: number, useNyala: boolean) {
    return blocks.map((block, index) => ({
        block,
        height: measureBlockHeight(block, widthPx, useNyala) + (index > 0 ? gapPx : 0),
    }));
}

function buildGreedyPages(blocks: PageBlock[], capacityPx: number, firstPageCapacityPx: number, widthPx: number, gapPx: number, useNyala: boolean) {
    const pages: LetterPage[] = [{ blocks: [] }];
    const heights = blockHeights(blocks, widthPx, gapPx, useNyala);
    let used = 0;
    let currentCapacity = firstPageCapacityPx;

    heights.forEach(({ block, height }) => {
        if (pages[pages.length - 1].blocks.length > 0 && used + height > currentCapacity) {
            pages.push({ blocks: [] });
            used = 0;
            currentCapacity = capacityPx;
        }

        pages[pages.length - 1].blocks.push(block);
        used += height;
    });

    return pages;
}

function computeUsedHeight(blocks: PageBlock[], widthPx: number, gapPx: number, useNyala: boolean) {
    const measured = blockHeights(blocks, widthPx, gapPx, useNyala);

    return measured.reduce((sum, item) => sum + item.height, 0);
}

function paginateRenderable(renderable: LetterRenderable, labels: LetterLabels) {
    const metrics = buildLayoutMetrics(renderable);
    const useNyala = usesNyalaTypography(renderable);
    const regularBlocks = buildRegularBlocks(renderable, labels);
    const lastPageBlocks = buildLastPageBlocks(renderable, labels);
    const firstPageTopBlockHeightPx = measureReferenceHeight(labels, renderable, metrics.pageContentWidthPx, useNyala);
    const firstPageCapacityPx = Math.max(
        metrics.pageContentHeightPx - firstPageTopBlockHeightPx - (firstPageTopBlockHeightPx > 0 ? metrics.blockGapPx : 0),
        120,
    );
    const normalCapacityPx = Math.max(metrics.pageContentHeightPx, 120);

    let pages = buildGreedyPages(regularBlocks, normalCapacityPx, firstPageCapacityPx, metrics.pageContentWidthPx, metrics.blockGapPx, useNyala);

    if (pages.length === 0) {
        pages = [{ blocks: [] }];
    }

    if (lastPageBlocks.length > 0) {
        const lastPageOnlyHeight = computeUsedHeight(lastPageBlocks, metrics.pageContentWidthPx, metrics.blockGapPx, useNyala);
        const lastPage = pages[pages.length - 1];
        const lastPageCapacityPx = pages.length === 1 ? firstPageCapacityPx : normalCapacityPx;
        const lastPageRegularHeight = computeUsedHeight(lastPage.blocks, metrics.pageContentWidthPx, metrics.blockGapPx, useNyala);
        const needsLeadingGap = lastPage.blocks.length > 0;
        const reserveHeight = lastPageOnlyHeight + (needsLeadingGap ? metrics.blockGapPx : 0);
        const finalPageMaxRegularHeight = Math.max(lastPageCapacityPx - reserveHeight, 0);

        if (lastPageRegularHeight > finalPageMaxRegularHeight) {
            const overflowBlocks: PageBlock[] = [];

            while (
                lastPage.blocks.length > 0
                && computeUsedHeight(lastPage.blocks, metrics.pageContentWidthPx, metrics.blockGapPx, useNyala) > finalPageMaxRegularHeight
            ) {
                const moved = lastPage.blocks.pop();

                if (moved) {
                    overflowBlocks.unshift(moved);
                }
            }

            if (overflowBlocks.length > 0) {
                const redistributedPages = buildGreedyPages(
                    overflowBlocks,
                    normalCapacityPx,
                    normalCapacityPx,
                    metrics.pageContentWidthPx,
                    metrics.blockGapPx,
                    useNyala,
                );

                pages = [...pages.slice(0, -1), ...redistributedPages, lastPage];
            }
        }

        if (computeUsedHeight(pages[pages.length - 1].blocks, metrics.pageContentWidthPx, metrics.blockGapPx, useNyala) > Math.max(lastPageCapacityPx - reserveHeight, 0)) {
            pages.push({ blocks: [] });
        }
    }

    return {
        metrics,
        pages,
        lastPageBlocks,
        useNyala,
    };
}

function RenderBlock({ block, useNyala }: { block: PageBlock; useNyala: boolean }) {
    switch (block.kind) {
        case 'recipient':
            return (
                <Section useNyala={useNyala}>
                    <ContentBlock value={block.value} className="whitespace-pre-line text-sm leading-7" />
                </Section>
            );
        case 'subject':
            return (
                <Section useNyala={useNyala}>
                    <div className="border-y border-slate-300 py-3 text-center">
                        <p
                            className={useNyala ? 'text-sm font-semibold text-slate-500' : 'text-sm font-semibold uppercase tracking-[0.12em] text-slate-500'}
                            style={useNyala ? { fontFamily: LETTER_NYALA_FONT_FAMILY } : undefined}
                        >
                            {block.label}
                        </p>
                        <p className="mt-2 text-base font-semibold text-slate-950" style={useNyala ? { fontFamily: LETTER_NYALA_FONT_FAMILY } : undefined}>{block.value}</p>
                    </div>
                </Section>
            );
        case 'salutation':
            return (
                <Section useNyala={useNyala}>
                    <ContentBlock value={block.value} className="whitespace-pre-line text-sm leading-7" />
                </Section>
            );
        case 'body':
            return (
                <Section useNyala={useNyala}>
                    <ContentBlock value={block.value} className="prose prose-slate max-w-none text-sm leading-7" allowFontSize />
                </Section>
            );
        case 'closing':
            return (
                <Section useNyala={useNyala}>
                    <ContentBlock value={block.value} className="whitespace-pre-line text-sm leading-7" />
                </Section>
            );
        case 'signature':
            return (
                <Section useNyala={useNyala}>
                    <div className="ml-auto flex max-w-[240px] flex-col items-end space-y-4 pt-6 text-right break-inside-avoid">
                        {block.signatureImageUrl ? (
                            <img src={block.signatureImageUrl} alt="" className="block max-h-[88px] max-w-full w-auto object-contain object-right" />
                        ) : null}
                        {block.signerName?.trim() ? (
                            <div className="space-y-1 text-right">
                                <p className="text-sm font-semibold text-slate-950" style={useNyala ? { fontFamily: LETTER_NYALA_FONT_FAMILY } : undefined}>{block.signerName}</p>
                                {block.signerTitle?.trim() ? (
                                    <p className="text-sm text-slate-700" style={useNyala ? { fontFamily: LETTER_NYALA_FONT_FAMILY } : undefined}>{block.signerTitle}</p>
                                ) : null}
                            </div>
                        ) : null}
                        {block.value?.trim() ? (
                            <ContentBlock value={block.value} className="whitespace-pre-line text-right text-sm leading-7" />
                        ) : null}
                    </div>
                </Section>
            );
        case 'cc':
            return (
                <Section title={block.title} useNyala={useNyala}>
                    <BulletListBlock value={block.value} useNyala={useNyala} />
                </Section>
            );
        case 'enclosure':
            return (
                <Section title={block.title} useNyala={useNyala}>
                    <ContentBlock value={block.value} className="whitespace-pre-line text-sm leading-7" allowFontSize />
                </Section>
            );
    }
}

export function LetterSheet({
    document: renderable,
    labels,
}: {
    document: LetterRenderable;
    labels: LetterLabels;
}) {
    const signature = useMemo(() => JSON.stringify({ renderable, labels }), [labels, renderable]);
    const [pageState, setPageState] = useState(() => paginateRenderable(renderable, labels));

    useEffect(() => {
        setPageState(paginateRenderable(renderable, labels));
    }, [signature]);

    const { metrics, pages, lastPageBlocks, useNyala } = pageState;
    const referenceLabel = renderable.reference_label?.trim() || labels.reference;

    return (
        <div className="overflow-x-auto">
            <div className="mx-auto flex w-fit flex-col gap-4 print:gap-0">
                {pages.map((page, index) => {
                    const isFirstPage = index === 0;
                    const isLastPage = index === pages.length - 1;

                    return (
                        <div
                            key={`letter-page-${index}`}
                            data-letter-page={index + 1}
                            className="mx-auto bg-white text-slate-900 shadow-sm ring-1 ring-slate-200 print:shadow-none print:ring-0"
                            data-letter-font={useNyala ? 'nyala' : 'default'}
                            data-letter-language={renderable.language}
                            style={{
                                width: metrics.pageWidthStyle,
                                height: metrics.pageMinHeightStyle,
                                minHeight: metrics.pageMinHeightStyle,
                                pageBreakAfter: index === pages.length - 1 ? 'auto' : 'always',
                                breakAfter: index === pages.length - 1 ? 'auto' : 'page',
                                fontFamily: useNyala ? LETTER_NYALA_FONT_FAMILY : undefined,
                            }}
                        >
                            <div
                                className="grid h-full"
                                style={{
                                    height: metrics.pageMinHeightStyle,
                                    minHeight: metrics.pageMinHeightStyle,
                                    gridTemplateRows: `${metrics.headerSlotHeightMm}mm minmax(0, 1fr) ${metrics.footerSlotHeightMm}mm`,
                                    paddingLeft: `${metrics.leftMarginMm}mm`,
                                    paddingRight: `${metrics.rightMarginMm}mm`,
                                }}
                            >
                                <div
                                    className="grid border-b border-slate-200"
                                    data-letter-slot="header"
                                    style={{
                                        minHeight: `${metrics.headerSlotHeightMm}mm`,
                                        gridTemplateRows: `${metrics.headerTopMarginMm}mm ${metrics.headerHeightMm}mm ${metrics.headerBottomSpacingMm}mm`,
                                    }}
                                >
                                    <div aria-hidden="true" />
                                    <div className="flex items-start overflow-hidden">
                                        {renderable.header_image_url ? (
                                            <img src={renderable.header_image_url} alt="" className="block max-h-full w-full object-contain object-top" />
                                        ) : null}
                                    </div>
                                    <div aria-hidden="true" />
                                </div>

                                <div
                                    className="flex flex-col gap-6"
                                    style={{
                                        paddingTop: `${metrics.contentTopMarginMm}mm`,
                                        paddingBottom: `${metrics.contentBottomMarginMm}mm`,
                                    }}
                                >
                                    {isFirstPage ? (
                                        <div className="grid gap-4 text-sm md:grid-cols-2">
                                            <div>
                                                <p className="font-semibold text-slate-700" style={useNyala ? { fontFamily: LETTER_NYALA_FONT_FAMILY } : undefined}>{referenceLabel}</p>
                                                <p className="mt-1 text-slate-900">{renderable.reference_number || '-'}</p>
                                            </div>
                                            <div className="text-left md:text-right">
                                                <p className="font-semibold text-slate-700" style={useNyala ? { fontFamily: LETTER_NYALA_FONT_FAMILY } : undefined}>{labels.date}</p>
                                                <p className="mt-1 text-slate-900">{renderable.date || '-'}</p>
                                            </div>
                                        </div>
                                    ) : null}

                                    {page.blocks.map((block) => (
                                        <RenderBlock key={block.key} block={block} useNyala={useNyala} />
                                    ))}

                                    {isLastPage ? lastPageBlocks.map((block) => (
                                        <RenderBlock key={block.key} block={block} useNyala={useNyala} />
                                    )) : null}
                                </div>

                                <div
                                    className="grid border-t border-slate-200"
                                    data-letter-slot="footer"
                                    style={{
                                        minHeight: `${metrics.footerSlotHeightMm}mm`,
                                        gridTemplateRows: `${metrics.footerTopSpacingMm}mm ${metrics.footerHeightMm}mm ${metrics.footerBottomMarginMm}mm`,
                                        marginLeft: `-${metrics.leftMarginMm}mm`,
                                        marginRight: `-${metrics.rightMarginMm}mm`,
                                    }}
                                >
                                    <div aria-hidden="true" />
                                    <div
                                        className="flex items-end overflow-hidden"
                                        style={{
                                            paddingLeft: `${metrics.footerLeftMarginMm}mm`,
                                            paddingRight: `${metrics.footerRightMarginMm}mm`,
                                        }}
                                    >
                                        {renderable.footer_image_url ? (
                                            <img src={renderable.footer_image_url} alt="" className="block max-h-full w-full object-contain object-bottom" />
                                        ) : null}
                                    </div>
                                    <div aria-hidden="true" />
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
