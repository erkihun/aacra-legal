import { useI18n } from '@/lib/i18n';

type StatusBadgeProps = {
    value?: string | null;
    label?: string | null;
};

const colorMap: Record<string, string> = {
    submitted: 'bg-sky-500/12 text-sky-700 ring-sky-500/18 dark:text-sky-300',
    under_director_review: 'bg-amber-500/12 text-amber-700 ring-amber-500/18 dark:text-amber-300',
    returned: 'bg-orange-500/12 text-orange-700 ring-orange-500/18 dark:text-orange-300',
    rejected: 'bg-rose-500/12 text-rose-700 ring-rose-500/18 dark:text-rose-300',
    assigned_to_team_leader: 'bg-indigo-500/12 text-indigo-700 ring-indigo-500/18 dark:text-indigo-300',
    assigned_to_expert: 'bg-blue-500/12 text-blue-700 ring-blue-500/18 dark:text-blue-300',
    in_progress: 'bg-cyan-500/12 text-cyan-700 ring-cyan-500/18 dark:text-cyan-300',
    responded: 'bg-emerald-500/12 text-emerald-700 ring-emerald-500/18 dark:text-emerald-300',
    closed: 'bg-emerald-500/12 text-emerald-700 ring-emerald-500/18 dark:text-emerald-300',
    intake: 'bg-slate-500/12 text-slate-700 ring-slate-500/18 dark:text-slate-300',
    awaiting_decision: 'bg-fuchsia-500/12 text-fuchsia-700 ring-fuchsia-500/18 dark:text-fuchsia-300',
    decided: 'bg-violet-500/12 text-violet-700 ring-violet-500/18 dark:text-violet-300',
    appealed: 'bg-pink-500/12 text-pink-700 ring-pink-500/18 dark:text-pink-300',
    critical: 'bg-rose-500/12 text-rose-700 ring-rose-500/18 dark:text-rose-300',
    high: 'bg-amber-500/12 text-amber-700 ring-amber-500/18 dark:text-amber-300',
    medium: 'bg-cyan-500/12 text-cyan-700 ring-cyan-500/18 dark:text-cyan-300',
    low: 'bg-emerald-500/12 text-emerald-700 ring-emerald-500/18 dark:text-emerald-300',
    approved: 'bg-emerald-500/12 text-emerald-700 ring-emerald-500/18 dark:text-emerald-300',
    pending: 'bg-amber-500/12 text-amber-700 ring-amber-500/18 dark:text-amber-300',
};

export default function StatusBadge({ value, label }: StatusBadgeProps) {
    const { t } = useI18n();
    const normalized = value ?? 'pending';
    const classes = colorMap[normalized] ?? 'bg-slate-500/12 text-slate-700 ring-slate-500/18 dark:text-slate-300';
    const translated = t(`status.${normalized}`);
    const text = label ?? (translated === `status.${normalized}` ? normalized.replaceAll('_', ' ') : translated);

    return (
        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset ${classes}`}>
            {text}
        </span>
    );
}
