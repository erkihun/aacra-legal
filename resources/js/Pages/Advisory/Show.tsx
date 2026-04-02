import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import BackButton from '@/Components/Ui/BackButton';
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
import { Head, Link, useForm, useRemember } from '@inertiajs/react';
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

type AdvisoryWorkspacePanel = 'overview' | 'assign' | 'response' | 'attachments' | null;
type AdvisoryWorkspaceNavKey = Exclude<AdvisoryWorkspacePanel, null>;
type WorkspaceIconKind = 'workspace' | 'assignment' | 'expert' | 'attachment' | 'review' | 'response';
type AdvisoryWorkspaceNavItem = {
    key: AdvisoryWorkspaceNavKey;
    label: string;
    icon: WorkspaceIconKind;
    detail?: string;
    badge?: number;
};

export default function AdvisoryShow({
    requestItem,
    teamLeaders,
    experts,
    workspace,
    can,
}: ShowAdvisoryProps) {
    const normalizeArray = (value: unknown) => (Array.isArray(value) ? value : []);

    const { t, locale } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [activePanel, setActivePanel] = useRemember<AdvisoryWorkspacePanel>(
        'overview',
        `advisory-show-active-panel-${requestItem.id}`,
    );
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [attachmentToDelete, setAttachmentToDelete] = useState<any | null>(null);
    const [responseToDelete, setResponseToDelete] = useState<any | null>(null);

    const attachments = normalizeArray(requestItem.attachments);
    const responses = normalizeArray(requestItem.responses);

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
    const assignSectionEnabled = workspace.canAssignTeamLeader || workspace.canAssignExpert;
    const responseSectionEnabled = can.respond || responses.length > 0;
    const attachmentSectionEnabled = can.attach || attachments.length > 0;
    const sanitizedDescriptionHtml = useMemo(
        () => sanitizeRichTextHtml(requestItem.description),
        [requestItem.description],
    );
    const normalizedActivePanel = activePanel ?? 'overview';
    const navigationItems: AdvisoryWorkspaceNavItem[] = [
        {
            key: 'overview',
            label: t('common.overview'),
            icon: 'workspace',
            detail: requestItem.request_number,
        },
        ...(assignSectionEnabled
            ? [
                  {
                      key: 'assign',
                      label: t('common.assign'),
                      icon: workspace.canAssignTeamLeader ? 'review' : 'assignment',
                      detail: workspace.canAssignTeamLeader
                          ? t('advisory.director_review')
                          : t('advisory.assign_expert'),
                  } satisfies AdvisoryWorkspaceNavItem,
              ]
            : []),
        ...(responseSectionEnabled
            ? [
                  {
                      key: 'response',
                      label: responses.length > 0 ? t('advisory.response') : t('advisory.add_response'),
                      icon: 'response',
                      detail: can.respond ? t('advisory.add_response') : t('advisory.response'),
                      badge: responses.length,
                  } satisfies AdvisoryWorkspaceNavItem,
              ]
            : []),
        ...(attachmentSectionEnabled
            ? [
                  {
                      key: 'attachments',
                      label: t('common.attachments'),
                      icon: 'attachment',
                      detail: t('common.attachments'),
                      badge: attachments.length,
                  } satisfies AdvisoryWorkspaceNavItem,
              ]
            : []),
    ];
    const currentPanel = navigationItems.some((item) => item.key === normalizedActivePanel)
        ? normalizedActivePanel
        : 'overview';

    const workspaceContent = (
        <div className="grid gap-6 xl:grid-cols-[18rem,minmax(0,1fr)]">
            <SurfaceCard className="h-fit p-4 md:p-5 xl:sticky xl:top-24">
                <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1">
                        <p className="text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--muted)]">
                            {t('common.workspace')}
                        </p>
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">
                            {t('navigation.advisory_requests')}
                        </h2>
                        <p className="text-sm text-[color:var(--muted-strong)]">{requestItem.subject}</p>
                    </div>
                    <WorkspaceCountBadge value={navigationItems.length} />
                </div>

                <div className="mt-5 flex gap-2 overflow-x-auto pb-1 xl:flex-col xl:overflow-visible xl:pb-0">
                    {navigationItems.map((item) => (
                        <AdvisoryWorkspaceNavButton
                            key={item.key}
                            label={item.label}
                            detail={item.detail}
                            icon={item.icon}
                            badge={item.badge}
                            active={currentPanel === item.key}
                            onClick={() => setActivePanel(item.key)}
                        />
                    ))}
                </div>

                <div className="mt-5 grid gap-3 border-t border-[color:var(--border)] pt-4">
                    <WorkspaceInfoRow
                        label={t('advisory.team_leader')}
                        value={requestItem.assigned_team_leader?.name ?? t('common.unassigned')}
                    />
                    <WorkspaceInfoRow
                        label={t('advisory.expert')}
                        value={requestItem.assigned_legal_expert?.name ?? t('common.unassigned')}
                    />
                    <WorkspaceInfoRow
                        label={t('advisory.due_date')}
                        value={requestItem.due_date ?? t('common.not_available')}
                    />
                </div>
            </SurfaceCard>

            <div className="min-w-0 space-y-4">
                {currentPanel === 'overview' ? (
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
                            <OverviewItem
                                label={t('common.status')}
                                value={<StatusBadge value={requestItem.status} />}
                            />
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
                ) : null}

                {currentPanel === 'assign' && assignSectionEnabled ? (
                    <div className="space-y-4">
                        {workspace.canAssignTeamLeader ? (
                            <SurfaceCard className="space-y-6 p-6">
                                <ActionSection
                                    title={t('advisory.director_review')}
                                    footer={
                                        <div className="flex flex-wrap justify-end gap-3">
                                            <button
                                                type="button"
                                                onClick={() => setActivePanel('overview')}
                                                className="btn-base btn-secondary focus-ring"
                                            >
                                                {t('common.cancel')}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setConfirmOpen(true)}
                                                className="btn-base btn-primary focus-ring"
                                                disabled={reviewForm.processing}
                                            >
                                                {t('common.submit_review')}
                                            </button>
                                        </div>
                                    }
                                >
                                    <div className="grid gap-4 lg:grid-cols-2">
                                        <FormField
                                            label={t('advisory.director_review')}
                                            required
                                            error={reviewForm.errors.director_decision}
                                        >
                                            <select
                                                value={reviewForm.data.director_decision}
                                                onChange={(event) =>
                                                    reviewForm.setData('director_decision', event.target.value)
                                                }
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
                                                    onChange={(event) =>
                                                        reviewForm.setData(
                                                            'assigned_team_leader_id',
                                                            event.target.value,
                                                        )
                                                    }
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
                                                value={
                                                    requestItem.assigned_team_leader?.name ?? t('common.unassigned')
                                                }
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
                                            onChange={(event) =>
                                                reviewForm.setData('director_notes', event.target.value)
                                            }
                                            rows={5}
                                            className="textarea-ui"
                                        />
                                    </FormField>
                                </ActionSection>
                            </SurfaceCard>
                        ) : null}

                        {workspace.canAssignExpert ? (
                            <SurfaceCard className="space-y-6 p-6">
                                <ActionSection
                                    title={t('advisory.assign_expert')}
                                    footer={
                                        <div className="flex flex-wrap justify-end gap-3">
                                            <button
                                                type="button"
                                                onClick={() => setActivePanel('overview')}
                                                className="btn-base btn-secondary focus-ring"
                                            >
                                                {t('common.cancel')}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    assignForm.patch(
                                                        route('advisory.assign', {
                                                            advisoryRequest: requestItem.id,
                                                        }),
                                                        {
                                                            onSuccess: () => {
                                                                finishSuccessfulSubmission(assignForm, {
                                                                    reset: true,
                                                                    afterSuccess: () => {
                                                                        setActivePanel('overview');
                                                                    },
                                                                });
                                                            },
                                                        },
                                                    )
                                                }
                                                className="btn-base btn-primary focus-ring"
                                                disabled={assignForm.processing}
                                            >
                                                {t('common.assign')}
                                            </button>
                                        </div>
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
                                                onChange={(event) =>
                                                    assignForm.setData(
                                                        'assigned_legal_expert_id',
                                                        event.target.value,
                                                    )
                                                }
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

                                        <FormField
                                            label={t('common.assignment_notes')}
                                            optional
                                            error={assignForm.errors.notes}
                                        >
                                            <textarea
                                                value={assignForm.data.notes}
                                                onChange={(event) => assignForm.setData('notes', event.target.value)}
                                                rows={5}
                                                className="textarea-ui"
                                            />
                                        </FormField>
                                    </div>
                                </ActionSection>
                            </SurfaceCard>
                        ) : null}

                        {!workspace.canAssignTeamLeader && !workspace.canAssignExpert ? (
                            <SurfaceCard className="p-6">
                                <EmptyState
                                    title={t('common.assign')}
                                    description={t('common.no_actions_available')}
                                />
                            </SurfaceCard>
                        ) : null}
                    </div>
                ) : null}

                {currentPanel === 'response' && responseSectionEnabled ? (
                    <SurfaceCard className="space-y-6 p-6">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div className="space-y-1">
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                    {responses.length > 0 ? t('advisory.response') : t('advisory.add_response')}
                                </h2>
                            </div>

                            {can.respond ? (
                                <Link
                                    href={route('advisory.responses.create', {
                                        advisoryRequest: requestItem.id,
                                    })}
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
                                            <tr key={response.id ?? index} className="align-top">
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
                    </SurfaceCard>
                ) : null}

                {currentPanel === 'attachments' && attachmentSectionEnabled ? (
                    <SurfaceCard className="space-y-6 p-6">
                        <div className="space-y-1">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                {t('common.attachments')}
                            </h2>
                        </div>

                        {attachments.length > 0 ? (
                            <div className="space-y-3">
                                {attachments.map((attachment: any, index: number) => (
                                    <FileAttachmentCard
                                        key={attachment.id ?? index}
                                        name={attachment.original_name}
                                        meta={formatAttachmentMeta(attachment, t, formatDateTime)}
                                        viewUrl={attachment.view_url}
                                        downloadUrl={attachment.download_url}
                                        canDelete={attachment.can_delete}
                                        deleting={
                                            deleteAttachmentForm.processing &&
                                            attachmentToDelete?.id === attachment.id
                                        }
                                        onDelete={
                                            attachment.can_delete
                                                ? () => setAttachmentToDelete(attachment)
                                                : undefined
                                        }
                                    />
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                title={t('common.attachments')}
                                description={t('common.no_attachments')}
                            />
                        )}
                    </SurfaceCard>
                ) : null}
            </div>
        </div>
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
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('advisory.index')} />
                            {isRequesterReturned ? (
                                <Link
                                    href={route('advisory.edit', { advisoryRequest: requestItem.id })}
                                    className="btn-base btn-primary focus-ring"
                                >
                                    {t('advisory.resubmit_request')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={requestItem.status} />
                    <StatusBadge value={requestItem.priority} />
                    <StatusBadge value={requestItem.director_decision} />
                </div>

                {workspaceContent}
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
                                    setActivePanel('overview');
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

                    deleteResponseForm.delete(
                        route('advisory.responses.destroy', {
                            advisoryRequest: requestItem.id,
                            advisoryResponse: responseToDelete.id,
                        }),
                        {
                            preserveScroll: true,
                            onSuccess: () => {
                                finishSuccessfulSubmission(deleteResponseForm, {
                                    afterSuccess: () => {
                                        setResponseToDelete(null);
                                    },
                                });
                            },
                        },
                    );
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
            <dt className="text-xs font-semibold uppercase text-[color:var(--muted)]">{label}</dt>
            <dd className="text-sm font-medium text-[color:var(--text)]">{value ?? t('common.not_set')}</dd>
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
        <section className="space-y-4">
            <div className="space-y-1">
                <h3 className="text-base font-semibold text-[color:var(--text)]">{title}</h3>
            </div>
            <div className="space-y-4">{children}</div>
            {footer ? <div className="flex justify-end pt-2">{footer}</div> : null}
        </section>
    );
}

function AdvisoryWorkspaceNavButton({
    active,
    label,
    detail,
    icon,
    badge,
    onClick,
}: {
    active: boolean;
    label: string;
    detail?: string;
    icon: WorkspaceIconKind;
    badge?: number;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-current={active ? 'page' : undefined}
            className={`focus-ring group flex min-w-[12rem] items-center gap-3 rounded-2xl border px-4 py-3 text-left transition xl:min-w-0 ${
                active
                    ? 'border-[color:var(--primary)] bg-[var(--primary-soft)] text-[color:var(--primary)] shadow-sm'
                    : 'border-[color:var(--border)] bg-[color:var(--surface)] text-[color:var(--text)] hover:border-[color:var(--primary)]/30 hover:bg-[color:var(--surface-muted)]'
            }`}
        >
            <span
                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl ${
                    active
                        ? 'bg-[color:var(--primary)] text-white'
                        : 'bg-[color:var(--surface-muted)] text-[color:var(--muted-strong)] group-hover:text-[color:var(--primary)]'
                }`}
            >
                <WorkspaceIcon kind={icon} />
            </span>
            <span className="min-w-0 flex-1">
                <span className="flex items-center justify-between gap-3">
                    <span className="truncate text-sm font-semibold">{label}</span>
                    {typeof badge === 'number' ? <WorkspaceCountBadge value={badge} /> : null}
                </span>
                {detail ? (
                    <span
                        className={`mt-1 block truncate text-xs ${
                            active ? 'text-[color:var(--primary)]/80' : 'text-[color:var(--muted-strong)]'
                        }`}
                    >
                        {detail}
                    </span>
                ) : null}
            </span>
        </button>
    );
}

function WorkspaceInfoRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="surface-muted flex items-center justify-between gap-3 px-4 py-3">
            <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">{label}</p>
            <div className="text-right text-sm font-semibold text-[color:var(--text)]">{value}</div>
        </div>
    );
}

function WorkspaceCountBadge({ value }: { value: number }) {
    return (
        <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold uppercase text-[color:var(--muted-strong)]">
            {value}
        </span>
    );
}

function WorkspaceIcon({ kind }: { kind: WorkspaceIconKind }) {
    const paths: Record<WorkspaceIconKind, ReactNode> = {
        workspace: (
            <>
                <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h4A2.5 2.5 0 0 1 13 7.5v3A2.5 2.5 0 0 1 10.5 13h-4A2.5 2.5 0 0 1 4 10.5v-3Z" />
                <path d="M11 17.5A2.5 2.5 0 0 1 13.5 15h4a2.5 2.5 0 0 1 2.5 2.5v1A2.5 2.5 0 0 1 17.5 21h-4a2.5 2.5 0 0 1-2.5-2.5v-1Z" />
                <path d="M15 5h2.5A2.5 2.5 0 0 1 20 7.5v1A2.5 2.5 0 0 1 17.5 11H15A2.5 2.5 0 0 1 12.5 8.5v-1A2.5 2.5 0 0 1 15 5Z" />
                <path d="M4 17.5A2.5 2.5 0 0 1 6.5 15H9a2.5 2.5 0 0 1 2.5 2.5v1A2.5 2.5 0 0 1 9 21H6.5A2.5 2.5 0 0 1 4 18.5v-1Z" />
            </>
        ),
        assignment: (
            <>
                <path d="M4 7.5h10" />
                <path d="M4 12h16" />
                <path d="M4 16.5h9" />
                <path d="m16 7 4 5-4 5" />
            </>
        ),
        expert: (
            <>
                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                <path d="M5 20a7 7 0 0 1 14 0" />
                <path d="m18.5 9.5 1 1 2-2" />
            </>
        ),
        attachment: <path d="M8.5 12.5 14 7a3 3 0 1 1 4.2 4.2l-7.1 7.1a4.5 4.5 0 1 1-6.4-6.4l7.4-7.4" />,
        review: (
            <>
                <path d="M5 6.5A1.5 1.5 0 0 1 6.5 5h9A1.5 1.5 0 0 1 17 6.5V9h2.5A1.5 1.5 0 0 1 21 10.5v8a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5v-8A1.5 1.5 0 0 1 4.5 9H7V6.5Z" />
                <path d="m9.5 14 1.7 1.7 3.8-4.2" />
            </>
        ),
        response: (
            <>
                <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7a2.5 2.5 0 0 1-2.5 2.5H10l-4 4v-4H6.5A2.5 2.5 0 0 1 4 13.5v-7Z" />
                <path d="M8 8.5h8M8 11.5h8M8 14.5h5" />
            </>
        ),
    };

    return (
        <svg
            viewBox="0 0 24 24"
            className="h-5 w-5"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            {paths[kind]}
        </svg>
    );
}

function formatAttachmentMeta(
    attachment: {
        mime_type?: string | null;
        size?: number | null;
        uploaded_by?: string | null;
        created_at?: string | null;
    },
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
