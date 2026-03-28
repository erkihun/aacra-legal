import DataTable from '@/Components/Ui/DataTable';
import EmptyState from '@/Components/Ui/EmptyState';
import FiltersToolbar from '@/Components/Ui/FiltersToolbar';
import PageContainer from '@/Components/Ui/PageContainer';
import Pagination from '@/Components/Ui/Pagination';
import SectionHeader from '@/Components/Ui/SectionHeader';
import StatusBadge from '@/Components/Ui/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

type TeamRow = {
    id: string;
    code: string;
    name_en: string;
    name_am: string;
    type: string;
    leader?: { id: string; name: string } | null;
    is_active: boolean;
};

export default function TeamsIndex({ filters, teams, typeOptions, can }: any) {
    const { t, locale } = useI18n();
    const [isFiltering, setIsFiltering] = useState(false);
    const form = useForm({
        search: filters.search ?? '',
        type: filters.type ?? '',
        is_active: filters.is_active ?? '',
    });

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('navigation.dashboard'), href: route('dashboard') },
                { label: t('navigation.teams') },
            ]}
        >
            <Head title={t('teams.index_title')} />

            <PageContainer>
                <SectionHeader
                    eyebrow={t('teams.eyebrow')}
                    title={t('teams.index_title')}
                    description={t('teams.index_description')}
                    action={
                        can.create ? (
                            <Link href={route('teams.create')} className="btn-base btn-primary focus-ring">
                                {t('teams.new_team')}
                            </Link>
                        ) : undefined
                    }
                />

                <FiltersToolbar
                    title={t('teams.filters')}
                    actions={
                        <>
                            <button type="button" className="btn-base btn-secondary focus-ring" onClick={() => router.get(route('teams.index'))}>
                                {t('common.reset')}
                            </button>
                            <button
                                type="button"
                                className="btn-base btn-primary focus-ring"
                                disabled={isFiltering}
                                onClick={() => {
                                    setIsFiltering(true);
                                    router.get(route('teams.index'), form.data, {
                                        preserveState: true,
                                        replace: true,
                                        onFinish: () => setIsFiltering(false),
                                    });
                                }}
                            >
                                {t('common.apply_filters')}
                            </button>
                        </>
                    }
                >
                    <input value={form.data.search} onChange={(event) => form.setData('search', event.target.value)} className="input-ui" placeholder={t('teams.search_placeholder')} />
                    <select value={form.data.type} onChange={(event) => form.setData('type', event.target.value)} className="select-ui">
                        <option value="">{t('teams.type')}</option>
                        {typeOptions.map((option: any) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <select value={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.value)} className="select-ui">
                        <option value="">{t('common.status')}</option>
                        <option value="1">{t('common.active')}</option>
                        <option value="0">{t('common.inactive')}</option>
                    </select>
                </FiltersToolbar>

                {teams.data.length === 0 ? (
                    <EmptyState title={t('teams.empty_title')} description={t('teams.empty_description')} />
                ) : (
                    <>
                        <DataTable<TeamRow>
                            rows={teams.data}
                            rowKey={(row) => row.id}
                            emptyTitle={t('teams.empty_title')}
                            emptyDescription={t('teams.empty_description')}
                            actions={(row) => [
                                { label: t('common.view'), href: route('teams.show', row.id) },
                                ...(can.update ? [{ label: t('common.edit'), href: route('teams.edit', row.id) }] : []),
                            ]}
                            columns={[
                                { key: 'code', header: t('common.code'), cell: (row) => row.code },
                                {
                                    key: 'name',
                                    header: t('common.name'),
                                    cell: (row) => (
                                        <div>
                                            <p className="font-semibold text-[color:var(--text)]">{locale === 'am' ? row.name_am : row.name_en}</p>
                                            <p className="mt-1 text-sm text-[color:var(--muted)]">{row.leader?.name ?? t('common.unassigned')}</p>
                                        </div>
                                    ),
                                },
                                { key: 'type', header: t('teams.type'), cell: (row) => <StatusBadge value={row.type} /> },
                                { key: 'status', header: t('common.status'), cell: (row) => <StatusBadge value={row.is_active ? 'active' : 'inactive'} /> },
                            ]}
                        />
                        <Pagination links={teams.links} />
                    </>
                )}
            </PageContainer>
        </AuthenticatedLayout>
    );
}
