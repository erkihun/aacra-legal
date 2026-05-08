import { formatDocumentDateValue } from '@/lib/ethiopian-dates';
import { useI18n } from '@/lib/i18n';
import { EtCalendar } from '@/vendor/react-ethiopian-calendar';
import { type InputHTMLAttributes } from 'react';

type LocalizedDateInputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'value' | 'onChange'> & {
    value: string;
    onChange: (value: string) => void;
    previewClassName?: string;
};

type CalendarChangeValue = Date | {
    toDate?: () => Date;
    year?: () => number;
    month?: () => number;
    date?: () => number;
} | null;

function normalizeDateString(value: string) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return null;
    }

    const [year, month, day] = value.split('-').map((segment) => Number(segment));

    if (!year || !month || !day) {
        return null;
    }

    return new Date(year, month - 1, day, 12, 0, 0);
}

function formatIsoDate(year: number, month: number, day: number) {
    return [
        String(year).padStart(4, '0'),
        String(month + 1).padStart(2, '0'),
        String(day).padStart(2, '0'),
    ].join('-');
}

function resolveCalendarValue(value: CalendarChangeValue) {
    if (!value) {
        return '';
    }

    if (value instanceof Date) {
        return formatIsoDate(value.getFullYear(), value.getMonth(), value.getDate());
    }

    if (typeof value.toDate === 'function') {
        const date = value.toDate();

        return formatIsoDate(date.getFullYear(), date.getMonth(), date.getDate());
    }

    if (typeof value.year === 'function' && typeof value.month === 'function' && typeof value.date === 'function') {
        return formatIsoDate(value.year(), value.month(), value.date());
    }

    return '';
}

export default function LocalizedDateInput({
    value,
    onChange,
    className,
    previewClassName,
    ...props
}: LocalizedDateInputProps) {
    const { locale } = useI18n();
    const preview = locale === 'am'
        ? formatDocumentDateValue(value, 'am')
        : '';
    const calendarValue = normalizeDateString(value);

    if (locale === 'am') {
        return (
            <div className="space-y-2">
                <EtCalendar
                    value={calendarValue}
                    onChange={(nextValue: CalendarChangeValue) => onChange(resolveCalendarValue(nextValue))}
                    calendarType={true}
                    lang="am"
                    disabled={props.disabled}
                    minDate={typeof props.min === 'string' ? normalizeDateString(props.min) : null}
                    maxDate={typeof props.max === 'string' ? normalizeDateString(props.max) : null}
                    fullWidth={true}
                    placeholder={false}
                    borderRadius="0.75rem"
                    inputStyle={{
                        width: '100%',
                        minWidth: 0,
                        borderColor: 'var(--border)',
                        backgroundColor: 'var(--surface)',
                        color: 'var(--text)',
                    }}
                    onBlur={props.onBlur}
                />
                <input type="hidden" name={props.name} value={value} />
                {preview !== '' ? (
                    <p className={previewClassName ?? 'text-xs text-[color:var(--muted)]'}>{preview}</p>
                ) : null}
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <input
                {...props}
                type="date"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className={className}
            />
            {locale === 'am' && preview !== '' ? (
                <p className={previewClassName ?? 'text-xs text-[color:var(--muted)]'}>{preview}</p>
            ) : null}
        </div>
    );
}
