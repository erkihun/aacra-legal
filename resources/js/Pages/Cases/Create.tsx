import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type CreateCaseProps = {
    courts: Array<{ id: string; name_en: string; name_am: string }>;
    caseTypes: Array<{ id: string; name_en: string; name_am: string }>;
    priorityOptions: Array<{ label: string; value: string }>;
};

export default function CasesCreate({
    courts,
    caseTypes,
    priorityOptions,
}: CreateCaseProps) {
    const { t, locale } = useI18n();
    const { data, setData, post, processing, errors } = useForm({
        external_court_file_number: '',
        court_id: courts[0]?.id ?? '',
        case_type_id: caseTypes[0]?.id ?? '',
        plaintiff: '',
        defendant: '',
        bench_or_chamber: '',
        claim_summary: '',
        institution_position: '',
        filing_date: '',
        next_hearing_date: '',
        priority: priorityOptions[1]?.value ?? 'medium',
        attachments: [] as File[],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        post(route('cases.store'), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.legal_cases'), href: route('cases.index') },
                { label: t('cases.register_case') },
            ]}
        >
            <Head title={t('cases.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('cases.eyebrow')}
                    title={t('cases.create_title')}
                    description={t('cases.create_description')}
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid gap-4 xl:grid-cols-[1.1fr,0.9fr]">
                        <SurfaceCard className="space-y-5">
                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField label={t('cases.court')} required>
                                    <select
                                        value={data.court_id}
                                        onChange={(event) => setData('court_id', event.target.value)}
                                        className="select-ui"
                                    >
                                        {courts.map((court) => (
                                            <option key={court.id} value={court.id}>
                                                {locale === 'am' ? court.name_am || court.name_en : court.name_en}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>

                                <FormField label={t('cases.case_type')} required>
                                    <select
                                        value={data.case_type_id}
                                        onChange={(event) => setData('case_type_id', event.target.value)}
                                        className="select-ui"
                                    >
                                        {caseTypes.map((caseType) => (
                                            <option key={caseType.id} value={caseType.id}>
                                                {locale === 'am' ? caseType.name_am || caseType.name_en : caseType.name_en}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField label={t('cases.plaintiff')} required error={errors.plaintiff}>
                                    <input
                                        value={data.plaintiff}
                                        onChange={(event) => setData('plaintiff', event.target.value)}
                                        className="input-ui"
                                    />
                                </FormField>
                                <FormField label={t('cases.defendant')} required error={errors.defendant}>
                                    <input
                                        value={data.defendant}
                                        onChange={(event) => setData('defendant', event.target.value)}
                                        className="input-ui"
                                    />
                                </FormField>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <FormField label={t('cases.court_file_number')} optional>
                                    <input
                                        value={data.external_court_file_number}
                                        onChange={(event) => setData('external_court_file_number', event.target.value)}
                                        className="input-ui"
                                    />
                                </FormField>
                                <FormField label={t('cases.priority')}>
                                    <select
                                        value={data.priority}
                                        onChange={(event) => setData('priority', event.target.value)}
                                        className="select-ui"
                                    >
                                        {priorityOptions.map((priority) => (
                                            <option key={priority.value} value={priority.value}>
                                                {priority.label}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                            </div>
                        </SurfaceCard>

                        <SurfaceCard className="space-y-5">
                            <FormField label={t('cases.claim_summary')} required>
                                <textarea
                                    value={data.claim_summary}
                                    onChange={(event) => setData('claim_summary', event.target.value)}
                                    rows={6}
                                    className="textarea-ui"
                                />
                            </FormField>

                            <FormField label={t('cases.institution_position')} required>
                                <textarea
                                    value={data.institution_position}
                                    onChange={(event) => setData('institution_position', event.target.value)}
                                    rows={4}
                                    className="textarea-ui"
                                />
                            </FormField>

                            <div className="grid gap-4 md:grid-cols-3">
                                <FormField label={t('reports.opened_at')} optional>
                                    <input
                                        type="date"
                                        value={data.filing_date}
                                        onChange={(event) => setData('filing_date', event.target.value)}
                                        className="input-ui"
                                    />
                                </FormField>
                                <FormField label={t('cases.next_hearing')} optional>
                                    <input
                                        type="date"
                                        value={data.next_hearing_date}
                                        onChange={(event) => setData('next_hearing_date', event.target.value)}
                                        className="input-ui"
                                    />
                                </FormField>
                                <FormField label={t('common.attachments')} optional>
                                    <input
                                        type="file"
                                        multiple
                                        onChange={(event) => setData('attachments', Array.from(event.target.files ?? []))}
                                        className="input-ui file:me-4 file:rounded-full file:border-0 file:bg-[color:var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                    />
                                </FormField>
                            </div>
                        </SurfaceCard>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <Link href={route('cases.index')} className="btn-base btn-secondary focus-ring">
                            {t('common.cancel')}
                        </Link>
                        <button type="submit" disabled={processing} className="btn-base btn-primary focus-ring">
                            {t('cases.register_case')}
                        </button>
                    </div>
                </form>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
