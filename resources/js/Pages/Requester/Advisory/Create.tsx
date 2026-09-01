import FormalRequestLetter from '@/Components/Ui/FormalRequestLetter';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import RequesterLayout from '@/Layouts/RequesterLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type AdvisoryCreateProps = {
    categories: Array<{ id: string; name_en: string; name_am: string }>;
    requestingDepartment: {
        id: string | null;
        name_en: string | null;
        name_am: string | null;
    };
    defaultTemplate: {
        id: string;
        name: string;
        language: string;
        header_image_url: string | null;
        footer_image_url: string | null;
        salutation_template: string | null;
        body_content: string | null;
        closing_content: string | null;
    } | null;
    requestItem?: {
        id: string;
        category_id: string | null;
        subject: string;
        description: string;
    };
    mode?: 'create' | 'edit';
};

export default function RequesterAdvisoryCreate({
    categories,
    requestingDepartment,
    defaultTemplate,
    requestItem,
    mode = 'create',
}: AdvisoryCreateProps) {
    const { t, locale } = useI18n();
    const isEdit = mode === 'edit' && requestItem != null;

    const form = useForm<{
        category_id: string;
        subject: string;
        description: string;
        attachments: File[];
        _method?: string;
    }>({
        category_id: requestItem?.category_id ?? categories[0]?.id ?? '',
        subject: requestItem?.subject ?? '',
        description: requestItem?.description ?? defaultTemplate?.body_content ?? '',
        attachments: [],
        ...(isEdit ? { _method: 'PATCH' } : {}),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const url = isEdit
            ? route('requester.advisory.update', { advisoryRequest: requestItem!.id })
            : route('requester.advisory.store');

        form.post(url, { forceFormData: true });
    };

    const departmentName = locale === 'am'
        ? (requestingDepartment.name_am ?? requestingDepartment.name_en)
        : (requestingDepartment.name_en ?? requestingDepartment.name_am);

    return (
        <RequesterLayout
            breadcrumbs={[
                { label: t('requester.nav_dashboard'), href: route('requester.dashboard') },
                { label: t('requester.nav_advisory'), href: route('requester.advisory.index') },
                { label: isEdit ? t('requester.edit_advisory') : t('requester.new_advisory') },
            ]}
        >
            <Head title={isEdit ? t('requester.edit_advisory') : t('requester.new_advisory')} />

            <div className="mx-auto max-w-5xl space-y-6">
                <div>
                    <h1 className="text-xl font-bold text-[color:var(--text)]">
                        {isEdit ? t('requester.edit_advisory') : t('requester.new_advisory')}
                    </h1>
                    <p className="mt-1 text-sm text-[color:var(--muted)]">{t('requester.advisory_form_desc')}</p>
                </div>

                {defaultTemplate ? (
                    <FormalRequestLetter
                        title={t('requester.letter_preview')}
                        document={{
                            ...defaultTemplate,
                            body_content: form.data.description,
                            reference_number: null,
                            subject: form.data.subject,
                            date_submitted: null,
                            department_name: departmentName ?? null,
                        }}
                    >
                        <form id="advisory-form" onSubmit={submit} className="space-y-5">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.department')}</label>
                                    <input type="text" value={departmentName ?? ''} readOnly className="input-ui mt-1 bg-[color:var(--surface-muted)]" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.category')}</label>
                                    <select
                                        value={form.data.category_id}
                                        onChange={(event) => form.setData('category_id', event.target.value)}
                                        className="select-ui mt-1"
                                    >
                                        {categories.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {locale === 'am' ? category.name_am || category.name_en : category.name_en}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.category_id ? <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.category_id}</p> : null}
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.subject')}</label>
                                <input
                                    type="text"
                                    value={form.data.subject}
                                    onChange={(event) => form.setData('subject', event.target.value)}
                                    className="input-ui mt-1"
                                />
                                {form.errors.subject ? <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.subject}</p> : null}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.description')}</label>
                                <div className="mt-1">
                                    <RichTextEditor value={form.data.description} onChange={(value) => form.setData('description', value)} minHeight={320} />
                                </div>
                                {form.errors.description ? <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.description}</p> : null}
                            </div>
                        </form>
                    </FormalRequestLetter>
                ) : (
                    <div className="rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)] p-6 text-sm text-[color:var(--danger)]">
                        {t('requester.formal_template_unavailable')}
                    </div>
                )}

                <div className="rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)] p-5">
                    <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.attachments')}</label>
                    <p className="mt-0.5 text-xs text-[color:var(--muted)]">{t('requester.attach_files_hint')}</p>
                    <input
                        type="file"
                        multiple
                        onChange={(event) => form.setData('attachments', Array.from(event.target.files ?? []))}
                        className="mt-2 block text-sm text-[color:var(--text)]"
                    />
                    {form.errors.attachments ? <p className="mt-1 text-xs text-[color:var(--danger)]">{form.errors.attachments}</p> : null}
                </div>

                <div className="flex items-center justify-end gap-3">
                    <Link
                        href={isEdit
                            ? route('requester.advisory.show', { advisoryRequest: requestItem!.id })
                            : route('requester.advisory.index')}
                        className="btn-base btn-secondary focus-ring"
                    >
                        {t('common.cancel')}
                    </Link>
                    <button type="submit" form="advisory-form" disabled={form.processing || !defaultTemplate} className="btn-base btn-primary focus-ring">
                        {form.processing
                            ? `${t('common.saving')}...`
                            : isEdit ? t('requester.update_advisory') : t('requester.submit_advisory')}
                    </button>
                </div>
            </div>
        </RequesterLayout>
    );
}
