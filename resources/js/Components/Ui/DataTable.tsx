import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/react';
import { Link } from '@inertiajs/react';
import { ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { useI18n } from '@/lib/i18n';
import EmptyState from './EmptyState';

type Action = {
    label: string;
    href?: string;
    onClick?: () => void;
};

type Column<T> = {
    key: string;
    header: string;
    cell: (row: T) => ReactNode;
    mobileLabel?: string;
    className?: string;
};

export default function DataTable<T>({
    columns,
    rows,
    rowKey,
    emptyTitle,
    emptyDescription,
    actions,
}: {
    columns: Column<T>[];
    rows: T[];
    rowKey: (row: T, index: number) => string;
    emptyTitle: string;
    emptyDescription: string;
    actions?: (row: T) => Action[];
}) {
    const { t } = useI18n();

    if (rows.length === 0) {
        return <EmptyState title={emptyTitle} description={emptyDescription} />;
    }

    return (
        <>
            <div className="table-shell hidden overflow-x-auto lg:block">
                <table className="min-w-full table-fixed">
                    <thead className="table-header text-left text-xs uppercase text-[color:var(--muted-strong)]">
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className="[padding-block:var(--table-cell-padding-y)] [padding-inline:var(--table-cell-padding-x)] font-semibold"
                                >
                                    {column.header}
                                </th>
                            ))}
                            {actions ? (
                                <th className="[padding-block:var(--table-cell-padding-y)] [padding-inline:var(--table-cell-padding-x)] text-right font-semibold">{t('common.actions')}</th>
                            ) : null}
                        </tr>
                    </thead>
                    <tbody className="divide-y" style={{ borderColor: 'var(--border)' }}>
                        {rows.map((row, index) => (
                            <tr key={rowKey(row, index)} className="align-top">
                                {columns.map((column) => (
                                    <td
                                        key={column.key}
                                        className={cn(
                                            '[padding-block:var(--table-cell-padding-y)] [padding-inline:var(--table-cell-padding-x)] text-sm text-[color:var(--text)]',
                                            column.className,
                                        )}
                                    >
                                        {column.cell(row)}
                                    </td>
                                ))}
                                {actions ? (
                                    <td className="[padding-block:var(--table-cell-padding-y)] [padding-inline:var(--table-cell-padding-x)] text-right">
                                        <RowActions actions={actions(row)} />
                                    </td>
                                ) : null}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-4 lg:hidden">
                {rows.map((row, index) => (
                    <div key={rowKey(row, index)} className="section-shell space-y-4">
                        {columns.map((column) => (
                            <div key={column.key} className="space-y-1">
                                <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                                    {column.mobileLabel ?? column.header}
                                </p>
                                <div className="text-sm text-[color:var(--text)]">{column.cell(row)}</div>
                            </div>
                        ))}
                        {actions ? (
                            <div className="border-t pt-3" style={{ borderColor: 'var(--border)' }}>
                                <RowActions actions={actions(row)} />
                            </div>
                        ) : null}
                    </div>
                ))}
            </div>
        </>
    );
}

function RowActions({ actions }: { actions: Action[] }) {
    const { t } = useI18n();

    return (
        <Menu as="div" className="relative inline-block text-left">
            <MenuButton
                className="focus-ring rounded-full border px-3 py-2 text-sm font-medium text-[color:var(--muted-strong)] transition hover:bg-[color:var(--surface-muted)]"
                style={{ borderColor: 'var(--border)' }}
                aria-label={t('common.actions')}
            >
                ...
            </MenuButton>
            <MenuItems
                anchor="bottom end"
                className="surface-card-strong z-20 mt-2 w-48 p-2 outline-none"
            >
                {actions.map((action) => (
                    <MenuItem key={`${action.label}-${action.href ?? 'action'}`}>
                        {action.href ? (
                            <Link
                                href={action.href}
                                className="block rounded-2xl px-3 py-2 text-sm text-[color:var(--text)] transition hover:bg-[color:var(--surface-muted)]"
                            >
                                {action.label}
                            </Link>
                        ) : (
                            <button
                                type="button"
                                onClick={action.onClick}
                                className="block w-full rounded-2xl px-3 py-2 text-left text-sm text-[color:var(--text)] transition hover:bg-[color:var(--surface-muted)]"
                            >
                                {action.label}
                            </button>
                        )}
                    </MenuItem>
                ))}
            </MenuItems>
        </Menu>
    );
}
