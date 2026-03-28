import { ReactNode } from 'react';

type MetricCardProps = {
    label: string;
    value: number | string;
    hint?: string;
    icon?: ReactNode;
};

export default function MetricCard({ label, value, hint, icon }: MetricCardProps) {
    return (
        <div className="section-shell relative overflow-hidden">
            <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[var(--primary)] via-transparent to-transparent" />
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-semibold uppercase text-[color:var(--muted)]">
                        {label}
                    </p>
                    <p className="mt-4 text-4xl font-semibold text-[color:var(--text)]">{value}</p>
                    {hint ? <p className="mt-2 text-sm text-[color:var(--muted-strong)]">{hint}</p> : null}
                </div>
                {icon ? (
                    <div className="surface-muted flex h-11 w-11 items-center justify-center text-[color:var(--primary)]">
                        {icon}
                    </div>
                ) : null}
            </div>
        </div>
    );
}
