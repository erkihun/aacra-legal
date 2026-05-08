import BackButton from '@/Components/Ui/BackButton';
import FileAttachmentCard from '@/Components/Ui/FileAttachmentCard';
import FormField from '@/Components/Ui/FormField';
import LocalizedDateInput from '@/Components/Ui/LocalizedDateInput';
import PageContainer from '@/Components/Ui/PageContainer';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { localizeName } from '@/Pages/Complaints/shared';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import type { FormEvent, ReactNode } from 'react';

type Option = {
    value: string;
    label: string;
};

type Attachment = {
    id: string;
    original_name: string;
    view_url?: string | null;
    download_url?: string | null;
};

type ComplaintItem = {
    id: string;
    branch?: { id?: string | null; name_en?: string | null; name_am?: string | null } | null;
    department?: { id?: string | null; branch_id?: string | null; name_en?: string | null; name_am?: string | null } | null;
    complainant_name?: string | null;
    complainant_phone?: string | null;
    complainant_city?: string | null;
    complainant_sub_city?: string | null;
    complainant_woreda?: string | null;
    complainant_house_number?: string | null;
    subject?: string | null;
    details?: string | null;
    complaint_essence?: string | null;
    incident_date?: string | null;
    incident_sub_city?: string | null;
    incident_woreda?: string | null;
    concerned_employee_name?: string | null;
    evidence_note?: string | null;
    requested_resolution?: string | null;
    complainant_type?: string | null;
    complaint_number?: string | null;
    status?: string | null;
    attachments?: Attachment[];
};

type Props = {
    mode?: 'create' | 'edit';
    complaintItem?: ComplaintItem | null;
    branches: Array<{ id: string; name_en: string; name_am?: string | null }>;
    departments: Array<{ id: string; branch_id?: string | null; name_en: string; name_am?: string | null }>;
    authUser: {
        name?: string | null;
        phone?: string | null;
        branch_id?: string | null;
        department_id?: string | null;
    };
    priorityOptions?: Option[];
    complainantTypeOptions?: Option[];
    derivedComplainantType?: string | null;
};

type ComplaintFormData = {
    complainant_name: string;
    complainant_city: string;
    complainant_sub_city: string;
    complainant_woreda: string;
    complainant_house_number: string;
    complainant_phone: string;
    complaint_essence: string;
    incident_date: string;
    branch_id: string;
    incident_sub_city: string;
    incident_woreda: string;
    department_id: string;
    concerned_employee_name: string;
    evidence_note: string;
    requested_resolution: string;
    attachments: File[];
};

export default function ComplaintCreate({
    mode = 'create',
    complaintItem,
    branches,
    departments,
    authUser,
}: Props) {
    const isEdit = mode === 'edit' && complaintItem !== null;
    const existingAttachments = Array.isArray(complaintItem?.attachments) ? complaintItem.attachments : [];

    const form = useForm<ComplaintFormData>({
        complainant_name: complaintItem?.complainant_name ?? authUser.name ?? '',
        complainant_city: complaintItem?.complainant_city ?? '',
        complainant_sub_city: complaintItem?.complainant_sub_city ?? '',
        complainant_woreda: complaintItem?.complainant_woreda ?? '',
        complainant_house_number: complaintItem?.complainant_house_number ?? '',
        complainant_phone: complaintItem?.complainant_phone ?? authUser.phone ?? '',
        complaint_essence: complaintItem?.complaint_essence ?? complaintItem?.subject ?? complaintItem?.details ?? '',
        incident_date: complaintItem?.incident_date ?? '',
        branch_id: complaintItem?.branch?.id ?? authUser.branch_id ?? '',
        incident_sub_city: complaintItem?.incident_sub_city ?? '',
        incident_woreda: complaintItem?.incident_woreda ?? '',
        department_id: complaintItem?.department?.id ?? authUser.department_id ?? '',
        concerned_employee_name: complaintItem?.concerned_employee_name ?? '',
        evidence_note: complaintItem?.evidence_note ?? '',
        requested_resolution: complaintItem?.requested_resolution ?? '',
        attachments: [] as File[],
    });

    const { locale, t } = useI18n();
    const selectedDepartment = departments.find((department) => department.id === form.data.department_id) ?? null;
    const filteredDepartments = departments.filter((department) => department.branch_id === form.data.branch_id);
    const departmentOptions =
        selectedDepartment !== null && !filteredDepartments.some((department) => department.id === selectedDepartment.id)
            ? [selectedDepartment, ...filteredDepartments]
            : filteredDepartments;

    useEffect(() => {
        if (form.data.branch_id === '' || form.data.department_id === '') {
            return;
        }

        if (selectedDepartment === null || selectedDepartment.branch_id !== form.data.branch_id) {
            form.setData('department_id', '');
        }
    }, [form, form.data.branch_id, form.data.department_id, selectedDepartment]);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (isEdit && complaintItem) {
            form.patch(route('complaints.update', complaintItem.id), {
                forceFormData: true,
            });

            return;
        }

        form.post(route('complaints.store'), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.complaints'), href: route('complaints.index') },
                { label: isEdit ? complaintItem?.complaint_number ?? t('complaints.breadcrumbs.edit') : t('complaints.breadcrumbs.new') },
            ]}
        >
            <Head title={isEdit ? t('complaints.edit_title') : t('complaints.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('complaints.eyebrow')}
                    title={isEdit ? complaintItem?.complaint_number ?? t('complaints.edit_title') : t('complaints.create_title')}
                    description={t('complaints.create_description')}
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <BackButton fallbackHref={route('complaints.index')} />
                            {isEdit && complaintItem ? (
                                <>
                                    {complaintItem.status ? <StatusBadge value={complaintItem.status} /> : null}
                                    <Link href={route('complaints.show', complaintItem.id)} className="btn-base btn-secondary focus-ring">
                                        {t('complaints.open')}
                                    </Link>
                                </>
                            ) : null}
                        </div>
                    }
                />

                <form onSubmit={submit} className="space-y-5">
                    <ComplaintSection title={t('complaints.sections.a.title')} description={t('complaints.sections.a.description')}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('complaints.form.labels.complainant_name')} required error={form.errors.complainant_name}>
                                <input className="input-ui" value={form.data.complainant_name} onChange={(event) => form.setData('complainant_name', event.target.value)} />
                            </FormField>

                            <FormField label={t('complaints.form.labels.complainant_phone')} required error={form.errors.complainant_phone}>
                                <input className="input-ui" value={form.data.complainant_phone} onChange={(event) => form.setData('complainant_phone', event.target.value)} />
                            </FormField>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <FormField label={t('complaints.form.labels.complainant_city')} optional error={form.errors.complainant_city}>
                                <input className="input-ui" value={form.data.complainant_city} onChange={(event) => form.setData('complainant_city', event.target.value)} />
                            </FormField>

                            <FormField label={t('complaints.form.labels.complainant_sub_city')} optional error={form.errors.complainant_sub_city}>
                                <input className="input-ui" value={form.data.complainant_sub_city} onChange={(event) => form.setData('complainant_sub_city', event.target.value)} />
                            </FormField>

                            <FormField label={t('complaints.form.labels.complainant_woreda')} optional error={form.errors.complainant_woreda}>
                                <input className="input-ui" value={form.data.complainant_woreda} onChange={(event) => form.setData('complainant_woreda', event.target.value)} />
                            </FormField>

                            <FormField label={t('complaints.form.labels.complainant_house_number')} optional error={form.errors.complainant_house_number}>
                                <input className="input-ui" value={form.data.complainant_house_number} onChange={(event) => form.setData('complainant_house_number', event.target.value)} />
                            </FormField>
                        </div>
                    </ComplaintSection>

                    <ComplaintSection title={t('complaints.sections.b.title')} description={t('complaints.sections.b.description')}>
                        <FormField label={t('complaints.form.labels.complaint_essence')} required error={form.errors.complaint_essence}>
                            <RichTextEditor value={form.data.complaint_essence} onChange={(value) => form.setData('complaint_essence', value)} minHeight={320} />
                        </FormField>
                    </ComplaintSection>

                    <ComplaintSection title={t('complaints.sections.c.title')} description={t('complaints.sections.c.description')}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('complaints.form.labels.incident_date')} required error={form.errors.incident_date}>
                                <LocalizedDateInput value={form.data.incident_date} onChange={(value) => form.setData('incident_date', value)} className="input-ui" />
                            </FormField>

                            <FormField label={t('complaints.form.labels.branch')} required error={form.errors.branch_id}>
                                <select className="select-ui" value={form.data.branch_id} onChange={(event) => form.setData('branch_id', event.target.value)}>
                                    <option value="">{t('complaints.placeholders.select_office')}</option>
                                    {branches.map((branch) => (
                                        <option key={branch.id} value={branch.id}>
                                            {localizeName(branch, locale)}
                                        </option>
                                    ))}
                                </select>
                            </FormField>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label={t('complaints.form.labels.incident_sub_city')} optional error={form.errors.incident_sub_city}>
                                <input className="input-ui" value={form.data.incident_sub_city} onChange={(event) => form.setData('incident_sub_city', event.target.value)} />
                            </FormField>

                            <FormField label={t('complaints.form.labels.incident_woreda')} optional error={form.errors.incident_woreda}>
                                <input className="input-ui" value={form.data.incident_woreda} onChange={(event) => form.setData('incident_woreda', event.target.value)} />
                            </FormField>
                        </div>
                    </ComplaintSection>

                    <ComplaintSection title={t('complaints.sections.d.title')} description={t('complaints.sections.d.description')}>
                        <FormField label={t('complaints.form.labels.department')} required error={form.errors.department_id}>
                            <select
                                className="select-ui"
                                value={form.data.department_id}
                                disabled={form.data.branch_id === '' && form.data.department_id === ''}
                                onChange={(event) => form.setData('department_id', event.target.value)}
                            >
                                <option value="">{form.data.branch_id === '' ? t('complaints.placeholders.select_office_first') : t('complaints.placeholders.select_department')}</option>
                                {departmentOptions.map((department) => (
                                    <option key={department.id} value={department.id}>
                                        {localizeName(department, locale)}
                                    </option>
                                ))}
                            </select>
                        </FormField>
                    </ComplaintSection>

                    <ComplaintSection title={t('complaints.sections.e.title')} description={t('complaints.sections.e.description')}>
                        <FormField label={t('complaints.form.labels.concerned_employee_name')} optional error={form.errors.concerned_employee_name}>
                            <input className="input-ui" value={form.data.concerned_employee_name} onChange={(event) => form.setData('concerned_employee_name', event.target.value)} />
                        </FormField>
                    </ComplaintSection>

                    <ComplaintSection title={t('complaints.sections.f.title')} description={t('complaints.sections.f.description')}>
                        <FormField label={t('complaints.form.labels.evidence_note')} optional error={form.errors.evidence_note}>
                            <textarea
                                rows={4}
                                className="textarea-ui min-h-28"
                                value={form.data.evidence_note}
                                onChange={(event) => form.setData('evidence_note', event.target.value)}
                            />
                        </FormField>

                        <FormField label={t('complaints.form.labels.attachments')} optional error={form.errors.attachments}>
                            <input
                                type="file"
                                multiple
                                className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                onChange={(event) => form.setData('attachments', Array.from(event.target.files ?? []))}
                            />
                        </FormField>

                        {isEdit && existingAttachments.length > 0 ? (
                            <div className="space-y-3">
                                <p className="text-sm font-semibold text-[color:var(--text)]">{t('complaints.attachments.existing')}</p>
                                {existingAttachments.map((attachment) => (
                                    <FileAttachmentCard
                                        key={attachment.id}
                                        name={attachment.original_name}
                                        viewUrl={attachment.view_url ?? undefined}
                                        downloadUrl={attachment.download_url ?? undefined}
                                    />
                                ))}
                            </div>
                        ) : null}
                    </ComplaintSection>

                    <ComplaintSection title={t('complaints.sections.g.title')} description={t('complaints.sections.g.description')}>
                        <FormField label={t('complaints.form.labels.requested_resolution')} required error={form.errors.requested_resolution}>
                            <textarea
                                rows={5}
                                className="textarea-ui min-h-32"
                                value={form.data.requested_resolution}
                                onChange={(event) => form.setData('requested_resolution', event.target.value)}
                            />
                        </FormField>
                    </ComplaintSection>

                    <div className="flex justify-end gap-3">
                        <BackButton fallbackHref={isEdit && complaintItem ? route('complaints.show', complaintItem.id) : route('complaints.index')} />
                        <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                            {isEdit ? t('complaints.buttons.save') : t('complaints.buttons.submit')}
                        </button>
                    </div>
                </form>
            </PageContainer>
        </AuthenticatedLayout>
    );
}

function ComplaintSection({
    title,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <SurfaceCard>
            <div className="space-y-5">
                <div className="border-b border-[color:var(--border)] pb-4">
                    <h2 className="text-lg font-semibold text-[color:var(--text)]">{stripSectionPrefix(title)}</h2>
                </div>
                <div className="space-y-4">{children}</div>
            </div>
        </SurfaceCard>
    );
}

function stripSectionPrefix(title: string) {
    return title.replace(/^[A-Z]\.\s*/u, '');
}
