import BackButton from '@/Components/Ui/BackButton';
import ConfirmationDialog from '@/Components/Ui/ConfirmationDialog';
import FormField from '@/Components/Ui/FormField';
import PageContainer from '@/Components/Ui/PageContainer';
import SectionHeader from '@/Components/Ui/SectionHeader';
import SurfaceCard from '@/Components/Ui/SurfaceCard';
import RichTextEditor from '@/Components/Ui/RichTextEditor';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    appendPlaceholder,
    buildReferencePreview,
    buildTemplateRenderable,
    defaultPreviewData,
    LetterSheet,
    LetterTemplateItem,
    PlaceholderField,
    PreviewData,
} from './shared';

type Props = {
    templateItem?: LetterTemplateItem | null;
    canDelete: boolean;
    placeholderFields: PlaceholderField[];
    previewData?: PreviewData;
};

type PlaceholderTarget =
    | 'subject_template'
    | 'recipient_block_template'
    | 'salutation_template'
    | 'body_content'
    | 'closing_content'
    | 'signature_block_content'
    | 'cc_content'
    | 'enclosure_content';

export default function LetterTemplateForm({ templateItem, canDelete, placeholderFields, previewData = defaultPreviewData }: Props) {
    const { t } = useI18n();
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [placeholderTarget, setPlaceholderTarget] = useState<PlaceholderTarget>('body_content');
    const [headerPreviewUrl, setHeaderPreviewUrl] = useState<string | null>(templateItem?.header_image_url ?? null);
    const [footerPreviewUrl, setFooterPreviewUrl] = useState<string | null>(templateItem?.footer_image_url ?? null);
    const isEditing = !!templateItem;
    const form = useForm({
        name: templateItem?.name ?? '',
        code: templateItem?.code ?? '',
        document_type: templateItem?.document_type ?? '',
        language: templateItem?.language ?? 'en',
        page_size: templateItem?.page_size ?? 'A4',
        orientation: templateItem?.orientation ?? 'portrait',
        reference_label: templateItem?.reference_label ?? '',
        reference_prefix: templateItem?.reference_prefix ?? '',
        reference_start_number: String(templateItem?.reference_start_number ?? 1),
        numbering_separator: templateItem?.numbering_config?.separator ?? '/',
        numbering_include_year: templateItem?.numbering_config?.include_year ?? true,
        numbering_pad_length: String(templateItem?.numbering_config?.pad_length ?? 4),
        subject_template: templateItem?.subject_template ?? '',
        recipient_block_template: templateItem?.recipient_block_template ?? '',
        salutation_template: templateItem?.salutation_template ?? '',
        body_content: templateItem?.body_content ?? '',
        closing_content: templateItem?.closing_content ?? '',
        signature_block_content: templateItem?.signature_block_content ?? '',
        cc_content: templateItem?.cc_content ?? '',
        enclosure_content: templateItem?.enclosure_content ?? '',
        margin_top_mm: String(templateItem?.layout_config?.margin_top_mm ?? 20),
        margin_right_mm: String(templateItem?.layout_config?.margin_right_mm ?? 18),
        margin_bottom_mm: String(templateItem?.layout_config?.margin_bottom_mm ?? 20),
        margin_left_mm: String(templateItem?.layout_config?.margin_left_mm ?? 18),
        header_top_margin_mm: String(templateItem?.layout_config?.header_top_margin_mm ?? 0),
        header_bottom_spacing_mm: String(templateItem?.layout_config?.header_bottom_spacing_mm ?? 4),
        footer_top_spacing_mm: String(templateItem?.layout_config?.footer_top_spacing_mm ?? 4),
        footer_left_margin_mm: String(templateItem?.layout_config?.footer_left_margin_mm ?? templateItem?.layout_config?.margin_left_mm ?? 18),
        footer_right_margin_mm: String(templateItem?.layout_config?.footer_right_margin_mm ?? templateItem?.layout_config?.margin_right_mm ?? 18),
        footer_bottom_margin_mm: String(templateItem?.layout_config?.footer_bottom_margin_mm ?? 0),
        content_top_margin_mm: String(templateItem?.layout_config?.content_top_margin_mm ?? templateItem?.layout_config?.margin_top_mm ?? 20),
        content_bottom_margin_mm: String(templateItem?.layout_config?.content_bottom_margin_mm ?? templateItem?.layout_config?.margin_bottom_mm ?? 20),
        header_image: null as File | null,
        footer_image: null as File | null,
        is_active: templateItem?.is_active ?? true,
        is_default: templateItem?.is_default ?? false,
        notes: templateItem?.notes ?? '',
    });

    const previewReferenceNumber = useMemo(() => buildReferencePreview({
        referencePrefix: form.data.reference_prefix,
        currentReferenceNumber: templateItem?.current_reference_number ?? null,
        referenceStartNumber: numericOrNull(form.data.reference_start_number) ?? 1,
        numberingConfig: {
            separator: form.data.numbering_separator,
            include_year: form.data.numbering_include_year,
            pad_length: numericOrNull(form.data.numbering_pad_length) ?? 4,
        },
    }), [form.data.numbering_include_year, form.data.numbering_pad_length, form.data.numbering_separator, form.data.reference_prefix, form.data.reference_start_number, templateItem?.current_reference_number]);

    const previewTemplate = useMemo<LetterTemplateItem>(() => ({
        name: form.data.name,
        code: form.data.code,
        document_type: form.data.document_type,
        language: form.data.language as 'en' | 'am',
        page_size: 'A4',
        orientation: form.data.orientation as 'portrait' | 'landscape',
        reference_label: form.data.reference_label,
        reference_prefix: form.data.reference_prefix,
        reference_start_number: numericOrNull(form.data.reference_start_number) ?? 1,
        current_reference_number: templateItem?.current_reference_number ?? 0,
        numbering_config: {
            separator: form.data.numbering_separator,
            include_year: form.data.numbering_include_year,
            pad_length: numericOrNull(form.data.numbering_pad_length) ?? 4,
        },
        subject_template: form.data.subject_template,
        recipient_block_template: form.data.recipient_block_template,
        salutation_template: form.data.salutation_template,
        body_content: form.data.body_content,
        closing_content: form.data.closing_content,
        signature_block_content: form.data.signature_block_content,
        cc_content: form.data.cc_content,
        enclosure_content: form.data.enclosure_content,
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
        header_image_url: headerPreviewUrl,
        footer_image_url: footerPreviewUrl,
        next_reference_number_preview: previewReferenceNumber,
        is_active: form.data.is_active,
        is_default: form.data.is_default,
        notes: form.data.notes,
    }), [footerPreviewUrl, form.data.body_content, form.data.cc_content, form.data.closing_content, form.data.code, form.data.content_bottom_margin_mm, form.data.content_top_margin_mm, form.data.document_type, form.data.enclosure_content, form.data.footer_bottom_margin_mm, form.data.footer_left_margin_mm, form.data.footer_right_margin_mm, form.data.footer_top_spacing_mm, form.data.header_bottom_spacing_mm, form.data.header_top_margin_mm, form.data.is_active, form.data.is_default, form.data.language, form.data.margin_bottom_mm, form.data.margin_left_mm, form.data.margin_right_mm, form.data.margin_top_mm, form.data.name, form.data.numbering_include_year, form.data.numbering_pad_length, form.data.numbering_separator, form.data.orientation, form.data.recipient_block_template, form.data.reference_label, form.data.reference_prefix, form.data.reference_start_number, form.data.salutation_template, form.data.signature_block_content, form.data.subject_template, form.data.notes, headerPreviewUrl, previewReferenceNumber, templateItem?.current_reference_number]);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.letter_templates'), href: route('letter-templates.index') },
                { label: isEditing ? t('common.edit') : t('common.create_record') },
            ]}
        >
            <Head title={isEditing ? t('letter_templates.edit_title') : t('letter_templates.create_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('letter_templates.eyebrow')}
                    title={isEditing ? t('letter_templates.edit_title') : t('letter_templates.create_title')}
                    description={isEditing ? t('letter_templates.edit_description') : t('letter_templates.create_description')}
                    action={<BackButton fallbackHref={route('letter-templates.index')} />}
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();

                        if (isEditing && templateItem?.id) {
                            form.transform((data) => ({
                                ...data,
                                _method: 'patch',
                            }));
                            form.post(route('letter-templates.update', templateItem.id), {
                                forceFormData: true,
                                onFinish: () => form.transform((data) => data),
                            });

                            return;
                        }

                        form.post(route('letter-templates.store'), { forceFormData: true });
                    }}
                    className="space-y-4"
                >
                    <SurfaceCard className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.basic_information')}</h2>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">{t('letter_templates.section_help.basic_information')}</p>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <FormField label={t('letter_templates.fields.name')} required error={form.errors.name}>
                                <input value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('common.code')} required error={form.errors.code}>
                                <input value={form.data.code} onChange={(event) => form.setData('code', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.document_type')} optional error={form.errors.document_type}>
                                <input value={form.data.document_type} onChange={(event) => form.setData('document_type', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('common.language')} required error={form.errors.language}>
                                <select value={form.data.language} onChange={(event) => form.setData('language', event.target.value as 'en' | 'am')} className="select-ui">
                                    <option value="en">{t('letter_templates.languages.en')}</option>
                                    <option value="am">{t('letter_templates.languages.am')}</option>
                                </select>
                            </FormField>
                            <FormField label={t('common.status')} required error={form.errors.is_active as string | undefined}>
                                <select value={form.data.is_active ? '1' : '0'} onChange={(event) => form.setData('is_active', event.target.value === '1')} className="select-ui">
                                    <option value="1">{t('common.active')}</option>
                                    <option value="0">{t('common.inactive')}</option>
                                </select>
                            </FormField>
                            <FormField label={t('letter_templates.default_template')} required error={form.errors.is_default as string | undefined}>
                                <select value={form.data.is_default ? '1' : '0'} onChange={(event) => form.setData('is_default', event.target.value === '1')} className="select-ui">
                                    <option value="0">{t('common.no')}</option>
                                    <option value="1">{t('common.yes')}</option>
                                </select>
                            </FormField>
                        </div>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.layout_settings')}</h2>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">{t('letter_templates.section_help.layout_settings')}</p>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <FormField label={t('letter_templates.fields.page_size')} required error={form.errors.page_size}>
                                <select value={form.data.page_size} onChange={() => form.setData('page_size', 'A4')} className="select-ui">
                                    <option value="A4">A4</option>
                                </select>
                            </FormField>
                            <FormField label={t('letter_templates.fields.orientation')} required error={form.errors.orientation}>
                                <select value={form.data.orientation} onChange={(event) => form.setData('orientation', event.target.value as 'portrait' | 'landscape')} className="select-ui">
                                    <option value="portrait">{t('letter_templates.orientation.portrait')}</option>
                                    <option value="landscape">{t('letter_templates.orientation.landscape')}</option>
                                </select>
                            </FormField>
                            <FormField label={t('letter_templates.fields.reference_label')} optional error={form.errors.reference_label}>
                                <input value={form.data.reference_label} onChange={(event) => form.setData('reference_label', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.margin_right')} optional error={form.errors.margin_right_mm}>
                                <input value={form.data.margin_right_mm} onChange={(event) => form.setData('margin_right_mm', event.target.value)} className="input-ui" type="number" min="5" max="35" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.margin_left')} optional error={form.errors.margin_left_mm}>
                                <input value={form.data.margin_left_mm} onChange={(event) => form.setData('margin_left_mm', event.target.value)} className="input-ui" type="number" min="5" max="35" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.header_top_margin')} optional error={form.errors.header_top_margin_mm} hint={t('letter_templates.helpers.header_top_margin')}>
                                <input value={form.data.header_top_margin_mm} onChange={(event) => form.setData('header_top_margin_mm', event.target.value)} className="input-ui" type="number" min="0" max="25" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.header_bottom_spacing')} optional error={form.errors.header_bottom_spacing_mm} hint={t('letter_templates.helpers.header_bottom_spacing')}>
                                <input value={form.data.header_bottom_spacing_mm} onChange={(event) => form.setData('header_bottom_spacing_mm', event.target.value)} className="input-ui" type="number" min="0" max="25" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.footer_top_spacing')} optional error={form.errors.footer_top_spacing_mm} hint={t('letter_templates.helpers.footer_top_spacing')}>
                                <input value={form.data.footer_top_spacing_mm} onChange={(event) => form.setData('footer_top_spacing_mm', event.target.value)} className="input-ui" type="number" min="0" max="25" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.footer_left_margin')} optional error={form.errors.footer_left_margin_mm} hint={t('letter_templates.helpers.footer_left_margin')}>
                                <input value={form.data.footer_left_margin_mm} onChange={(event) => form.setData('footer_left_margin_mm', event.target.value)} className="input-ui" type="number" min="0" max="35" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.footer_right_margin')} optional error={form.errors.footer_right_margin_mm} hint={t('letter_templates.helpers.footer_right_margin')}>
                                <input value={form.data.footer_right_margin_mm} onChange={(event) => form.setData('footer_right_margin_mm', event.target.value)} className="input-ui" type="number" min="0" max="35" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.footer_bottom_margin')} optional error={form.errors.footer_bottom_margin_mm} hint={t('letter_templates.helpers.footer_bottom_margin')}>
                                <input value={form.data.footer_bottom_margin_mm} onChange={(event) => form.setData('footer_bottom_margin_mm', event.target.value)} className="input-ui" type="number" min="0" max="25" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.content_top_margin')} optional error={form.errors.content_top_margin_mm} hint={t('letter_templates.helpers.content_top_margin')}>
                                <input
                                    value={form.data.content_top_margin_mm}
                                    onChange={(event) => {
                                        form.setData('content_top_margin_mm', event.target.value);
                                        form.setData('margin_top_mm', event.target.value);
                                    }}
                                    className="input-ui"
                                    type="number"
                                    min="0"
                                    max="30"
                                />
                            </FormField>
                            <FormField label={t('letter_templates.fields.content_bottom_margin')} optional error={form.errors.content_bottom_margin_mm} hint={t('letter_templates.helpers.content_bottom_margin')}>
                                <input
                                    value={form.data.content_bottom_margin_mm}
                                    onChange={(event) => {
                                        form.setData('content_bottom_margin_mm', event.target.value);
                                        form.setData('margin_bottom_mm', event.target.value);
                                    }}
                                    className="input-ui"
                                    type="number"
                                    min="0"
                                    max="30"
                                />
                            </FormField>
                        </div>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.header_footer_assets')}</h2>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">{t('letter_templates.section_help.header_footer_assets')}</p>
                        </div>
                        <div className="grid gap-4 xl:grid-cols-2">
                            <FormField label={t('letter_templates.fields.header_image')} optional error={form.errors.header_image}>
                                <input
                                    type="file"
                                    accept="image/png"
                                    onChange={(event) => {
                                        const file = event.target.files?.[0] ?? null;
                                        form.setData('header_image', file);
                                        setHeaderPreviewUrl(file ? URL.createObjectURL(file) : (templateItem?.header_image_url ?? null));
                                    }}
                                    className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                />
                            </FormField>
                            <FormField label={t('letter_templates.fields.footer_image')} optional error={form.errors.footer_image}>
                                <input
                                    type="file"
                                    accept="image/png"
                                    onChange={(event) => {
                                        const file = event.target.files?.[0] ?? null;
                                        form.setData('footer_image', file);
                                        setFooterPreviewUrl(file ? URL.createObjectURL(file) : (templateItem?.footer_image_url ?? null));
                                    }}
                                    className="input-ui file:mr-4 file:rounded-full file:border-0 file:bg-[var(--primary-soft)] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[color:var(--primary)]"
                                />
                            </FormField>
                        </div>
                        <div className="grid gap-4 xl:grid-cols-2">
                            <AssetPreview title={t('letter_templates.fields.header_image')} imageUrl={headerPreviewUrl} emptyLabel={t('letter_templates.empty_header_image')} />
                            <AssetPreview title={t('letter_templates.fields.footer_image')} imageUrl={footerPreviewUrl} emptyLabel={t('letter_templates.empty_footer_image')} />
                        </div>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.numbering_settings')}</h2>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">{t('letter_templates.section_help.numbering_settings')}</p>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <FormField label={t('letter_templates.fields.reference_prefix')} required error={form.errors.reference_prefix}>
                                <input value={form.data.reference_prefix} onChange={(event) => form.setData('reference_prefix', event.target.value)} className="input-ui" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.reference_start_number')} required error={form.errors.reference_start_number}>
                                <input value={form.data.reference_start_number} onChange={(event) => form.setData('reference_start_number', event.target.value)} className="input-ui" type="number" min="1" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.numbering_separator')} required error={form.errors.numbering_separator}>
                                <input value={form.data.numbering_separator} onChange={(event) => form.setData('numbering_separator', event.target.value)} className="input-ui" maxLength={5} />
                            </FormField>
                            <FormField label={t('letter_templates.fields.numbering_pad_length')} required error={form.errors.numbering_pad_length}>
                                <input value={form.data.numbering_pad_length} onChange={(event) => form.setData('numbering_pad_length', event.target.value)} className="input-ui" type="number" min="1" max="8" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.numbering_include_year')} required error={form.errors.numbering_include_year as string | undefined}>
                                <select
                                    value={form.data.numbering_include_year ? '1' : '0'}
                                    onChange={(event) => form.setData('numbering_include_year', event.target.value === '1')}
                                    className="select-ui"
                                >
                                    <option value="1">{t('common.yes')}</option>
                                    <option value="0">{t('common.no')}</option>
                                </select>
                            </FormField>
                            <InfoPill label={t('letter_templates.fields.current_reference_number')} value={String(templateItem?.current_reference_number ?? 0)} />
                            <InfoPill label={t('letter_templates.fields.next_reference_number_preview')} value={previewReferenceNumber} />
                        </div>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-4">
                        <div>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.placeholders')}</h2>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">{t('letter_templates.section_help.placeholders')}</p>
                        </div>
                        <div className="grid gap-4 xl:grid-cols-[0.8fr,1.2fr]">
                            <FormField label={t('letter_templates.fields.placeholder_target')} required>
                                <select value={placeholderTarget} onChange={(event) => setPlaceholderTarget(event.target.value as PlaceholderTarget)} className="select-ui">
                                    <option value="recipient_block_template">{t('letter_templates.fields.recipient_block_template')}</option>
                                    <option value="subject_template">{t('letter_templates.fields.subject_template')}</option>
                                    <option value="salutation_template">{t('letter_templates.fields.salutation_template')}</option>
                                    <option value="body_content">{t('letter_templates.fields.body_template')}</option>
                                    <option value="closing_content">{t('letter_templates.fields.closing_template')}</option>
                                    <option value="signature_block_content">{t('letter_templates.fields.signature_block_template')}</option>
                                    <option value="cc_content">{t('letter_templates.fields.cc_template')}</option>
                                    <option value="enclosure_content">{t('letter_templates.fields.enclosure_template')}</option>
                                </select>
                            </FormField>
                            <div className="flex flex-wrap gap-2">
                                {placeholderFields.map((field) => (
                                    <button
                                        key={field.token}
                                        type="button"
                                        className="rounded-full border border-[color:var(--border)] px-3 py-2 text-sm font-medium text-[color:var(--muted-strong)] transition hover:bg-[color:var(--surface-muted)]"
                                        title={field.description}
                                        onClick={() => {
                                            form.setData((data) => ({
                                                ...data,
                                                [placeholderTarget]: appendPlaceholder(String(data[placeholderTarget] ?? ''), field.token),
                                            }));

                                            if (typeof navigator !== 'undefined' && navigator.clipboard) {
                                                void navigator.clipboard.writeText(field.token);
                                            }
                                        }}
                                    >
                                        {field.token}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-4">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.default_content')}</h2>
                        <div className="grid gap-4 xl:grid-cols-2">
                            <FormField label={t('letter_templates.fields.recipient_block_template')} optional error={form.errors.recipient_block_template}>
                                <textarea value={form.data.recipient_block_template} onChange={(event) => form.setData('recipient_block_template', event.target.value)} className="textarea-ui min-h-36" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.subject_template')} optional error={form.errors.subject_template}>
                                <textarea value={form.data.subject_template} onChange={(event) => form.setData('subject_template', event.target.value)} className="textarea-ui min-h-36" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.salutation_template')} optional error={form.errors.salutation_template}>
                                <textarea value={form.data.salutation_template} onChange={(event) => form.setData('salutation_template', event.target.value)} className="textarea-ui min-h-28" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.body_template')} required error={form.errors.body_content}>
                                <RichTextEditor value={form.data.body_content} onChange={(value) => form.setData('body_content', value)} minHeight={320} />
                            </FormField>
                        </div>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-4">
                        <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.signature_and_optional')}</h2>
                        <div className="grid gap-4 xl:grid-cols-2">
                            <FormField label={t('letter_templates.fields.closing_template')} optional error={form.errors.closing_content}>
                                <textarea value={form.data.closing_content} onChange={(event) => form.setData('closing_content', event.target.value)} className="textarea-ui min-h-28" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.signature_block_template')} optional error={form.errors.signature_block_content}>
                                <textarea value={form.data.signature_block_content} onChange={(event) => form.setData('signature_block_content', event.target.value)} className="textarea-ui min-h-28" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.cc_template')} optional error={form.errors.cc_content}>
                                <textarea value={form.data.cc_content} onChange={(event) => form.setData('cc_content', event.target.value)} className="textarea-ui min-h-24" />
                            </FormField>
                            <FormField label={t('letter_templates.fields.enclosure_template')} optional error={form.errors.enclosure_content}>
                                <textarea value={form.data.enclosure_content} onChange={(event) => form.setData('enclosure_content', event.target.value)} className="textarea-ui min-h-24" />
                            </FormField>
                            <FormField label={t('common.notes')} optional error={form.errors.notes} className="xl:col-span-2">
                                <textarea value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} className="textarea-ui min-h-24" />
                            </FormField>
                        </div>
                    </SurfaceCard>

                    <SurfaceCard className="space-y-4 overflow-hidden">
                        <div>
                            <h2 className="text-lg font-semibold text-[color:var(--text)]">{t('letter_templates.sections.preview')}</h2>
                            <p className="mt-1 text-sm text-[color:var(--muted)]">{t('letter_templates.section_help.preview')}</p>
                        </div>
                        <div className="overflow-x-auto rounded-3xl bg-slate-100 p-4 dark:bg-slate-900">
                            <LetterSheet
                                document={buildTemplateRenderable(previewTemplate, {
                                    ...previewData,
                                    reference_number: previewReferenceNumber,
                                })}
                                labels={{
                                    subject: t('letter_templates.preview.subject'),
                                    cc: t('letter_templates.preview.cc'),
                                    enclosure: t('letter_templates.preview.enclosure'),
                                    reference: t('letter_templates.preview.reference'),
                                    date: t('letter_templates.preview.date'),
                                }}
                            />
                        </div>
                    </SurfaceCard>

                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            {isEditing && canDelete && templateItem?.id ? (
                                <button type="button" className="btn-base btn-danger focus-ring" onClick={() => setConfirmOpen(true)}>
                                    {t('common.delete')}
                                </button>
                            ) : null}
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {isEditing && templateItem?.id ? (
                                <Link href={route('letter-templates.show', templateItem.id)} className="btn-base btn-secondary focus-ring">
                                    {t('common.view')}
                                </Link>
                            ) : null}
                            <button type="submit" className="btn-base btn-primary focus-ring" disabled={form.processing}>
                                {isEditing ? t('common.save_changes') : t('common.create_record')}
                            </button>
                        </div>
                    </div>
                </form>
            </PageContainer>

            <ConfirmationDialog
                open={confirmOpen}
                title={t('letter_templates.delete_title')}
                description={t('letter_templates.delete_confirm')}
                confirmLabel={t('common.delete')}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => {
                    if (!templateItem?.id) {
                        return;
                    }

                    router.delete(route('letter-templates.destroy', templateItem.id));
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

function AssetPreview({ title, imageUrl, emptyLabel }: { title: string; imageUrl: string | null; emptyLabel: string }) {
    return (
        <div className="surface-muted space-y-3 p-4">
            <p className="text-sm font-semibold text-[color:var(--text)]">{title}</p>
            {imageUrl ? (
                <img src={imageUrl} alt={title} className="max-h-40 w-full rounded-2xl border border-[color:var(--border)] object-contain bg-white p-3" />
            ) : (
                <p className="text-sm text-[color:var(--muted)]">{emptyLabel}</p>
            )}
        </div>
    );
}

function InfoPill({ label, value }: { label: string; value: string }) {
    return (
        <div className="surface-muted flex min-h-[82px] flex-col justify-center px-4 py-3">
            <p className="text-xs uppercase text-[color:var(--muted)]">{label}</p>
            <p className="mt-2 text-sm font-semibold text-[color:var(--text)]">{value}</p>
        </div>
    );
}
