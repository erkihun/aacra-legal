import { formatDocumentDateValue } from '@/lib/ethiopian-dates';
import { useI18n } from '@/lib/i18n';
import { type InputHTMLAttributes } from 'react';

type LocalizedDateInputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'value' | 'onChange'> & {
    value: string;
    onChange: (value: string) => void;
    previewClassName?: string;
};

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
