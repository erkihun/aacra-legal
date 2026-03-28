import { ElementType, PropsWithChildren } from 'react';
import { cn } from '@/lib/cn';

type SurfaceCardProps<T extends ElementType> = PropsWithChildren<{
    as?: T;
    className?: string;
    strong?: boolean;
}>;

export default function SurfaceCard<T extends ElementType = 'section'>({
    as,
    className,
    strong = false,
    children,
}: SurfaceCardProps<T>) {
    const Component = as ?? 'section';

    return (
        <Component className={cn(strong ? 'surface-card-strong' : 'section-shell', className)}>
            {children}
        </Component>
    );
}
