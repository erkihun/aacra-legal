type TimelineItem = {
    id: string;
    title: string;
    body?: string | null;
    meta?: string | null;
};

type TimelineProps = {
    items: TimelineItem[];
};

export default function Timeline({ items }: TimelineProps) {
    return (
        <div className="space-y-4">
            {items.map((item, index) => (
                <div key={item.id} className="relative ps-8">
                    {index < items.length - 1 ? (
                        <span
                            className="absolute left-[0.92rem] top-8 h-[calc(100%+0.5rem)] w-px"
                            style={{ backgroundColor: 'var(--border-strong)' }}
                        />
                    ) : null}
                    <span className="absolute left-0 top-1 flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--primary-soft)] text-[color:var(--primary)]">
                        <svg viewBox="0 0 20 20" className="h-4 w-4" fill="currentColor">
                            <circle cx="10" cy="10" r="4" />
                        </svg>
                    </span>
                    <div className="surface-muted p-5">
                        <div className="flex items-center justify-between gap-4">
                            <p className="font-semibold text-[color:var(--text)]">{item.title}</p>
                            {item.meta ? (
                                <span className="text-xs uppercase text-[color:var(--muted)]">
                                    {item.meta}
                                </span>
                            ) : null}
                        </div>
                        {item.body ? (
                            <p className="mt-3 text-sm leading-6 text-[color:var(--muted-strong)]">
                                {item.body}
                            </p>
                        ) : null}
                    </div>
                </div>
            ))}
        </div>
    );
}
