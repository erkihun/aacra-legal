import { sanitizeRichTextHtml } from '@/lib/sanitize-rich-text';
import { ReactNode } from 'react';

export type LayoutConfig = {
    margin_top_mm?: number | null;
    margin_right_mm?: number | null;
    margin_bottom_mm?: number | null;
    margin_left_mm?: number | null;
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

function ContentBlock({ value, className }: { value: string; className?: string }) {
    if (value.trim() === '') {
        return null;
    }

    if (contentIsHtml(value)) {
        return <div className={className} dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(value) }} />;
    }

    return <div className={className ?? 'whitespace-pre-line'}>{value}</div>;
}

function parseBulletListItems(value: string) {
    return value
        .replace(/\r\n/g, '\n')
        .replace(/^(cc|copy to)\s*:\s*/i, '')
        .split(/\n+|;\s*/)
        .map((item) => item.replace(/^[\u2022*-]\s*/, '').trim())
        .filter((item) => item !== '');
}

function BulletListBlock({ value }: { value: string }) {
    if (value.trim() === '') {
        return null;
    }

    if (contentIsHtml(value)) {
        return <div className="prose prose-slate max-w-none text-sm leading-7" dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(value) }} />;
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

function Section({ title, children }: { title?: string; children?: ReactNode }) {
    if (!children) {
        return null;
    }

    return (
        <section className="space-y-2">
            {title ? <h3 className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">{title}</h3> : null}
            {children}
        </section>
    );
}

export function LetterSheet({
    document,
    labels,
}: {
    document: LetterRenderable;
    labels: {
        subject: string;
        cc: string;
        enclosure: string;
        reference: string;
        date: string;
    };
}) {
    const config = document.layout_config ?? {};
    const top = config.margin_top_mm ?? 20;
    const right = config.margin_right_mm ?? 18;
    const bottom = config.margin_bottom_mm ?? 20;
    const left = config.margin_left_mm ?? 18;
    const referenceLabel = document.reference_label?.trim() || labels.reference;
    const sheetWidth = document.orientation === 'landscape' ? '297mm' : '210mm';
    const sheetMinHeight = document.orientation === 'landscape' ? '210mm' : '297mm';
    const headerZoneMm = document.header_image_url ? 34 : 0;
    const footerZoneMm = document.footer_image_url ? 26 : 0;

    return (
        <div className="overflow-x-auto">
            <div
                className="mx-auto bg-white text-slate-900 shadow-sm ring-1 ring-slate-200 print:shadow-none print:ring-0"
                style={{
                    width: sheetWidth,
                    minHeight: sheetMinHeight,
                    paddingTop: `${top}mm`,
                    paddingRight: `${right}mm`,
                    paddingBottom: `${bottom}mm`,
                    paddingLeft: `${left}mm`,
                }}
            >
                <div
                    className="grid min-h-full gap-6"
                    style={{
                        minHeight: `calc(${sheetMinHeight} - ${top + bottom}mm)`,
                        gridTemplateRows: `${headerZoneMm > 0 ? `${headerZoneMm}mm` : 'auto'} minmax(0, 1fr) ${footerZoneMm > 0 ? `${footerZoneMm}mm` : 'auto'}`,
                    }}
                >
                    {document.header_image_url ? (
                        <div className="flex items-start border-b border-slate-200 pb-4" data-letter-slot="header">
                            <img src={document.header_image_url} alt="" className="block h-full max-h-full w-full object-contain object-top" />
                        </div>
                    ) : (
                        <div data-letter-slot="header" />
                    )}

                    <div className="flex flex-col gap-6">
                        <div className="grid gap-4 text-sm md:grid-cols-2">
                            <div>
                                <p className="font-semibold text-slate-700">{referenceLabel}</p>
                                <p className="mt-1 text-slate-900">{document.reference_number || '-'}</p>
                            </div>
                            <div className="text-left md:text-right">
                                <p className="font-semibold text-slate-700">{labels.date}</p>
                                <p className="mt-1 text-slate-900">{document.date || '-'}</p>
                            </div>
                        </div>

                        <Section>
                            <ContentBlock value={document.recipient_block || ''} className="whitespace-pre-line text-sm leading-7" />
                        </Section>

                        <Section>
                            {document.subject?.trim() ? (
                                <div className="border-y border-slate-300 py-3 text-center">
                                    <p className="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">{labels.subject}</p>
                                    <p className="mt-2 text-base font-semibold text-slate-950">{document.subject}</p>
                                </div>
                            ) : null}
                        </Section>

                        <Section>
                            <ContentBlock value={document.salutation || ''} className="whitespace-pre-line text-sm leading-7" />
                        </Section>

                        <Section>
                            <ContentBlock value={document.body_content || ''} className="prose prose-slate max-w-none text-sm leading-7" />
                        </Section>

                        <Section>
                            <ContentBlock value={document.closing_content || ''} className="whitespace-pre-line text-sm leading-7" />
                        </Section>

                        <Section>
                            <div className="ml-auto flex max-w-[240px] flex-col items-end space-y-4 pt-6 text-right">
                                {document.signature_image_url ? (
                                    <img src={document.signature_image_url} alt="" className="block max-h-[88px] max-w-full w-auto object-contain object-right" />
                                ) : null}
                                {document.signer_full_name?.trim() ? (
                                    <div className="space-y-1 text-right">
                                        <p className="text-sm font-semibold text-slate-950">{document.signer_full_name}</p>
                                        {document.signer_title?.trim() ? (
                                            <p className="text-sm text-slate-700">{document.signer_title}</p>
                                        ) : null}
                                    </div>
                                ) : null}
                                <ContentBlock value={document.signature_block_content || ''} className="whitespace-pre-line text-right text-sm leading-7" />
                            </div>
                        </Section>

                        <Section title={labels.cc}>
                            <BulletListBlock value={document.cc_content || ''} />
                        </Section>

                        <Section title={labels.enclosure}>
                            <ContentBlock value={document.enclosure_content || ''} className="whitespace-pre-line text-sm leading-7" />
                        </Section>
                    </div>

                    {document.footer_image_url ? (
                        <div className="flex items-end border-t border-slate-200 pt-4" data-letter-slot="footer">
                            <img src={document.footer_image_url} alt="" className="block h-full max-h-full w-full object-contain object-bottom" />
                        </div>
                    ) : (
                        <div data-letter-slot="footer" />
                    )}
                </div>
            </div>
        </div>
    );
}
