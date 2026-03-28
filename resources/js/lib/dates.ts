import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

type DateFormatType = 'date' | 'datetime';

function pad(value: string | undefined) {
    return (value ?? '').padStart(2, '0');
}

function resolveDateParts(value: string | Date, timeZone: string) {
    const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });

    const parts = formatter.formatToParts(typeof value === 'string' ? new Date(value) : value);

    return {
        year: parts.find((part) => part.type === 'year')?.value,
        month: parts.find((part) => part.type === 'month')?.value,
        day: parts.find((part) => part.type === 'day')?.value,
        hour: parts.find((part) => part.type === 'hour')?.value,
        minute: parts.find((part) => part.type === 'minute')?.value,
    };
}

function applyPattern(value: string | Date, pattern: string, timeZone: string) {
    const parts = resolveDateParts(value, timeZone);

    return pattern
        .replaceAll('Y', parts.year ?? '')
        .replaceAll('m', pad(parts.month))
        .replaceAll('d', pad(parts.day))
        .replaceAll('H', pad(parts.hour))
        .replaceAll('i', pad(parts.minute));
}

export function useDateFormatter() {
    const { props } = usePage<PageProps>();
    const localization = props.appMeta.localization;

    const format = (value: string | null | undefined, type: DateFormatType, fallback = '') => {
        if (!value) {
            return fallback;
        }

        const parsed = new Date(value);

        if (Number.isNaN(parsed.getTime())) {
            return fallback || value;
        }

        const pattern = type === 'date' ? localization.date_format : localization.datetime_format;

        return applyPattern(parsed, pattern, localization.timezone);
    };

    return {
        formatDate: (value?: string | null, fallback = '') => format(value, 'date', fallback),
        formatDateTime: (value?: string | null, fallback = '') => format(value, 'datetime', fallback),
    };
}
