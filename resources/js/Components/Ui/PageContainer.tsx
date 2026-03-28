import { PropsWithChildren } from 'react';
import { cn } from '@/lib/cn';

export default function PageContainer({
    children,
    className,
}: PropsWithChildren<{ className?: string }>) {
    return <div className={cn('page-shell', className)}>{children}</div>;
}
