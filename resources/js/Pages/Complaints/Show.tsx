import BackButton from '@/Components/Ui/BackButton';
import FileAttachmentCard from '@/Components/Ui/FileAttachmentCard';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import StatusBadge from '@/Components/Ui/StatusBadge';
import { useDateFormatter } from '@/lib/dates';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { finishSuccessfulSubmission } from '@/lib/form-submission';
import { sanitizeRichTextHtml } from '@/lib/sanitize-rich-text';
import {
    localizeName,
    translateComplaintEscalationReason,
    translateComplaintHistoryAction,
    translateComplaintHistoryNote,
    translateComplaintValue,
} from '@/Pages/Complaints/shared';
import { Head, Link, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';

type Attachment = {
    id: string;
    original_name: string;
    view_url?: string | null;
    download_url?: string | null;
};

type ComplaintActor = {
    id?: string;
    name?: string | null;
};

type ComplaintDepartment = {
    id?: string;
    name_en?: string | null;
    name_am?: string | null;
};

type ComplaintBranch = {
    id?: string;
    name_en?: string | null;
    name_am?: string | null;
};

type ComplaintResponse = {
    id: string;
    subject?: string | null;
    response_content?: string | null;
    responded_at?: string | null;
    responder?: ComplaintActor | null;
    responder_department?: ComplaintDepartment | null;
    attachments?: Attachment[];
};

type CommitteeDecision = {
    id: string;
    decision_summary?: string | null;
    decision_detail?: string | null;
    investigation_notes?: string | null;
    decision_date?: string | null;
    outcome?: string | null;
    committee_actor?: ComplaintActor | null;
    attachments?: Attachment[];
};

type Escalation = {
    id: string;
    escalation_type?: string | null;
    reason?: string | null;
    escalated_at?: string | null;
    escalated_by?: ComplaintActor | null;
};

type History = {
    id: string;
    action?: string | null;
    notes?: string | null;
    metadata?: { outcome?: string | null } | null;
    acted_at?: string | null;
    actor?: ComplaintActor | null;
};

type ComplaintItem = {
    id: string;
    complaint_number: string;
    complainant_name?: string | null;
    complainant_phone?: string | null;
    complainant_city?: string | null;
    complainant_sub_city?: string | null;
    complainant_woreda?: string | null;
    complainant_house_number?: string | null;
    complainant_email?: string | null;
    complainant_type?: string | null;
    complaint_essence?: string | null;
    subject?: string | null;
    details?: string | null;
    incident_date?: string | null;
    incident_sub_city?: string | null;
    incident_woreda?: string | null;
    concerned_employee_name?: string | null;
    evidence_note?: string | null;
    requested_resolution?: string | null;
    category?: string | null;
    priority?: string | null;
    branch?: ComplaintBranch | null;
    department?: ComplaintDepartment | null;
    status?: string | null;
    is_overdue?: boolean;
    is_dissatisfied?: boolean;
    dissatisfaction_reason?: string | null;
    submitted_at?: string | null;
    department_response_deadline_at?: string | null;
    department_responded_at?: string | null;
    forwarded_to_committee_at?: string | null;
    committee_decision_at?: string | null;
    resolved_at?: string | null;
    attachments?: Attachment[];
    responses?: ComplaintResponse[];
    committee_decisions?: CommitteeDecision[];
    escalations?: Escalation[];
    histories?: History[];
};

type Props = {
    complaintItem: ComplaintItem;
    can: {
        update?: boolean;
        respondDepartment: boolean;
        forwardToCommittee: boolean;
        decideCommittee: boolean;
        attach: boolean;
    };
    committeeOutcomeOptions: Array<{ value: string; label: string }>;
};

type ResponseFormData = {
    subject: string;
    response_content: string;
    attachments: File[];
};

type DissatisfactionFormData = {
    dissatisfaction_reason: string;
};

type DecisionFormData = {
    investigation_notes: string;
    decision_summary: string;
    decision_detail: string;
    outcome: string;
    attachments: File[];
};

type AttachmentFormData = {
    attachments: File[];
};

export default function ComplaintShow({ complaintItem, can, committeeOutcomeOptions }: Props) {
    const attachments = Array.isArray(complaintItem.attachments) ? complaintItem.attachments : [];
    const responses = Array.isArray(complaintItem.responses) ? complaintItem.responses : [];
    const decisions = Array.isArray(complaintItem.committee_decisions) ? complaintItem.committee_decisions : [];
    const histories = Array.isArray(complaintItem.histories) ? complaintItem.histories : [];
    const escalations = Array.isArray(complaintItem.escalations) ? complaintItem.escalations : [];

    const responseForm = useForm<ResponseFormData>({
        subject: '',
        response_content: '',
        attachments: [] as File[],
    });

    const dissatisfactionForm = useForm<DissatisfactionFormData>({
        dissatisfaction_reason: complaintItem.dissatisfaction_reason ?? '',
    });

    const decisionForm = useForm<DecisionFormData>({
        investigation_notes: '',
        decision_summary: '',
        decision_detail: '',
        outcome: committeeOutcomeOptions[0]?.value ?? '',
        attachments: [] as File[],
    });

    const attachmentForm = useForm<AttachmentFormData>({
        attachments: [] as File[],
    });

    const latestResponse = responses[0] ?? null;
    const latestDecision = decisions[0] ?? null;
    const latestEscalation = escalations[0] ?? null;

    const { locale, t } = useI18n();
    const { formatDate, formatDateTime } = useDateFormatter();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.complaints'), href: route('complaints.index') },
                { label: complaintItem.complaint_number },
            ]}
        >
            <Head title={complaintItem.complaint_number} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('complaints.record_eyebrow')}
                    title={complaintItem.complaint_number}
                    description={complaintItem.subject ?? t('complaints.record_description')}
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <BackButton fallbackHref={route('complaints.index')} />
                            {complaintItem.status ? <StatusBadge value={complaintItem.status} /> : null}
                            {complaintItem.is_overdue ? <StatusBadge label={t('complaints.labels.overdue')} value="rejected" /> : null}
                            <a href={route('complaints.print', complaintItem.id)} target="_blank" rel="noreferrer" className="btn-base btn-secondary focus-ring">
                                {t('complaints.actions.print_view')}
                            </a>
                            {can.update ? (
                                <Link href={route('complaints.edit', complaintItem.id)} className="btn-base btn-secondary focus-ring">
                                    {t('complaints.actions.edit_intake')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="space-y-5">
                    <SurfaceCard>
                        <div className="grid gap-5 xl:grid-cols-[1.45fr,0.95fr]">
                            <div className="space-y-5">
                                <StructuredSection title={t('complaints.sections.a.title')} description={t('complaints.sections.a.description')}>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <InfoItem label={t('complaints.form.labels.complainant_name')} value={displayValue(complaintItem.complainant_name)} />
                                        <InfoItem label={t('complaints.form.labels.complainant_phone')} value={displayValue(complaintItem.complainant_phone)} />
                                        <InfoItem label={t('complaints.form.labels.complainant_city')} value={displayValue(complaintItem.complainant_city)} />
                                        <InfoItem label={t('complaints.form.labels.complainant_sub_city')} value={displayValue(complaintItem.complainant_sub_city)} />
                                        <InfoItem label={t('complaints.form.labels.complainant_woreda')} value={displayValue(complaintItem.complainant_woreda)} />
                                        <InfoItem label={t('complaints.form.labels.complainant_house_number')} value={displayValue(complaintItem.complainant_house_number)} />
                                    </div>
                                </StructuredSection>

                                <StructuredSection title={t('complaints.sections.b.title')} description={t('complaints.sections.b.description')}>
                                    <NarrativeCard value={complaintItem.complaint_essence ?? complaintItem.details ?? complaintItem.subject} emptyLabel={t('complaints.empty.complaint_essence')} />
                                </StructuredSection>

                                <StructuredSection title={t('complaints.sections.c.title')} description={t('complaints.sections.c.description')}>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <InfoItem label={t('complaints.form.labels.incident_date')} value={formatDate(complaintItem.incident_date, '-')} />
                                        <InfoItem label={t('complaints.form.labels.branch')} value={localizeName(complaintItem.branch, locale)} />
                                        <InfoItem label={t('complaints.form.labels.incident_sub_city')} value={displayValue(complaintItem.incident_sub_city)} />
                                        <InfoItem label={t('complaints.form.labels.incident_woreda')} value={displayValue(complaintItem.incident_woreda)} />
                                    </div>
                                </StructuredSection>

                                <StructuredSection title={t('complaints.sections.d.title')} description={t('complaints.sections.d.description')}>
                                    <InfoItem label={t('complaints.form.labels.department')} value={localizeName(complaintItem.department, locale)} />
                                </StructuredSection>

                                <StructuredSection title={t('complaints.sections.e.title')} description={t('complaints.sections.e.description')}>
                                    <InfoItem label={t('complaints.form.labels.concerned_employee_name')} value={displayValue(complaintItem.concerned_employee_name)} />
                                </StructuredSection>

                                <StructuredSection title={t('complaints.sections.f.title')} description={t('complaints.sections.f.description')}>
                                    <InfoItem label={t('complaints.labels.evidence_note')} value={displayValue(complaintItem.evidence_note)} />
                                    <AttachmentList attachments={attachments} emptyLabel={t('complaints.empty.no_attachments')} />
                                </StructuredSection>

                                <StructuredSection title={t('complaints.sections.g.title')} description={t('complaints.sections.g.description')}>
                                    <NarrativeCard value={complaintItem.requested_resolution} emptyLabel={t('complaints.empty.requested_resolution')} />
                                </StructuredSection>
                            </div>

                            <div className="space-y-4">
                                <StructuredSection title={t('complaints.workflow_snapshot.title')} description={t('complaints.workflow_snapshot.description')}>
                                    <InfoItem label={t('common.status')} value={complaintItem.status ? <StatusBadge value={complaintItem.status} /> : '-'} />
                                    <InfoItem label={t('complaints.labels.submitted')} value={formatDateTime(complaintItem.submitted_at)} />
                                    <InfoItem label={t('complaints.labels.response_deadline')} value={formatDateTime(complaintItem.department_response_deadline_at)} />
                                    <InfoItem label={t('complaints.labels.department_responded')} value={formatDateTime(complaintItem.department_responded_at)} />
                                    <InfoItem label={t('complaints.labels.forwarded_to_committee')} value={formatDateTime(complaintItem.forwarded_to_committee_at)} />
                                    <InfoItem label={t('complaints.labels.committee_decided')} value={formatDateTime(complaintItem.committee_decision_at)} />
                                    <InfoItem label={t('complaints.labels.resolved')} value={formatDateTime(complaintItem.resolved_at)} />
                                </StructuredSection>

                                <StructuredSection title={t('complaints.additional_record_data.title')} description={t('complaints.additional_record_data.description')}>
                                    <InfoItem label={t('complaints.labels.complainant_type')} value={translateComplaintValue('complaints.complainant_types', complaintItem.complainant_type, t)} />
                                    <InfoItem label={t('complaints.labels.category')} value={displayValue(complaintItem.category)} />
                                    <InfoItem label={t('complaints.labels.priority')} value={translateComplaintValue('status', complaintItem.priority, t)} />
                                    <InfoItem label={t('common.email')} value={displayValue(complaintItem.complainant_email)} />
                                </StructuredSection>

                                {latestEscalation ? (
                                    <StructuredSection title={t('complaints.escalation_detail.title')} description={t('complaints.escalation_detail.description')}>
                                        <InfoItem label={t('complaints.labels.escalation_type')} value={translateComplaintValue('complaints.escalation_types', latestEscalation.escalation_type, t)} />
                                        <InfoItem label={t('complaints.labels.escalated_by')} value={displayValue(latestEscalation.escalated_by?.name ?? t('system.actor'))} />
                                        <InfoItem label={t('complaints.labels.escalated_at')} value={formatDateTime(latestEscalation.escalated_at)} />
                                        <InfoItem label={t('complaints.labels.reason')} value={translateComplaintEscalationReason(latestEscalation.reason, t)} />
                                    </StructuredSection>
                                ) : null}
                            </div>
                        </div>
                    </SurfaceCard>

                    <section id="department-response">
                        <SurfaceCard>
                            <SectionTitle title={t('complaints.department_response.title')} description={t('complaints.department_response.description')} />

                            {latestResponse ? (
                                <WorkflowCard
                                    title={latestResponse.subject ?? t('complaints.department_response.default_title')}
                                    meta={`${displayValue(latestResponse.responder?.name)} | ${formatDateTime(latestResponse.responded_at)}`}
                                    badge={<StatusBadge value="department_responded" />}
                                >
                                    <NarrativeCard value={latestResponse.response_content} emptyLabel={t('complaints.empty.no_response_text')} />
                                    <AttachmentList attachments={Array.isArray(latestResponse.attachments) ? latestResponse.attachments : []} emptyLabel={t('complaints.empty.no_response_attachments')} />
                                </WorkflowCard>
                            ) : (
                                <EmptyNote>{t('complaints.empty.no_department_response')}</EmptyNote>
                            )}

                            {can.respondDepartment ? (
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        responseForm.post(route('complaints.respond', complaintItem.id), {
                                            forceFormData: true,
                                            onSuccess: () => finishSuccessfulSubmission(responseForm, {
                                                reset: ['subject', 'response_content', 'attachments'],
                                            }),
                                        });
                                    }}
                                    className="mt-6 space-y-4 border-t border-[color:var(--border)] pt-6"
                                >
                                    <FormField label={t('complaints.form.labels.response_subject')} required error={responseForm.errors.subject}>
                                        <input className="input-ui" value={responseForm.data.subject} onChange={(event) => responseForm.setData('subject', event.target.value)} />
                                    </FormField>

                                    <FormField label={t('complaints.form.labels.response_content')} required error={responseForm.errors.response_content}>
                                        <RichTextEditor value={responseForm.data.response_content} onChange={(value) => responseForm.setData('response_content', value)} minHeight={260} />
                                    </FormField>

                                    <FormField label={t('common.attachments')} optional error={responseForm.errors.attachments}>
                                        <input
                                            type="file"
                                            multiple
                                            className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                            onChange={(event) => responseForm.setData('attachments', Array.from(event.target.files ?? []))}
                                        />
                                    </FormField>

                                    <div className="flex justify-end">
                                        <button type="submit" className="btn-base btn-primary focus-ring" disabled={responseForm.processing}>
                                            {t('complaints.buttons.submit_department_response')}
                                        </button>
                                    </div>
                                </form>
                            ) : null}
                        </SurfaceCard>
                    </section>

                    <div className="grid gap-5 xl:grid-cols-2">
                        <section id="complainant-actions">
                            <SurfaceCard>
                                <SectionTitle title={t('complaints.complainant_action.title')} description={t('complaints.complainant_action.description')} />

                                {latestEscalation ? (
                                    <WorkflowCard
                                        title={t('complaints.escalation_detail.title')}
                                        meta={`${displayValue(latestEscalation.escalated_by?.name ?? t('system.actor'))} | ${formatDateTime(latestEscalation.escalated_at)}`}
                                        badge={<StatusBadge value="escalated_to_committee" />}
                                    >
                                        <InfoItem label={t('complaints.labels.escalation_type')} value={translateComplaintValue('complaints.escalation_types', latestEscalation.escalation_type, t)} />
                                        <InfoItem label={t('complaints.labels.reason')} value={translateComplaintEscalationReason(latestEscalation.reason, t)} />
                                    </WorkflowCard>
                                ) : null}

                                {can.forwardToCommittee ? (
                                    <form
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            dissatisfactionForm.post(route('complaints.forward', complaintItem.id), {
                                                onSuccess: () => finishSuccessfulSubmission(dissatisfactionForm, {
                                                    reset: ['dissatisfaction_reason'],
                                                }),
                                            });
                                        }}
                                        className="mt-6 space-y-4 border-t border-[color:var(--border)] pt-6"
                                    >
                                        <FormField label={t('complaints.form.labels.dissatisfaction_reason')} required error={dissatisfactionForm.errors.dissatisfaction_reason}>
                                            <textarea
                                                rows={5}
                                                className="textarea-ui"
                                                value={dissatisfactionForm.data.dissatisfaction_reason}
                                                onChange={(event) => dissatisfactionForm.setData('dissatisfaction_reason', event.target.value)}
                                            />
                                        </FormField>
                                        <div className="flex justify-end">
                                            <button type="submit" className="btn-base btn-primary focus-ring" disabled={dissatisfactionForm.processing}>
                                                {t('complaints.actions.forward_to_committee')}
                                            </button>
                                        </div>
                                    </form>
                                ) : null}
                            </SurfaceCard>
                        </section>

                        <section id="attachments">
                            <SurfaceCard>
                                <SectionTitle title={t('complaints.attachments.title')} description={t('complaints.attachments.description')} />
                                <AttachmentList attachments={attachments} emptyLabel={t('complaints.empty.no_attachments')} />

                                {can.attach ? (
                                    <form
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            attachmentForm.post(route('complaints.attachments.store', complaintItem.id), {
                                                forceFormData: true,
                                                onSuccess: () => finishSuccessfulSubmission(attachmentForm, {
                                                    reset: ['attachments'],
                                                }),
                                            });
                                        }}
                                        className="mt-6 space-y-4 border-t border-[color:var(--border)] pt-6"
                                    >
                                        <FormField label={t('complaints.form.labels.add_attachment')} optional error={attachmentForm.errors.attachments}>
                                            <input
                                                type="file"
                                                multiple
                                                className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                                onChange={(event) => attachmentForm.setData('attachments', Array.from(event.target.files ?? []))}
                                            />
                                        </FormField>
                                        <div className="flex justify-end">
                                            <button type="submit" className="btn-base btn-primary focus-ring" disabled={attachmentForm.processing}>
                                                {t('complaints.buttons.upload_attachment')}
                                            </button>
                                        </div>
                                    </form>
                                ) : null}
                            </SurfaceCard>
                        </section>
                    </div>

                    <section id="committee-review">
                        <SurfaceCard>
                            <SectionTitle title={t('complaints.committee_review.title')} description={t('complaints.committee_review.description')} />

                            {latestDecision ? (
                                <WorkflowCard
                                    title={latestDecision.decision_summary ?? t('complaints.committee_review.default_title')}
                                    meta={`${displayValue(latestDecision.committee_actor?.name)} | ${formatDateTime(latestDecision.decision_date)}`}
                                    badge={latestDecision.outcome ? <StatusBadge value={latestDecision.outcome} /> : null}
                                >
                                    {latestDecision.investigation_notes ? (
                                        <NarrativeCard value={latestDecision.investigation_notes} emptyLabel={t('complaints.empty.no_investigation_notes')} />
                                    ) : null}
                                    <NarrativeCard value={latestDecision.decision_detail} emptyLabel={t('complaints.empty.no_committee_decision_detail')} />
                                    <AttachmentList attachments={Array.isArray(latestDecision.attachments) ? latestDecision.attachments : []} emptyLabel={t('complaints.empty.no_committee_attachments')} />
                                </WorkflowCard>
                            ) : (
                                <EmptyNote>{t('complaints.empty.no_committee_decision')}</EmptyNote>
                            )}

                            {can.decideCommittee ? (
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        decisionForm.post(route('complaints.decide', complaintItem.id), {
                                            forceFormData: true,
                                            onSuccess: () => finishSuccessfulSubmission(decisionForm, {
                                                reset: ['investigation_notes', 'decision_summary', 'decision_detail', 'attachments'],
                                            }),
                                        });
                                    }}
                                    className="mt-6 space-y-4 border-t border-[color:var(--border)] pt-6"
                                >
                                    <FormField label={t('complaints.form.labels.investigation_notes')} optional error={decisionForm.errors.investigation_notes}>
                                        <RichTextEditor value={decisionForm.data.investigation_notes} onChange={(value) => decisionForm.setData('investigation_notes', value)} minHeight={220} />
                                    </FormField>

                                    <div className="grid gap-4 md:grid-cols-[1.5fr,0.9fr]">
                                        <FormField label={t('complaints.form.labels.decision_summary')} required error={decisionForm.errors.decision_summary}>
                                            <input className="input-ui" value={decisionForm.data.decision_summary} onChange={(event) => decisionForm.setData('decision_summary', event.target.value)} />
                                        </FormField>

                                        <FormField label={t('complaints.form.labels.outcome')} required error={decisionForm.errors.outcome}>
                                            <select className="select-ui" value={decisionForm.data.outcome} onChange={(event) => decisionForm.setData('outcome', event.target.value)}>
                                                {committeeOutcomeOptions.map((option) => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </FormField>
                                    </div>

                                    <FormField label={t('complaints.form.labels.decision_detail')} required error={decisionForm.errors.decision_detail}>
                                        <RichTextEditor value={decisionForm.data.decision_detail} onChange={(value) => decisionForm.setData('decision_detail', value)} minHeight={260} />
                                    </FormField>

                                    <FormField label={t('common.attachments')} optional error={decisionForm.errors.attachments}>
                                        <input
                                            type="file"
                                            multiple
                                            className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                            onChange={(event) => decisionForm.setData('attachments', Array.from(event.target.files ?? []))}
                                        />
                                    </FormField>

                                    <div className="flex justify-end">
                                        <button type="submit" className="btn-base btn-primary focus-ring" disabled={decisionForm.processing}>
                                            {t('complaints.buttons.record_committee_decision')}
                                        </button>
                                    </div>
                                </form>
                            ) : null}
                        </SurfaceCard>
                    </section>

                    <SurfaceCard>
                        <SectionTitle title={t('complaints.timeline.title')} description={t('complaints.timeline.description')} />
                        {histories.length === 0 ? (
                            <EmptyNote>{t('complaints.empty.no_timeline_entries')}</EmptyNote>
                        ) : (
                            <div className="mt-4 space-y-3">
                                {histories.map((history) => (
                                    <div key={history.id} className="rounded-2xl border border-[color:var(--border)] px-4 py-4">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p className="font-medium capitalize text-[color:var(--text)]">{translateComplaintHistoryAction(history.action, t)}</p>
                                                <p className="mt-1 text-sm text-[color:var(--muted)]">{displayValue(history.actor?.name ?? t('system.actor'))}</p>
                                            </div>
                                            <p className="text-sm text-[color:var(--muted)]">{formatDateTime(history.acted_at)}</p>
                                        </div>
                                        {history.notes ? <p className="mt-3 whitespace-pre-line text-sm text-[color:var(--muted-strong)]">{translateComplaintHistoryNote(history.notes, t)}</p> : null}
                                    </div>
                                ))}
                            </div>
                        )}
                    </SurfaceCard>
                </div>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function StructuredSection({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="rounded-3xl border border-[color:var(--border)] px-5 py-5">
            <div className="border-b border-[color:var(--border)] pb-4">
                <h2 className="text-base font-semibold text-[color:var(--text)]">{title}</h2>
                <p className="mt-1 text-sm text-[color:var(--muted)]">{description}</p>
            </div>
            <div className="mt-4 space-y-4">{children}</div>
        </section>
    );
}

function SectionTitle({ title, description }: { title: string; description: string }) {
    return (
        <div>
            <h2 className="text-xl font-semibold text-[color:var(--text)]">{title}</h2>
            <p className="mt-1 text-sm text-[color:var(--muted)]">{description}</p>
        </div>
    );
}

function WorkflowCard({
    title,
    meta,
    badge,
    children,
}: {
    title: string;
    meta: string;
    badge?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="mt-5 rounded-3xl border border-[color:var(--border)] px-5 py-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-lg font-semibold text-[color:var(--text)]">{title}</p>
                    <p className="mt-1 text-sm text-[color:var(--muted)]">{meta}</p>
                </div>
                {badge}
            </div>
            <div className="mt-4 space-y-4">{children}</div>
        </div>
    );
}

function InfoItem({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="rounded-2xl border border-[color:var(--border)] px-4 py-4">
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--muted)]">{label}</p>
            <div className="mt-2 text-sm text-[color:var(--text)]">{value}</div>
        </div>
    );
}

function NarrativeCard({ value, emptyLabel }: { value?: string | null; emptyLabel: string }) {
    const normalized = (value ?? '').trim();

    if (normalized === '') {
        return <EmptyNote>{emptyLabel}</EmptyNote>;
    }

    if (looksLikeHtml(normalized)) {
        return <div className="prose prose-slate max-w-none dark:prose-invert" dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(normalized) }} />;
    }

    return <p className="whitespace-pre-line text-sm leading-7 text-[color:var(--text)]">{normalized}</p>;
}

function AttachmentList({ attachments, emptyLabel }: { attachments: Attachment[]; emptyLabel: string }) {
    if (attachments.length === 0) {
        return <EmptyNote>{emptyLabel}</EmptyNote>;
    }

    return (
        <div className="space-y-3">
            {attachments.map((attachment) => (
                <FileAttachmentCard
                    key={attachment.id}
                    name={attachment.original_name}
                    viewUrl={attachment.view_url ?? undefined}
                    downloadUrl={attachment.download_url ?? undefined}
                />
            ))}
        </div>
    );
}

function EmptyNote({ children }: { children: ReactNode }) {
    return <p className="text-sm text-[color:var(--muted)]">{children}</p>;
}

function looksLikeHtml(value: string) {
    return /<\/?[a-z][\s\S]*>/i.test(value);
}

function displayValue(value?: string | null) {
    return value && value.trim() !== '' ? value : '-';
}
