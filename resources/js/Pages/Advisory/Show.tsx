import FileAttachmentCard from '@/Components/Ui/FileAttachmentCard';
import CommentItem from '@/Components/Ui/CommentItem';
import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import EmptyState from '@/Components/Ui/EmptyState';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import Tabs from '@/Components/Ui/Tabs';
import Timeline from '@/Components/Ui/Timeline';
import WorkflowProgress from '@/Components/Ui/WorkflowProgress';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

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
        comment: boolean;
        attach: boolean;
        update: boolean;
        requester_comment_public: boolean;
    };
};

export default function AdvisoryShow({
    requestItem,
    teamLeaders,
    experts,
    workspace,
    can,
}: ShowAdvisoryProps) {
    const normalizeArray = (value: any) => (Array.isArray(value) ? value : []);

    const attachments = normalizeArray(requestItem.attachments);
    const comments = normalizeArray(requestItem.comments);
    const responses = normalizeArray(requestItem.responses);
    const assignments = normalizeArray(requestItem.assignments);

    const { t, locale } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [attachmentToDelete, setAttachmentToDelete] = useState<any | null>(null);

    const reviewForm = useForm({
        director_decision: 'approved',
        director_notes: '',
        assigned_team_leader_id: teamLeaders[0]?.id ?? '',
    });

    const assignForm = useForm({
        assigned_legal_expert_id: experts[0]?.id ?? '',
        notes: '',
    });

    const responseForm = useForm({
        response_type: 'written',
        summary: '',
        advice_text: '',
        follow_up_notes: '',
    });

    const commentForm = useForm({
        body: '',
        is_internal: !can.requester_comment_public,
    });

    const attachmentForm = useForm({
        attachments: [] as File[],
    });
    const deleteAttachmentForm = useForm({});

    const stages = [
        { label: t('advisory.workflow.requester'), complete: true, active: false },
        {
            label: t('advisory.workflow.director'),
            complete: ['assigned_to_team_leader', 'assigned_to_expert', 'responded', 'completed', 'closed'].includes(requestItem.status),
            active: requestItem.workflow_stage === 'director',
        },
        {
            label: t('advisory.workflow.team_leader'),
            complete: ['assigned_to_expert', 'responded', 'completed', 'closed'].includes(requestItem.status),
            active: requestItem.workflow_stage === 'team_leader',
        },
        {
            label: t('advisory.workflow.expert'),
            complete: ['responded', 'completed', 'closed'].includes(requestItem.status),
            active: requestItem.workflow_stage === 'expert',
        },
    ];

    const timelineItems = useMemo(() => {
        const assignmentItems = assignments.map((assignment: any) => ({
            id: assignment.id,
            title: `${assignment.assignment_role.replace('_', ' ')} ${t('common.assignment')}`,
            body: `${assignment.assigned_by} ${t('common.assigned_to')} ${assignment.assigned_to}${assignment.notes ? `. ${assignment.notes}` : ''}`,
            meta: assignment.assigned_at,
        }));

        const responseItems = responses.map((response: any) => ({
            id: response.id,
            title: `${response.response_type} ${t('advisory.response')}`,
            body: `${response.responder}: ${response.summary}`,
            meta: response.responded_at,
        }));

        const commentsItems = comments.map((comment: any) => ({
            id: comment.id,
            title: t('common.internal_comment'),
            body: `${comment.user?.name ?? t('common.not_available')}: ${comment.body}`,
            meta: comment.created_at,
        }));

        return [...assignmentItems, ...responseItems, ...commentsItems].sort((a, b) => `${b.meta}`.localeCompare(`${a.meta}`));
    }, [assignments, responses, comments, t]);

    const departmentName =
        locale === 'am'
            ? requestItem.department?.name_am ?? requestItem.department?.name_en
            : requestItem.department?.name_en;
    const categoryName =
        locale === 'am'
            ? requestItem.category?.name_am ?? requestItem.category?.name_en
            : requestItem.category?.name_en;

    const latestResponse = responses[0] ?? null;
    const isRequesterReturned = can.update && requestItem.status === 'returned';
    const commentLabel = can.requester_comment_public ? t('advisory.follow_up_note') : t('common.add_internal_note');

    const tabItems = [
        {
            key: 'overview',
            label: t('common.overview'),
            content: (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <DetailCard label={t('advisory.department')} value={departmentName} />
                    <DetailCard label={t('advisory.category')} value={categoryName} />
                    <DetailCard label={t('advisory.requester')} value={requestItem.requester?.name} />
                    <DetailCard label={t('advisory.request_type')} value={requestItem.request_type} />
                    <DetailCard label={t('advisory.team_leader')} value={requestItem.assigned_team_leader?.name ?? t('common.unassigned')} />
                    <DetailCard label={t('advisory.expert')} value={requestItem.assigned_legal_expert?.name ?? t('common.unassigned')} />
                    <DetailCard label={t('advisory.priority')} value={requestItem.priority} />
                    <DetailCard label={t('advisory.due_date')} value={requestItem.due_date} />
                    <DetailCard label={t('reports.completed_at')} value={requestItem.completed_at} />
                </div>
            ),
        },
        {
            key: 'activity',
            label: t('common.timeline'),
            content: (
                <SurfaceCard>
                    <Timeline items={timelineItems} />
                </SurfaceCard>
            ),
        },
        {
            key: 'workspace',
            label: t('common.workspace'),
            content: (
                <div className="grid gap-4">
                    {workspace.canAssignTeamLeader ? (
                        <SurfaceCard>
                            <PanelTitle title={t('advisory.director_review')} />
                            <div className="grid gap-4 md:grid-cols-2">
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
                                ) : null}
                            </div>
                            <FormField
                                label={t('advisory.director_notes')}
                                optional
                                error={reviewForm.errors.director_notes}
                                className="mt-4"
                            >
                                <textarea
                                    value={reviewForm.data.director_notes}
                                    onChange={(event) => reviewForm.setData('director_notes', event.target.value)}
                                    rows={4}
                                    className="textarea-ui"
                                />
                            </FormField>
                            <div className="mt-5 flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setConfirmOpen(true)}
                                    className="btn-base btn-primary focus-ring"
                                    disabled={reviewForm.processing}
                                >
                                    {t('common.submit_review')}
                                </button>
                            </div>
                        </SurfaceCard>
                    ) : null}

                    {workspace.canAssignExpert ? (
                        <SurfaceCard>
                            <PanelTitle title={t('advisory.assign_expert')} />
                            <div className="grid gap-4 md:grid-cols-2">
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
                                <FormField
                                    label={t('common.assignment_notes')}
                                    optional
                                    error={assignForm.errors.notes}
                                >
                                    <textarea
                                        value={assignForm.data.notes}
                                        onChange={(event) => assignForm.setData('notes', event.target.value)}
                                        rows={4}
                                        className="textarea-ui"
                                    />
                                </FormField>
                            </div>
                            <div className="mt-5 flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => assignForm.patch(route('advisory.assign', { advisoryRequest: requestItem.id }))}
                                    className="btn-base btn-primary focus-ring"
                                    disabled={assignForm.processing}
                                >
                                    {t('common.assign')}
                                </button>
                            </div>
                        </SurfaceCard>
                    ) : null}

                    {can.respond ? (
                        <SurfaceCard>
                            <PanelTitle title={t('advisory.record_response')} />
                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField
                                    label={t('advisory.request_type')}
                                    required
                                    error={responseForm.errors.response_type}
                                >
                                    <select
                                        value={responseForm.data.response_type}
                                        onChange={(event) => responseForm.setData('response_type', event.target.value)}
                                        className="select-ui"
                                    >
                                        <option value="written">{t('common.written')}</option>
                                        <option value="verbal">{t('common.verbal')}</option>
                                    </select>
                                </FormField>
                                <FormField
                                    label={t('advisory.response_summary')}
                                    required
                                    error={responseForm.errors.summary}
                                >
                                    <textarea
                                        value={responseForm.data.summary}
                                        onChange={(event) => responseForm.setData('summary', event.target.value)}
                                        rows={4}
                                        className="textarea-ui"
                                    />
                                </FormField>
                            </div>
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <FormField
                                    label={t('advisory.advice_text')}
                                    optional={responseForm.data.response_type === 'verbal'}
                                    required={responseForm.data.response_type === 'written'}
                                    error={responseForm.errors.advice_text}
                                >
                                    <textarea
                                        value={responseForm.data.advice_text}
                                        onChange={(event) => responseForm.setData('advice_text', event.target.value)}
                                        rows={6}
                                        className="textarea-ui"
                                    />
                                </FormField>
                                <FormField
                                    label={t('common.assignment_notes')}
                                    optional
                                    error={responseForm.errors.follow_up_notes}
                                >
                                    <textarea
                                        value={responseForm.data.follow_up_notes}
                                        onChange={(event) => responseForm.setData('follow_up_notes', event.target.value)}
                                        rows={6}
                                        className="textarea-ui"
                                    />
                                </FormField>
                            </div>
                            <div className="mt-5 flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => responseForm.post(route('advisory.respond', { advisoryRequest: requestItem.id }))}
                                    className="btn-base btn-primary focus-ring"
                                    disabled={responseForm.processing}
                                >
                                    {t('common.save_response')}
                                </button>
                            </div>
                        </SurfaceCard>
                    ) : null}

                    {!workspace.canAssignTeamLeader && !workspace.canAssignExpert && !can.respond ? (
                        <EmptyState
                            title={t('common.workspace')}
                            description={t('common.no_actions_available')}
                        />
                    ) : null}
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.advisory_requests'), href: route('advisory.index') },
                { label: requestItem.request_number },
            ]}
        >
            <Head title={requestItem.request_number} />

            <PageContainer>
                <SectionHeader
                    eyebrow={requestItem.request_number}
                    title={requestItem.subject}
                    description={requestItem.description}
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

                <WorkflowProgress stages={stages} />

                <div className="grid gap-4 2xl:grid-cols-[minmax(0,1.55fr),minmax(20rem,0.95fr)]">
                    <div className="space-y-4">
                        <Tabs items={tabItems} />
                    </div>

                    <div className="space-y-4">
                        <SurfaceCard>
                            <PanelTitle title={t('advisory.response')} />
                            {latestResponse ? (
                                <div className="space-y-4">
                                    <div className="flex flex-wrap gap-2">
                                        <StatusBadge value={latestResponse.response_type} />
                                        <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold uppercase text-[color:var(--muted-strong)]">
                                            {latestResponse.responded_at ?? t('common.not_available')}
                                        </span>
                                    </div>
                                    <div className="space-y-2">
                                        <p className="text-sm font-semibold text-[color:var(--text)]">
                                            {latestResponse.summary}
                                        </p>
                                        {latestResponse.advice_text ? (
                                            <p className="text-sm leading-6 text-[color:var(--muted-strong)]">
                                                {latestResponse.advice_text}
                                            </p>
                                        ) : null}
                                        {latestResponse.follow_up_notes ? (
                                            <p className="text-sm leading-6 text-[color:var(--muted)]">
                                                {latestResponse.follow_up_notes}
                                            </p>
                                        ) : null}
                                    </div>
                                </div>
                            ) : (
                                <EmptyState
                                    title={t('advisory.record_response')}
                                    description={t('common.not_available')}
                                />
                            )}
                        </SurfaceCard>

                        <SurfaceCard>
                            <PanelTitle title={t('common.internal_comment')} />
                            {can.comment ? (
                                <div className="space-y-4">
                                    <FormField
                                        label={commentLabel}
                                        required
                                        error={commentForm.errors.body}
                                    >
                                        <textarea
                                            value={commentForm.data.body}
                                            onChange={(event) => commentForm.setData('body', event.target.value)}
                                            rows={4}
                                            className="textarea-ui"
                                        />
                                    </FormField>
                                    <div className="flex flex-wrap justify-end gap-3">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                commentForm.transform((data) => ({
                                                    ...data,
                                                    is_internal: !can.requester_comment_public,
                                                }));
                                                commentForm.post(route('advisory.comments.store', { advisoryRequest: requestItem.id }), {
                                                    onFinish: () => commentForm.transform((data) => data),
                                                });
                                            }}
                                            className="btn-base btn-secondary focus-ring"
                                            disabled={commentForm.processing}
                                        >
                                            {t('common.add_comment')}
                                        </button>
                                    </div>
                                </div>
                            ) : null}

                            <div className="mt-4 space-y-3">
                                {comments.length === 0 ? (
                                    <EmptyState
                                        title={t('common.internal_comment')}
                                        description={t('common.no_comments')}
                                    />
                                ) : (
                                    comments.map((comment: any) => (
                                        <CommentItem
                                            key={comment.id}
                                            author={comment.user?.name}
                                            body={comment.body}
                                            date={formatDateTime(comment.created_at)}
                                        />
                                    ))
                                )}
                            </div>
                        </SurfaceCard>

                        <SurfaceCard>
                            <PanelTitle title={t('common.attachments')} />
                            {can.attach ? (
                                <div className="space-y-4">
                                    <FormField
                                        label={t('common.attachments')}
                                        optional
                                        error={attachmentForm.errors.attachments as string | undefined}
                                    >
                                        <input
                                            type="file"
                                            multiple
                                            onChange={(event) =>
                                                attachmentForm.setData(
                                                    'attachments',
                                                    Array.from(event.target.files ?? []),
                                                )
                                            }
                                            className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                        />
                                    </FormField>
                                    <div className="flex flex-wrap justify-end gap-3">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                attachmentForm.post(
                                                    route('advisory.attachments.store', { advisoryRequest: requestItem.id }),
                                                    { forceFormData: true },
                                                )
                                            }
                                            className="btn-base btn-secondary focus-ring"
                                            disabled={attachmentForm.processing}
                                        >
                                            {t('common.upload_files')}
                                        </button>
                                    </div>
                                </div>
                            ) : null}

                            <div className="mt-4 space-y-3">
                                {attachments.length === 0 ? (
                                    <EmptyState
                                        title={t('common.attachments')}
                                        description={t('common.no_attachments')}
                                    />
                                ) : (
                                    attachments.map((attachment: any) => (
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
                                    ))
                                )}
                            </div>
                        </SurfaceCard>
                    </div>
                </div>
            </PageContainer>

            <ConfirmationDialog
                open={confirmOpen}
                title={t('advisory.confirm_review')}
                description={t('advisory.confirm_review_description')}
                confirmLabel={t('advisory.confirm_review_button')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => {
                    reviewForm.patch(route('advisory.review', { advisoryRequest: requestItem.id }), {
                        onSuccess: () => setConfirmOpen(false),
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
                        onSuccess: () => setAttachmentToDelete(null),
                    });
                }}
                processing={deleteAttachmentForm.processing}
            />
        </AuthenticatedLayout>
    );
}

function DetailCard({ label, value }: { label: string; value?: string | null }) {
    const { t } = useI18n();

    return (
        <div className="surface-muted px-4 py-4">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-sm font-semibold text-[color:var(--text)]">
                {value ?? t('common.not_set')}
            </p>
        </div>
    );
}

function PanelTitle({ title }: { title: string }) {
    return <h2 className="text-lg font-semibold text-[color:var(--text)]">{title}</h2>;
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
