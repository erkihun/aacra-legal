import FormField from '@/Components/Ui/FormField';
import BackButton from '@/Components/Ui/BackButton';
import FileAttachmentCard from '@/Components/Ui/FileAttachmentCard';
import PageContainer from '@/Components/Ui/PageContainer';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type AdvisoryResponseCreateProps = {
    requestItem: {
        id: string;
        request_number: string;
        subject: string;
        request_type: string;
    };
    responseItem?: {
        id: string;
        subject?: string | null;
        response?: string | null;
        attachments?: Array<any>;
    } | null;
    mode?: 'create' | 'edit';
};

export default function AdvisoryResponseCreate({
    requestItem,
    responseItem = null,
    mode = 'create',
}: AdvisoryResponseCreateProps) {
    const { t } = useI18n();
    const isEditing = mode === 'edit' && responseItem !== null;
    const form = useForm({
        subject: responseItem?.subject ?? '',
        response: responseItem?.response ?? '',
        attachments: [] as File[],
    });
    const existingAttachments = Array.isArray(responseItem?.attachments) ? responseItem.attachments : [];

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const submitOptions = {
            forceFormData: true,
        };

        if (isEditing && responseItem) {
            form.transform((data) => ({
                ...data,
                _method: 'patch',
            }));

            form.post(route('advisory.responses.update', {
                advisoryRequest: requestItem.id,
                advisoryResponse: responseItem.id,
            }), {
                ...submitOptions,
                onFinish: () => form.transform((data) => data),
            });

            return;
        }

        form.post(route('advisory.respond', { advisoryRequest: requestItem.id }), submitOptions);
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.advisory_requests'), href: route('advisory.index') },
                { label: requestItem.request_number, href: route('advisory.show', { advisoryRequest: requestItem.id }) },
                { label: isEditing ? t('advisory.edit_response') : t('advisory.record_response') },
            ]}
        >
            <Head title={`${isEditing ? t('advisory.edit_response') : t('advisory.record_response')} ${requestItem.request_number}`} />

            <PageContainer>
                <SectionHeader
                    eyebrow={requestItem.request_number}
                    title={isEditing ? t('advisory.edit_response') : t('advisory.record_response')}
                    description={requestItem.subject}
                    action={<BackButton fallbackHref={route('advisory.show', { advisoryRequest: requestItem.id })} />}
                />

                <form onSubmit={submit} className="space-y-6">
                    <SurfaceCard className="space-y-6 p-6">
                        <FormField label={t('advisory.subject')} required error={form.errors.subject}>
                            <input
                                value={form.data.subject}
                                onChange={(event) => form.setData('subject', event.target.value)}
                                className="input-ui"
                            />
                        </FormField>

                        <FormField label={t('advisory.response')} required error={form.errors.response}>
                            <RichTextEditor
                                value={form.data.response}
                                onChange={(value) => form.setData('response', value)}
                                minHeight={360}
                            />
                        </FormField>

                        <FormField
                            label={t('common.attachments')}
                            optional
                            error={form.errors.attachments as string | undefined}
                        >
                            <input
                                type="file"
                                multiple
                                onChange={(event) => form.setData('attachments', Array.from(event.target.files ?? []))}
                                className="input-ui file:me-4 file:rounded-full file:border-0 file:bg-[color:var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                            />
                        </FormField>

                        {existingAttachments.length > 0 ? (
                            <div className="space-y-3 border-t border-[color:var(--border)] pt-5">
                                <h3 className="text-sm font-semibold text-[color:var(--text)]">
                                    {t('common.attachments')}
                                </h3>
                                <div className="space-y-3">
                                    {existingAttachments.map((attachment) => (
                                        <FileAttachmentCard
                                            key={attachment.id}
                                            name={attachment.original_name}
                                            meta={attachment.mime_type ?? t('common.not_available')}
                                            viewUrl={attachment.view_url}
                                            downloadUrl={attachment.download_url}
                                            canDelete={false}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[color:var(--border)] pt-5">
                            <Link
                                href={route('advisory.show', { advisoryRequest: requestItem.id })}
                                className="btn-base btn-secondary focus-ring"
                            >
                                {t('common.cancel')}
                            </Link>
                            <button type="submit" disabled={form.processing} className="btn-base btn-primary focus-ring">
                                {isEditing ? t('common.save_changes') : t('advisory.record_response')}
                            </button>
                        </div>
                    </SurfaceCard>
                </form>
            </PageContainer>
        </AuthenticatedLayout>
    );
}
