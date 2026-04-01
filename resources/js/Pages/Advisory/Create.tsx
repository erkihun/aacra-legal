import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type CreateAdvisoryProps = {
    departments: Array<{ id: string; name_en: string; name_am: string }>;
    categories: Array<{ id: string; name_en: string; name_am: string }>;
    priorityOptions: Array<{ label: string; value: string }>;
    typeOptions: Array<{ label: string; value: string }>;
    authDepartmentId?: string | null;
    requestItem?: {
        id: string;
        department?: { id: string } | null;
        category?: { id: string } | null;
        subject: string;
        request_type: string;
        priority: string;
        description: string;
        due_date?: string | null;
    } | null;
    mode?: 'create' | 'edit';
};

export default function AdvisoryCreate({
    departments,
    categories,
    priorityOptions,
    typeOptions,
    authDepartmentId,
    requestItem = null,
    mode = 'create',
}: CreateAdvisoryProps) {
    const { t, locale } = useI18n();
    const isEditing = mode === 'edit' && requestItem !== null;
    const form = useForm({
        department_id: requestItem?.department?.id ?? authDepartmentId ?? departments[0]?.id ?? '',
        category_id: requestItem?.category?.id ?? categories[0]?.id ?? '',
        subject: requestItem?.subject ?? '',
        request_type: requestItem?.request_type ?? typeOptions[0]?.value ?? 'written',
        priority: requestItem?.priority ?? priorityOptions[1]?.value ?? 'medium',
        description: requestItem?.description ?? '',
        due_date: requestItem?.due_date ?? '',
        attachments: [] as File[],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEditing && requestItem) {
            form.transform((data) => ({
                ...data,
                _method: 'patch',
            }));

            form.post(route('advisory.update', { advisoryRequest: requestItem.id }), {
                forceFormData: true,
                onFinish: () => form.transform((data) => data),
            });

            return;
        }

        form.post(route('advisory.store'), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.advisory_requests'), href: route('advisory.index') },
                { label: isEditing ? t('advisory.resubmit_request') : t('advisory.new_request') },
            ]}
        >
            <Head title={isEditing ? t('advisory.edit_title') : t('advisory.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('advisory.eyebrow')}
                    title={isEditing ? t('advisory.edit_title') : t('advisory.create_title')}
                    description={isEditing ? t('advisory.edit_description') : t('advisory.create_description')}
                />

                <form onSubmit={submit} className="space-y-6">
                    <SurfaceCard strong className="overflow-hidden p-0">
                        <div className="border-b border-[color:var(--border)]/80 px-6 py-5 md:px-7">
                            <div className="flex flex-wrap justify-end gap-4">
                                <div className="surface-muted min-w-56 px-4 py-3">
                                    <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                                        {t('advisory.request_type')}
                                    </p>
                                    <p className="mt-2 text-sm font-semibold text-[color:var(--text)]">
                                        {typeOptions.find((type) => type.value === form.data.request_type)?.label ?? t('common.not_available')}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-8 px-6 py-6 md:px-7 md:py-7">
                            <div className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <FormField label={t('advisory.department')} required error={form.errors.department_id}>
                                        <select
                                            value={form.data.department_id}
                                            onChange={(event) => form.setData('department_id', event.target.value)}
                                            className="select-ui"
                                        >
                                            {departments.map((department) => (
                                                <option key={department.id} value={department.id}>
                                                    {locale === 'am' ? department.name_am || department.name_en : department.name_en}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>

                                    <FormField label={t('advisory.category')} required error={form.errors.category_id}>
                                        <select
                                            value={form.data.category_id}
                                            onChange={(event) => form.setData('category_id', event.target.value)}
                                            className="select-ui"
                                        >
                                            {categories.map((category) => (
                                                <option key={category.id} value={category.id}>
                                                    {locale === 'am' ? category.name_am || category.name_en : category.name_en}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>

                                    <FormField label={t('advisory.request_type')} required>
                                        <select
                                            value={form.data.request_type}
                                            onChange={(event) => form.setData('request_type', event.target.value)}
                                            className="select-ui"
                                        >
                                            {typeOptions.map((type) => (
                                                <option key={type.value} value={type.value}>
                                                    {type.label}
                                                </option>
                                            ))}
                                        </select>
                                    </FormField>

                                    <FormField label={t('advisory.priority')} required>
                                        <select
                                            value={form.data.priority}
                                            onChange={(event) => form.setData('priority', event.target.value)}
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

                                <FormField label={t('advisory.subject')} required error={form.errors.subject}>
                                    <input
                                        value={form.data.subject}
                                        onChange={(event) => form.setData('subject', event.target.value)}
                                        className="input-ui"
                                    />
                                </FormField>
                            </div>

                            <div className="space-y-4 border-t border-[color:var(--border)]/70 pt-8">
                                <FormField label={t('advisory.description')} required error={form.errors.description}>
                                    <RichTextEditor
                                        value={form.data.description}
                                        onChange={(value) => form.setData('description', value)}
                                        minHeight={360}
                                    />
                                </FormField>
                            </div>

                            <div className="space-y-4 border-t border-[color:var(--border)]/70 pt-8">
                                <div>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-[color:var(--muted)]">
                                        {t('common.attachments')}
                                    </h3>
                                    <p className="mt-2 text-sm text-[color:var(--muted-strong)]">
                                        {t('common.optional')}
                                    </p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <FormField label={t('advisory.due_date')} optional>
                                        <input
                                            type="date"
                                            value={form.data.due_date}
                                            onChange={(event) => form.setData('due_date', event.target.value)}
                                            className="input-ui"
                                        />
                                    </FormField>

                                    <FormField label={t('common.attachments')} optional>
                                        <input
                                            type="file"
                                            multiple
                                            onChange={(event) => form.setData('attachments', Array.from(event.target.files ?? []))}
                                            className="input-ui file:me-4 file:rounded-full file:border-0 file:bg-[color:var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                        />
                                    </FormField>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[color:var(--border)]/80 px-6 py-5 md:px-7">
                            <Link href={route('advisory.index')} className="btn-base btn-secondary focus-ring">
                                {t('common.cancel')}
                            </Link>
                            <button type="submit" disabled={form.processing} className="btn-base btn-primary focus-ring">
                                {isEditing ? t('advisory.resubmit_request') : t('advisory.submit_request')}
                            </button>
                        </div>
                    </SurfaceCard>
                </form>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
