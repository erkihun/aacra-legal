import StatusBadge from '@/Components/Ui/StatusBadge';
import { useDateFormatter } from '@/lib/dates';
import { useI18n } from '@/lib/i18n';
import { sanitizeRichTextHtml } from '@/lib/sanitize-rich-text';
import { localizeName } from '@/Pages/Complaints/shared';
import { Head } from '@inertiajs/react';
import { useEffect, type ReactNode } from 'react';

type Attachment = {
    id: string;
    original_name: string;
    view_url?: string | null;
    download_url?: string | null;
};

type ComplaintItem = {
    complaint_number: string;
    complainant_name?: string | null;
    complainant_phone?: string | null;
    complainant_city?: string | null;
    complainant_sub_city?: string | null;
    complainant_woreda?: string | null;
    complainant_house_number?: string | null;
    complaint_essence?: string | null;
    subject?: string | null;
    details?: string | null;
    incident_date?: string | null;
    incident_sub_city?: string | null;
    incident_woreda?: string | null;
    concerned_employee_name?: string | null;
    evidence_note?: string | null;
    requested_resolution?: string | null;
    branch?: { name_en?: string | null; name_am?: string | null } | null;
    department?: { name_en?: string | null; name_am?: string | null } | null;
    status?: string | null;
    submitted_at?: string | null;
    attachments?: Attachment[];
};

type Props = {
    complaintItem: ComplaintItem;
};

export default function ComplaintPrint({ complaintItem }: Props) {
    const attachments = Array.isArray(complaintItem.attachments) ? complaintItem.attachments : [];
    const { locale, t } = useI18n();
    const { formatDate, formatDateTime } = useDateFormatter();

    useEffect(() => {
        document.body.classList.add('bg-white');

        return () => {
            document.body.classList.remove('bg-white');
        };
    }, []);

    return (
        <>
            <Head title={`${complaintItem.complaint_number} ${t('complaints.print.document_suffix')}`} />

            <div className="min-h-screen bg-white px-4 py-6 text-slate-900 print:px-0 print:py-0">
                <div className="mx-auto max-w-5xl space-y-5 print:max-w-none">
                    <div className="flex items-start justify-between gap-4 border-b border-slate-300 pb-4 print:hidden">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{t('complaints.print.header')}</p>
                            <h1 className="mt-1 text-2xl font-semibold">{complaintItem.complaint_number}</h1>
                        </div>
                        <div className="flex gap-2">
                            <button type="button" onClick={() => window.print()} className="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                                {t('complaints.print.actions.print')}
                            </button>
                            <button type="button" onClick={() => window.close()} className="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                                {t('complaints.print.actions.close')}
                            </button>
                        </div>
                    </div>

                    <div className="border border-slate-300 p-6 print:border-none print:p-0">
                        <div className="flex flex-wrap items-start justify-between gap-4 border-b border-slate-300 pb-5">
                            <div>
                                <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{t('complaints.print.title')}</p>
                                <h1 className="mt-2 text-2xl font-semibold">{complaintItem.complaint_number}</h1>
                                <p className="mt-2 text-sm text-slate-600">{t('complaints.print.submitted_at')}: {formatDateTime(complaintItem.submitted_at, '-')}</p>
                            </div>
                            <div className="text-right">
                                {complaintItem.status ? <StatusBadge value={complaintItem.status} /> : null}
                            </div>
                        </div>

                        <div className="mt-5 space-y-5">
                            <PrintSection title={t('complaints.sections.a.title')}>
                                <PrintGrid
                                    items={[
                                        [t('complaints.form.labels.complainant_name'), displayValue(complaintItem.complainant_name)],
                                        [t('complaints.form.labels.complainant_phone'), displayValue(complaintItem.complainant_phone)],
                                        [t('complaints.form.labels.complainant_city'), displayValue(complaintItem.complainant_city)],
                                        [t('complaints.form.labels.complainant_sub_city'), displayValue(complaintItem.complainant_sub_city)],
                                        [t('complaints.form.labels.complainant_woreda'), displayValue(complaintItem.complainant_woreda)],
                                        [t('complaints.form.labels.complainant_house_number'), displayValue(complaintItem.complainant_house_number)],
                                    ]}
                                />
                            </PrintSection>

                            <PrintSection title={t('complaints.sections.b.title')}>
                                <NarrativeBlock value={complaintItem.complaint_essence ?? complaintItem.details ?? complaintItem.subject} />
                            </PrintSection>

                            <PrintSection title={t('complaints.sections.c.title')}>
                                <PrintGrid
                                    items={[
                                        [t('complaints.form.labels.incident_date'), formatDate(complaintItem.incident_date, '-')],
                                        [t('complaints.form.labels.branch'), localizeName(complaintItem.branch, locale)],
                                        [t('complaints.form.labels.incident_sub_city'), displayValue(complaintItem.incident_sub_city)],
                                        [t('complaints.form.labels.incident_woreda'), displayValue(complaintItem.incident_woreda)],
                                    ]}
                                />
                            </PrintSection>

                            <PrintSection title={t('complaints.sections.d.title')}>
                                <PrintGrid
                                    items={[
                                        [t('complaints.form.labels.department'), localizeName(complaintItem.department, locale)],
                                    ]}
                                />
                            </PrintSection>

                            <PrintSection title={t('complaints.sections.e.title')}>
                                <PrintGrid
                                    items={[
                                        [t('complaints.form.labels.concerned_employee_name'), displayValue(complaintItem.concerned_employee_name)],
                                    ]}
                                />
                            </PrintSection>

                            <PrintSection title={t('complaints.sections.f.title')}>
                                <PrintGrid
                                    items={[
                                        [t('complaints.form.labels.evidence_note'), displayValue(complaintItem.evidence_note)],
                                    ]}
                                />
                                <div className="mt-4 space-y-3">
                                    {attachments.length === 0 ? (
                                        <p className="text-sm text-slate-600">{t('complaints.empty.no_attachments')}</p>
                                    ) : (
                                        attachments.map((attachment) => (
                                            <div key={attachment.id} className="border border-slate-200 px-3 py-3 text-sm text-slate-900">
                                                {attachment.original_name}
                                            </div>
                                        ))
                                    )}
                                </div>
                            </PrintSection>

                            <PrintSection title={t('complaints.sections.g.title')}>
                                <NarrativeBlock value={complaintItem.requested_resolution} />
                            </PrintSection>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

function PrintSection({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="border border-slate-300 px-4 py-4">
            <h2 className="text-base font-semibold text-slate-900">{title}</h2>
            <div className="mt-4">{children}</div>
        </section>
    );
}

function PrintGrid({ items }: { items: Array<[string, string]> }) {
    return (
        <div className="grid gap-3 md:grid-cols-2">
            {items.map(([label, value]) => (
                <div key={label} className="border border-slate-200 px-3 py-3">
                    <p className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p>
                    <p className="mt-2 text-sm text-slate-900">{value}</p>
                </div>
            ))}
        </div>
    );
}

function NarrativeBlock({ value }: { value?: string | null }) {
    const normalized = (value ?? '').trim();

    if (normalized === '') {
        return <p className="text-sm text-slate-600">-</p>;
    }

    if (/<\/?[a-z][\s\S]*>/i.test(normalized)) {
        return <div className="prose prose-slate max-w-none" dangerouslySetInnerHTML={{ __html: sanitizeRichTextHtml(normalized) }} />;
    }

    return <p className="whitespace-pre-line text-sm leading-7 text-slate-900">{normalized}</p>;
}

function displayValue(value?: string | null) {
    return value && value.trim() !== '' ? value : '-';
}
