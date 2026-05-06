import CommentItem from '@/Components/Ui/CommentItem';
import EmptyState from '@/Components/Ui/EmptyState';
import BackButton from '@/Components/Ui/BackButton';
import FileAttachmentCard from '@/Components/Ui/FileAttachmentCard';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { finishSuccessfulSubmission } from '@/lib/form-submission';
import { useI18n } from '@/lib/i18n';
import { sanitizeRichTextHtml } from '@/lib/sanitize-rich-text';
import { Head, Link, useForm } from '@inertiajs/react';
import { type ReactNode, useMemo } from 'react';

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
        approval_status?: string | null;
        approved_at?: string | null;
        approver?: string | null;
        actor?: string | null;
        can_approve?: boolean;
        can_comment?: boolean;
        comments?: Array<{
            id: string;
            body: string;
            is_internal?: boolean;
            created_at?: string | null;
            user?: { name?: string | null } | null;
        }>;
        attachments?: Array<any>;
    };
};

export default function AdvisoryResponseShow({ requestItem, responseItem }: AdvisoryResponseShowProps) {
    const { t, locale } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const commentForm = useForm({
        body: '',
    });

    const departmentName =
        locale === 'am'
            ? requestItem.department?.name_am ?? requestItem.department?.name_en
            : requestItem.department?.name_en;

    const sanitizedResponseHtml = useMemo(
        () => sanitizeRichTextHtml(responseItem.response),
        [responseItem.response],
    );

    const attachments = Array.isArray(responseItem.attachments) ? responseItem.attachments : [];
    const comments = Array.isArray(responseItem.comments) ? responseItem.comments : [];

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
                    action={
                        <div className="flex flex-wrap gap-3">
                            {responseItem.can_approve ? (
                                <Link
                                    href={route('advisory.responses.approve', {
                                        advisoryRequest: requestItem.id,
                                        advisoryResponse: responseItem.id,
                                    })}
                                    method="patch"
                                    as="button"
                                    className="btn-base btn-primary focus-ring"
                                >
                                    {t('advisory.approve_response')}
                                </Link>
                            ) : null}
                            <BackButton fallbackHref={route('advisory.show', { advisoryRequest: requestItem.id })} />
                        </div>
                    }
                />

                <SurfaceCard className="space-y-5 p-6">
                    <dl className="grid gap-x-8 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                        <ResponseMetaItem label={t('common.date')} value={responseItem.responded_at ? formatDateTime(responseItem.responded_at) : t('common.not_available')} />
                        <ResponseMetaItem label={t('advisory.request_code')} value={requestItem.request_number} />
                        <ResponseMetaItem label={t('advisory.requester')} value={requestItem.requester?.name ?? t('common.not_available')} />
                        <ResponseMetaItem label={t('advisory.department')} value={departmentName ?? t('common.not_available')} />
                        <ResponseMetaItem label={t('audit.actor')} value={responseItem.actor ?? t('common.not_available')} />
                        <ResponseMetaItem label={t('advisory.response_approval_status')} valueNode={<StatusBadge value={responseItem.approval_status ?? 'pending'} />} />
                        <ResponseMetaItem label={t('advisory.response_approved_by')} value={responseItem.approver ?? t('common.not_available')} />
                        <ResponseMetaItem label={t('advisory.response_approved_at')} value={responseItem.approved_at ? formatDateTime(responseItem.approved_at) : t('common.not_available')} />
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

                <SurfaceCard className="space-y-5 p-6">
                    <h2 className="text-lg font-semibold text-[color:var(--text)]">
                        {t('advisory.response_comments')}
                    </h2>

                    {responseItem.can_comment ? (
                        <FormField label={t('common.add_comment')} required error={commentForm.errors.body}>
                            <div className="space-y-3">
                                <textarea
                                    value={commentForm.data.body}
                                    onChange={(event) => commentForm.setData('body', event.target.value)}
                                    rows={4}
                                    className="textarea-ui"
                                />
                                <div className="flex justify-end">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            commentForm.post(route('advisory.responses.comments.store', {
                                                advisoryRequest: requestItem.id,
                                                advisoryResponse: responseItem.id,
                                            }), {
                                                onSuccess: () => {
                                                    finishSuccessfulSubmission(commentForm, {
                                                        reset: ['body'],
                                                    });
                                                },
                                            })
                                        }
                                        className="btn-base btn-primary focus-ring"
                                        disabled={commentForm.processing}
                                    >
                                        {t('common.add_comment')}
                                    </button>
                                </div>
                            </div>
                        </FormField>
                    ) : null}

                    {comments.length === 0 ? (
                        <EmptyState
                            title={t('advisory.response_comments')}
                            description={t('common.no_comments')}
                        />
                    ) : (
                        <div className="space-y-3">
                            {comments.map((comment) => (
                                <CommentItem
                                    key={comment.id}
                                    author={comment.user?.name}
                                    body={comment.body}
                                    date={comment.created_at ? formatDateTime(comment.created_at) : null}
                                />
                            ))}
                        </div>
                    )}
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

function ResponseMetaItem({
    label,
    value,
    valueNode,
}: {
    label: string;
    value?: string;
    valueNode?: ReactNode;
}) {
    return (
        <div className="space-y-1.5 border-b border-[color:var(--border)] pb-4 last:border-b-0 last:pb-0">
            <dt className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                {label}
            </dt>
            <dd className="text-sm font-medium text-[color:var(--text)]">
                {valueNode ?? value}
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
