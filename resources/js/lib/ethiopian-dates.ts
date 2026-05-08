const ISO_DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

function pad(value: string | undefined) {
    return (value ?? '').padStart(2, '0');
}

function resolveDateParts(value: Date, timeZone: string) {
    const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });

    const parts = formatter.formatToParts(value);

    return {
        year: parts.find((part) => part.type === 'year')?.value,
        month: parts.find((part) => part.type === 'month')?.value,
        day: parts.find((part) => part.type === 'day')?.value,
        hour: parts.find((part) => part.type === 'hour')?.value,
        minute: parts.find((part) => part.type === 'minute')?.value,
    };
}

function applyGregorianPattern(value: Date, pattern: string, timeZone: string) {
    const parts = resolveDateParts(value, timeZone);

    return pattern
        .replaceAll('Y', parts.year ?? '')
        .replaceAll('m', pad(parts.month))
        .replaceAll('d', pad(parts.day))
        .replaceAll('H', pad(parts.hour))
        .replaceAll('i', pad(parts.minute));
}

function normalizeDateValue(value: string) {
    if (ISO_DATE_PATTERN.test(value)) {
        const [year, month, day] = value.split('-').map((part) => Number(part));

        return new Date(Date.UTC(year, month - 1, day, 12, 0, 0));
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function formatEthiopicDate(value: Date, timeZone: string) {
    return new Intl.DateTimeFormat('am-ET-u-ca-ethiopic', {
        timeZone,
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(value);
}

function formatLocalizedTime(value: Date, timeZone: string) {
    return new Intl.DateTimeFormat('am-ET', {
        timeZone,
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(value);
}

export function formatLocalizedDateValue(
    value: string | null | undefined,
    options: {
        locale: string;
        timeZone: string;
        gregorianPattern: string;
        includeTime?: boolean;
        fallback?: string;
    },
) {
    const fallback = options.fallback ?? '';

    if (!value) {
        return fallback;
    }

    const parsed = normalizeDateValue(value);

    if (!parsed) {
        return fallback || value;
    }

    if (options.locale === 'am') {
        const datePart = formatEthiopicDate(parsed, options.timeZone);

        if (!options.includeTime) {
            return datePart;
        }

        return `${datePart} ${formatLocalizedTime(parsed, options.timeZone)}`;
    }

    return applyGregorianPattern(parsed, options.gregorianPattern, options.timeZone);
}

export function formatDocumentDateValue(
    value: string | null | undefined,
    language: string | null | undefined,
    fallback = '',
) {
    return formatLocalizedDateValue(value, {
        locale: language === 'am' ? 'am' : 'en',
        timeZone: 'Africa/Addis_Ababa',
        gregorianPattern: 'Y-m-d',
        includeTime: false,
        fallback,
    });
}
