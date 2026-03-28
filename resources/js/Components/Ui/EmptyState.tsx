import { ReactNode } from 'react';

type EmptyStateProps = {
    title: string;
    description: string;
    action?: ReactNode;
};

export default function EmptyState({ title, description, action }: EmptyStateProps) {
    return (
        <div className="section-shell border-dashed px-6 py-12 text-center">
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[color:var(--primary-soft)] text-[color:var(--primary)]">
                <svg viewBox="0 0 24 24" className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="1.8">
                    <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-9Z" />
                    <path d="M8.5 10h7M8.5 14h4" />
                </svg>
            </div>
            <p className="mt-5 text-lg font-semibold text-[color:var(--text)]">{title}</p>
            <p className="mx-auto mt-2 max-w-xl text-sm leading-6 text-[color:var(--muted-strong)]">{description}</p>
            {action ? <div className="mt-5">{action}</div> : null}
        </div>
    );
}
