import { ReactNode, useState } from 'react';
import { cn } from '@/lib/cn';

export type TabItem = {
    key: string;
    label: string;
    content: ReactNode;
};

export default function Tabs({
    items,
    defaultTab,
}: {
    items: TabItem[];
    defaultTab?: string;
}) {
    const [activeTab, setActiveTab] = useState(defaultTab ?? items[0]?.key);

    const activeItem = items.find((item) => item.key === activeTab) ?? items[0];

    return (
        <div className="space-y-5">
            <div className="surface-muted flex flex-wrap gap-2 p-1.5" role="tablist">
                {items.map((item) => {
                    const active = item.key === activeItem?.key;

                    return (
                        <button
                            key={item.key}
                            type="button"
                            onClick={() => setActiveTab(item.key)}
                            role="tab"
                            aria-selected={active}
                            aria-controls={`tab-panel-${item.key}`}
                            id={`tab-${item.key}`}
                            className={cn(
                                'focus-ring rounded-full px-4 py-2.5 text-sm font-semibold transition',
                                active
                                    ? 'bg-[var(--surface-strong)] text-[color:var(--text)] shadow-sm'
                                    : 'text-[color:var(--muted-strong)] hover:bg-[color:var(--surface-strong)]',
                            )}
                        >
                            {item.label}
                        </button>
                    );
                })}
            </div>

            <div
                role="tabpanel"
                id={`tab-panel-${activeItem?.key ?? 'panel'}`}
                aria-labelledby={`tab-${activeItem?.key ?? 'tab'}`}
            >
                {activeItem?.content}
            </div>
        </div>
    );
}
