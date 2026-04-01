import CommentItem from '@/Components/Ui/CommentItem';
import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import EmptyState from '@/Components/Ui/EmptyState';
import FileAttachmentCard from '@/Components/Ui/FileAttachmentCard';
import FormField from '@/Components/Ui/FormField';
import Modal from '@/Components/Modal';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import { WorkspacePanel, WorkspaceStatCard } from '@/Components/Ui/WorkspacePanel';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { finishSuccessfulSubmission } from '@/lib/form-submission';
import { useI18n } from '@/lib/i18n';
import { sanitizeRichTextHtml } from '@/lib/sanitize-rich-text';
import { Head, useForm, useRemember } from '@inertiajs/react';
import { type ReactNode, useMemo, useState } from 'react';

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

    const hearings = normalizeArray(caseItem.hearings);
    const comments = normalizeArray(caseItem.comments);
    const attachments = normalizeArray(caseItem.attachments);

    const { t, locale } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [activePanel, setActivePanel] = useRemember<'hearing' | 'close' | 'comment' | 'attachments' | null>(
        null,
        `cases-show-active-panel-${caseItem.id}`,
    );
    const [confirmCloseOpen, setConfirmCloseOpen] = useState(false);
    const [hearingModalMode, setHearingModalMode] = useState<'create' | 'view' | 'edit' | null>(null);
    const [selectedHearing, setSelectedHearing] = useState<any | null>(null);
    const [hearingToDelete, setHearingToDelete] = useState<any | null>(null);
    const [commentModalMode, setCommentModalMode] = useState<'create' | 'view' | 'edit' | null>(null);
    const [selectedComment, setSelectedComment] = useState<any | null>(null);
    const [commentToDelete, setCommentToDelete] = useState<any | null>(null);
    const [attachmentModalMode, setAttachmentModalMode] = useState<'create' | 'view' | 'edit' | null>(null);
    const [selectedAttachment, setSelectedAttachment] = useState<any | null>(null);
    const [attachmentToDelete, setAttachmentToDelete] = useState<any | null>(null);
    const [attachmentInputKey, setAttachmentInputKey] = useState(0);

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
    const attachmentEditForm = useForm({
        original_name: '',
    });
    const deleteHearingForm = useForm({});
    const deleteCommentForm = useForm({});
    const deleteAttachmentForm = useForm({});

    const courtName =
        locale === 'am' ? caseItem.court?.name_am ?? caseItem.court?.name_en : caseItem.court?.name_en;
    const caseTypeName =
        locale === 'am'
            ? caseItem.case_type?.name_am ?? caseItem.case_type?.name_en
            : caseItem.case_type?.name_en;

    const latestHearing = hearings[0] ?? null;
    const availableActionCount = [workspace.canAssignTeamLeader, workspace.canAssignExpert, can.recordHearing, can.close].filter(Boolean).length;
    const pageTitle =
        caseItem.plaintiff || caseItem.defendant
            ? `${caseItem.plaintiff ?? t('common.not_set')} ${t('common.versus')} ${caseItem.defendant ?? t('common.not_set')}`
            : caseItem.case_number;
    const sanitizedClaimSummaryHtml = useMemo(
        () => sanitizeRichTextHtml(caseItem.claim_summary),
        [caseItem.claim_summary],
    );

    const openCreateHearing = () => {
        hearingForm.reset();
        hearingForm.clearErrors();
        hearingForm.setData({
            hearing_date: '',
            next_hearing_date: '',
            appearance_status: 'attended',
            summary: '',
            institution_position: '',
            court_decision: '',
            outcome: '',
        });
        setSelectedHearing(null);
        setHearingModalMode('create');
    };

    const openEditHearing = (hearing: any) => {
        hearingForm.clearErrors();
        hearingForm.setData({
            hearing_date: hearing.hearing_date ?? '',
            next_hearing_date: hearing.next_hearing_date ?? '',
            appearance_status: hearing.appearance_status ?? 'attended',
            summary: hearing.summary ?? '',
            institution_position: hearing.institution_position ?? '',
            court_decision: hearing.court_decision ?? '',
            outcome: hearing.outcome ?? '',
        });
        setSelectedHearing(hearing);
        setHearingModalMode('edit');
    };

    const openCreateComment = () => {
        commentForm.reset();
        commentForm.clearErrors();
        commentForm.setData({
            body: '',
            is_internal: true,
        });
        setSelectedComment(null);
        setCommentModalMode('create');
    };

    const openEditComment = (comment: any) => {
        commentForm.clearErrors();
        commentForm.setData({
            body: comment.body ?? '',
            is_internal: true,
        });
        setSelectedComment(comment);
        setCommentModalMode('edit');
    };

    const openCreateAttachment = () => {
        attachmentForm.reset();
        attachmentForm.clearErrors();
        setAttachmentInputKey((current) => current + 1);
        setSelectedAttachment(null);
        setAttachmentModalMode('create');
    };

    const openEditAttachment = (attachment: any) => {
        attachmentEditForm.clearErrors();
        attachmentEditForm.setData({
            original_name: attachment.original_name ?? '',
        });
        setSelectedAttachment(attachment);
        setAttachmentModalMode('edit');
    };

    const legacyWorkspaceContent = (
        <SurfaceCard className="space-y-6 p-6">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold text-[color:var(--text)]">
                        {t('common.workspace')}
                    </h2>
                    {availableActionCount === 0 && !can.comment && !can.attach ? (
                        <p className="text-sm text-[color:var(--muted-strong)]">
                            {t('common.no_actions_available')}
                        </p>
                    ) : null}
                </div>
                <div className="flex flex-wrap gap-2">
                    {caseItem.assigned_team_leader?.name ? (
                        <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold text-[color:var(--muted-strong)]">
                            {t('cases.team_leader')}: {caseItem.assigned_team_leader.name}
                        </span>
                    ) : null}
                    {caseItem.assigned_legal_expert?.name ? (
                        <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold text-[color:var(--muted-strong)]">
                            {t('cases.expert')}: {caseItem.assigned_legal_expert.name}
                        </span>
                    ) : null}
                    {latestHearing?.hearing_date ? (
                        <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold text-[color:var(--muted-strong)]">
                            {t('cases.hearing_on')}: {latestHearing.hearing_date}
                        </span>
                    ) : null}
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                <WorkspaceStatCard
                    label={t('common.workspace')}
                    value={availableActionCount}
                    helper={availableActionCount > 0 ? caseItem.status : t('common.no_actions_available')}
                    icon={<WorkspaceIcon kind="workspace" />}
                />
                <WorkspaceStatCard
                    label={t('cases.team_leader')}
                    value={caseItem.assigned_team_leader?.name ?? t('common.unassigned')}
                    helper={caseItem.director_decision ?? t('common.not_available')}
                    icon={<WorkspaceIcon kind="assignment" />}
                />
                <WorkspaceStatCard
                    label={t('cases.expert')}
                    value={caseItem.assigned_legal_expert?.name ?? t('common.unassigned')}
                    helper={caseItem.workflow_stage ?? t('common.not_available')}
                    icon={<WorkspaceIcon kind="expert" />}
                />
                <WorkspaceStatCard
                    label={t('cases.record_hearing')}
                    value={hearings.length}
                    helper={`${attachments.length} ${t('common.attachments')}`}
                    icon={<WorkspaceIcon kind="hearing" />}
                />
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                {workspace.canAssignTeamLeader ? (
                    <WorkspacePanel
                        eyebrow={t('common.workspace')}
                        title={t('cases.director_review')}
                        description={t('advisory.confirm_review_description')}
                        tone="accent"
                        icon={<WorkspaceIcon kind="review" />}
                        footer={
                            <div className="flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() =>
                                        reviewForm.patch(route('cases.review', { legalCase: caseItem.id }), {
                                            onSuccess: () => {
                                                finishSuccessfulSubmission(reviewForm, {
                                                    reset: true,
                                                });
                                            },
                                        })
                                    }
                                    className="btn-base btn-primary focus-ring"
                                    disabled={reviewForm.processing}
                                >
                                    {t('common.submit_review')}
                                </button>
                            </div>
                        }
                    >
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
                            ) : (
                                <WorkspaceInfoRow
                                    label={t('cases.team_leader')}
                                    value={caseItem.assigned_team_leader?.name ?? t('common.unassigned')}
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
                    </WorkspacePanel>
                ) : null}

                {workspace.canAssignExpert ? (
                    <WorkspacePanel
                        eyebrow={t('common.assignment')}
                        title={t('cases.assign_expert')}
                        description={caseItem.assigned_team_leader?.name ?? t('common.unassigned')}
                        tone="accent"
                        icon={<WorkspaceIcon kind="expert" />}
                        footer={
                            <div className="flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() =>
                                        assignForm.patch(route('cases.assign', { legalCase: caseItem.id }), {
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
                            </div>
                        }
                    >
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
                                    rows={5}
                                    className="textarea-ui"
                                />
                            </FormField>
                        </div>
                    </WorkspacePanel>
                ) : null}

                {can.recordHearing ? (
                    <WorkspacePanel
                        eyebrow={t('cases.record_hearing')}
                        title={t('cases.record_hearing')}
                        description={caseItem.next_hearing_date ?? t('common.not_available')}
                        tone="success"
                        icon={<WorkspaceIcon kind="hearing" />}
                        className="xl:col-span-2"
                        footer={
                            <div className="flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() =>
                                        hearingForm.post(route('cases.hearings.store', { legalCase: caseItem.id }), {
                                            onSuccess: () => {
                                                finishSuccessfulSubmission(hearingForm, {
                                                    reset: true,
                                                });
                                            },
                                        })
                                    }
                                    className="btn-base btn-primary focus-ring"
                                    disabled={hearingForm.processing}
                                >
                                    {t('cases.save_hearing')}
                                </button>
                            </div>
                        }
                    >
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

                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('cases.hearing_summary')} required error={hearingForm.errors.summary}>
                                <textarea
                                    value={hearingForm.data.summary}
                                    onChange={(event) => hearingForm.setData('summary', event.target.value)}
                                    rows={5}
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
                                    rows={5}
                                    className="textarea-ui"
                                />
                            </FormField>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField
                                label={t('cases.final_outcome')}
                                optional
                                error={hearingForm.errors.outcome}
                            >
                                <textarea
                                    value={hearingForm.data.outcome}
                                    onChange={(event) => hearingForm.setData('outcome', event.target.value)}
                                    rows={4}
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
                                    rows={4}
                                    className="textarea-ui"
                                />
                            </FormField>
                        </div>
                    </WorkspacePanel>
                ) : null}

                {can.close ? (
                    <WorkspacePanel
                        eyebrow={t('cases.close_case')}
                        title={t('cases.close_case')}
                        description={caseItem.outcome ?? t('common.not_available')}
                        tone="warning"
                        icon={<WorkspaceIcon kind="close" />}
                        className="xl:col-span-2"
                        footer={
                            <div className="flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setConfirmCloseOpen(true)}
                                    className="btn-base btn-primary focus-ring"
                                    disabled={closeForm.processing}
                                >
                                    {t('cases.close_case')}
                                </button>
                            </div>
                        }
                    >
                        <div className="grid gap-4 md:grid-cols-[minmax(0,1.2fr),minmax(16rem,0.8fr)]">
                            <FormField label={t('cases.final_outcome')} required error={closeForm.errors.outcome}>
                                <textarea
                                    value={closeForm.data.outcome}
                                    onChange={(event) => closeForm.setData('outcome', event.target.value)}
                                    rows={6}
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
                    </WorkspacePanel>
                ) : null}

                {!workspace.canAssignTeamLeader && !workspace.canAssignExpert && !can.recordHearing && !can.close ? (
                    <div className="xl:col-span-2">
                        <EmptyState
                            title={t('common.workspace')}
                            description={t('common.no_actions_available')}
                        />
                    </div>
                ) : null}
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                <WorkspacePanel
                    eyebrow={t('common.workspace')}
                    title={t('common.internal_comment')}
                    tone="default"
                    icon={<WorkspaceIcon kind="comment" />}
                    actions={<WorkspaceCountBadge value={comments.length} />}
                    footer={
                        can.comment ? (
                            <div className="flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() =>
                                        commentForm.post(route('cases.comments.store', { legalCase: caseItem.id }), {
                                            onSuccess: () => {
                                                finishSuccessfulSubmission(commentForm, {
                                                    reset: ['body'],
                                                });
                                            },
                                        })
                                    }
                                    className="btn-base btn-secondary focus-ring"
                                    disabled={commentForm.processing}
                                >
                                    {t('common.add_comment')}
                                </button>
                            </div>
                        ) : undefined
                    }
                >
                    {can.comment ? (
                        <FormField label={t('common.add_internal_note')} required error={commentForm.errors.body}>
                            <textarea
                                value={commentForm.data.body}
                                onChange={(event) => commentForm.setData('body', event.target.value)}
                                rows={4}
                                className="textarea-ui"
                            />
                        </FormField>
                    ) : null}

                    <div className="space-y-3">
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
                </WorkspacePanel>

                <WorkspacePanel
                    eyebrow={t('common.workspace')}
                    title={t('common.attachments')}
                    tone="default"
                    icon={<WorkspaceIcon kind="attachment" />}
                    actions={<WorkspaceCountBadge value={attachments.length} />}
                    footer={
                        can.attach ? (
                            <div className="flex flex-wrap justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() =>
                                        attachmentForm.post(
                                            route('cases.attachments.store', { legalCase: caseItem.id }),
                                            {
                                                forceFormData: true,
                                                onSuccess: () => {
                                                    finishSuccessfulSubmission(attachmentForm, {
                                                        reset: ['attachments'],
                                                        afterSuccess: () => {
                                                            setAttachmentInputKey((current) => current + 1);
                                                        },
                                                    });
                                                },
                                            },
                                        )
                                    }
                                    className="btn-base btn-secondary focus-ring"
                                    disabled={attachmentForm.processing}
                                >
                                    {t('common.upload_files')}
                                </button>
                            </div>
                        ) : undefined
                    }
                >
                    {can.attach ? (
                        <FormField
                            label={t('common.attachments')}
                            optional
                            error={attachmentForm.errors.attachments as string | undefined}
                        >
                            <input
                                key={attachmentInputKey}
                                type="file"
                                multiple
                                onChange={(event) =>
                                    attachmentForm.setData('attachments', Array.from(event.target.files ?? []))
                                }
                                className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                            />
                        </FormField>
                    ) : null}

                    <div className="space-y-3">
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
                </WorkspacePanel>
            </div>
        </SurfaceCard>
    );

    void legacyWorkspaceContent;

    const hearingPanelEnabled = can.recordHearing || hearings.length > 0;
    const commentPanelEnabled = can.comment || comments.length > 0;
    const attachmentPanelEnabled = can.attach || attachments.length > 0;

    const workspaceContent = (
        <div className="space-y-4">
            <SurfaceCard className="space-y-4 p-6">
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('common.workspace')}</h2>
                </div>

                <div className="flex flex-wrap gap-3">
                    {hearingPanelEnabled ? (
                        <ActionToggleButton
                            label={t('cases.record_hearing')}
                            active={activePanel === 'hearing'}
                            onClick={() => setActivePanel((current) => (current === 'hearing' ? null : 'hearing'))}
                        />
                    ) : null}
                    {can.close ? (
                        <ActionToggleButton
                            label={t('cases.close_case')}
                            active={activePanel === 'close'}
                            onClick={() => setActivePanel((current) => (current === 'close' ? null : 'close'))}
                        />
                    ) : null}
                    {commentPanelEnabled ? (
                        <ActionToggleButton
                            label={t('common.internal_comment')}
                            active={activePanel === 'comment'}
                            onClick={() => setActivePanel((current) => (current === 'comment' ? null : 'comment'))}
                        />
                    ) : null}
                    {attachmentPanelEnabled ? (
                        <ActionToggleButton
                            label={t('common.attachments')}
                            active={activePanel === 'attachments'}
                            onClick={() => setActivePanel((current) => (current === 'attachments' ? null : 'attachments'))}
                        />
                    ) : null}
                </div>

                {!hearingPanelEnabled && !can.close && !commentPanelEnabled && !attachmentPanelEnabled ? (
                    <EmptyState
                        title={t('common.workspace')}
                        description={t('common.no_actions_available')}
                    />
                ) : null}
            </SurfaceCard>

            {activePanel === 'hearing' ? (
                <SurfaceCard className="space-y-6 p-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1">
                            <h3 className="text-lg font-semibold text-[color:var(--text)]">
                                {t('cases.record_hearing')}
                            </h3>
                            <p className="text-sm text-[color:var(--muted-strong)]">
                                {t('cases.next_hearing')}: {caseItem.next_hearing_date ?? t('common.not_available')}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {can.recordHearing ? (
                                <button
                                    type="button"
                                    onClick={openCreateHearing}
                                    className="btn-base btn-primary focus-ring"
                                >
                                    {t('cases.add_hearing')}
                                </button>
                            ) : null}
                            <button
                                type="button"
                                onClick={() => setActivePanel(null)}
                                className="btn-base btn-secondary focus-ring"
                            >
                                {t('common.cancel')}
                            </button>
                        </div>
                    </div>

                    {hearings.length === 0 ? (
                        <EmptyState
                            title={t('cases.record_hearing')}
                            description={t('common.not_available')}
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-[color:var(--border)] text-sm">
                                <thead className="bg-[color:var(--surface-muted)] text-left text-xs font-semibold uppercase tracking-wide text-[color:var(--muted-strong)]">
                                    <tr>
                                        <th className="px-4 py-3">#</th>
                                        <th className="px-4 py-3">{t('cases.hearing_on')}</th>
                                        <th className="px-4 py-3">{t('cases.next_hearing')}</th>
                                        <th className="px-4 py-3">{t('cases.hearing_summary')}</th>
                                        <th className="px-4 py-3">{t('common.status')}</th>
                                        <th className="px-4 py-3">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[color:var(--border)]">
                                    {hearings.map((hearing: any, index: number) => (
                                        <tr key={hearing.id} className="align-top">
                                            <td className="px-4 py-3 text-[color:var(--muted-strong)]">{index + 1}</td>
                                            <td className="px-4 py-3">{hearing.hearing_date ?? t('common.not_available')}</td>
                                            <td className="px-4 py-3">{hearing.next_hearing_date ?? t('common.not_available')}</td>
                                            <td className="px-4 py-3">
                                                <div className="max-w-md">
                                                    <p className="line-clamp-2 text-[color:var(--text)]">{hearing.summary ?? t('common.not_available')}</p>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge value={hearing.appearance_status} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap gap-2">
                                                    <InlineActionButton label={t('common.view')} onClick={() => {
                                                        setSelectedHearing(hearing);
                                                        setHearingModalMode('view');
                                                    }} />
                                                    {hearing.can_update ? (
                                                        <InlineActionButton label={t('common.edit')} onClick={() => openEditHearing(hearing)} />
                                                    ) : null}
                                                    {hearing.can_delete ? (
                                                        <InlineActionButton
                                                            label={t('common.delete')}
                                                            tone="danger"
                                                            onClick={() => setHearingToDelete(hearing)}
                                                        />
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </SurfaceCard>
            ) : null}

            {activePanel === 'close' ? (
                <SurfaceCard className="space-y-6 p-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1">
                            <h3 className="text-lg font-semibold text-[color:var(--text)]">
                                {t('cases.close_case')}
                            </h3>
                            <p className="text-sm text-[color:var(--muted-strong)]">
                                {caseItem.outcome ?? t('common.not_available')}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setActivePanel(null)}
                            className="btn-base btn-secondary focus-ring"
                        >
                            {t('common.cancel')}
                        </button>
                    </div>

                    <div className="grid gap-4 md:grid-cols-[minmax(0,1.2fr),minmax(16rem,0.8fr)]">
                        <FormField label={t('cases.final_outcome')} required error={closeForm.errors.outcome}>
                            <textarea
                                value={closeForm.data.outcome}
                                onChange={(event) => closeForm.setData('outcome', event.target.value)}
                                rows={6}
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

                    <div className="flex flex-wrap justify-end gap-3 border-t border-[color:var(--border)] pt-4">
                        <button
                            type="button"
                            onClick={() => setActivePanel(null)}
                            className="btn-base btn-secondary focus-ring"
                        >
                            {t('common.cancel')}
                        </button>
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

            {activePanel === 'comment' ? (
                <SurfaceCard className="space-y-6 p-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1">
                            <h3 className="text-lg font-semibold text-[color:var(--text)]">
                                {t('common.internal_comment')}
                            </h3>
                            <p className="text-sm text-[color:var(--muted-strong)]">
                                {comments.length} {t('common.internal_comment')}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {can.comment ? (
                                <button
                                    type="button"
                                    onClick={openCreateComment}
                                    className="btn-base btn-primary focus-ring"
                                >
                                    {t('common.add_comment')}
                                </button>
                            ) : null}
                            <button
                                type="button"
                                onClick={() => setActivePanel(null)}
                                className="btn-base btn-secondary focus-ring"
                            >
                                {t('common.cancel')}
                            </button>
                        </div>
                    </div>

                    {comments.length === 0 ? (
                        <EmptyState
                            title={t('common.internal_comment')}
                            description={t('common.no_comments')}
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-[color:var(--border)] text-sm">
                                <thead className="bg-[color:var(--surface-muted)] text-left text-xs font-semibold uppercase tracking-wide text-[color:var(--muted-strong)]">
                                    <tr>
                                        <th className="px-4 py-3">#</th>
                                        <th className="px-4 py-3">{t('common.date')}</th>
                                        <th className="px-4 py-3">{t('common.actor')}</th>
                                        <th className="px-4 py-3">{t('common.internal_comment')}</th>
                                        <th className="px-4 py-3">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[color:var(--border)]">
                                    {comments.map((comment: any, index: number) => (
                                        <tr key={comment.id} className="align-top">
                                            <td className="px-4 py-3 text-[color:var(--muted-strong)]">{index + 1}</td>
                                            <td className="px-4 py-3">{formatDateTime(comment.created_at)}</td>
                                            <td className="px-4 py-3">{comment.user?.name ?? t('common.not_available')}</td>
                                            <td className="px-4 py-3">
                                                <div className="max-w-md">
                                                    <p className="line-clamp-2 text-[color:var(--text)]">{comment.body}</p>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap gap-2">
                                                    <InlineActionButton label={t('common.view')} onClick={() => {
                                                        setSelectedComment(comment);
                                                        setCommentModalMode('view');
                                                    }} />
                                                    {comment.can_update ? (
                                                        <InlineActionButton label={t('common.edit')} onClick={() => openEditComment(comment)} />
                                                    ) : null}
                                                    {comment.can_delete ? (
                                                        <InlineActionButton
                                                            label={t('common.delete')}
                                                            tone="danger"
                                                            onClick={() => setCommentToDelete(comment)}
                                                        />
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </SurfaceCard>
            ) : null}

            {activePanel === 'attachments' ? (
                <SurfaceCard className="space-y-6 p-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-1">
                            <h3 className="text-lg font-semibold text-[color:var(--text)]">
                                {t('common.attachments')}
                            </h3>
                            <p className="text-sm text-[color:var(--muted-strong)]">
                                {attachments.length} {t('common.attachments')}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {can.attach ? (
                                <button
                                    type="button"
                                    onClick={openCreateAttachment}
                                    className="btn-base btn-primary focus-ring"
                                >
                                    {t('common.add_attachment')}
                                </button>
                            ) : null}
                            <button
                                type="button"
                                onClick={() => setActivePanel(null)}
                                className="btn-base btn-secondary focus-ring"
                            >
                                {t('common.cancel')}
                            </button>
                        </div>
                    </div>

                    {attachments.length === 0 ? (
                        <EmptyState
                            title={t('common.attachments')}
                            description={t('common.no_attachments')}
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-[color:var(--border)] text-sm">
                                <thead className="bg-[color:var(--surface-muted)] text-left text-xs font-semibold uppercase tracking-wide text-[color:var(--muted-strong)]">
                                    <tr>
                                        <th className="px-4 py-3">#</th>
                                        <th className="px-4 py-3">{t('common.file_name')}</th>
                                        <th className="px-4 py-3">{t('common.type')}</th>
                                        <th className="px-4 py-3">{t('common.actor')}</th>
                                        <th className="px-4 py-3">{t('common.date')}</th>
                                        <th className="px-4 py-3">{t('common.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[color:var(--border)]">
                                    {attachments.map((attachment: any, index: number) => (
                                        <tr key={attachment.id} className="align-top">
                                            <td className="px-4 py-3 text-[color:var(--muted-strong)]">{index + 1}</td>
                                            <td className="px-4 py-3">{attachment.original_name}</td>
                                            <td className="px-4 py-3">{attachment.mime_type ?? t('common.not_available')}</td>
                                            <td className="px-4 py-3">{attachment.uploaded_by ?? t('common.not_available')}</td>
                                            <td className="px-4 py-3">{formatDateTime(attachment.created_at)}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap gap-2">
                                                    <a
                                                        href={attachment.view_url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="btn-base bg-[color:var(--surface-muted)] text-[color:var(--text)]"
                                                    >
                                                        {t('common.view')}
                                                    </a>
                                                    {attachment.can_update ? (
                                                        <InlineActionButton label={t('common.edit')} onClick={() => openEditAttachment(attachment)} />
                                                    ) : null}
                                                    {attachment.can_delete ? (
                                                        <InlineActionButton
                                                            label={t('common.delete')}
                                                            tone="danger"
                                                            onClick={() => setAttachmentToDelete(attachment)}
                                                        />
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </SurfaceCard>
            ) : null}
        </div>
    );

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.legal_cases'), href: route('cases.index') },
                { label: caseItem.case_number },
            ]}
        >
            <Head title={caseItem.case_number} />

            <PageContainer className="space-y-6">
                <SectionHeader eyebrow={caseItem.case_number} title={pageTitle} />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={caseItem.status} />
                    <StatusBadge value={caseItem.priority} />
                    <StatusBadge value={caseItem.director_decision} />
                </div>

                <SurfaceCard className="space-y-5 p-6">
                    <div className="space-y-1">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">
                            {t('common.overview')}
                        </h2>
                    </div>

                    <dl className="grid gap-x-8 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                        <OverviewItem label={t('cases.case_number')} value={caseItem.case_number} />
                        <OverviewItem
                            label={t('cases.main_case_type_label')}
                            value={caseItem.main_case_type ? t(`cases.main_case_type.${caseItem.main_case_type}`) : undefined}
                        />
                        <OverviewItem label={t('cases.registrar')} value={caseItem.registered_by?.name} />
                        <OverviewItem label={t('cases.court')} value={courtName} />
                        <OverviewItem label={t('cases.case_type')} value={caseTypeName} />
                        <OverviewItem label={t('cases.court_file_number')} value={caseItem.external_court_file_number} />
                        <OverviewItem label={t('cases.team_leader')} value={caseItem.assigned_team_leader?.name ?? t('common.unassigned')} />
                        <OverviewItem label={t('cases.expert')} value={caseItem.assigned_legal_expert?.name ?? t('common.unassigned')} />
                        <OverviewItem label={t('cases.next_hearing')} value={caseItem.next_hearing_date} />
                        <OverviewItem label={t('cases.plaintiff')} value={caseItem.plaintiff} />
                        <OverviewItem label={t('cases.defendant')} value={caseItem.defendant} />
                        <OverviewItem label={t('cases.amount')} value={caseItem.amount} />
                        <OverviewItem label={t('cases.crime_scene')} value={caseItem.crime_scene} />
                        <OverviewItem label={t('cases.police_station')} value={caseItem.police_station} />
                        <OverviewItem label={t('cases.stolen_property_type')} value={caseItem.stolen_property_type} />
                        <OverviewItem label={t('cases.statement_date')} value={caseItem.statement_date} />
                    </dl>

                    <div className="border-t border-[color:var(--border)] pt-5">
                        <p className="text-sm font-semibold text-[color:var(--muted-strong)]">
                            {t('cases.detailed_description')}
                        </p>
                        {sanitizedClaimSummaryHtml ? (
                            <div
                                className="prose prose-sm mt-4 max-w-none text-[color:var(--text)] dark:prose-invert"
                                dangerouslySetInnerHTML={{ __html: sanitizedClaimSummaryHtml }}
                            />
                        ) : (
                            <p className="mt-3 text-sm leading-7 text-[color:var(--text)]">
                                {t('common.not_available')}
                            </p>
                        )}
                    </div>
                </SurfaceCard>

                {workspaceContent}
            </PageContainer>

            <Modal
                show={hearingModalMode !== null}
                maxWidth="2xl"
                onClose={() => {
                    setHearingModalMode(null);
                    setSelectedHearing(null);
                }}
            >
                <div className="space-y-6 p-6">
                    <div className="space-y-1">
                        <h3 className="text-lg font-semibold text-[color:var(--text)]">
                            {hearingModalMode === 'edit'
                                ? t('common.edit')
                                : hearingModalMode === 'view'
                                  ? t('common.view')
                                  : t('cases.add_hearing')}
                        </h3>
                    </div>

                    {hearingModalMode === 'view' ? (
                        <dl className="grid gap-4 md:grid-cols-2">
                            <OverviewItem label={t('cases.hearing_on')} value={selectedHearing?.hearing_date} />
                            <OverviewItem label={t('cases.next_hearing')} value={selectedHearing?.next_hearing_date} />
                            <OverviewItem label={t('common.status')} value={selectedHearing?.appearance_status} />
                            <OverviewItem label={t('common.actor')} value={selectedHearing?.recorded_by} />
                            <OverviewItem label={t('cases.hearing_summary')} value={selectedHearing?.summary} />
                            <OverviewItem label={t('cases.court_decision')} value={selectedHearing?.court_decision} />
                        </dl>
                    ) : (
                        <>
                            <div className="space-y-4">
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
                                <FormField label={t('cases.hearing_summary')} required error={hearingForm.errors.summary}>
                                    <textarea
                                        value={hearingForm.data.summary}
                                        onChange={(event) => hearingForm.setData('summary', event.target.value)}
                                        rows={6}
                                        className="textarea-ui"
                                    />
                                </FormField>
                                <FormField
                                    label={t('cases.court_decision')}
                                    optional
                                    error={hearingForm.errors.court_decision}
                                >
                                    <textarea
                                        value={hearingForm.data.court_decision}
                                        onChange={(event) => hearingForm.setData('court_decision', event.target.value)}
                                        rows={6}
                                        className="textarea-ui"
                                    />
                                </FormField>
                            </div>
                        </>
                    )}

                    <div className="flex flex-wrap justify-end gap-3 border-t border-[color:var(--border)] pt-4">
                        <button
                            type="button"
                            onClick={() => {
                                setHearingModalMode(null);
                                setSelectedHearing(null);
                            }}
                            className="btn-base btn-secondary focus-ring"
                        >
                            {t('common.cancel')}
                        </button>
                        {hearingModalMode !== 'view' ? (
                            <button
                                type="button"
                                onClick={() => {
                                    const options = {
                                        onSuccess: () => {
                                            finishSuccessfulSubmission(hearingForm, {
                                                reset: true,
                                                afterSuccess: () => {
                                                    setHearingModalMode(null);
                                                    setSelectedHearing(null);
                                                },
                                            });
                                        },
                                    };

                                    if (hearingModalMode === 'edit' && selectedHearing) {
                                        hearingForm.patch(route('cases.hearings.update', {
                                            legalCase: caseItem.id,
                                            hearing: selectedHearing.id,
                                        }), options);

                                        return;
                                    }

                                    hearingForm.post(route('cases.hearings.store', { legalCase: caseItem.id }), options);
                                }}
                                className="btn-base btn-primary focus-ring"
                                disabled={hearingForm.processing}
                            >
                                {hearingModalMode === 'edit' ? t('common.save_changes') : t('cases.save_hearing')}
                            </button>
                        ) : null}
                    </div>
                </div>
            </Modal>

            <Modal
                show={commentModalMode !== null}
                maxWidth="xl"
                onClose={() => {
                    setCommentModalMode(null);
                    setSelectedComment(null);
                }}
            >
                <div className="space-y-6 p-6">
                    <div className="space-y-1">
                        <h3 className="text-lg font-semibold text-[color:var(--text)]">
                            {commentModalMode === 'edit'
                                ? t('common.edit')
                                : commentModalMode === 'view'
                                  ? t('common.view')
                                  : t('common.add_comment')}
                        </h3>
                    </div>

                    {commentModalMode === 'view' ? (
                        <div className="space-y-4">
                            <OverviewItem label={t('common.actor')} value={selectedComment?.user?.name} />
                            <OverviewItem label={t('common.date')} value={formatDateTime(selectedComment?.created_at)} />
                            <div className="space-y-2">
                                <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                                    {t('common.internal_comment')}
                                </p>
                                <div className="rounded-2xl border border-[color:var(--border)] p-4 text-sm leading-7 text-[color:var(--text)]">
                                    {selectedComment?.body ?? t('common.not_available')}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <FormField label={t('common.add_internal_note')} required error={commentForm.errors.body}>
                            <textarea
                                value={commentForm.data.body}
                                onChange={(event) => commentForm.setData('body', event.target.value)}
                                rows={6}
                                className="textarea-ui"
                            />
                        </FormField>
                    )}

                    <div className="flex flex-wrap justify-end gap-3 border-t border-[color:var(--border)] pt-4">
                        <button
                            type="button"
                            onClick={() => {
                                setCommentModalMode(null);
                                setSelectedComment(null);
                            }}
                            className="btn-base btn-secondary focus-ring"
                        >
                            {t('common.cancel')}
                        </button>
                        {commentModalMode !== 'view' ? (
                            <button
                                type="button"
                                onClick={() => {
                                    const options = {
                                        onSuccess: () => {
                                            finishSuccessfulSubmission(commentForm, {
                                                reset: ['body'],
                                                afterSuccess: () => {
                                                    setCommentModalMode(null);
                                                    setSelectedComment(null);
                                                },
                                            });
                                        },
                                    };

                                    if (commentModalMode === 'edit' && selectedComment) {
                                        commentForm.patch(route('cases.comments.update', {
                                            legalCase: caseItem.id,
                                            comment: selectedComment.id,
                                        }), options);

                                        return;
                                    }

                                    commentForm.post(route('cases.comments.store', { legalCase: caseItem.id }), options);
                                }}
                                className="btn-base btn-primary focus-ring"
                                disabled={commentForm.processing}
                            >
                                {commentModalMode === 'edit' ? t('common.save_changes') : t('common.add_comment')}
                            </button>
                        ) : null}
                    </div>
                </div>
            </Modal>

            <Modal
                show={attachmentModalMode !== null}
                maxWidth="xl"
                onClose={() => {
                    setAttachmentModalMode(null);
                    setSelectedAttachment(null);
                }}
            >
                <div className="space-y-6 p-6">
                    <div className="space-y-1">
                        <h3 className="text-lg font-semibold text-[color:var(--text)]">
                            {attachmentModalMode === 'edit'
                                ? t('common.edit')
                                : attachmentModalMode === 'view'
                                  ? t('common.view')
                                  : t('common.add_attachment')}
                        </h3>
                    </div>

                    {attachmentModalMode === 'view' ? (
                        <dl className="grid gap-4 md:grid-cols-2">
                            <OverviewItem label={t('common.file_name')} value={selectedAttachment?.original_name} />
                            <OverviewItem label={t('common.type')} value={selectedAttachment?.mime_type} />
                            <OverviewItem label={t('common.actor')} value={selectedAttachment?.uploaded_by} />
                            <OverviewItem label={t('common.date')} value={formatDateTime(selectedAttachment?.created_at)} />
                        </dl>
                    ) : attachmentModalMode === 'edit' ? (
                        <FormField label={t('common.file_name')} required error={attachmentEditForm.errors.original_name}>
                            <input
                                type="text"
                                value={attachmentEditForm.data.original_name}
                                onChange={(event) => attachmentEditForm.setData('original_name', event.target.value)}
                                className="input-ui"
                            />
                        </FormField>
                    ) : (
                        <FormField
                            label={t('common.attachments')}
                            optional
                            error={attachmentForm.errors.attachments as string | undefined}
                        >
                            <input
                                key={attachmentInputKey}
                                type="file"
                                multiple
                                onChange={(event) =>
                                    attachmentForm.setData('attachments', Array.from(event.target.files ?? []))
                                }
                                className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                            />
                        </FormField>
                    )}

                    <div className="flex flex-wrap justify-end gap-3 border-t border-[color:var(--border)] pt-4">
                        <button
                            type="button"
                            onClick={() => {
                                setAttachmentModalMode(null);
                                setSelectedAttachment(null);
                            }}
                            className="btn-base btn-secondary focus-ring"
                        >
                            {t('common.cancel')}
                        </button>
                        {attachmentModalMode === 'edit' ? (
                            <button
                                type="button"
                                onClick={() => {
                                    if (!selectedAttachment?.update_url) {
                                        return;
                                    }

                                    attachmentEditForm.patch(selectedAttachment.update_url, {
                                        onSuccess: () => {
                                            finishSuccessfulSubmission(attachmentEditForm, {
                                                reset: true,
                                                afterSuccess: () => {
                                                    setAttachmentModalMode(null);
                                                    setSelectedAttachment(null);
                                                },
                                            });
                                        },
                                    });
                                }}
                                className="btn-base btn-primary focus-ring"
                                disabled={attachmentEditForm.processing}
                            >
                                {t('common.save_changes')}
                            </button>
                        ) : attachmentModalMode === 'create' ? (
                            <button
                                type="button"
                                onClick={() =>
                                    attachmentForm.post(route('cases.attachments.store', { legalCase: caseItem.id }), {
                                        forceFormData: true,
                                        onSuccess: () => {
                                            finishSuccessfulSubmission(attachmentForm, {
                                                reset: ['attachments'],
                                                afterSuccess: () => {
                                                    setAttachmentInputKey((current) => current + 1);
                                                    setAttachmentModalMode(null);
                                                    setSelectedAttachment(null);
                                                },
                                            });
                                        },
                                    })
                                }
                                className="btn-base btn-primary focus-ring"
                                disabled={attachmentForm.processing}
                            >
                                {t('common.upload_files')}
                            </button>
                        ) : selectedAttachment?.view_url ? (
                            <a
                                href={selectedAttachment.view_url}
                                target="_blank"
                                rel="noreferrer"
                                className="btn-base btn-primary focus-ring"
                            >
                                {t('common.view')}
                            </a>
                        ) : null}
                    </div>
                </div>
            </Modal>

            <ConfirmationDialog
                open={confirmCloseOpen}
                title={t('cases.confirm_close')}
                description={t('cases.confirm_close_description')}
                confirmLabel={t('cases.close_case')}
                onCancel={() => setConfirmCloseOpen(false)}
                onConfirm={() =>
                    closeForm.patch(route('cases.close', { legalCase: caseItem.id }), {
                        onSuccess: () => {
                            finishSuccessfulSubmission(closeForm, {
                                reset: true,
                                afterSuccess: () => {
                                    setConfirmCloseOpen(false);
                                },
                            });
                        },
                    })
                }
                processing={closeForm.processing}
            />

            <ConfirmationDialog
                open={hearingToDelete !== null}
                title={t('common.delete')}
                description={t('cases.delete_hearing_description')}
                confirmLabel={t('common.delete')}
                onCancel={() => setHearingToDelete(null)}
                onConfirm={() => {
                    if (!hearingToDelete?.id) {
                        return;
                    }

                    deleteHearingForm.delete(route('cases.hearings.destroy', {
                        legalCase: caseItem.id,
                        hearing: hearingToDelete.id,
                    }), {
                        onSuccess: () => {
                            finishSuccessfulSubmission(deleteHearingForm, {
                                afterSuccess: () => setHearingToDelete(null),
                            });
                        },
                    });
                }}
                processing={deleteHearingForm.processing}
            />

            <ConfirmationDialog
                open={commentToDelete !== null}
                title={t('common.delete')}
                description={t('comments.delete_description')}
                confirmLabel={t('common.delete')}
                onCancel={() => setCommentToDelete(null)}
                onConfirm={() => {
                    if (!commentToDelete?.id) {
                        return;
                    }

                    deleteCommentForm.delete(route('cases.comments.destroy', {
                        legalCase: caseItem.id,
                        comment: commentToDelete.id,
                    }), {
                        onSuccess: () => {
                            finishSuccessfulSubmission(deleteCommentForm, {
                                afterSuccess: () => setCommentToDelete(null),
                            });
                        },
                    });
                }}
                processing={deleteCommentForm.processing}
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

function InlineActionButton({
    label,
    onClick,
    tone = 'default',
}: {
    label: string;
    onClick: () => void;
    tone?: 'default' | 'danger';
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`btn-base ${
                tone === 'danger'
                    ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/30'
                    : 'bg-[color:var(--surface-muted)] text-[color:var(--text)]'
            }`}
        >
            {label}
        </button>
    );
}

function ActionToggleButton({
    active,
    label,
    onClick,
}: {
    active: boolean;
    label: string;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`btn-base focus-ring ${
                active
                    ? 'bg-[color:var(--primary)] text-white shadow-sm'
                    : 'bg-[color:var(--surface-muted)] text-[color:var(--text)]'
            }`}
        >
            {label}
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

function WorkspaceIcon({
    kind,
}: {
    kind: 'workspace' | 'assignment' | 'expert' | 'attachment' | 'review' | 'hearing' | 'comment' | 'timeline' | 'close';
}) {
    const paths: Record<typeof kind, ReactNode> = {
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
        hearing: (
            <>
                <path d="M7 4v3M17 4v3M4.5 8.5h15" />
                <path d="M6.5 6h11A1.5 1.5 0 0 1 19 7.5v10A2.5 2.5 0 0 1 16.5 20h-9A2.5 2.5 0 0 1 5 17.5v-10A1.5 1.5 0 0 1 6.5 6Z" />
                <path d="M9 12h6M9 15.5h3.5" />
            </>
        ),
        comment: (
            <>
                <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7a2.5 2.5 0 0 1-2.5 2.5H10l-4 4v-4H6.5A2.5 2.5 0 0 1 4 13.5v-7Z" />
                <path d="M8 8.5h8M8 11.5h5" />
            </>
        ),
        timeline: (
            <>
                <path d="M12 5v14" />
                <path d="M6 8.5h4M14 15.5h4" />
                <circle cx="12" cy="8.5" r="1.2" fill="currentColor" stroke="none" />
                <circle cx="12" cy="15.5" r="1.2" fill="currentColor" stroke="none" />
            </>
        ),
        close: (
            <>
                <path d="M7 7 17 17M17 7 7 17" />
                <circle cx="12" cy="12" r="8" />
            </>
        ),
    };

    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
            {paths[kind]}
        </svg>
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
