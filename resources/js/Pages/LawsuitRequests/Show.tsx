import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import BackButton from '@/Components/Ui/BackButton';
import EmptyState from '@/Components/Ui/EmptyState';
import FileAttachmentCard from '@/Components/Ui/FileAttachmentCard';
import FormField from '@/Components/Ui/FormField';
import FormalRequestLetter from '@/Components/Ui/FormalRequestLetter';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useDateFormatter } from '@/lib/dates';
import { finishSuccessfulSubmission } from '@/lib/form-submission';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm, useRemember } from '@inertiajs/react';
import { type ReactNode, useState } from 'react';

type ShowLawsuitRequestProps = {
    requestItem: any;
    can: {
        review: boolean;
        attach: boolean;
        update: boolean;
    };
    reviewStatusOptions: Array<{ label: string; value: string }>;
};

type WorkspacePanel = 'overview' | 'review' | 'attachments';
type IconKind = 'workspace' | 'attachment' | 'review';
type WorkspaceNavItem = {
    key: WorkspacePanel;
    label: string;
    icon: IconKind;
    detail?: string;
    badge?: number;
};

export default function LawsuitRequestShow({
    requestItem,
    can,
    reviewStatusOptions,
}: ShowLawsuitRequestProps) {
    const normalizeArray = (value: unknown) => (Array.isArray(value) ? value : []);

    const { t, locale } = useI18n();
    const { formatDateTime } = useDateFormatter();
    const [activePanel, setActivePanel] = useRemember<WorkspacePanel>(
        'overview',
        `lawsuit-show-active-panel-${requestItem.id}`,
    );
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [attachmentToDelete, setAttachmentToDelete] = useState<any | null>(null);

    const attachments = normalizeArray(requestItem.attachments);

    const reviewForm = useForm({
        status: reviewStatusOptions[0]?.value ?? 'under_review',
        reviewer_notes: '',
    });

    const deleteAttachmentForm = useForm({});

    const departmentName =
        locale === 'am'
            ? requestItem.requesting_department?.name_am ?? requestItem.requesting_department?.name_en
            : requestItem.requesting_department?.name_en;

    const isEditable = can.update && (requestItem.status === 'returned' || requestItem.status === 'submitted');
    const attachmentSectionEnabled = can.attach || attachments.length > 0;

    const navigationItems: WorkspaceNavItem[] = [
        {
            key: 'overview',
            label: t('common.overview'),
            icon: 'workspace',
            detail: requestItem.request_code,
        },
        ...(can.review
            ? [
                  {
                      key: 'review' as WorkspacePanel,
                      label: t('lawsuit_requests.review_action'),
                      icon: 'review' as IconKind,
                  },
              ]
            : []),
        ...(attachmentSectionEnabled
            ? [
                  {
                      key: 'attachments' as WorkspacePanel,
                      label: t('common.attachments'),
                      icon: 'attachment' as IconKind,
                      badge: attachments.length,
                  },
              ]
            : []),
    ];

    const currentPanel: WorkspacePanel = navigationItems.some((item) => item.key === activePanel)
        ? activePanel
        : 'overview';

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.lawsuit_requests'), href: route('lawsuit-requests.index') },
                { label: requestItem.request_code },
            ]}
        >
            <Head title={requestItem.request_code} />

            <PageContainer className="space-y-6">
                <SectionHeader
                    eyebrow={requestItem.request_code}
                    title={requestItem.subject}
                    action={
                        <div className="flex flex-wrap justify-end gap-3">
                            <BackButton fallbackHref={route('lawsuit-requests.index')} />
                            {isEditable ? (
                                <Link
                                    href={route('lawsuit-requests.edit', { lawsuitFilingRequest: requestItem.id })}
                                    className="btn-base btn-primary focus-ring"
                                >
                                    {t('common.edit')}
                                </Link>
                            ) : null}
                        </div>
                    }
                />

                <div className="flex flex-wrap gap-2">
                    <StatusBadge value={requestItem.status} />
                </div>

                <div className="grid gap-6 xl:grid-cols-[18rem,minmax(0,1fr)]">
                    <SurfaceCard className="h-fit p-4 md:p-5 xl:sticky xl:top-24">
                        <div className="flex items-start justify-between gap-3">
                            <div className="space-y-1">
                                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--muted)]">
                                    {t('common.workspace')}
                                </p>
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                    {t('navigation.lawsuit_requests')}
                                </h2>
                                <p className="text-sm text-[color:var(--muted-strong)]">{requestItem.subject}</p>
                            </div>
                            <CountBadge value={navigationItems.length} />
                        </div>

                        <div className="mt-5 flex gap-2 overflow-x-auto pb-1 xl:flex-col xl:overflow-visible xl:pb-0">
                            {navigationItems.map((item) => (
                                <WorkspaceNavButton
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
                            <InfoRow label={t('common.status')} value={<StatusBadge value={requestItem.status} />} />
                            <InfoRow
                                label={t('lawsuit_requests.requesting_department')}
                                value={departmentName ?? t('common.not_available')}
                            />
                            <InfoRow
                                label={t('lawsuit_requests.requester')}
                                value={requestItem.creator?.name ?? t('common.not_available')}
                            />
                            {requestItem.reviewer ? (
                                <InfoRow
                                    label={t('lawsuit_requests.reviewer')}
                                    value={requestItem.reviewer.name}
                                />
                            ) : null}
                        </div>
                    </SurfaceCard>

                    <div className="min-w-0 space-y-4">
                        {currentPanel === 'overview' ? (
                            <SurfaceCard className="space-y-5 p-6">
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                    {t('common.overview')}
                                </h2>

                                <dl className="grid gap-x-8 gap-y-5 md:grid-cols-2">
                                    <OverviewItem label={t('lawsuit_requests.requesting_department')} value={departmentName} />
                                    <OverviewItem label={t('lawsuit_requests.requester')} value={requestItem.creator?.name} />
                                    <OverviewItem label={t('common.status')} value={<StatusBadge value={requestItem.status} />} />
                                    <OverviewItem label={t('lawsuit_requests.date_submitted')} value={requestItem.date_submitted} />
                                </dl>

                                <div className="border-t border-[color:var(--border)] pt-5">
                                    {requestItem.formal_letter ? (
                                        <FormalRequestLetter
                                            title={t('requester.letter_preview')}
                                            document={requestItem.formal_letter}
                                        />
                                    ) : (
                                        <>
                                            <p className="text-sm font-semibold text-[color:var(--muted-strong)]">
                                                {t('lawsuit_requests.description')}
                                            </p>
                                            <p className="mt-3 whitespace-pre-wrap text-sm leading-7 text-[color:var(--text)]">
                                                {requestItem.description ?? t('common.not_available')}
                                            </p>
                                        </>
                                    )}
                                </div>

                                {requestItem.reviewer_notes ? (
                                    <div className="border-t border-[color:var(--border)] pt-5">
                                        <p className="text-sm font-semibold text-[color:var(--muted-strong)]">
                                            {t('lawsuit_requests.reviewer_notes')}
                                        </p>
                                        <p className="mt-3 whitespace-pre-wrap text-sm leading-7 text-[color:var(--text)]">
                                            {requestItem.reviewer_notes}
                                        </p>
                                    </div>
                                ) : null}
                            </SurfaceCard>
                        ) : null}

                        {currentPanel === 'review' && can.review ? (
                            <SurfaceCard className="space-y-6 p-6">
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                    {t('lawsuit_requests.review_action')}
                                </h2>

                                <div className="space-y-4">
                                    <FormField label={t('common.status')} required error={reviewForm.errors.status}>
                                        <select
                                            value={reviewForm.data.status}
                                            onChange={(e) => reviewForm.setData('status', e.target.value)}
                                            className="select-ui"
                                        >
                                            {reviewStatusOptions.map((opt) => (
                                                <option key={opt.value} value={opt.value}>
                                                    {opt.label}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>

                                    <FormField
                                        label={t('lawsuit_requests.reviewer_notes')}
                                        optional
                                        error={reviewForm.errors.reviewer_notes}
                                    >
                                        <textarea
                                            value={reviewForm.data.reviewer_notes}
                                            onChange={(e) => reviewForm.setData('reviewer_notes', e.target.value)}
                                            rows={5}
                                            className="textarea-ui"
                                        />
                                    </FormField>
                                </div>

                                <div className="flex flex-wrap justify-end gap-3 border-t border-[color:var(--border)] pt-4">
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
                            </SurfaceCard>
                        ) : null}

                        {currentPanel === 'attachments' && attachmentSectionEnabled ? (
                            <SurfaceCard className="space-y-6 p-6">
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                    {t('common.attachments')}
                                </h2>

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
            </PageContainer>

            <ConfirmationDialog
                open={confirmOpen}
                title={t('lawsuit_requests.confirm_review')}
                description={t('lawsuit_requests.confirm_review_description')}
                confirmLabel={t('lawsuit_requests.confirm_review_button')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => {
                    reviewForm.patch(
                        route('lawsuit-requests.review', { lawsuitFilingRequest: requestItem.id }),
                        {
                            onSuccess: () => {
                                finishSuccessfulSubmission(reviewForm, {
                                    reset: true,
                                    afterSuccess: () => {
                                        setConfirmOpen(false);
                                        setActivePanel('overview');
                                    },
                                });
                            },
                        },
                    );
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
                    if (!attachmentToDelete?.delete_url) return;

                    deleteAttachmentForm.delete(attachmentToDelete.delete_url, {
                        preserveScroll: true,
                        onSuccess: () => {
                            finishSuccessfulSubmission(deleteAttachmentForm, {
                                afterSuccess: () => setAttachmentToDelete(null),
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
            <dt className="text-xs font-semibold uppercase text-[color:var(--muted)]">{label}</dt>
            <dd className="text-sm font-medium text-[color:var(--text)]">{value ?? t('common.not_set')}</dd>
        </div>
    );
}

function InfoRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="surface-muted flex items-center justify-between gap-3 px-4 py-3">
            <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">{label}</p>
            <div className="text-right text-sm font-semibold text-[color:var(--text)]">{value}</div>
        </div>
    );
}

function CountBadge({ value }: { value: number }) {
    return (
        <span className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs font-semibold uppercase text-[color:var(--muted-strong)]">
            {value}
        </span>
    );
}

function WorkspaceNavButton({
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
    icon: IconKind;
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
                <NavIcon kind={icon} />
            </span>
            <span className="min-w-0 flex-1">
                <span className="flex items-center justify-between gap-3">
                    <span className="truncate text-sm font-semibold">{label}</span>
                    {typeof badge === 'number' ? <CountBadge value={badge} /> : null}
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

function NavIcon({ kind }: { kind: IconKind }) {
    const paths: Record<IconKind, ReactNode> = {
        workspace: (
            <>
                <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h4A2.5 2.5 0 0 1 13 7.5v3A2.5 2.5 0 0 1 10.5 13h-4A2.5 2.5 0 0 1 4 10.5v-3Z" />
                <path d="M11 17.5A2.5 2.5 0 0 1 13.5 15h4a2.5 2.5 0 0 1 2.5 2.5v1A2.5 2.5 0 0 1 17.5 21h-4a2.5 2.5 0 0 1-2.5-2.5v-1Z" />
                <path d="M15 5h2.5A2.5 2.5 0 0 1 20 7.5v1A2.5 2.5 0 0 1 17.5 11H15A2.5 2.5 0 0 1 12.5 8.5v-1A2.5 2.5 0 0 1 15 5Z" />
                <path d="M4 17.5A2.5 2.5 0 0 1 6.5 15H9a2.5 2.5 0 0 1 2.5 2.5v1A2.5 2.5 0 0 1 9 21H6.5A2.5 2.5 0 0 1 4 18.5v-1Z" />
            </>
        ),
        attachment: <path d="M8.5 12.5 14 7a3 3 0 1 1 4.2 4.2l-7.1 7.1a4.5 4.5 0 1 1-6.4-6.4l7.4-7.4" />,
        review: (
            <>
                <path d="M5 6.5A1.5 1.5 0 0 1 6.5 5h9A1.5 1.5 0 0 1 17 6.5V9h2.5A1.5 1.5 0 0 1 21 10.5v8a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5v-8A1.5 1.5 0 0 1 4.5 9H7V6.5Z" />
                <path d="m9.5 14 1.7 1.7 3.8-4.2" />
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
    if (attachment.created_at) parts.push(formatDateTime(attachment.created_at));
    return parts.filter(Boolean).join(' | ') || t('common.not_available');
}

function formatBytes(value?: number | null) {
    if (!value) return null;
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}
