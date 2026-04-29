import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import { useEffect } from 'react';
import { buildLetterRenderable, LetterItem, LetterSheet } from '../LetterTemplates/shared';

type Props = {
    letterItem: LetterItem;
};

export default function LetterPrint({ letterItem }: Props) {
    const { t } = useI18n();

    useEffect(() => {
        document.body.classList.add('bg-white');

        return () => {
            document.body.classList.remove('bg-white');
        };
    }, []);

    return (
        <>
            <Head title={`${letterItem.reference_number ?? t('letters.detail_title')} ${t('letters.actions.print')}`} />

            <div className="min-h-screen bg-white px-4 py-6 text-slate-900 print:px-0 print:py-0">
                <div className="mx-auto max-w-[1280px] space-y-5 print:max-w-none">
                    <div className="flex items-start justify-between gap-4 border-b border-slate-300 pb-4 print:hidden">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{t('letters.print.header')}</p>
                            <h1 className="mt-1 text-2xl font-semibold">{letterItem.reference_number ?? t('letters.detail_title')}</h1>
                        </div>
                        <div className="flex gap-2">
                            <button type="button" onClick={() => window.print()} className="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                                {t('letters.actions.print')}
                            </button>
                            <button type="button" onClick={() => window.close()} className="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                                {t('common.close')}
                            </button>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <LetterSheet
                            document={buildLetterRenderable(letterItem)}
                            labels={{
                                subject: t('letters.preview.subject'),
                                cc: t('letters.preview.cc'),
                                enclosure: t('letters.preview.enclosure'),
                                reference: t('letters.preview.reference'),
                                date: t('letters.preview.date'),
                            }}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
