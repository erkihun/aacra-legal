import { ReactNode } from 'react';
import { cn } from '@/lib/cn';
import SurfaceCard from '@/Components/Ui/SurfaceCard';

type WorkspaceTone = 'default' | 'accent' | 'success' | 'warning' | 'danger';

const toneClasses: Record<WorkspaceTone, string> = {
    default: 'border-[color:var(--border)] bg-transparent',
    accent: 'border-cyan-400/25 bg-cyan-500/8 dark:border-cyan-400/15 dark:bg-cyan-500/10',
    success: 'border-emerald-400/25 bg-emerald-500/8 dark:border-emerald-400/15 dark:bg-emerald-500/10',
    warning: 'border-amber-400/30 bg-amber-500/10 dark:border-amber-400/20 dark:bg-amber-500/10',
    danger: 'border-rose-400/25 bg-rose-500/8 dark:border-rose-400/15 dark:bg-rose-500/10',
};

export function WorkspacePanel({
    eyebrow,
    title,
    description,
    icon,
    actions,
    footer,
    children,
    className,
    contentClassName,
    tone = 'default',
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    icon?: ReactNode;
    actions?: ReactNode;
    footer?: ReactNode;
    children: ReactNode;
    className?: string;
    contentClassName?: string;
    tone?: WorkspaceTone;
}) {
    return (
        <SurfaceCard strong className={cn('overflow-hidden border', toneClasses[tone], className)}>
            <div className="flex flex-wrap items-start justify-between gap-4 border-b border-[color:var(--border)]/80 px-5 py-4">
                <div className="flex min-w-0 items-start gap-3">
                    {icon ? (
                        <span className="surface-muted flex h-11 w-11 shrink-0 items-center justify-center text-[color:var(--primary)]">
                            {icon}
                        </span>
                    ) : null}
                    <div className="min-w-0">
                        {eyebrow ? (
                            <p className="text-[11px] font-semibold uppercase tracking-wide text-[color:var(--primary)]">
                                {eyebrow}
                            </p>
                        ) : null}
                        <h2 className="mt-1 text-lg font-semibold text-[color:var(--text)]">{title}</h2>
                        {description ? (
                            <p className="mt-2 max-w-3xl text-sm leading-6 text-[color:var(--muted-strong)]">
                                {description}
                            </p>
                        ) : null}
                    </div>
                </div>
                {actions ? <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div> : null}
            </div>

            <div className={cn('space-y-5 px-5 py-5', contentClassName)}>{children}</div>

            {footer ? (
                <div className="border-t border-[color:var(--border)]/80 px-5 py-4">
                    {footer}
                </div>
            ) : null}
        </SurfaceCard>
    );
}

export function WorkspaceStatCard({
    label,
    value,
    helper,
    icon,
}: {
    label: string;
    value: string | number;
    helper?: string;
    icon?: ReactNode;
}) {
    return (
        <div className="section-shell flex items-start gap-3 px-4 py-4">
            {icon ? (
                <span className="surface-muted mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center text-[color:var(--primary)]">
                    {icon}
                </span>
            ) : null}
            <div className="min-w-0">
                <p className="text-[11px] font-semibold uppercase text-[color:var(--muted)]">{label}</p>
                <p className="mt-2 text-xl font-semibold text-[color:var(--text)]">{value}</p>
                {helper ? <p className="mt-1 text-xs leading-5 text-[color:var(--muted-strong)]">{helper}</p> : null}
            </div>
        </div>
    );
}
