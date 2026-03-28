import { ReactNode } from 'react';
import { cn } from '@/lib/cn';

type SectionHeaderProps = {
    eyebrow?: string;
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
};

export default function SectionHeader({
    eyebrow,
    title,
    description,
    action,
    className,
}: SectionHeaderProps) {
    return (
        <div className={cn('surface-card-strong flex flex-col gap-5 p-6 xl:flex-row xl:items-end xl:justify-between', className)}>
            <div className="space-y-2">
                {eyebrow ? (
                    <p className="text-xs font-semibold uppercase text-[color:var(--primary)]">
                        {eyebrow}
                    </p>
                ) : null}
                <div>
                    <h1 className="text-3xl font-semibold tracking-tight text-[color:var(--text)] md:text-4xl">
                        {title}
                    </h1>
                    {description ? (
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-[color:var(--muted-strong)]">
                            {description}
                        </p>
                    ) : null}
                </div>
            </div>

            {action ? <div className="shrink-0">{action}</div> : null}
        </div>
    );
}
