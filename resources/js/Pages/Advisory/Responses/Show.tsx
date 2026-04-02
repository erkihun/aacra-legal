import EmptyState from '@/Components/Ui/EmptyState';
import BackButton from '@/Components/Ui/BackButton';
import FileAttachmentCard from '@/Components/Ui/FileAttachmentCard';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { sanitizeRichTextHtml } from '@/lib/sanitize-rich-text';
import { Head } from '@inertiajs/react';
import { useMemo } from 'react';

type AdvisoryResponseShowProps = {
    requestItem: {
        id: string;
        request_number: string;
        subject: string;
        requester?: { name?: string | null } | null;
        department?: { name_en?: string | null; name_am?: string | null } | null;
    };
    responseItem: {
        id: string;
        subject?: string | null;
        response?: string | null;
        responded_at?: string | null;
        actor?: string | null;
        attachments?: Array<any>;
    };
};

export default function AdvisoryResponseShow({ requestItem, responseItem }: AdvisoryResponseShowProps) {
    const { t, locale } = useI18n();
    const { formatDateTime } = useDateFormatter();

    const departmentName =
        locale === 'am'
            ? requestItem.department?.name_am ?? requestItem.department?.name_en
            : requestItem.department?.name_en;

    const sanitizedResponseHtml = useMemo(
        () => sanitizeRichTextHtml(responseItem.response),
        [responseItem.response],
    );

    const attachments = Array.isArray(responseItem.attachments) ? responseItem.attachments : [];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.advisory_requests'), href: route('advisory.index') },
                { label: requestItem.request_number, href: route('advisory.show', { advisoryRequest: requestItem.id }) },
                { label: t('advisory.response') },
            ]}
        >
            <Head title={`${t('advisory.response')} ${requestItem.request_number}`} />

            <PageContainer className="space-y-6">
                <SectionHeader
                    eyebrow={requestItem.request_number}
                    title={responseItem.subject ?? t('common.not_available')}
                    action={<BackButton fallbackHref={route('advisory.show', { advisoryRequest: requestItem.id })} />}
                />

                <SurfaceCard className="space-y-5 p-6">
                    <dl className="grid gap-x-8 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                        <ResponseMetaItem label={t('common.date')} value={responseItem.responded_at ? formatDateTime(responseItem.responded_at) : t('common.not_available')} />
                        <ResponseMetaItem label={t('advisory.request_code')} value={requestItem.request_number} />
                        <ResponseMetaItem label={t('advisory.requester')} value={requestItem.requester?.name ?? t('common.not_available')} />
                        <ResponseMetaItem label={t('advisory.department')} value={departmentName ?? t('common.not_available')} />
                        <ResponseMetaItem label={t('audit.actor')} value={responseItem.actor ?? t('common.not_available')} />
                    </dl>

                    <div className="border-t border-[color:var(--border)] pt-5">
                        <p className="text-sm font-semibold text-[color:var(--muted-strong)]">
                            {t('advisory.response')}
                        </p>
                        {sanitizedResponseHtml ? (
                            <div
                                className="prose prose-sm mt-4 max-w-none text-[color:var(--text)] dark:prose-invert"
                                dangerouslySetInnerHTML={{ __html: sanitizedResponseHtml }}
                            />
                        ) : (
                            <p className="mt-4 text-sm text-[color:var(--muted)]">
                                {t('common.not_available')}
                            </p>
                        )}
                    </div>
                </SurfaceCard>

                <SurfaceCard className="space-y-4 p-6">
                    <h2 className="text-lg font-semibold text-[color:var(--text)]">
                        {t('common.attachments')}
                    </h2>

                    {attachments.length === 0 ? (
                        <EmptyState
                            title={t('common.attachments')}
                            description={t('common.no_attachments')}
                        />
                    ) : (
                        <div className="space-y-3">
                            {attachments.map((attachment) => (
                                <FileAttachmentCard
                                    key={attachment.id}
                                    name={attachment.original_name}
                                    meta={formatAttachmentMeta(attachment, t, formatDateTime)}
                                    viewUrl={attachment.view_url}
                                    downloadUrl={attachment.download_url}
                                    canDelete={false}
                                />
                            ))}
                        </div>
                    )}
                </SurfaceCard>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function ResponseMetaItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-1.5 border-b border-[color:var(--border)] pb-4 last:border-b-0 last:pb-0">
            <dt className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                {label}
            </dt>
            <dd className="text-sm font-medium text-[color:var(--text)]">
                {value}
            </dd>
        </div>
    );
}

function formatAttachmentMeta(
    attachment: { mime_type?: string | null; size?: number | null; uploaded_by?: string | null; created_at?: string | null },
    t: (key: string) => string,
    formatDateTime: (value?: string | null, fallback?: string) => string,
) {
    const parts = [attachment.mime_type, formatBytes(attachment.size), attachment.uploaded_by];

    if (attachment.created_at) {
        parts.push(formatDateTime(attachment.created_at));
    }

    return parts.filter(Boolean).join(' | ') || t('common.not_available');
}

function formatBytes(value?: number | null) {
    if (!value) {
        return null;
    }

    if (value < 1024) {
        return `${value} B`;
    }

    if (value < 1024 * 1024) {
        return `${(value / 1024).toFixed(1)} KB`;
    }

    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}
