import FormalRequestLetter from '@/Components/Ui/FormalRequestLetter';
import StatusBadge from '@/Components/Ui/StatusBadge';
import RequesterLayout from '@/Layouts/RequesterLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

type LawsuitShowProps = {
    requestItem: {
        id: string;
        request_code: string;
        subject: string;
        formal_letter: {
            template_name?: string | null;
            language?: string | null;
            header_image_url?: string | null;
            footer_image_url?: string | null;
            salutation_template?: string | null;
            body_content: string;
            closing_content?: string | null;
            reference_number?: string | null;
            subject?: string | null;
            date_submitted?: string | null;
            department_name?: string | null;
        };
        status: string;
        date_submitted: string | null;
        reviewer_notes?: string | null;
        attachments: Array<{ id: string; original_name: string; download_url: string }>;
        can_edit: boolean;
    };
};

export default function RequesterLawsuitShow({ requestItem }: LawsuitShowProps) {
    const { t } = useI18n();

    return (
        <RequesterLayout
            breadcrumbs={[
                { label: t('requester.nav_dashboard'), href: route('requester.dashboard') },
                { label: t('requester.nav_lawsuit'), href: route('requester.lawsuit-requests.index') },
                { label: requestItem.request_code },
            ]}
        >
            <Head title={requestItem.request_code} />

            <div className="mx-auto max-w-5xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-1">
                        <p className="font-mono text-xs text-[color:var(--muted)]">{requestItem.request_code}</p>
                        <h1 className="text-xl font-bold text-[color:var(--text)]">{requestItem.subject}</h1>
                        <StatusBadge value={requestItem.status} />
                    </div>
                    {requestItem.can_edit ? (
                        <Link
                            href={route('requester.lawsuit-requests.edit', { lawsuitRequest: requestItem.id })}
                            className="btn-base btn-secondary focus-ring"
                        >
                            {t('requester.edit_lawsuit')}
                        </Link>
                    ) : null}
                </div>

                <div className="divide-y divide-[color:var(--border)] rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)]">
                    <DetailRow
                        label={t('requester.date_submitted')}
                        value={requestItem.date_submitted ?? t('common.not_available')}
                    />
                </div>

                <FormalRequestLetter title={t('requester.letter_preview')} document={requestItem.formal_letter} />

                {requestItem.reviewer_notes ? (
                    <div className="rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)] px-5 py-4">
                        <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                            {t('requester.reviewer_notes')}
                        </p>
                        <p className="mt-2 whitespace-pre-wrap text-sm text-[color:var(--text)]">
                            {requestItem.reviewer_notes}
                        </p>
                    </div>
                ) : null}

                <div className="rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)]">
                    <div className="border-b border-[color:var(--border)] px-5 py-4">
                        <h2 className="text-sm font-semibold text-[color:var(--text)]">
                            {t('requester.attachments')}
                        </h2>
                    </div>
                    {requestItem.attachments.length === 0 ? (
                        <div className="px-5 py-8 text-center text-sm text-[color:var(--muted)]">
                            {t('requester.no_attachments')}
                        </div>
                    ) : (
                        <ul className="divide-y divide-[color:var(--border)]">
                            {requestItem.attachments.map((attachment) => (
                                <li key={attachment.id} className="flex items-center justify-between px-5 py-3">
                                    <p className="truncate text-sm text-[color:var(--text)]">
                                        {attachment.original_name}
                                    </p>
                                    <a
                                        href={attachment.download_url}
                                        className="btn-base btn-secondary focus-ring ml-4 shrink-0 text-xs"
                                        download
                                    >
                                        {t('requester.download')}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div>
                    <Link
                        href={route('requester.lawsuit-requests.index')}
                        className="btn-base btn-secondary focus-ring text-xs"
                    >
                        ← {t('requester.back_to_list')}
                    </Link>
                </div>
            </div>
        </RequesterLayout>
    );
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start gap-4 px-5 py-4">
            <p className="w-40 shrink-0 text-xs font-semibold uppercase text-[color:var(--muted)]">{label}</p>
            <p className="text-sm text-[color:var(--text)]">{value}</p>
        </div>
    );
}
