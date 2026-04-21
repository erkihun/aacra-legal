import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import StatusBadge from '@/Components/Ui/StatusBadge';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { finishSuccessfulSubmission } from '@/lib/form-submission';
import { sanitizeRichTextHtml } from '@/lib/sanitize-rich-text';
import { Head, Link, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';

type Attachment = {
    id: string;
    original_name: string;
    view_url?: string;
    download_url?: string;
};

type Props = {
    complaintItem: any;
    can: {
        update?: boolean;
        respondDepartment: boolean;
        forwardToCommittee: boolean;
        decideCommittee: boolean;
        attach: boolean;
    };
    committeeOutcomeOptions: Array<{ value: string; label: string }>;
};

export default function ComplaintShow({ complaintItem, can, committeeOutcomeOptions }: Props) {
    const attachments: Attachment[] = Array.isArray(complaintItem.attachments) ? complaintItem.attachments : [];
    const responses = Array.isArray(complaintItem.responses) ? complaintItem.responses : [];
    const decisions = Array.isArray(complaintItem.committee_decisions) ? complaintItem.committee_decisions : [];
    const histories = Array.isArray(complaintItem.histories) ? complaintItem.histories : [];
    const escalations = Array.isArray(complaintItem.escalations) ? complaintItem.escalations : [];

    const responseForm = useForm({
        subject: '',
        response_content: '',
        attachments: [] as File[],
    });

    const dissatisfactionForm = useForm({
        dissatisfaction_reason: complaintItem.dissatisfaction_reason ?? '',
    });

    const decisionForm = useForm({
        investigation_notes: '',
        decision_summary: '',
        decision_detail: '',
        outcome: committeeOutcomeOptions[0]?.value ?? '',
        attachments: [] as File[],
    });

    const attachmentForm = useForm({
        attachments: [] as File[],
    });

    const latestResponse = responses[0] ?? null;
    const latestDecision = decisions[0] ?? null;
    const latestEscalation = escalations[0] ?? null;

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Complaints', href: route('complaints.index') },
                { label: complaintItem.complaint_number },
            ]}
        >
            <Head title={complaintItem.complaint_number} />
            <PageContainer>
                <SectionHeader
                    eyebrow="Complaint record"
                    title={complaintItem.complaint_number}
                    description={complaintItem.subject}
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusBadge value={complaintItem.status} />
                            {complaintItem.is_overdue ? <StatusBadge label="Overdue" value="rejected" /> : null}
                            {can.update ? <Link href={route('complaints.edit', complaintItem.id)} className="btn-base btn-secondary focus-ring">Edit</Link> : null}
                        </div>
                    }
                />

                <div className="grid gap-4">
                    <SurfaceCard>
                        <div className="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
                            <div className="space-y-6">
                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <OverviewItem label="Status" value={<StatusBadge value={complaintItem.status} />} />
                                    <OverviewItem label="Complainant" value={complaintItem.complainant_name} />
                                    <OverviewItem label="Complainant Type" value={String(complaintItem.complainant_type ?? '-').replaceAll('_', ' ')} />
                                    <OverviewItem label="Priority" value={complaintItem.priority ? String(complaintItem.priority).replaceAll('_', ' ') : '-'} />
                                    <OverviewItem label="Branch" value={complaintItem.branch?.name_en ?? '-'} />
                                    <OverviewItem label="Department" value={complaintItem.department?.name_en ?? '-'} />
                                    <OverviewItem label="Submitted" value={formatDateTime(complaintItem.submitted_at)} />
                                    <OverviewItem label="Response Deadline" value={formatDateTime(complaintItem.department_response_deadline_at)} />
                                </div>

                                <section>
                                    <p className="text-xs font-semibold uppercase tracking-[0.18em] text-[color:var(--muted)]">Complaint details</p>
                                    <div
                                        className="prose prose-slate mt-4 max-w-none dark:prose-invert"
                                        dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(complaintItem.details) }}
                                    />
                                </section>
                            </div>

                            <div className="space-y-4">
                                <InfoPanel title="Complainant snapshot">
                                    <InfoRow label="Email" value={complaintItem.complainant_email ?? '-'} />
                                    <InfoRow label="Phone" value={complaintItem.complainant_phone ?? '-'} />
                                    <InfoRow label="Category" value={complaintItem.category ?? '-'} />
                                </InfoPanel>

                                <InfoPanel title="Workflow state">
                                    <InfoRow label="Department responded" value={formatDateTime(complaintItem.department_responded_at)} />
                                    <InfoRow label="Forwarded to committee" value={formatDateTime(complaintItem.forwarded_to_committee_at)} />
                                    <InfoRow label="Committee decided" value={formatDateTime(complaintItem.committee_decision_at)} />
                                    <InfoRow label="Resolved" value={formatDateTime(complaintItem.resolved_at)} />
                                </InfoPanel>
                            </div>
                        </div>
                    </SurfaceCard>

                    <section id="department-response">
                    <SurfaceCard>
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-xl font-semibold text-[color:var(--text)]">Department Response</h2>
                                <p className="mt-1 text-sm text-[color:var(--muted)]">The responsible department can submit one official response for the complaint.</p>
                            </div>
                            {latestResponse ? <StatusBadge value="department_responded" /> : null}
                        </div>

                        {latestResponse ? (
                            <div className="mt-5 rounded-3xl border border-[color:var(--border)] px-5 py-5">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="text-lg font-semibold text-[color:var(--text)]">{latestResponse.subject}</p>
                                        <p className="mt-1 text-sm text-[color:var(--muted)]">
                                            {latestResponse.responder?.name ?? '-'} | {formatDateTime(latestResponse.responded_at)}
                                        </p>
                                    </div>
                                    <div className="text-sm text-[color:var(--muted)]">{latestResponse.responder_department?.name_en ?? '-'}</div>
                                </div>
                                <div
                                    className="prose prose-slate mt-4 max-w-none dark:prose-invert"
                                    dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(latestResponse.response_content) }}
                                />
                                <AttachmentList attachments={Array.isArray(latestResponse.attachments) ? latestResponse.attachments : []} emptyLabel="No response attachments." />
                            </div>
                        ) : (
                            <p className="mt-4 text-sm text-[color:var(--muted)]">No department response has been recorded yet.</p>
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
                                <div className="grid gap-4">
                                    <FormField label="Response Subject" required error={responseForm.errors.subject}>
                                        <input className="input-ui" value={responseForm.data.subject} onChange={(event) => responseForm.setData('subject', event.target.value)} />
                                    </FormField>
                                    <FormField label="Response Content" required error={responseForm.errors.response_content}>
                                        <RichTextEditor value={responseForm.data.response_content} onChange={(value) => responseForm.setData('response_content', value)} minHeight={260} />
                                    </FormField>
                                    <FormField label="Attachments" optional error={responseForm.errors.attachments}>
                                        <input
                                            type="file"
                                            multiple
                                            className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                            onChange={(event) => responseForm.setData('attachments', Array.from(event.target.files ?? []))}
                                        />
                                    </FormField>
                                </div>
                                <div className="flex justify-end">
                                    <button type="submit" className="btn-base btn-primary focus-ring" disabled={responseForm.processing}>
                                        Submit Department Response
                                    </button>
                                </div>
                            </form>
                        ) : null}
                    </SurfaceCard>
                    </section>

                    <div className="grid gap-4 xl:grid-cols-2">
                        <section id="complainant-actions">
                        <SurfaceCard>
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h2 className="text-xl font-semibold text-[color:var(--text)]">Complainant Action</h2>
                                    <p className="mt-1 text-sm text-[color:var(--muted)]">Forward the complaint to the committee only when the department response is not satisfactory.</p>
                                </div>
                                {complaintItem.is_dissatisfied ? <StatusBadge label="Forwarded" value="escalated_to_committee" /> : null}
                            </div>

                            {latestEscalation ? (
                                <div className="mt-5 rounded-3xl border border-[color:var(--border)] px-5 py-5">
                                    <InfoRow label="Escalation Type" value={String(latestEscalation.escalation_type ?? '-').replaceAll('_', ' ')} />
                                    <InfoRow label="Escalated By" value={latestEscalation.escalated_by?.name ?? 'System'} />
                                    <InfoRow label="Escalated At" value={formatDateTime(latestEscalation.escalated_at)} />
                                    <InfoRow label="Reason" value={latestEscalation.reason ?? '-'} />
                                </div>
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
                                    <FormField label="Dissatisfaction Reason" required error={dissatisfactionForm.errors.dissatisfaction_reason}>
                                        <textarea
                                            rows={5}
                                            className="textarea-ui"
                                            value={dissatisfactionForm.data.dissatisfaction_reason}
                                            onChange={(event) => dissatisfactionForm.setData('dissatisfaction_reason', event.target.value)}
                                        />
                                    </FormField>
                                    <div className="flex justify-end">
                                        <button type="submit" className="btn-base btn-primary focus-ring" disabled={dissatisfactionForm.processing}>
                                            Forward to Committee
                                        </button>
                                    </div>
                                </form>
                            ) : null}
                        </SurfaceCard>
                        </section>

                        <section id="attachments">
                        <SurfaceCard>
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h2 className="text-xl font-semibold text-[color:var(--text)]">Attachments</h2>
                                    <p className="mt-1 text-sm text-[color:var(--muted)]">Complaint-level attachments remain available throughout the workflow.</p>
                                </div>
                            </div>

                            <AttachmentList attachments={attachments} emptyLabel="No complaint attachments." />

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
                                    <FormField label="Add Attachment" optional error={attachmentForm.errors.attachments}>
                                        <input
                                            type="file"
                                            multiple
                                            className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                            onChange={(event) => attachmentForm.setData('attachments', Array.from(event.target.files ?? []))}
                                        />
                                    </FormField>
                                    <div className="flex justify-end">
                                        <button type="submit" className="btn-base btn-primary focus-ring" disabled={attachmentForm.processing}>
                                            Upload Attachment
                                        </button>
                                    </div>
                                </form>
                            ) : null}
                        </SurfaceCard>
                        </section>
                    </div>

                    <section id="committee-review">
                    <SurfaceCard>
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-xl font-semibold text-[color:var(--text)]">Committee Review</h2>
                                <p className="mt-1 text-sm text-[color:var(--muted)]">Committee members review the original complaint, the department response, the timeline, and any escalation reason before recording a final decision.</p>
                            </div>
                            {latestDecision ? <StatusBadge value="committee_decided" /> : null}
                        </div>

                        {latestDecision ? (
                            <div className="mt-5 rounded-3xl border border-[color:var(--border)] px-5 py-5">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="text-lg font-semibold text-[color:var(--text)]">{latestDecision.decision_summary}</p>
                                        <p className="mt-1 text-sm text-[color:var(--muted)]">
                                            {latestDecision.committee_actor?.name ?? '-'} | {formatDateTime(latestDecision.decision_date)}
                                        </p>
                                    </div>
                                    <StatusBadge value={latestDecision.outcome} />
                                </div>
                                {latestDecision.investigation_notes ? (
                                    <div
                                        className="prose prose-slate mt-4 max-w-none dark:prose-invert"
                                        dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(latestDecision.investigation_notes) }}
                                    />
                                ) : null}
                                <div
                                    className="prose prose-slate mt-4 max-w-none dark:prose-invert"
                                    dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(latestDecision.decision_detail) }}
                                />
                                <AttachmentList attachments={Array.isArray(latestDecision.attachments) ? latestDecision.attachments : []} emptyLabel="No committee attachments." />
                            </div>
                        ) : (
                            <p className="mt-4 text-sm text-[color:var(--muted)]">No committee decision has been recorded yet.</p>
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
                                <FormField label="Investigation Notes" optional error={decisionForm.errors.investigation_notes}>
                                    <RichTextEditor value={decisionForm.data.investigation_notes} onChange={(value) => decisionForm.setData('investigation_notes', value)} minHeight={220} />
                                </FormField>
                                <div className="grid gap-4 md:grid-cols-[1.5fr,0.9fr]">
                                    <FormField label="Decision Summary" required error={decisionForm.errors.decision_summary}>
                                        <input className="input-ui" value={decisionForm.data.decision_summary} onChange={(event) => decisionForm.setData('decision_summary', event.target.value)} />
                                    </FormField>
                                    <FormField label="Outcome" required error={decisionForm.errors.outcome}>
                                        <select className="select-ui" value={decisionForm.data.outcome} onChange={(event) => decisionForm.setData('outcome', event.target.value)}>
                                            {committeeOutcomeOptions.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>
                                </div>
                                <FormField label="Decision Detail" required error={decisionForm.errors.decision_detail}>
                                    <RichTextEditor value={decisionForm.data.decision_detail} onChange={(value) => decisionForm.setData('decision_detail', value)} minHeight={260} />
                                </FormField>
                                <FormField label="Attachments" optional error={decisionForm.errors.attachments}>
                                    <input
                                        type="file"
                                        multiple
                                        className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                        onChange={(event) => decisionForm.setData('attachments', Array.from(event.target.files ?? []))}
                                    />
                                </FormField>
                                <div className="flex justify-end">
                                    <button type="submit" className="btn-base btn-primary focus-ring" disabled={decisionForm.processing}>
                                        Record Committee Decision
                                    </button>
                                </div>
                            </form>
                        ) : null}
                    </SurfaceCard>
                    </section>

                    <SurfaceCard>
                        <h2 className="text-xl font-semibold text-[color:var(--text)]">Timeline</h2>
                        {histories.length === 0 ? (
                            <p className="mt-4 text-sm text-[color:var(--muted)]">No timeline entries yet.</p>
                        ) : (
                            <div className="mt-4 space-y-3">
                                {histories.map((history: any) => (
                                    <div key={history.id} className="rounded-2xl border border-[color:var(--border)] px-4 py-4">
                                        <div className="flex items-center justify-between gap-3">
                                            <div>
                                                <p className="font-medium capitalize text-[color:var(--text)]">{String(history.action ?? '').replaceAll('_', ' ')}</p>
                                                <p className="mt-1 text-sm text-[color:var(--muted)]">{history.actor?.name ?? 'System'}</p>
                                            </div>
                                            <p className="text-sm text-[color:var(--muted)]">{formatDateTime(history.acted_at)}</p>
                                        </div>
                                        {history.notes ? <p className="mt-3 text-sm text-[color:var(--muted-strong)]">{history.notes}</p> : null}
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

function OverviewItem({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="rounded-2xl border border-[color:var(--border)] px-4 py-4">
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--muted)]">{label}</p>
            <div className="mt-2 text-sm text-[color:var(--text)]">{value}</div>
        </div>
    );
}

function InfoPanel({ title, children, id }: { title: string; children: ReactNode; id?: string }) {
    return (
        <section id={id} className="rounded-3xl border border-[color:var(--border)] px-5 py-5">
            <h3 className="text-sm font-semibold uppercase tracking-[0.16em] text-[color:var(--muted)]">{title}</h3>
            <div className="mt-4 space-y-3">{children}</div>
        </section>
    );
}

function InfoRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-3 text-sm">
            <span className="text-[color:var(--muted)]">{label}</span>
            <span className="text-right text-[color:var(--text)]">{value}</span>
        </div>
    );
}

function AttachmentList({ attachments, emptyLabel }: { attachments: Attachment[]; emptyLabel: string }) {
    if (attachments.length === 0) {
        return <p className="mt-4 text-sm text-[color:var(--muted)]">{emptyLabel}</p>;
    }

    return (
        <div className="mt-4 space-y-2">
            {attachments.map((attachment) => (
                <div key={attachment.id} className="flex items-center justify-between rounded-2xl border border-[color:var(--border)] px-4 py-3">
                    <span className="text-sm text-[color:var(--text)]">{attachment.original_name}</span>
                    <div className="flex gap-2">
                        {attachment.view_url ? <a className="btn-base btn-secondary focus-ring" href={attachment.view_url}>View</a> : null}
                        {attachment.download_url ? <a className="btn-base btn-secondary focus-ring" href={attachment.download_url}>Download</a> : null}
                    </div>
                </div>
            ))}
        </div>
    );
}

function formatDateTime(value?: string | null) {
    return value ? new Date(value).toLocaleString() : '-';
}
