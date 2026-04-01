import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import EmptyState from '@/Components/Ui/EmptyState';
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
import { type ReactNode, useMemo, useState } from 'react';

type ShowAdvisoryProps = {
    requestItem: any;
    teamLeaders: Array<{ id: string; name: string }>;
    experts: Array<{ id: string; name: string }>;
    workspace: {
        canAssignTeamLeader: boolean;
        canAssignExpert: boolean;
    };
    can: {
        review: boolean;
        assign: boolean;
        respond: boolean;
        attach: boolean;
        update: boolean;
    };
};

export default function AdvisoryShow({
    requestItem,
    teamLeaders,
    experts,
    workspace,
    can,
}: ShowAdvisoryProps) {
    const { t, locale } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [attachmentToDelete, setAttachmentToDelete] = useState<any | null>(null);
    const [responseToDelete, setResponseToDelete] = useState<any | null>(null);

    const attachments = Array.isArray(requestItem.attachments) ? requestItem.attachments : [];
    const responses = Array.isArray(requestItem.responses) ? requestItem.responses : [];

    const reviewForm = useForm({
        director_decision: 'approved',
        director_notes: '',
        assigned_team_leader_id: teamLeaders[0]?.id ?? '',
    });

    const assignForm = useForm({
        assigned_legal_expert_id: experts[0]?.id ?? '',
        notes: '',
    });

    const deleteAttachmentForm = useForm({});
    const deleteResponseForm = useForm({});

    const departmentName =
        locale === 'am'
            ? requestItem.department?.name_am ?? requestItem.department?.name_en
            : requestItem.department?.name_en;
    const categoryName =
        locale === 'am'
            ? requestItem.category?.name_am ?? requestItem.category?.name_en
            : requestItem.category?.name_en;

    const isRequesterReturned = can.update && requestItem.status === 'returned';
    const hasWorkspaceActions = workspace.canAssignTeamLeader || workspace.canAssignExpert || can.respond;
    const sanitizedDescriptionHtml = useMemo(
        () => sanitizeRichTextHtml(requestItem.description),
        [requestItem.description],
    );

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.advisory_requests'), href: route('advisory.index') },
                { label: requestItem.request_number },
            ]}
        >
            <Head title={requestItem.request_number} />

            <PageContainer className="space-y-6">
                <SectionHeader
                    eyebrow={requestItem.request_number}
                    title={requestItem.subject}
                    action={
                        isRequesterReturned ? (
                            <Link href={route('advisory.edit', { advisoryRequest: requestItem.id })} className="btn-base btn-primary focus-ring">
                                {t('advisory.resubmit_request')}
                            </Link>
                        ) : undefined
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={requestItem.status} />
                    <StatusBadge value={requestItem.priority} />
                    <StatusBadge value={requestItem.director_decision} />
                </div>

                <SurfaceCard className="space-y-5 p-6">
                    <div className="space-y-1">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">
                            {t('common.overview')}
                        </h2>
                    </div>

                    <dl className="grid gap-x-8 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                        <OverviewItem label={t('advisory.department')} value={departmentName} />
                        <OverviewItem label={t('advisory.category')} value={categoryName} />
                        <OverviewItem label={t('advisory.requester')} value={requestItem.requester?.name} />
                        <OverviewItem label={t('advisory.request_type')} value={requestItem.request_type} />
                        <OverviewItem label={t('advisory.priority')} value={requestItem.priority} />
                        <OverviewItem label={t('advisory.due_date')} value={requestItem.due_date} />
                        <OverviewItem label={t('common.status')} value={<StatusBadge value={requestItem.status} />} />
                        <OverviewItem
                            label={t('advisory.team_leader')}
                            value={requestItem.assigned_team_leader?.name ?? t('common.unassigned')}
                        />
                        <OverviewItem
                            label={t('advisory.expert')}
                            value={requestItem.assigned_legal_expert?.name ?? t('common.unassigned')}
                        />
                    </dl>

                    <div className="border-t border-[color:var(--border)] pt-5">
                        <p className="text-sm font-semibold text-[color:var(--muted-strong)]">
                            {t('common.description')}
                        </p>
                        {sanitizedDescriptionHtml ? (
                            <div
                                className="prose prose-sm mt-4 max-w-none text-[color:var(--text)] dark:prose-invert"
                                dangerouslySetInnerHTML={{ __html: sanitizedDescriptionHtml }}
                            />
                        ) : (
                            <p className="mt-3 text-sm leading-7 text-[color:var(--text)]">
                                {t('common.not_available')}
                            </p>
                        )}
                    </div>
                </SurfaceCard>

                <SurfaceCard className="space-y-6 p-6">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div className="space-y-1">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                {t('common.workspace')}
                            </h2>
                            {!hasWorkspaceActions ? (
                                <p className="text-sm text-[color:var(--muted-strong)]">
                                    {t('common.no_actions_available')}
                                </p>
                            ) : null}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {requestItem.assigned_team_leader?.name ? (
                                <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold text-[color:var(--muted-strong)]">
                                    {t('advisory.team_leader')}: {requestItem.assigned_team_leader.name}
                                </span>
                            ) : null}
                            {requestItem.assigned_legal_expert?.name ? (
                                <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold text-[color:var(--muted-strong)]">
                                    {t('advisory.expert')}: {requestItem.assigned_legal_expert.name}
                                </span>
                            ) : null}
                        </div>
                    </div>

                    {workspace.canAssignTeamLeader ? (
                        <ActionSection
                            title={t('advisory.director_review')}
                            footer={
                                <button
                                    type="button"
                                    onClick={() => setConfirmOpen(true)}
                                    className="btn-base btn-primary focus-ring"
                                    disabled={reviewForm.processing}
                                >
                                    {t('common.submit_review')}
                                </button>
                            }
                        >
                            <div className="grid gap-4 lg:grid-cols-2">
                                <FormField label={t('advisory.director_review')} required error={reviewForm.errors.director_decision}>
                                    <select
                                        value={reviewForm.data.director_decision}
                                        onChange={(event) => reviewForm.setData('director_decision', event.target.value)}
                                        className="select-ui"
                                    >
                                        <option value="approved">{t('common.approved')}</option>
                                        <option value="returned">{t('common.returned')}</option>
                                        <option value="rejected">{t('common.rejected')}</option>
                                    </select>
                                </FormField>

                                {reviewForm.data.director_decision === 'approved' ? (
                                    <FormField
                                        label={t('advisory.team_leader')}
                                        required
                                        error={reviewForm.errors.assigned_team_leader_id}
                                    >
                                        <select
                                            value={reviewForm.data.assigned_team_leader_id}
                                            onChange={(event) => reviewForm.setData('assigned_team_leader_id', event.target.value)}
                                            className="select-ui"
                                        >
                                            <option value="">{t('common.unassigned')}</option>
                                            {teamLeaders.map((leader) => (
                                                <option key={leader.id} value={leader.id}>
                                                    {leader.name}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>
                                ) : (
                                    <OverviewItem
                                        label={t('advisory.team_leader')}
                                        value={requestItem.assigned_team_leader?.name ?? t('common.unassigned')}
                                    />
                                )}
                            </div>

                            <FormField
                                label={t('advisory.director_notes')}
                                optional
                                error={reviewForm.errors.director_notes}
                            >
                                <textarea
                                    value={reviewForm.data.director_notes}
                                    onChange={(event) => reviewForm.setData('director_notes', event.target.value)}
                                    rows={5}
                                    className="textarea-ui"
                                />
                            </FormField>
                        </ActionSection>
                    ) : null}

                    {workspace.canAssignExpert ? (
                        <ActionSection
                            title={t('advisory.assign_expert')}
                            footer={
                                <button
                                    type="button"
                                    onClick={() =>
                                        assignForm.patch(route('advisory.assign', { advisoryRequest: requestItem.id }), {
                                            onSuccess: () => {
                                                finishSuccessfulSubmission(assignForm, {
                                                    reset: true,
                                                });
                                            },
                                        })
                                    }
                                    className="btn-base btn-primary focus-ring"
                                    disabled={assignForm.processing}
                                >
                                    {t('common.assign')}
                                </button>
                            }
                        >
                            <div className="grid gap-4 lg:grid-cols-2">
                                <FormField
                                    label={t('advisory.expert')}
                                    required
                                    error={assignForm.errors.assigned_legal_expert_id}
                                >
                                    <select
                                        value={assignForm.data.assigned_legal_expert_id}
                                        onChange={(event) => assignForm.setData('assigned_legal_expert_id', event.target.value)}
                                        className="select-ui"
                                    >
                                        <option value="">{t('common.unassigned')}</option>
                                        {experts.map((expert) => (
                                            <option key={expert.id} value={expert.id}>
                                                {expert.name}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>

                                <FormField label={t('common.assignment_notes')} optional error={assignForm.errors.notes}>
                                    <textarea
                                        value={assignForm.data.notes}
                                        onChange={(event) => assignForm.setData('notes', event.target.value)}
                                        rows={5}
                                        className="textarea-ui"
                                    />
                                </FormField>
                            </div>
                        </ActionSection>
                    ) : null}

                    <div className="space-y-6 border-t border-[color:var(--border)] pt-6">
                        <section className="space-y-4">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                <div className="space-y-1">
                                    <h3 className="text-base font-semibold text-[color:var(--text)]">
                                        {t('advisory.response')}
                                    </h3>
                                </div>

                                {can.respond ? (
                                    <Link
                                        href={route('advisory.responses.create', { advisoryRequest: requestItem.id })}
                                        className="btn-base btn-primary focus-ring"
                                    >
                                        {t('advisory.add_response')}
                                    </Link>
                                ) : null}
                            </div>

                            {responses.length > 0 ? (
                                <div className="overflow-x-auto rounded-[var(--radius-lg)] border border-[color:var(--border)]">
                                    <table className="min-w-full divide-y divide-[color:var(--border)] text-sm">
                                        <thead className="bg-[color:var(--surface-muted)]/70">
                                            <tr className="text-left text-xs font-semibold uppercase text-[color:var(--muted)]">
                                                <th className="px-4 py-3">#</th>
                                                <th className="px-4 py-3">{t('common.date')}</th>
                                                <th className="px-4 py-3">{t('advisory.request_code')}</th>
                                                <th className="px-4 py-3">{t('advisory.requester')}</th>
                                                <th className="px-4 py-3">{t('advisory.department')}</th>
                                                <th className="px-4 py-3">{t('audit.actor')}</th>
                                                <th className="px-4 py-3">{t('common.actions')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-[color:var(--border)] bg-[color:var(--surface)]">
                                            {responses.map((response: any, index: number) => (
                                                <tr key={response.id} className="align-top">
                                                    <td className="px-4 py-4 font-medium text-[color:var(--text)]">
                                                        {index + 1}
                                                    </td>
                                                    <td className="px-4 py-4 text-[color:var(--muted-strong)]">
                                                        {response.responded_at
                                                            ? formatDateTime(response.responded_at)
                                                            : t('common.not_available')}
                                                    </td>
                                                    <td className="px-4 py-4 font-medium text-[color:var(--text)]">
                                                        {requestItem.request_number}
                                                    </td>
                                                    <td className="px-4 py-4 text-[color:var(--text)]">
                                                        {requestItem.requester?.name ?? t('common.not_available')}
                                                    </td>
                                                    <td className="px-4 py-4 text-[color:var(--text)]">
                                                        {departmentName ?? t('common.not_available')}
                                                    </td>
                                                    <td className="px-4 py-4 font-medium text-[color:var(--text)]">
                                                        {response.responder ?? t('common.not_available')}
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        <div className="flex flex-wrap gap-2">
                                                            <Link
                                                                href={route('advisory.responses.show', {
                                                                    advisoryRequest: requestItem.id,
                                                                    advisoryResponse: response.id,
                                                                })}
                                                                className="btn-base btn-secondary focus-ring"
                                                            >
                                                                {t('common.view')}
                                                            </Link>
                                                            {response.can_update ? (
                                                                <Link
                                                                    href={route('advisory.responses.edit', {
                                                                        advisoryRequest: requestItem.id,
                                                                        advisoryResponse: response.id,
                                                                    })}
                                                                    className="btn-base btn-secondary focus-ring"
                                                                >
                                                                    {t('common.edit')}
                                                                </Link>
                                                            ) : null}
                                                            {response.can_delete ? (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setResponseToDelete(response)}
                                                                    className="btn-base btn-danger focus-ring"
                                                                >
                                                                    {t('common.delete')}
                                                                </button>
                                                            ) : null}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <EmptyState
                                    title={t('advisory.response')}
                                    description={t('common.not_available')}
                                />
                            )}
                        </section>

                        {attachments.length > 0 ? (
                            <section className="space-y-4 border-t border-[color:var(--border)] pt-6">
                                <div className="space-y-1">
                                    <h3 className="text-base font-semibold text-[color:var(--text)]">
                                        {t('common.attachments')}
                                    </h3>
                                </div>

                                <div className="space-y-3">
                                    {attachments.map((attachment: any) => (
                                        <FileAttachmentCard
                                            key={attachment.id}
                                            name={attachment.original_name}
                                            meta={formatAttachmentMeta(attachment, t, formatDateTime)}
                                            viewUrl={attachment.view_url}
                                            downloadUrl={attachment.download_url}
                                            canDelete={attachment.can_delete}
                                            deleting={deleteAttachmentForm.processing && attachmentToDelete?.id === attachment.id}
                                            onDelete={
                                                attachment.can_delete
                                                    ? () => setAttachmentToDelete(attachment)
                                                    : undefined
                                            }
                                        />
                                    ))}
                                </div>
                            </section>
                        ) : null}
                    </div>
                </SurfaceCard>
            </PageContainer>

            <ConfirmationDialog
                open={confirmOpen}
                title={t('advisory.confirm_review')}
                description={t('advisory.confirm_review_description')}
                confirmLabel={t('advisory.confirm_review_button')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => {
                    reviewForm.patch(route('advisory.review', { advisoryRequest: requestItem.id }), {
                        onSuccess: () => {
                            finishSuccessfulSubmission(reviewForm, {
                                reset: true,
                                afterSuccess: () => {
                                    setConfirmOpen(false);
                                },
                            });
                        },
                    });
                }}
                processing={reviewForm.processing}
            />

            <ConfirmationDialog
                open={attachmentToDelete !== null}
                title={t('attachments.delete_title')}
                description={t('attachments.delete_description')}
                confirmLabel={t('common.delete')}
                onCancel={() => setAttachmentToDelete(null)}
                onConfirm={() => {
                    if (!attachmentToDelete?.delete_url) {
                        return;
                    }

                    deleteAttachmentForm.delete(attachmentToDelete.delete_url, {
                        preserveScroll: true,
                        onSuccess: () => {
                            finishSuccessfulSubmission(deleteAttachmentForm, {
                                afterSuccess: () => {
                                    setAttachmentToDelete(null);
                                },
                            });
                        },
                    });
                }}
                processing={deleteAttachmentForm.processing}
            />

            <ConfirmationDialog
                open={responseToDelete !== null}
                title={t('advisory.delete_response_title')}
                description={t('advisory.delete_response_description')}
                confirmLabel={t('common.delete')}
                onCancel={() => setResponseToDelete(null)}
                onConfirm={() => {
                    if (!responseToDelete) {
                        return;
                    }

                    deleteResponseForm.delete(route('advisory.responses.destroy', {
                        advisoryRequest: requestItem.id,
                        advisoryResponse: responseToDelete.id,
                    }), {
                        preserveScroll: true,
                        onSuccess: () => {
                            finishSuccessfulSubmission(deleteResponseForm, {
                                afterSuccess: () => {
                                    setResponseToDelete(null);
                                },
                            });
                        },
                    });
                }}
                processing={deleteResponseForm.processing}
            />
        </AuthenticatedLayout>
    );
}

function OverviewItem({ label, value }: { label: string; value?: ReactNode }) {
    const { t } = useI18n();

    return (
        <div className="space-y-1.5 border-b border-[color:var(--border)] pb-4 last:border-b-0 last:pb-0">
            <dt className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                {label}
            </dt>
            <dd className="text-sm font-medium text-[color:var(--text)]">
                {value ?? t('common.not_set')}
            </dd>
        </div>
    );
}

function ActionSection({
    title,
    children,
    footer,
}: {
    title: string;
    children: ReactNode;
    footer?: ReactNode;
}) {
    return (
        <section className="space-y-4 border-t border-[color:var(--border)] pt-6 first:border-t-0 first:pt-0">
            <div className="space-y-1">
                <h3 className="text-base font-semibold text-[color:var(--text)]">{title}</h3>
            </div>
            <div className="space-y-4">{children}</div>
            {footer ? <div className="flex justify-end pt-2">{footer}</div> : null}
        </section>
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
