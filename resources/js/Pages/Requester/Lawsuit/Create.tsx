import FormalRequestLetter from '@/Components/Ui/FormalRequestLetter';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import RequesterLayout from '@/Layouts/RequesterLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type LawsuitCreateProps = {
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
        subject: string;
        description: string;
    };
    mode?: 'create' | 'edit';
};

export default function RequesterLawsuitCreate({
    requestingDepartment,
    defaultTemplate,
    requestItem,
    mode = 'create',
}: LawsuitCreateProps) {
    const { t, locale } = useI18n();
    const isEdit = mode === 'edit' && requestItem != null;

    const form = useForm<{
        subject: string;
        description: string;
        attachments: File[];
        _method?: string;
    }>({
        subject: requestItem?.subject ?? '',
        description: requestItem?.description ?? defaultTemplate?.body_content ?? '',
        attachments: [],
        ...(isEdit ? { _method: 'PATCH' } : {}),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const url = isEdit
            ? route('requester.lawsuit-requests.update', { lawsuitRequest: requestItem!.id })
            : route('requester.lawsuit-requests.store');

        form.post(url, { forceFormData: true });
    };

    const departmentName = locale === 'am'
        ? (requestingDepartment.name_am ?? requestingDepartment.name_en)
        : (requestingDepartment.name_en ?? requestingDepartment.name_am);

    return (
        <RequesterLayout
            breadcrumbs={[
                { label: t('requester.nav_dashboard'), href: route('requester.dashboard') },
                { label: t('requester.nav_lawsuit'), href: route('requester.lawsuit-requests.index') },
                { label: isEdit ? t('requester.edit_lawsuit') : t('requester.new_lawsuit') },
            ]}
        >
            <Head title={isEdit ? t('requester.edit_lawsuit') : t('requester.new_lawsuit')} />

            <div className="mx-auto max-w-5xl space-y-6">
                <div>
                    <h1 className="text-xl font-bold text-[color:var(--text)]">
                        {isEdit ? t('requester.edit_lawsuit') : t('requester.new_lawsuit')}
                    </h1>
                    <p className="mt-1 text-sm text-[color:var(--muted)]">{t('requester.lawsuit_form_desc')}</p>
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
                        <form id="lawsuit-form" onSubmit={submit} className="space-y-5">
                            <div>
                                <label className="block text-sm font-medium text-[color:var(--text)]">{t('requester.department')}</label>
                                <input type="text" value={departmentName ?? ''} readOnly className="input-ui mt-1 bg-[color:var(--surface-muted)]" />
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
                            ? route('requester.lawsuit-requests.show', { lawsuitRequest: requestItem!.id })
                            : route('requester.lawsuit-requests.index')}
                        className="btn-base btn-secondary focus-ring"
                    >
                        {t('common.cancel')}
                    </Link>
                    <button type="submit" form="lawsuit-form" disabled={form.processing || !defaultTemplate} className="btn-base btn-primary focus-ring">
                        {form.processing
                            ? `${t('common.saving')}...`
                            : isEdit ? t('requester.update_lawsuit') : t('requester.submit_lawsuit')}
                    </button>
                </div>
            </div>
        </RequesterLayout>
    );
}
