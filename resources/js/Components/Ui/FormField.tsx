import InputError from '@/Components/InputError';
import { cn } from '@/lib/cn';
import { useI18n } from '@/lib/i18n';
import { PropsWithChildren } from 'react';

export default function FormField({
    label,
    required = false,
    optional = false,
    error,
    hint,
    className,
    children,
}: PropsWithChildren<{
    label: string;
    required?: boolean;
    optional?: boolean;
    error?: string;
    hint?: string;
    className?: string;
}>) {
    const { t } = useI18n();

    return (
        <label className={cn('block space-y-2', className)}>
            <span className="flex items-center gap-2 text-sm font-medium text-[color:var(--text)]">
                <span>{label}</span>
                {required ? <span className="text-rose-500">*</span> : null}
                {optional ? (
                    <span className="text-xs text-[color:var(--muted)]">{t('common.optional')}</span>
                ) : null}
            </span>
            {children}
            {hint ? <p className="text-xs text-[color:var(--muted)]">{hint}</p> : null}
            <InputError message={error} />
        </label>
    );
}
