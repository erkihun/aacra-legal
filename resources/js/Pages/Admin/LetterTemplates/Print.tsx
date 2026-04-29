import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import { useEffect } from 'react';
import { buildTemplateRenderable, defaultPreviewData, LetterSheet, LetterTemplateItem, PreviewData } from './shared';

type Props = {
    templateItem: LetterTemplateItem;
    previewData?: PreviewData;
};

export default function LetterTemplatePrint({ templateItem, previewData = defaultPreviewData }: Props) {
    const { t } = useI18n();

    useEffect(() => {
        document.body.classList.add('bg-white');

        return () => {
            document.body.classList.remove('bg-white');
        };
    }, []);

    return (
        <>
            <Head title={`${templateItem.name} ${t('letter_templates.actions.print')}`} />

            <div className="min-h-screen bg-white px-4 py-6 text-slate-900 print:px-0 print:py-0">
                <div className="mx-auto max-w-[1280px] space-y-5 print:max-w-none">
                    <div className="flex items-start justify-between gap-4 border-b border-slate-300 pb-4 print:hidden">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{t('letter_templates.print.header')}</p>
                            <h1 className="mt-1 text-2xl font-semibold">{templateItem.name}</h1>
                        </div>
                        <div className="flex gap-2">
                            <button type="button" onClick={() => window.print()} className="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                                {t('letter_templates.actions.print')}
                            </button>
                            <button type="button" onClick={() => window.close()} className="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                                {t('common.close')}
                            </button>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <LetterSheet
                            document={buildTemplateRenderable(templateItem, previewData)}
                            labels={{
                                subject: t('letter_templates.preview.subject'),
                                cc: t('letter_templates.preview.cc'),
                                enclosure: t('letter_templates.preview.enclosure'),
                                reference: t('letter_templates.preview.reference'),
                                date: t('letter_templates.preview.date'),
                            }}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
