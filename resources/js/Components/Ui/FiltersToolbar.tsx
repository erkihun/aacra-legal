import { PropsWithChildren, ReactNode } from 'react';
import { cn } from '@/lib/cn';
import SurfaceCard from './SurfaceCard';

export default function FiltersToolbar({
    title,
    actions,
    children,
    className,
}: PropsWithChildren<{ title?: string; actions?: ReactNode; className?: string }>) {
    return (
        <SurfaceCard className={cn('space-y-4', className)}>
            {title || actions ? (
                <div className="flex flex-wrap items-center justify-between gap-3">
                    {title ? (
                        <div>
                            <p className="text-sm font-semibold text-[color:var(--text)]">{title}</p>
                        </div>
                    ) : (
                        <span />
                    )}
                    {actions ? <div className="flex flex-wrap items-center gap-3">{actions}</div> : null}
                </div>
            ) : null}
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">{children}</div>
        </SurfaceCard>
    );
}
