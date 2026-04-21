import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Option = {
    value: string;
    label: string;
};

type ComplaintItem = {
    id: string;
    branch?: { id?: string | null } | null;
    department?: { id?: string | null } | null;
    subject?: string | null;
    details?: string | null;
    category?: string | null;
    priority?: string | null;
    complainant_type?: string | null;
    complaint_number?: string | null;
    status?: string | null;
};

type Props = {
    mode?: 'create' | 'edit';
    complaintItem?: ComplaintItem | null;
    branches: Array<{ id: string; name_en: string; name_am?: string | null }>;
    departments: Array<{ id: string; name_en: string; name_am?: string | null }>;
    priorityOptions: Option[];
    complainantTypeOptions: Option[];
    derivedComplainantType?: string | null;
    authUser: {
        branch_id?: string | null;
        department_id?: string | null;
    };
};

export default function ComplaintCreate({
    mode = 'create',
    complaintItem,
    branches,
    departments,
    priorityOptions,
    complainantTypeOptions,
    derivedComplainantType,
    authUser,
}: Props) {
    const isEdit = mode === 'edit' && complaintItem;
    const currentComplainantType = complaintItem?.complainant_type ?? derivedComplainantType ?? 'client';
    const complainantTypeLabel = complainantTypeOptions.find((option) => option.value === currentComplainantType)?.label ?? currentComplainantType;

    const form = useForm({
        branch_id: complaintItem?.branch?.id ?? authUser.branch_id ?? '',
        department_id: complaintItem?.department?.id ?? authUser.department_id ?? '',
        subject: complaintItem?.subject ?? '',
        details: complaintItem?.details ?? '',
        category: complaintItem?.category ?? '',
        priority: complaintItem?.priority ?? priorityOptions[1]?.value ?? priorityOptions[0]?.value ?? '',
        attachments: [] as File[],
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (isEdit) {
            form.patch(route('complaints.update', complaintItem.id));
            return;
        }

        form.post(route('complaints.store'), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Complaints', href: route('complaints.index') },
                { label: isEdit ? complaintItem.complaint_number ?? 'Edit complaint' : 'New complaint' },
            ]}
        >
            <Head title={isEdit ? 'Edit Complaint' : 'Submit Complaint'} />
            <PageContainer>
                <SectionHeader
                    eyebrow="Complaint management"
                    title={isEdit ? complaintItem?.complaint_number ?? 'Edit complaint' : 'Submit complaint'}
                    description={
                        isEdit
                            ? 'Revise the complaint routing and narrative before the workflow moves further.'
                            : 'Record the branch, responsible department, subject, details, and supporting files for this complaint.'
                    }
                    action={
                        isEdit && complaintItem ? (
                            <div className="flex items-center gap-2">
                                {complaintItem.status ? <StatusBadge value={complaintItem.status} /> : null}
                                <Link href={route('complaints.show', complaintItem.id)} className="btn-base btn-secondary focus-ring">
                                    Open Complaint
                                </Link>
                            </div>
                        ) : undefined
                    }
                />

                <SurfaceCard>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-4 lg:grid-cols-[1.4fr,0.9fr,0.9fr]">
                            <div className="surface-muted rounded-3xl px-5 py-5">
                                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-[color:var(--muted)]">Complainant profile</p>
                                <div className="mt-3 flex flex-wrap items-center gap-3">
                                    <StatusBadge label={complainantTypeLabel} value={currentComplainantType} />
                                    {isEdit && complaintItem?.complaint_number ? (
                                        <span className="text-sm font-medium text-[color:var(--muted-strong)]">{complaintItem.complaint_number}</span>
                                    ) : null}
                                </div>
                                <p className="mt-3 text-sm leading-6 text-[color:var(--muted-strong)]">
                                    The complainant type is derived from the signed-in account, while the branch and department routing can still be adjusted as needed.
                                </p>
                            </div>

                            <FormField label="Branch" optional error={form.errors.branch_id}>
                                <select className="select-ui" value={form.data.branch_id} onChange={(event) => form.setData('branch_id', event.target.value)}>
                                    <option value="">Select branch</option>
                                    {branches.map((branch) => (
                                        <option key={branch.id} value={branch.id}>
                                            {branch.name_en}
                                        </option>
                                    ))}
                                </select>
                            </FormField>

                            <FormField label="Department" required error={form.errors.department_id}>
                                <select className="select-ui" value={form.data.department_id} onChange={(event) => form.setData('department_id', event.target.value)}>
                                    <option value="">Select department</option>
                                    {departments.map((department) => (
                                        <option key={department.id} value={department.id}>
                                            {department.name_en}
                                        </option>
                                    ))}
                                </select>
                            </FormField>
                        </div>

                        <div className="grid gap-4 md:grid-cols-[1.8fr,1fr,1fr]">
                            <FormField label="Subject" required error={form.errors.subject}>
                                <input className="input-ui" value={form.data.subject} onChange={(event) => form.setData('subject', event.target.value)} />
                            </FormField>
                            <FormField label="Category" optional error={form.errors.category}>
                                <input className="input-ui" value={form.data.category} onChange={(event) => form.setData('category', event.target.value)} />
                            </FormField>
                            <FormField label="Priority" optional error={form.errors.priority}>
                                <select className="select-ui" value={form.data.priority} onChange={(event) => form.setData('priority', event.target.value)}>
                                    {priorityOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </FormField>
                        </div>

                        <FormField label="Complaint details" required error={form.errors.details}>
                            <RichTextEditor value={form.data.details} onChange={(value) => form.setData('details', value)} minHeight={360} />
                        </FormField>

                        {!isEdit ? (
                            <FormField label="Attachments" optional error={form.errors.attachments}>
                                <input
                                    type="file"
                                    multiple
                                    className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                    onChange={(event) => form.setData('attachments', Array.from(event.target.files ?? []))}
                                />
                            </FormField>
                        ) : null}

                        <div className="flex justify-end border-t border-[color:var(--border)] pt-5">
                            <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                                {isEdit ? 'Save Complaint' : 'Submit Complaint'}
                            </button>
                        </div>
                    </form>
                </SurfaceCard>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
