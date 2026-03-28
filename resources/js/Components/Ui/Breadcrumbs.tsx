import { cn } from '@/lib/cn';
import { Link } from '@inertiajs/react';

export type BreadcrumbItem = {
    label: string;
    href?: string;
};

export default function Breadcrumbs({
    items,
    className,
}: {
    items: BreadcrumbItem[];
    className?: string;
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <nav aria-label="Breadcrumb" className={cn('flex flex-wrap items-center gap-2 text-sm', className)}>
            {items.map((item, index) => (
                <div key={`${item.label}-${index}`} className="flex items-center gap-2">
                    {index > 0 ? (
                        <svg viewBox="0 0 20 20" className="h-4 w-4 text-[color:var(--muted)]" fill="none" stroke="currentColor" strokeWidth="1.6">
                            <path d="m8 5 5 5-5 5" />
                        </svg>
                    ) : null}
                    {item.href ? (
                        <Link href={item.href} className="text-[color:var(--muted-strong)] transition hover:text-[color:var(--text)]">
                            {item.label}
                        </Link>
                    ) : (
                        <span className="font-medium text-[color:var(--text)]">{item.label}</span>
                    )}
                </div>
            ))}
        </nav>
    );
}
