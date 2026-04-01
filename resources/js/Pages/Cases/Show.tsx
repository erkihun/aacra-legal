import CommentItem from '@/Components/Ui/CommentItem';
import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import EmptyState from '@/Components/Ui/EmptyState';
import FileAttachmentCard from '@/Components/Ui/FileAttachmentCard';
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
import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

type ShowCaseProps = {
    caseItem: any;
    teamLeaders: Array<{ id: string; name: string }>;
    experts: Array<{ id: string; name: string }>;
    workspace: {
        canAssignTeamLeader: boolean;
        canAssignExpert: boolean;
    };
    can: {
        review: boolean;
        assign: boolean;
        recordHearing: boolean;
        close: boolean;
        comment: boolean;
        attach: boolean;
    };
};

export default function CasesShow({
    caseItem,
    teamLeaders,
    experts,
    workspace,
    can,
}: ShowCaseProps) {
    const normalizeArray = (value: any) => (Array.isArray(value) ? value : []);

    const assignments = normalizeArray(caseItem.assignments);
    const hearings = normalizeArray(caseItem.hearings);
    const comments = normalizeArray(caseItem.comments);
    const attachments = normalizeArray(caseItem.attachments);

    const { t, locale } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [confirmCloseOpen, setConfirmCloseOpen] = useState(false);
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

    const hearingForm = useForm({
        hearing_date: '',
        next_hearing_date: '',
        appearance_status: 'attended',
        summary: '',
        institution_position: '',
        court_decision: '',
        outcome: '',
    });

    const closeForm = useForm({
        outcome: caseItem.outcome ?? '',
        decision_date: caseItem.decision_date ?? '',
        appeal_deadline: caseItem.appeal_deadline ?? '',
    });

    const commentForm = useForm({
        body: '',
        is_internal: true,
    });

    const attachmentForm = useForm({
        attachments: [] as File[],
    });
    const deleteAttachmentForm = useForm({});

    const timelineItems = useMemo(() => {
        const assignmentItems = assignments.map((assignment: any) => ({
            id: assignment.id,
            title: `${assignment.assignment_role.replace('_', ' ')} ${t('common.assignment')}`,
            body: `${assignment.assigned_by} ${t('common.assigned_to')} ${assignment.assigned_to}${assignment.notes ? `. ${assignment.notes}` : ''}`,
            meta: assignment.assigned_at,
        }));

        const hearingItems = hearings.map((hearing: any) => ({
            id: hearing.id,
            title: `${t('cases.hearing_on')} ${hearing.hearing_date}`,
            body: hearing.summary,
            meta: hearing.hearing_date,
        }));

        const commentItems = comments.map((comment: any) => ({
            id: comment.id,
            title: t('common.internal_comment'),
            body: `${comment.user?.name ?? t('common.not_available')}: ${comment.body}`,
            meta: comment.created_at,
        }));

        return [...assignmentItems, ...hearingItems, ...commentItems].sort((a, b) => `${b.meta}`.localeCompare(`${a.meta}`));
    }, [assignments, hearings, comments, t]);

    const stages = [
        { label: t('cases.workflow.registrar'), complete: true, active: false },
        {
            label: t('cases.workflow.director'),
            complete: ['assigned_to_team_leader', 'assigned_to_expert', 'in_progress', 'awaiting_hearing', 'judgment_recorded', 'completed', 'closed'].includes(caseItem.status),
            active: caseItem.workflow_stage === 'director',
        },
        {
            label: t('cases.workflow.team_leader'),
            complete: ['assigned_to_expert', 'in_progress', 'awaiting_hearing', 'judgment_recorded', 'completed', 'closed'].includes(caseItem.status),
            active: caseItem.workflow_stage === 'team_leader',
        },
        {
            label: t('cases.workflow.expert'),
            complete: ['judgment_recorded', 'completed', 'closed'].includes(caseItem.status),
            active: caseItem.workflow_stage === 'expert',
        },
    ];

    const courtName =
        locale === 'am' ? caseItem.court?.name_am ?? caseItem.court?.name_en : caseItem.court?.name_en;
    const caseTypeName =
        locale === 'am'
            ? caseItem.case_type?.name_am ?? caseItem.case_type?.name_en
            : caseItem.case_type?.name_en;

    const latestHearing = hearings[0] ?? null;

    const tabs = [
        {
            key: 'overview',
            label: t('common.overview'),
            content: (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <DetailCard label={t('cases.court')} value={courtName} />
                    <DetailCard label={t('cases.case_type')} value={caseTypeName} />
                    <DetailCard label={t('cases.registrar')} value={caseItem.registered_by?.name} />
                    <DetailCard label={t('cases.next_hearing')} value={caseItem.next_hearing_date} />
                    <DetailCard label={t('cases.team_leader')} value={caseItem.assigned_team_leader?.name ?? t('common.unassigned')} />
                    <DetailCard label={t('cases.expert')} value={caseItem.assigned_legal_expert?.name ?? t('common.unassigned')} />
                    <DetailCard label={t('reports.priority')} value={caseItem.priority} />
                    <DetailCard label={t('reports.due_date')} value={caseItem.appeal_deadline} />
                    <DetailCard label={t('reports.completed_at')} value={caseItem.decision_date} />
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
                            <PanelTitle title={t('cases.director_review')} />
                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField label={t('cases.director_review')} required error={reviewForm.errors.director_decision}>
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
                                        label={t('cases.team_leader')}
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
                                    onClick={() => reviewForm.patch(route('cases.review', { legalCase: caseItem.id }))}
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
                            <PanelTitle title={t('cases.assign_expert')} />
                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField
                                    label={t('cases.expert')}
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
                                    onClick={() => assignForm.patch(route('cases.assign', { legalCase: caseItem.id }))}
                                    className="btn-base btn-primary focus-ring"
                                    disabled={assignForm.processing}
                                >
                                    {t('common.assign')}
                                </button>
                            </div>
                        </SurfaceCard>
                    ) : null}

                    {can.recordHearing ? (
                        <SurfaceCard>
                            <PanelTitle title={t('cases.record_hearing')} />
                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField label={t('cases.hearing_on')} required error={hearingForm.errors.hearing_date}>
                                    <input
                                        type="date"
                                        value={hearingForm.data.hearing_date}
                                        onChange={(event) => hearingForm.setData('hearing_date', event.target.value)}
                                        className="input-ui"
                                    />
                                </FormField>
                                <FormField
                                    label={t('cases.next_hearing')}
                                    optional
                                    error={hearingForm.errors.next_hearing_date}
                                >
                                    <input
                                        type="date"
                                        value={hearingForm.data.next_hearing_date}
                                        onChange={(event) => hearingForm.setData('next_hearing_date', event.target.value)}
                                        className="input-ui"
                                    />
                                </FormField>
                            </div>
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <FormField label={t('cases.hearing_summary')} required error={hearingForm.errors.summary}>
                                    <textarea
                                        value={hearingForm.data.summary}
                                        onChange={(event) => hearingForm.setData('summary', event.target.value)}
                                        rows={4}
                                        className="textarea-ui"
                                    />
                                </FormField>
                                <FormField
                                    label={t('cases.institution_position')}
                                    optional
                                    error={hearingForm.errors.institution_position}
                                >
                                    <textarea
                                        value={hearingForm.data.institution_position}
                                        onChange={(event) => hearingForm.setData('institution_position', event.target.value)}
                                        rows={4}
                                        className="textarea-ui"
                                    />
                                </FormField>
                            </div>
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <FormField
                                    label={t('cases.final_outcome')}
                                    optional
                                    error={hearingForm.errors.outcome}
                                >
                                    <textarea
                                        value={hearingForm.data.outcome}
                                        onChange={(event) => hearingForm.setData('outcome', event.target.value)}
                                        rows={3}
                                        className="textarea-ui"
                                    />
                                </FormField>
                                <FormField
                                    label={t('cases.court_decision')}
                                    optional
                                    error={hearingForm.errors.court_decision}
                                    hint={t('cases.hearing_decision_hint')}
                                >
                                    <textarea
                                        value={hearingForm.data.court_decision}
                                        onChange={(event) => hearingForm.setData('court_decision', event.target.value)}
                                        rows={3}
                                        className="textarea-ui"
                                    />
                                </FormField>
                            </div>
                            <div className="mt-5 flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => hearingForm.post(route('cases.hearings.store', { legalCase: caseItem.id }))}
                                    className="btn-base btn-primary focus-ring"
                                    disabled={hearingForm.processing}
                                >
                                    {t('cases.save_hearing')}
                                </button>
                            </div>
                        </SurfaceCard>
                    ) : null}

                    {can.close ? (
                        <SurfaceCard>
                            <PanelTitle title={t('cases.close_case')} />
                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField label={t('cases.final_outcome')} required error={closeForm.errors.outcome}>
                                    <textarea
                                        value={closeForm.data.outcome}
                                        onChange={(event) => closeForm.setData('outcome', event.target.value)}
                                        rows={5}
                                        className="textarea-ui"
                                    />
                                </FormField>
                                <div className="grid gap-4">
                                    <FormField
                                        label={t('reports.completed_at')}
                                        optional
                                        error={closeForm.errors.decision_date}
                                    >
                                        <input
                                            type="date"
                                            value={closeForm.data.decision_date}
                                            onChange={(event) => closeForm.setData('decision_date', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>
                                    <FormField
                                        label={t('reports.due_date')}
                                        optional
                                        error={closeForm.errors.appeal_deadline}
                                    >
                                        <input
                                            type="date"
                                            value={closeForm.data.appeal_deadline}
                                            onChange={(event) => closeForm.setData('appeal_deadline', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>
                                </div>
                            </div>
                            <div className="mt-5 flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setConfirmCloseOpen(true)}
                                    className="btn-base btn-primary focus-ring"
                                    disabled={closeForm.processing}
                                >
                                    {t('cases.close_case')}
                                </button>
                            </div>
                        </SurfaceCard>
                    ) : null}

                    {!workspace.canAssignTeamLeader && !workspace.canAssignExpert && !can.recordHearing && !can.close ? (
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
                { label: t('navigation.legal_cases'), href: route('cases.index') },
                { label: caseItem.case_number },
            ]}
        >
            <Head title={caseItem.case_number} />

            <PageContainer>
                <SectionHeader
                    eyebrow={caseItem.case_number}
                    title={`${caseItem.plaintiff} ${t('common.versus')} ${caseItem.defendant}`}
                    description={caseItem.claim_summary}
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={caseItem.status} />
                    <StatusBadge value={caseItem.priority} />
                    <StatusBadge value={caseItem.director_decision} />
                </div>

                <WorkflowProgress stages={stages} />

                <div className="grid gap-4 2xl:grid-cols-[minmax(0,1.55fr),minmax(20rem,0.95fr)]">
                    <div className="space-y-4">
                        <Tabs items={tabs} />
                    </div>

                    <div className="space-y-4">
                        <SurfaceCard>
                            <PanelTitle title={t('cases.record_hearing')} />
                            {latestHearing ? (
                                <div className="space-y-4">
                                    <div className="flex flex-wrap gap-2">
                                        <StatusBadge value={caseItem.status} />
                                        <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold uppercase text-[color:var(--muted-strong)]">
                                            {latestHearing.hearing_date}
                                        </span>
                                    </div>
                                    <p className="text-sm leading-6 text-[color:var(--muted-strong)]">
                                        {latestHearing.summary}
                                    </p>
                                    {latestHearing.court_decision ? (
                                        <p className="text-sm text-[color:var(--muted)]">
                                            {latestHearing.court_decision}
                                        </p>
                                    ) : null}
                                </div>
                            ) : (
                                <EmptyState
                                    title={t('cases.record_hearing')}
                                    description={t('common.not_available')}
                                />
                            )}
                        </SurfaceCard>

                        <SurfaceCard>
                            <PanelTitle title={t('common.internal_comment')} />
                            {can.comment ? (
                                <div className="space-y-4">
                                    <FormField label={t('common.add_internal_note')} required error={commentForm.errors.body}>
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
                                            onClick={() => commentForm.post(route('cases.comments.store', { legalCase: caseItem.id }))}
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
                                                attachmentForm.setData('attachments', Array.from(event.target.files ?? []))
                                            }
                                            className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                        />
                                    </FormField>
                                    <div className="flex flex-wrap justify-end gap-3">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                attachmentForm.post(
                                                    route('cases.attachments.store', { legalCase: caseItem.id }),
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
                open={confirmCloseOpen}
                title={t('cases.confirm_close')}
                description={t('cases.confirm_close_description')}
                confirmLabel={t('cases.close_case')}
                onCancel={() => setConfirmCloseOpen(false)}
                onConfirm={() =>
                    closeForm.patch(route('cases.close', { legalCase: caseItem.id }), {
                        onSuccess: () => setConfirmCloseOpen(false),
                    })
                }
                processing={closeForm.processing}
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
