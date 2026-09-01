import BackButton from '@/Components/Ui/BackButton';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type CreateLawsuitRequestProps = {
    departments: Array<{ id: string; name_en: string; name_am: string }>;
    authDepartmentId?: string | null;
    requestItem?: {
        id: string;
        requesting_department?: { id: string } | null;
        subject: string;
        description: string;
    } | null;
    mode?: 'create' | 'edit';
};

export default function LawsuitRequestCreate({
    departments,
    authDepartmentId,
    requestItem = null,
    mode = 'create',
}: CreateLawsuitRequestProps) {
    const { t, locale } = useI18n();
    const isEditing = mode === 'edit' && requestItem !== null;

    const form = useForm({
        requesting_department_id: requestItem?.requesting_department?.id ?? authDepartmentId ?? departments[0]?.id ?? '',
        subject: requestItem?.subject ?? '',
        description: requestItem?.description ?? '',
        attachments: [] as File[],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (isEditing && requestItem) {
            form.transform((data) => ({ ...data, _method: 'patch' }));
            form.post(route('lawsuit-requests.update', { lawsuitFilingRequest: requestItem.id }), {
                forceFormData: true,
                onFinish: () => form.transform((data) => data),
            });
            return;
        }

        form.post(route('lawsuit-requests.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.lawsuit_requests'), href: route('lawsuit-requests.index') },
                { label: isEditing ? t('lawsuit_requests.edit_title') : t('lawsuit_requests.new_request') },
            ]}
        >
            <Head title={isEditing ? t('lawsuit_requests.edit_title') : t('lawsuit_requests.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('lawsuit_requests.eyebrow')}
                    title={isEditing ? t('lawsuit_requests.edit_title') : t('lawsuit_requests.create_title')}
                    description={isEditing ? t('lawsuit_requests.edit_description') : t('lawsuit_requests.create_description')}
                    action={<BackButton fallbackHref={route('lawsuit-requests.index')} />}
                />

                <form onSubmit={submit} className="space-y-6">
                    <SurfaceCard strong className="overflow-hidden p-0">
                        <div className="space-y-6 px-6 py-6 md:px-7 md:py-7">
                            <FormField label={t('lawsuit_requests.requesting_department')} required error={form.errors.requesting_department_id}>
                                <select
                                    value={form.data.requesting_department_id}
                                    onChange={(e) => form.setData('requesting_department_id', e.target.value)}
                                    className="select-ui"
                                >
                                    {departments.map((dept) => (
                                        <option key={dept.id} value={dept.id}>
                                            {locale === 'am' ? dept.name_am || dept.name_en : dept.name_en}
                                        </option>
                                    ))}
                                </select>
                            </FormField>

                            <FormField label={t('lawsuit_requests.subject')} required error={form.errors.subject}>
                                <input
                                    value={form.data.subject}
                                    onChange={(e) => form.setData('subject', e.target.value)}
                                    className="input-ui"
                                />
                            </FormField>

                            <FormField label={t('lawsuit_requests.description')} required error={form.errors.description}>
                                <textarea
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    rows={6}
                                    className="textarea-ui"
                                />
                            </FormField>

                            <FormField label={t('common.attachments')} optional>
                                <input
                                    type="file"
                                    multiple
                                    accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                                    onChange={(e) => form.setData('attachments', Array.from(e.target.files ?? []))}
                                    className="input-ui file:me-4 file:rounded-full file:border-0 file:bg-[color:var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                />
                            </FormField>
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[color:var(--border)]/80 px-6 py-5 md:px-7">
                            <Link href={route('lawsuit-requests.index')} className="btn-base btn-secondary focus-ring">
                                {t('common.cancel')}
                            </Link>
                            <button type="submit" disabled={form.processing} className="btn-base btn-primary focus-ring">
                                {isEditing ? t('lawsuit_requests.resubmit_request') : t('lawsuit_requests.submit_request')}
                            </button>
                        </div>
                    </SurfaceCard>
                </form>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
