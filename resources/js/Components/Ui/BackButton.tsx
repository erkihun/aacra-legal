import { router } from '@inertiajs/react';
import { MouseEvent } from 'react';
import { cn } from '@/lib/cn';
import { useI18n } from '@/lib/i18n';
import { hasBackHistory } from '@/lib/navigation-history';

type BackButtonProps = {
    fallbackHref: string;
    className?: string;
    label?: string;
};

export default function BackButton({
    fallbackHref,
    className,
    label,
}: BackButtonProps) {
    const { t } = useI18n();
    const buttonLabel = label ?? t('common.back');

    const handleClick = (event: MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();

        if (hasBackHistory() && window.history.length > 1) {
            window.history.back();

            return;
        }

        router.visit(fallbackHref);
    };

    return (
        <button
            type="button"
            onClick={handleClick}
            className={cn('btn-base btn-secondary focus-ring whitespace-nowrap', className)}
            aria-label={buttonLabel}
        >
            <svg
                viewBox="0 0 24 24"
                className="h-4 w-4"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.8"
                strokeLinecap="round"
                strokeLinejoin="round"
                aria-hidden="true"
            >
                <path d="M15 18l-6-6 6-6" />
                <path d="M9 12h10" />
            </svg>
            <span>{buttonLabel}</span>
        </button>
    );
}
