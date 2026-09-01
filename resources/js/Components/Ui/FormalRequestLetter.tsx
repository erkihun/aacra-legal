import { useI18n } from '@/lib/i18n';
import { sanitizeRichTextHtml } from '@/lib/sanitize-rich-text';
import { type ReactNode } from 'react';

type FormalRequestLetterProps = {
    document: {
        name?: string | null;
        template_name?: string | null;
        language?: string | null;
        header_image_url?: string | null;
        footer_image_url?: string | null;
        subject_template?: string | null;
        recipient_block_template?: string | null;
        salutation_template?: string | null;
        body_content: string;
        closing_content?: string | null;
        signature_block_content?: string | null;
        reference_number?: string | null;
        subject?: string | null;
        date_submitted?: string | null;
        department_name?: string | null;
    };
    title?: string;
    children?: ReactNode;
};

function renderBlock(value?: string | null) {
    if (!value || value.trim() === '') {
        return null;
    }

    const sanitized = sanitizeRichTextHtml(value);
    const looksLikeHtml = /<\/?[a-z][\s\S]*>/i.test(value);

    if (!looksLikeHtml) {
        return <p className="whitespace-pre-wrap text-sm leading-7 text-[color:var(--text)]">{value}</p>;
    }

    return (
        <div
            className="prose prose-sm max-w-none text-[color:var(--text)] dark:prose-invert"
            dangerouslySetInnerHTML={{ __html: sanitized }}
        />
    );
}

export default function FormalRequestLetter({ document, title, children }: FormalRequestLetterProps) {
    const { t } = useI18n();
    const templateName = document.template_name ?? document.name ?? null;

    return (
        <div className="overflow-hidden rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)]">
            {title ? (
                <div className="border-b border-[color:var(--border)] px-5 py-3">
                    <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">{title}</p>
                    {templateName ? (
                        <p className="text-sm text-[color:var(--text)]">{templateName}</p>
                    ) : null}
                </div>
            ) : null}

            {document.header_image_url ? (
                <div className="border-b border-[color:var(--border)] px-5 py-4">
                    <img src={document.header_image_url} alt="header" className="mx-auto max-h-24 object-contain" />
                </div>
            ) : null}

            <div className="space-y-6 px-6 py-5">
                <div className="grid gap-4 text-sm text-[color:var(--text)] md:grid-cols-2">
                    <div>
                        <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                            {t('requester.department')}
                        </p>
                        <p className="mt-1">{document.department_name ?? t('common.not_available')}</p>
                    </div>
                    <div>
                        <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                            {t('requester.date_submitted')}
                        </p>
                        <p className="mt-1">{document.date_submitted ?? t('common.not_available')}</p>
                    </div>
                </div>

                {renderBlock(document.recipient_block_template)}

                {document.reference_number ? (
                    <div>
                        <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                            {t('letters.fields.reference_number')}
                        </p>
                        <p className="mt-1 text-sm font-medium text-[color:var(--text)]">
                            {document.reference_number}
                        </p>
                    </div>
                ) : null}

                {renderBlock(document.subject_template)}

                {document.subject ? (
                    <div>
                        <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                            {t('requester.subject')}
                        </p>
                        <p className="mt-1 text-base font-semibold text-[color:var(--text)]">{document.subject}</p>
                    </div>
                ) : null}

                {renderBlock(document.salutation_template)}

                {children ?? renderBlock(document.body_content)}

                {renderBlock(document.closing_content)}

                {renderBlock(document.signature_block_content)}
            </div>

            {document.footer_image_url ? (
                <div className="border-t border-[color:var(--border)] px-5 py-4">
                    <img src={document.footer_image_url} alt="footer" className="mx-auto max-h-20 object-contain" />
                </div>
            ) : null}
        </div>
    );
}
