import { Link } from '@inertiajs/react';
import { useI18n } from '@/lib/i18n';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginationMeta = {
    current_page?: number;
    from?: number | null;
    last_page?: number;
    to?: number | null;
    total?: number;
};

export default function Pagination({
    links,
    meta,
}: {
    links: PaginationLink[];
    meta?: PaginationMeta;
}) {
    const { t } = useI18n();
    const items = links.map((link) => ({
        ...link,
        label: normalizePaginationLabel(link.label, t('common.previous'), t('common.next')),
    }));

    const hasAnotherPage =
        (meta?.last_page ?? 1) > 1
        || items.some((link) => link.url !== null && !link.active);

    if (!hasAnotherPage) {
        return null;
    }

    return (
        <nav className="surface-card flex flex-wrap items-center justify-center gap-2 p-3" aria-label={t('common.pagination')}>
            {items.map((link, index) =>
                link.url ? (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        className={`focus-ring inline-flex min-w-10 items-center justify-center rounded-full px-3 py-2 text-sm font-medium transition ${
                            link.active
                                ? 'bg-[var(--primary)] text-white dark:text-slate-950'
                                : 'text-[color:var(--muted-strong)] hover:bg-[color:var(--surface-muted)]'
                        }`}
                    >
                        {link.label}
                    </Link>
                ) : (
                    <span
                        key={`${link.label}-${index}`}
                        className="inline-flex min-w-10 items-center justify-center rounded-full px-3 py-2 text-sm text-[color:var(--muted)]"
                    >
                        {link.label}
                    </span>
                ),
            )}
        </nav>
    );
}

function normalizePaginationLabel(label: string, previousLabel: string, nextLabel: string): string {
    return label
        .replace(/&laquo;\s*Previous/i, previousLabel)
        .replace(/Next\s*&raquo;/i, nextLabel)
        .replace(/&laquo;/g, '<<')
        .replace(/&raquo;/g, '>>')
        .replace(/&hellip;/g, '...');
}
