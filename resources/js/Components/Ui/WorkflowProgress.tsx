type WorkflowProgressProps = {
    stages: Array<{ label: string; active: boolean; complete: boolean }>;
};

export default function WorkflowProgress({ stages }: WorkflowProgressProps) {
    return (
        <div className="section-shell">
            <div className="grid gap-3 md:grid-cols-4">
                {stages.map((stage, index) => (
                    <div key={`${stage.label}-${index}`} className="surface-muted relative overflow-hidden px-4 py-4">
                        <div
                            className="absolute inset-x-0 top-0 h-1"
                            style={{
                                backgroundColor: stage.complete
                                    ? '#10b981'
                                    : stage.active
                                      ? 'var(--primary)'
                                      : 'rgba(148, 163, 184, 0.35)',
                            }}
                        />
                        <div
                            className={`mb-3 h-2 rounded-full ${
                                stage.complete
                                    ? 'bg-emerald-400'
                                    : stage.active
                                      ? 'bg-[var(--primary)]'
                                      : 'bg-slate-300 dark:bg-slate-700'
                            }`}
                        />
                        <p className="text-sm font-semibold text-[color:var(--text)]">{stage.label}</p>
                    </div>
                ))}
            </div>
        </div>
    );
}
