import { router, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useI18n } from '@/lib/i18n';
import { useState } from 'react';

export default function LanguageSwitcher() {
    const { props } = usePage<PageProps>();
    const { t } = useI18n();
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <label className="sr-only" htmlFor="locale-switcher">
                {t('common.language')}
            </label>
            <select
                id="locale-switcher"
                value={props.locale}
                onChange={(event) => {
                    const locale = event.target.value;

                    setProcessing(true);

                    router.post(
                        route('locale.update'),
                        { locale },
                        {
                        preserveScroll: true,
                            preserveState: false,
                            replace: true,
                            onFinish: () => setProcessing(false),
                        },
                    );
                }}
                disabled={processing}
                className="select-ui focus-ring min-w-[6rem]"
            >
                {props.availableLocales.map((locale) => (
                    <option key={locale.value} value={locale.value}>
                        {locale.label}
                    </option>
                ))}
            </select>
        </>
    );
}
