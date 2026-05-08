import BackButton from '@/Components/Ui/BackButton';
import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import FormField from '@/Components/Ui/FormField';
import LocalizedDateInput from '@/Components/Ui/LocalizedDateInput';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { buildLetterRenderable, LetterItem, LetterRecipient, LetterSheet, LetterTemplateSelection, previewDocumentLabels } from '../LetterTemplates/shared';
import nyalaFontUrl from '../../../../fonts/pdf/Nyala.ttf?url';

type TemplateOption = {
    id: string;
    name: string;
    code: string;
    language: 'en' | 'am';
    reference_prefix?: string | null;
    is_active: boolean;
};

type DepartmentOption = {
    id: string;
    name_en: string;
    name_am: string;
    is_active: boolean;
};

type Props = {
    letterItem?: LetterItem | null;
    selectedTemplate?: LetterTemplateSelection | null;
    templateOptions: TemplateOption[];
    departments: DepartmentOption[];
    canDelete: boolean;
};

const LETTER_BODY_TOOLBAR = 'undo redo | fontsizeinput lineheight | bold italic underline | bullist numlist | alignleft aligncenter alignright alignjustify | link | removeformat';

function buildLetterEditorContentStyle(language: 'en' | 'am') {
    const baseStyles = `
        body {
            max-width: 174mm;
            margin: 0 auto 2rem;
            padding: 0;
        }
    `;

    if (language !== 'am') {
        return baseStyles;
    }

    return `
        @font-face {
            font-family: 'LetterNyala';
            src: url('${nyalaFontUrl}') format('truetype');
            font-style: normal;
            font-weight: 400 700;
        }
        body {
            max-width: 174mm;
            margin: 0 auto 2rem;
            padding: 0;
            font-family: 'LetterNyala', 'Nyala', serif;
            line-height: 1.85;
        }
    `;
}

export default function LetterForm({ letterItem, selectedTemplate, templateOptions, departments, canDelete }: Props) {
    const { t } = useI18n();
    const { props } = usePage<PageProps>();
    const isEditing = !!letterItem;
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [selectedTemplateId, setSelectedTemplateId] = useState<string>(selectedTemplate?.id ?? '');
    const [departmentMenuOpen, setDepartmentMenuOpen] = useState(false);
    const sessionSigner = {
        name: props.auth.user?.name ?? '',
        jobTitle: props.auth.user?.job_title ?? '',
        signatureUrl: props.auth.user?.signature_url ?? null,
    };
    const effectiveSigner = letterItem?.approval_status === 'approved'
        ? {
            name: letterItem.signer_full_name ?? '',
            jobTitle: letterItem.signer_title ?? '',
            signatureUrl: letterItem.signature_image_url ?? null,
        }
        : sessionSigner;
    const form = useForm({
        template_id: selectedTemplate?.id ?? letterItem?.template_id ?? '',
        reference_number: letterItem?.reference_number ?? selectedTemplate?.reference_number_preview ?? '',
        reference_number_preview: selectedTemplate?.reference_number_preview ?? letterItem?.reference_number ?? '',
        letter_date: letterItem?.letter_date ?? new Date().toISOString().slice(0, 10),
        recipient_names_text: normalizeRecipientNamesTextForForm(letterItem),
        recipient_department_ids: normalizeRecipientDepartmentIdsForForm(letterItem),
        subject: letterItem?.subject ?? selectedTemplate?.subject_template ?? '',
        salutation: letterItem?.salutation ?? selectedTemplate?.salutation_template ?? '',
        body_content: letterItem?.body_content ?? selectedTemplate?.body_content ?? '',
        closing_content: letterItem?.closing_content ?? selectedTemplate?.closing_content ?? '',
        signature_block_content: letterItem?.signature_block_content ?? selectedTemplate?.signature_block_content ?? '',
        cc_content: letterItem?.cc_content ?? selectedTemplate?.cc_content ?? '',
        enclosure_content: letterItem?.enclosure_content ?? selectedTemplate?.enclosure_content ?? '',
        status: letterItem?.status ?? 'draft',
        language: letterItem?.language ?? selectedTemplate?.language ?? 'en',
        page_size: letterItem?.page_size ?? selectedTemplate?.page_size ?? 'A4',
        orientation: letterItem?.orientation ?? selectedTemplate?.orientation ?? 'portrait',
        margin_top_mm: String(letterItem?.layout_config?.margin_top_mm ?? selectedTemplate?.layout_config?.margin_top_mm ?? 20),
        margin_right_mm: String(letterItem?.layout_config?.margin_right_mm ?? selectedTemplate?.layout_config?.margin_right_mm ?? 18),
        margin_bottom_mm: String(letterItem?.layout_config?.margin_bottom_mm ?? selectedTemplate?.layout_config?.margin_bottom_mm ?? 20),
        margin_left_mm: String(letterItem?.layout_config?.margin_left_mm ?? selectedTemplate?.layout_config?.margin_left_mm ?? 18),
        header_top_margin_mm: String(letterItem?.layout_config?.header_top_margin_mm ?? selectedTemplate?.layout_config?.header_top_margin_mm ?? 0),
        header_bottom_spacing_mm: String(letterItem?.layout_config?.header_bottom_spacing_mm ?? selectedTemplate?.layout_config?.header_bottom_spacing_mm ?? 4),
        footer_top_spacing_mm: String(letterItem?.layout_config?.footer_top_spacing_mm ?? selectedTemplate?.layout_config?.footer_top_spacing_mm ?? 4),
        footer_left_margin_mm: String(letterItem?.layout_config?.footer_left_margin_mm ?? selectedTemplate?.layout_config?.footer_left_margin_mm ?? letterItem?.layout_config?.margin_left_mm ?? selectedTemplate?.layout_config?.margin_left_mm ?? 18),
        footer_right_margin_mm: String(letterItem?.layout_config?.footer_right_margin_mm ?? selectedTemplate?.layout_config?.footer_right_margin_mm ?? letterItem?.layout_config?.margin_right_mm ?? selectedTemplate?.layout_config?.margin_right_mm ?? 18),
        footer_bottom_margin_mm: String(letterItem?.layout_config?.footer_bottom_margin_mm ?? selectedTemplate?.layout_config?.footer_bottom_margin_mm ?? 0),
        content_top_margin_mm: String(letterItem?.layout_config?.content_top_margin_mm ?? selectedTemplate?.layout_config?.content_top_margin_mm ?? letterItem?.layout_config?.margin_top_mm ?? selectedTemplate?.layout_config?.margin_top_mm ?? 20),
        content_bottom_margin_mm: String(letterItem?.layout_config?.content_bottom_margin_mm ?? selectedTemplate?.layout_config?.content_bottom_margin_mm ?? letterItem?.layout_config?.margin_bottom_mm ?? selectedTemplate?.layout_config?.margin_bottom_mm ?? 20),
        notes: letterItem?.notes ?? '',
    });

    const previewRecipients = useMemo(
        () => buildPreviewRecipientsFromSources(form.data.recipient_names_text, form.data.recipient_department_ids, departments),
        [departments, form.data.recipient_department_ids, form.data.recipient_names_text],
    );
    const recipientGroupError = (form.errors as Record<string, string | undefined>).recipients;

    const previewLetter = useMemo<LetterItem>(() => ({
        template_id: form.data.template_id,
        reference_number: form.data.reference_number,
        letter_date: form.data.letter_date,
        recipient_name: previewRecipients[0]?.recipient_name ?? resolvePreviewRecipientName(previewRecipients, form.data.language as 'en' | 'am'),
        recipient_organization: resolvePreviewRecipientOrganization(previewRecipients, form.data.language as 'en' | 'am'),
        recipients: previewRecipients,
        subject: form.data.subject,
        salutation: form.data.salutation,
        body_content: form.data.body_content,
        closing_content: form.data.closing_content,
        signature_block_content: form.data.signature_block_content,
        cc_content: form.data.cc_content,
        enclosure_content: form.data.enclosure_content,
        header_image_url: letterItem?.header_image_url ?? selectedTemplate?.header_image_url ?? null,
        footer_image_url: letterItem?.footer_image_url ?? selectedTemplate?.footer_image_url ?? null,
        signature_image_url: letterItem?.signature_image_url ?? effectiveSigner.signatureUrl,
        signer_full_name: letterItem?.signer_full_name ?? effectiveSigner.name,
        signer_title: letterItem?.signer_title ?? effectiveSigner.jobTitle,
        approval_status: letterItem?.approval_status ?? 'draft',
        language: form.data.language as 'en' | 'am',
        page_size: 'A4',
        orientation: form.data.orientation as 'portrait' | 'landscape',
        status: form.data.status,
        layout_config: {
            margin_top_mm: numericOrNull(form.data.margin_top_mm),
            margin_right_mm: numericOrNull(form.data.margin_right_mm),
            margin_bottom_mm: numericOrNull(form.data.margin_bottom_mm),
            margin_left_mm: numericOrNull(form.data.margin_left_mm),
            header_top_margin_mm: numericOrNull(form.data.header_top_margin_mm),
            header_bottom_spacing_mm: numericOrNull(form.data.header_bottom_spacing_mm),
            footer_top_spacing_mm: numericOrNull(form.data.footer_top_spacing_mm),
            footer_left_margin_mm: numericOrNull(form.data.footer_left_margin_mm),
            footer_right_margin_mm: numericOrNull(form.data.footer_right_margin_mm),
            footer_bottom_margin_mm: numericOrNull(form.data.footer_bottom_margin_mm),
            content_top_margin_mm: numericOrNull(form.data.content_top_margin_mm),
            content_bottom_margin_mm: numericOrNull(form.data.content_bottom_margin_mm),
        },
        notes: form.data.notes,
        template: letterItem?.template ?? (selectedTemplate ? { id: selectedTemplate.id, name: selectedTemplate.name, code: selectedTemplate.code } : null),
    }), [effectiveSigner.jobTitle, effectiveSigner.name, effectiveSigner.signatureUrl, form.data.body_content, form.data.cc_content, form.data.closing_content, form.data.content_bottom_margin_mm, form.data.content_top_margin_mm, form.data.enclosure_content, form.data.footer_bottom_margin_mm, form.data.footer_left_margin_mm, form.data.footer_right_margin_mm, form.data.footer_top_spacing_mm, form.data.header_bottom_spacing_mm, form.data.header_top_margin_mm, form.data.language, form.data.letter_date, form.data.margin_bottom_mm, form.data.margin_left_mm, form.data.margin_right_mm, form.data.margin_top_mm, form.data.notes, form.data.orientation, form.data.reference_number, form.data.salutation, form.data.signature_block_content, form.data.status, form.data.subject, form.data.template_id, letterItem?.approval_status, letterItem?.footer_image_url, letterItem?.header_image_url, letterItem?.signature_image_url, letterItem?.signer_full_name, letterItem?.signer_title, letterItem?.template, previewRecipients, selectedTemplate]);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.letters'), href: route('letters.index') },
                { label: isEditing ? t('common.edit') : t('common.create_record') },
            ]}
        >
            <Head title={isEditing ? t('letters.edit_title') : t('letters.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('letters.eyebrow')}
                    title={isEditing ? t('letters.edit_title') : t('letters.create_title')}
                    description={isEditing ? t('letters.edit_description') : t('letters.create_description')}
                    action={<BackButton fallbackHref={isEditing && letterItem?.id ? route('letters.show', letterItem.id) : route('letters.index')} />}
                />

                {!isEditing ? (
                    <SurfaceCard className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.template_selection')}</h2>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">{t('letters.section_help.template_selection')}</p>
                        </div>
                        <div className="grid gap-4 md:grid-cols-[1fr,auto]">
                            <FormField label={t('letters.fields.template')} required>
                                <select value={selectedTemplateId} onChange={(event) => setSelectedTemplateId(event.target.value)} className="select-ui">
                                    <option value="">{t('letters.select_template_placeholder')}</option>
                                    {templateOptions.map((template) => (
                                        <option key={template.id} value={template.id}>
                                            {template.name} ({template.code})
                                        </option>
                                    ))}
                                </select>
                            </FormField>
                            <div className="flex items-end">
                                <button
                                    type="button"
                                    className="btn-base btn-secondary focus-ring"
                                    disabled={selectedTemplateId === ''}
                                    onClick={() => router.get(route('letters.create'), { template_id: selectedTemplateId })}
                                >
                                    {t('letters.load_template')}
                                </button>
                            </div>
                        </div>
                    </SurfaceCard>
                ) : null}

                {selectedTemplate || isEditing ? (
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();

                            if (isEditing && letterItem?.id) {
                                form.patch(route('letters.update', letterItem.id));
                                return;
                            }

                            form.post(route('letters.store'));
                        }}
                        className="space-y-4"
                    >
                        <SurfaceCard className="space-y-4">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.template_selection')}</h2>
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <InfoPill label={t('letters.fields.template')} value={selectedTemplate?.name ?? letterItem?.template?.name ?? '-'} />
                                <InfoPill label={t('common.code')} value={selectedTemplate?.code ?? letterItem?.template?.code ?? '-'} />
                                <InfoPill label={t('letters.fields.reference_number')} value={form.data.reference_number || '-'} />
                                <InfoPill label={t('common.language')} value={form.data.language === 'am' ? t('letter_templates.languages.am') : t('letter_templates.languages.en')} />
                            </div>
                        </SurfaceCard>

                        <SurfaceCard className="space-y-4">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.letter_metadata')}</h2>
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <FormField label={t('letters.fields.reference_number')} required error={form.errors.reference_number}>
                                    <input value={form.data.reference_number} onChange={(event) => form.setData('reference_number', event.target.value)} className="input-ui" />
                                </FormField>
                                <FormField label={t('letters.fields.letter_date')} required error={form.errors.letter_date}>
                                    <LocalizedDateInput value={form.data.letter_date} onChange={(value) => form.setData('letter_date', value)} className="input-ui" />
                                </FormField>
                                {letterItem?.approval_status === 'approved' ? (
                                    <InfoPill label={t('common.status')} value={t('letters.status.final')} />
                                ) : (
                                    <FormField label={t('common.status')} required error={form.errors.status}>
                                        <select value={form.data.status} onChange={(event) => form.setData('status', event.target.value)} className="select-ui">
                                            <option value="draft">{t('letters.status.draft')}</option>
                                            <option value="archived">{t('letters.status.archived')}</option>
                                        </select>
                                    </FormField>
                                )}
                                <FormField label={t('letters.fields.orientation')} required error={form.errors.orientation}>
                                    <select value={form.data.orientation} onChange={(event) => form.setData('orientation', event.target.value as 'portrait' | 'landscape')} className="select-ui">
                                        <option value="portrait">{t('letter_templates.orientation.portrait')}</option>
                                        <option value="landscape">{t('letter_templates.orientation.landscape')}</option>
                                    </select>
                                </FormField>
                            </div>
                        </SurfaceCard>

                        <SurfaceCard className="space-y-4">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.recipient_information')}</h2>
                            <div className="grid gap-4 xl:grid-cols-2">
                                <FormField
                                    label={t('letters.fields.recipient_name')}
                                    optional
                                    error={form.errors.recipient_names_text ?? recipientGroupError}
                                    hint={t('letters.recipient_names_help')}
                                >
                                    <textarea
                                        value={form.data.recipient_names_text}
                                        onChange={(event) => form.setData('recipient_names_text', event.target.value)}
                                        className="textarea-ui min-h-36"
                                        placeholder={t('letters.recipient_names_placeholder')}
                                    />
                                </FormField>
                                <FormField
                                    label={t('letters.fields.recipient_department')}
                                    optional
                                    error={form.errors.recipient_department_ids ?? recipientGroupError}
                                    hint={t('letters.recipient_department_help')}
                                >
                                    <div className="relative">
                                        <button
                                            type="button"
                                            className="select-ui flex min-h-11 w-full items-center justify-between text-left"
                                            onClick={() => setDepartmentMenuOpen((current) => ! current)}
                                        >
                                            <span className={form.data.recipient_department_ids.length === 0 ? 'text-[color:var(--muted)]' : ''}>
                                                {selectedDepartmentSummary(form.data.recipient_department_ids, departments, form.data.language as 'en' | 'am', t('letters.recipient_department_placeholder'))}
                                            </span>
                                            <span className="ml-3 text-xs text-[color:var(--muted)]">{departmentMenuOpen ? '▲' : '▼'}</span>
                                        </button>
                                        {departmentMenuOpen ? (
                                            <div className="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-2xl border border-[color:var(--border)] bg-[color:var(--surface)] p-2 shadow-xl">
                                                {departments.length === 0 ? (
                                                    <p className="px-3 py-2 text-sm text-[color:var(--muted)]">{t('common.no_results')}</p>
                                                ) : (
                                                    departments.map((department) => {
                                                        const checked = form.data.recipient_department_ids.includes(department.id);

                                                        return (
                                                            <label
                                                                key={department.id}
                                                                className="flex cursor-pointer items-start gap-3 rounded-xl px-3 py-2 hover:bg-[color:var(--surface-muted)]"
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    className="mt-1 h-4 w-4 rounded border-[color:var(--border)] text-[color:var(--primary)] focus:ring-[color:var(--primary)]"
                                                                    checked={checked}
                                                                    onChange={() => toggleDepartmentSelection(department.id, form.data.recipient_department_ids, form.setData)}
                                                                />
                                                                <span className="text-sm text-[color:var(--text)]">
                                                                    {departmentLabel(department, form.data.language as 'en' | 'am')}
                                                                </span>
                                                            </label>
                                                        );
                                                    })
                                                )}
                                            </div>
                                        ) : null}
                                    </div>
                                </FormField>
                            </div>
                        </SurfaceCard>

                        <SurfaceCard className="space-y-4">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.subject_and_salutation')}</h2>
                            <div className="grid gap-4 xl:grid-cols-2">
                                <FormField label={t('letters.fields.subject')} optional error={form.errors.subject}>
                                    <input value={form.data.subject} onChange={(event) => form.setData('subject', event.target.value)} className="input-ui" />
                                </FormField>
                                <FormField label={t('letters.fields.salutation')} optional error={form.errors.salutation}>
                                    <textarea value={form.data.salutation} onChange={(event) => form.setData('salutation', event.target.value)} className="textarea-ui min-h-24" />
                                </FormField>
                            </div>
                        </SurfaceCard>

                        <SurfaceCard className="space-y-4">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.main_body')}</h2>
                            <div className="mx-auto w-full max-w-[820px]">
                                <FormField label={t('letters.fields.body_content')} required error={form.errors.body_content}>
                                    <RichTextEditor
                                        value={form.data.body_content}
                                        onChange={(value) => form.setData('body_content', value)}
                                        minHeight={360}
                                        toolbar={LETTER_BODY_TOOLBAR}
                                        contentStyle={buildLetterEditorContentStyle(form.data.language as 'en' | 'am')}
                                    />
                                </FormField>
                            </div>
                        </SurfaceCard>

                        <SurfaceCard className="space-y-4">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.closing_signature')}</h2>
                            <div className="grid gap-4 md:grid-cols-3">
                                <InfoPill label={t('letters.fields.signature_image')} value={effectiveSigner.signatureUrl ? t('letters.signature_available') : t('common.not_available')} />
                                <InfoPill label={t('letters.fields.signer_full_name')} value={effectiveSigner.name || t('common.not_available')} />
                                <InfoPill label={t('letters.fields.signer_title')} value={effectiveSigner.jobTitle || t('common.not_available')} />
                            </div>
                            {!effectiveSigner.signatureUrl && letterItem?.approval_status !== 'approved' ? (
                                <div className="rounded-2xl border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-900 dark:text-amber-200">
                                    <p className="font-semibold">{t('letters.signature_warning_title')}</p>
                                    <p className="mt-1">{t('letters.signature_warning_description')}</p>
                                </div>
                            ) : null}
                            <div className="grid gap-4 xl:grid-cols-2">
                                <FormField label={t('letters.fields.closing_content')} optional error={form.errors.closing_content}>
                                    <textarea value={form.data.closing_content} onChange={(event) => form.setData('closing_content', event.target.value)} className="textarea-ui min-h-28" />
                                </FormField>
                                <FormField label={t('letters.fields.signature_block_content')} optional error={form.errors.signature_block_content}>
                                    <textarea value={form.data.signature_block_content} onChange={(event) => form.setData('signature_block_content', event.target.value)} className="textarea-ui min-h-28" />
                                </FormField>
                            </div>
                        </SurfaceCard>

                        <SurfaceCard className="space-y-4">
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.optional_sections')}</h2>
                            <div className="grid gap-4 xl:grid-cols-2">
                                <FormField label={t('letters.fields.cc_content')} optional error={form.errors.cc_content}>
                                    <textarea value={form.data.cc_content} onChange={(event) => form.setData('cc_content', event.target.value)} className="textarea-ui min-h-24" />
                                </FormField>
                                <FormField label={t('letters.fields.enclosure_content')} optional error={form.errors.enclosure_content}>
                                    <textarea value={form.data.enclosure_content} onChange={(event) => form.setData('enclosure_content', event.target.value)} className="textarea-ui min-h-24" />
                                </FormField>
                                <FormField label={t('common.notes')} optional error={form.errors.notes} className="xl:col-span-2">
                                    <textarea value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} className="textarea-ui min-h-24" />
                                </FormField>
                            </div>
                        </SurfaceCard>

                        <SurfaceCard className="space-y-4 overflow-hidden">
                            <div>
                                <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letters.sections.preview')}</h2>
                                <p className="mt-1 text-sm text-[color:var(--muted)]">{t('letters.section_help.preview')}</p>
                            </div>
                            <div className="overflow-x-auto rounded-3xl bg-slate-100 p-4 dark:bg-slate-900">
                                <LetterSheet
                                    document={buildLetterRenderable(previewLetter)}
                                    labels={previewDocumentLabels(previewLetter.language)}
                                />
                            </div>
                        </SurfaceCard>

                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                {isEditing && canDelete && letterItem?.id ? (
                                    <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setConfirmOpen(true)}>
                                        {t('common.delete')}
                                    </button>
                                ) : null}
                            </div>
                            <div className="flex flex-wrap gap-3">
                                {isEditing && letterItem?.id ? (
                                    <Link href={route('letters.show', letterItem.id)} className="btn-base btn-secondary focus-ring">
                                        {t('common.view')}
                                    </Link>
                                ) : null}
                                <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                                    {isEditing ? t('common.save_changes') : t('letters.save_letter')}
                                </button>
                            </div>
                        </div>
                    </form>
                ) : (
                    <SurfaceCard>
                        <p className="text-sm text-[color:var(--muted)]">{t('letters.select_template_message')}</p>
                    </SurfaceCard>
                )}
            </PageContainer>

            <ConfirmationDialog
                open={confirmOpen}
                title={t('letters.delete_title')}
                description={t('letters.delete_confirm')}
                confirmLabel={t('common.delete')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => {
                    if (!letterItem?.id) {
                        return;
                    }

                    router.delete(route('letters.destroy', letterItem.id));
                }}
            />
        </AuthenticatedLayout>
    );
}

function numericOrNull(value: string) {
    if (value.trim() === '') {
        return null;
    }

    const numeric = Number(value);

    return Number.isFinite(numeric) ? numeric : null;
}

function departmentLabel(department: DepartmentOption, language: 'en' | 'am') {
    return language === 'am'
        ? (department.name_am || department.name_en)
        : (department.name_en || department.name_am);
}

function resolveDepartmentDisplayName(
    departments: DepartmentOption[],
    departmentId: string,
    language: 'en' | 'am',
) {
    const department = departments.find((item) => item.id === departmentId);

    if (!department) {
        return '';
    }

    return language === 'am'
        ? (department.name_am || department.name_en)
        : (department.name_en || department.name_am);
}

function splitRecipientNameLines(value?: string | null): string[] {
    if (! value) {
        return [];
    }

    return value
        .split(/\r\n|\r|\n/)
        .map((line) => line.trim())
        .filter((line, index, lines) => line !== '' && lines.indexOf(line) === index);
}

function normalizeRecipientNamesTextForForm(letterItem?: LetterItem | null): string {
    if (Array.isArray(letterItem?.recipients) && letterItem.recipients.length > 0) {
        const lines = letterItem.recipients.flatMap((recipient) => {
            if (recipient?.recipient_type === 'department' && ! recipient.recipient_department_id) {
                const fallbackDepartmentName = recipient.recipient_department_name_en ?? recipient.recipient_department_name_am ?? '';

                return fallbackDepartmentName.trim() !== '' ? [fallbackDepartmentName.trim()] : [];
            }

            const name = recipient?.recipient_name ?? '';

            return name.trim() !== '' ? [name.trim()] : [];
        });

        return lines.filter((line, index, all) => all.indexOf(line) === index).join('\n');
    }

    return (letterItem?.recipient_name ?? '').trim();
}

function normalizeRecipientDepartmentIdsForForm(letterItem?: LetterItem | null): string[] {
    if (! Array.isArray(letterItem?.recipients)) {
        return [];
    }

    return letterItem.recipients
        .map((recipient) => recipient?.recipient_department_id?.trim() ?? '')
        .filter((departmentId, index, all) => departmentId !== '' && all.indexOf(departmentId) === index);
}

function buildPreviewRecipientsFromSources(
    recipientNamesText: string,
    recipientDepartmentIds: string[],
    departments: DepartmentOption[],
): LetterRecipient[] {
    const nameRecipients: LetterRecipient[] = splitRecipientNameLines(recipientNamesText).map((recipientName) => ({
        recipient_type: 'text',
        recipient_name: recipientName,
        recipient_department_id: null,
        recipient_department_name_en: null,
        recipient_department_name_am: null,
    }));

    const departmentRecipients: LetterRecipient[] = recipientDepartmentIds
        .map((departmentId) => departments.find((department) => department.id === departmentId))
        .filter((department): department is DepartmentOption => department !== undefined)
        .map((department) => ({
            recipient_type: 'department',
            recipient_name: null,
            recipient_department_id: department.id,
            recipient_department_name_en: department.name_en,
            recipient_department_name_am: department.name_am,
        }));

    return [...nameRecipients, ...departmentRecipients];
}

function resolvePreviewRecipientName(recipients: LetterRecipient[], language: 'en' | 'am'): string {
    const firstTextRecipient = recipients.find((recipient) => (recipient.recipient_name ?? '').trim() !== '');

    if (firstTextRecipient?.recipient_name) {
        return firstTextRecipient.recipient_name;
    }

    return resolvePreviewRecipientOrganization(recipients, language);
}

function resolvePreviewRecipientOrganization(recipients: LetterRecipient[], language: 'en' | 'am'): string {
    const firstDepartmentRecipient = recipients.find((recipient) => {
        const name = language === 'am'
            ? (recipient.recipient_department_name_am ?? recipient.recipient_department_name_en)
            : (recipient.recipient_department_name_en ?? recipient.recipient_department_name_am);

        return (name ?? '').trim() !== '';
    });

    if (! firstDepartmentRecipient) {
        return '';
    }

    return language === 'am'
        ? (firstDepartmentRecipient.recipient_department_name_am ?? firstDepartmentRecipient.recipient_department_name_en ?? '')
        : (firstDepartmentRecipient.recipient_department_name_en ?? firstDepartmentRecipient.recipient_department_name_am ?? '');
}

function toggleDepartmentSelection(
    departmentId: string,
    selectedDepartmentIds: string[],
    setData: (key: 'recipient_department_ids', value: string[]) => void,
) {
    setData(
        'recipient_department_ids',
        selectedDepartmentIds.includes(departmentId)
            ? selectedDepartmentIds.filter((value) => value !== departmentId)
            : [...selectedDepartmentIds, departmentId],
    );
}

function selectedDepartmentSummary(
    selectedDepartmentIds: string[],
    departments: DepartmentOption[],
    language: 'en' | 'am',
    placeholder: string,
): string {
    if (selectedDepartmentIds.length === 0) {
        return placeholder;
    }

    return selectedDepartmentIds
        .map((departmentId) => departments.find((department) => department.id === departmentId))
        .filter((department): department is DepartmentOption => department !== undefined)
        .map((department) => departmentLabel(department, language))
        .join(', ');
}

function InfoPill({ label, value }: { label: string; value: string }) {
    return (
        <div className="surface-muted flex min-h-[82px] flex-col justify-center px-4 py-3">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-sm font-semibold text-[color:var(--text)]">{value}</p>
        </div>
    );
}
