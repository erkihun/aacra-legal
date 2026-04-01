import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useMemo } from 'react';

type CaseTypeOption = {
    id: string;
    code: string;
    name_en: string;
    name_am: string;
    main_case_type: 'civil-law' | 'crime' | 'labour-dispute';
};

type CaseFormProps = {
    courts: Array<{ id: string; name_en: string; name_am: string }>;
    caseTypes: CaseTypeOption[];
    mainCaseTypeOptions: Array<{ label: string; value: 'civil-law' | 'crime' | 'labour-dispute' }>;
    statusOptions: Array<{ label: string; value: string }>;
    priorityOptions: Array<{ label: string; value: string }>;
    mode?: 'create' | 'edit';
    caseItem?: {
        id: string;
        case_number: string;
        main_case_type?: 'civil-law' | 'crime' | 'labour-dispute' | null;
        court?: { id: string } | null;
        case_type?: { id: string } | null;
        plaintiff?: string | null;
        defendant?: string | null;
        status?: string | null;
        claim_summary?: string | null;
        amount?: string | number | null;
        crime_scene?: string | null;
        police_station?: string | null;
        stolen_property_type?: string | null;
        stolen_property_estimated_value?: string | number | null;
        suspect_names?: string | null;
        statement_date?: string | null;
        filing_date?: string | null;
        next_hearing_date?: string | null;
        priority?: string | null;
        attachments?: Array<{ id: string; original_name: string }>;
    };
};

export default function CasesCreate({
    courts,
    caseTypes,
    mainCaseTypeOptions,
    statusOptions,
    priorityOptions,
    mode = 'create',
    caseItem,
}: CaseFormProps) {
    const { t, locale } = useI18n();
    const isEditing = mode === 'edit' && !!caseItem;

    const { data, setData, post, patch, processing, errors } = useForm({
        case_number: caseItem?.case_number ?? '',
        main_case_type: caseItem?.main_case_type ?? ('civil-law' as 'civil-law' | 'crime' | 'labour-dispute'),
        court_id: caseItem?.court?.id ?? '',
        case_type_id: caseItem?.case_type?.id ?? '',
        plaintiff: caseItem?.plaintiff ?? '',
        defendant: caseItem?.defendant ?? '',
        status: caseItem?.status ?? statusOptions.find((option) => option.value === 'under_director_review')?.value ?? statusOptions[0]?.value ?? '',
        claim_summary: caseItem?.claim_summary ?? '',
        amount: caseItem?.amount ? String(caseItem.amount) : '',
        crime_scene: caseItem?.crime_scene ?? '',
        police_station: caseItem?.police_station ?? '',
        stolen_property_type: caseItem?.stolen_property_type ?? '',
        stolen_property_estimated_value: caseItem?.stolen_property_estimated_value ? String(caseItem.stolen_property_estimated_value) : '',
        suspect_names: caseItem?.suspect_names ?? '',
        statement_date: caseItem?.statement_date ?? '',
        filing_date: caseItem?.filing_date ?? '',
        next_hearing_date: caseItem?.next_hearing_date ?? '',
        priority: caseItem?.priority ?? priorityOptions[1]?.value ?? priorityOptions[0]?.value ?? 'medium',
        attachments: [] as File[],
    });

    const civilLawTypes = useMemo(
        () => caseTypes.filter((caseType) => caseType.main_case_type === 'civil-law'),
        [caseTypes],
    );

    const isCrime = data.main_case_type === 'crime';
    const isCivilLaw = data.main_case_type === 'civil-law';
    const isLabourDispute = data.main_case_type === 'labour-dispute';

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const payload = isEditing
            ? patch
            : post;

        payload(
            isEditing
                ? route('cases.update', { legalCase: caseItem.id })
                : route('cases.store'),
            {
                forceFormData: true,
            },
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.legal_cases'), href: route('cases.index') },
                { label: isEditing ? t('cases.edit_title') : t('cases.create_title') },
            ]}
        >
            <Head title={isEditing ? t('cases.edit_title') : t('cases.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('cases.eyebrow')}
                    title={isEditing ? t('cases.edit_title') : t('cases.create_title')}
                    description={isEditing ? t('cases.edit_description') : t('cases.create_description')}
                />

                <form onSubmit={submit} className="space-y-6">
                    <SurfaceCard className="space-y-8">
                        <div className="border-b border-[color:var(--border)] pb-5">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">
                                {t('cases.form_title')}
                            </h2>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">
                                {t('cases.form_description')}
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-3">
                            <FormField label={t('cases.main_case_type_label')} required error={errors.main_case_type}>
                                <select
                                    value={data.main_case_type}
                                    onChange={(event) => {
                                        const value = event.target.value as 'civil-law' | 'crime' | 'labour-dispute';
                                        setData((current) => ({
                                            ...current,
                                            main_case_type: value,
                                            case_type_id: value === 'civil-law' ? current.case_type_id : '',
                                            court_id: value === 'crime' ? '' : current.court_id,
                                            plaintiff: value === 'crime' ? '' : current.plaintiff,
                                            defendant: value === 'crime' ? '' : current.defendant,
                                        }));
                                    }}
                                    className="select-ui"
                                >
                                    {mainCaseTypeOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </FormField>

                            {isCivilLaw ? (
                                <FormField label={t('cases.civil_law_type')} required error={errors.case_type_id}>
                                    <select
                                        value={data.case_type_id}
                                        onChange={(event) => setData('case_type_id', event.target.value)}
                                        className="select-ui"
                                    >
                                        <option value="">{t('common.not_set')}</option>
                                        {civilLawTypes.map((caseType) => (
                                            <option key={caseType.id} value={caseType.id}>
                                                {locale === 'am' ? caseType.name_am || caseType.name_en : caseType.name_en}
                                            </option>
                                        ))}
                                    </select>
                                </FormField>
                            ) : (
                                <div className="hidden md:block" />
                            )}

                            <FormField label={t('cases.case_number')} required error={errors.case_number}>
                                <input
                                    value={data.case_number}
                                    onChange={(event) => setData('case_number', event.target.value)}
                                    className="input-ui"
                                />
                            </FormField>
                        </div>

                        {isCivilLaw ? (
                            <div className="space-y-6">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <FormField label={t('cases.court')} required error={errors.court_id}>
                                        <select
                                            value={data.court_id}
                                            onChange={(event) => setData('court_id', event.target.value)}
                                            className="select-ui"
                                        >
                                            <option value="">{t('common.not_set')}</option>
                                            {courts.map((court) => (
                                                <option key={court.id} value={court.id}>
                                                    {locale === 'am' ? court.name_am || court.name_en : court.name_en}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>
                                    <FormField label={t('cases.case_status')} required error={errors.status}>
                                        <select
                                            value={data.status}
                                            onChange={(event) => setData('status', event.target.value)}
                                            className="select-ui"
                                        >
                                            {statusOptions.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>
                                    <FormField label={t('cases.amount')} optional error={errors.amount}>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={data.amount}
                                            onChange={(event) => setData('amount', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>
                                </div>

                                <div className="grid gap-4 md:grid-cols-3">
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
                                    <div className="hidden md:block" />
                                </div>

                                <FormField label={t('cases.detailed_description')} required error={errors.claim_summary}>
                                    <RichTextEditor
                                        value={data.claim_summary}
                                        onChange={(value) => setData('claim_summary', value)}
                                        minHeight={360}
                                    />
                                </FormField>
                            </div>
                        ) : null}

                        {isCrime ? (
                            <div className="space-y-6">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <FormField label={t('cases.case_status')} required error={errors.status}>
                                        <select
                                            value={data.status}
                                            onChange={(event) => setData('status', event.target.value)}
                                            className="select-ui"
                                        >
                                            {statusOptions.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>
                                    <FormField label={t('cases.crime_scene')} required error={errors.crime_scene}>
                                        <input
                                            value={data.crime_scene}
                                            onChange={(event) => setData('crime_scene', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>
                                    <FormField label={t('cases.police_station')} required error={errors.police_station}>
                                        <input
                                            value={data.police_station}
                                            onChange={(event) => setData('police_station', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>
                                </div>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <FormField label={t('cases.stolen_property_type')} required error={errors.stolen_property_type}>
                                        <input
                                            value={data.stolen_property_type}
                                            onChange={(event) => setData('stolen_property_type', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>
                                    <FormField label={t('cases.stolen_property_estimated_value')} optional error={errors.stolen_property_estimated_value}>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={data.stolen_property_estimated_value}
                                            onChange={(event) => setData('stolen_property_estimated_value', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>
                                    <FormField label={t('cases.statement_date')} required error={errors.statement_date}>
                                        <input
                                            type="date"
                                            value={data.statement_date}
                                            onChange={(event) => setData('statement_date', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>
                                </div>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="md:col-span-3">
                                        <FormField label={t('cases.suspect_names')} required error={errors.suspect_names}>
                                            <textarea
                                                value={data.suspect_names}
                                                onChange={(event) => setData('suspect_names', event.target.value)}
                                                rows={4}
                                                className="textarea-ui"
                                            />
                                        </FormField>
                                    </div>
                                </div>

                                <FormField label={t('cases.crime_details')} required error={errors.claim_summary}>
                                    <RichTextEditor
                                        value={data.claim_summary}
                                        onChange={(value) => setData('claim_summary', value)}
                                        minHeight={420}
                                    />
                                </FormField>
                            </div>
                        ) : null}

                        {isLabourDispute ? (
                            <div className="space-y-6">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <FormField label={t('cases.court')} required error={errors.court_id}>
                                        <select
                                            value={data.court_id}
                                            onChange={(event) => setData('court_id', event.target.value)}
                                            className="select-ui"
                                        >
                                            <option value="">{t('common.not_set')}</option>
                                            {courts.map((court) => (
                                                <option key={court.id} value={court.id}>
                                                    {locale === 'am' ? court.name_am || court.name_en : court.name_en}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>
                                    <FormField label={t('cases.case_status')} required error={errors.status}>
                                        <select
                                            value={data.status}
                                            onChange={(event) => setData('status', event.target.value)}
                                            className="select-ui"
                                        >
                                            {statusOptions.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>
                                    <FormField label={t('cases.amount')} optional error={errors.amount}>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={data.amount}
                                            onChange={(event) => setData('amount', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>
                                </div>

                                <div className="grid gap-4 md:grid-cols-3">
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
                                    <div className="hidden md:block" />
                                </div>

                                <FormField label={t('cases.detailed_description')} required error={errors.claim_summary}>
                                    <RichTextEditor
                                        value={data.claim_summary}
                                        onChange={(value) => setData('claim_summary', value)}
                                        minHeight={360}
                                    />
                                </FormField>
                            </div>
                        ) : null}

                        <div className="grid gap-4 border-t border-[color:var(--border)] pt-6 md:grid-cols-3">
                            <FormField label={t('cases.priority')} error={errors.priority}>
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
                            <FormField label={t('reports.opened_at')} optional error={errors.filing_date}>
                                <input
                                    type="date"
                                    value={data.filing_date}
                                    onChange={(event) => setData('filing_date', event.target.value)}
                                    className="input-ui"
                                />
                            </FormField>
                            <FormField label={t('common.attachments')} optional error={errors.attachments}>
                                <input
                                    type="file"
                                    multiple
                                    onChange={(event) => setData('attachments', Array.from(event.target.files ?? []))}
                                    className="input-ui file:me-4 file:rounded-full file:border-0 file:bg-[color:var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                />
                            </FormField>
                        </div>

                        {isEditing && (caseItem?.attachments?.length ?? 0) > 0 ? (
                            <div className="rounded-2xl border border-dashed border-[color:var(--border)] p-4">
                                <p className="text-sm font-semibold text-[color:var(--text)]">
                                    {t('common.attachments')}
                                </p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {caseItem?.attachments?.map((attachment) => (
                                        <span
                                            key={attachment.id}
                                            className="rounded-full bg-[color:var(--surface-muted)] px-3 py-1 text-xs text-[color:var(--muted)]"
                                        >
                                            {attachment.original_name}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[color:var(--border)] pt-6">
                            <Link href={route('cases.index')} className="btn-base btn-secondary focus-ring">
                                {t('common.cancel')}
                            </Link>
                            <button type="submit" disabled={processing} className="btn-base btn-primary focus-ring">
                                {isEditing ? t('common.save_changes') : t('cases.register_case')}
                            </button>
                        </div>
                    </SurfaceCard>
                </form>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
