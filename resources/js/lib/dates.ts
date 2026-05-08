import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { formatLocalizedDateValue } from '@/lib/ethiopian-dates';

type DateFormatType = 'date' | 'datetime';

export function useDateFormatter() {
    const { props } = usePage<PageProps>();
    const localization = props.appMeta.localization;

    const format = (value: string | null | undefined, type: DateFormatType, fallback = '') => {
        if (!value) {
            return fallback;
        }

        const pattern = type === 'date' ? localization.date_format : localization.datetime_format;

        return formatLocalizedDateValue(value, {
            locale: props.locale,
            timeZone: localization.timezone,
            gregorianPattern: pattern,
            includeTime: type === 'datetime',
            fallback,
        });
    };

    return {
        formatDate: (value?: string | null, fallback = '') => format(value, 'date', fallback),
        formatDateTime: (value?: string | null, fallback = '') => format(value, 'datetime', fallback),
    };
}
