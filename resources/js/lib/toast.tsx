import { createContext, PropsWithChildren, ReactNode, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';

export type ToastVariant = 'success' | 'error' | 'warning' | 'info';

export type ToastMessage = {
    variant: ToastVariant;
    message: string;
};

type ToastItem = ToastMessage & {
    id: number;
};

type ToastContextValue = {
    pushToast: (toast: ToastMessage) => void;
    dismissToast: (id: number) => void;
};

const ToastContext = createContext<ToastContextValue | null>(null);

const timeoutByVariant: Record<ToastVariant, number> = {
    success: 4200,
    error: 6200,
    warning: 5200,
    info: 4200,
};

const toastStyles: Record<ToastVariant, { frame: string; iconFrame: string; icon: ReactNode }> = {
    success: {
        frame: 'border-emerald-700/90 bg-emerald-600 text-white shadow-[0_22px_52px_-30px_rgba(5,150,105,0.75)] dark:border-emerald-500 dark:bg-emerald-500 dark:text-white',
        iconFrame: 'bg-white/16 ring-1 ring-inset ring-white/18',
        icon: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
                <path d="m5 12 4 4L19 6" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        ),
    },
    error: {
        frame: 'border-rose-700/90 bg-rose-600 text-white shadow-[0_22px_52px_-30px_rgba(225,29,72,0.75)] dark:border-rose-500 dark:bg-rose-500 dark:text-white',
        iconFrame: 'bg-white/16 ring-1 ring-inset ring-white/18',
        icon: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
                <path d="M12 8v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72-3L13.71 3.86a2 2 0 0 0-3.42 0Z" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        ),
    },
    warning: {
        frame: 'border-amber-400/30 bg-amber-500/12 text-amber-950 dark:text-amber-100',
        iconFrame: 'bg-amber-500/14 ring-1 ring-inset ring-amber-400/25',
        icon: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
                <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72-3L13.71 3.86a2 2 0 0 0-3.42 0Z" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        ),
    },
    info: {
        frame: 'border-sky-400/30 bg-sky-500/12 text-sky-950 dark:text-sky-100',
        iconFrame: 'bg-sky-500/14 ring-1 ring-inset ring-sky-400/25',
        icon: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
                <path d="M12 16v-4m0-4h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        ),
    },
};

export function ToastProvider({ children }: PropsWithChildren) {
    const nextIdRef = useRef(1);
    const timeoutMapRef = useRef<Map<number, number>>(new Map());
    const [toasts, setToasts] = useState<ToastItem[]>([]);

    const dismissToast = useCallback((id: number) => {
        const timeoutId = timeoutMapRef.current.get(id);

        if (timeoutId !== undefined) {
            window.clearTimeout(timeoutId);
            timeoutMapRef.current.delete(id);
        }

        setToasts((current) => current.filter((toast) => toast.id !== id));
    }, []);

    const pushToast = useCallback((toast: ToastMessage) => {
        const message = toast.message.trim();

        if (message === '') {
            return;
        }

        const id = nextIdRef.current++;

        setToasts((current) => [...current, { ...toast, message, id }]);

        const timeoutId = window.setTimeout(() => {
            dismissToast(id);
        }, timeoutByVariant[toast.variant]);

        timeoutMapRef.current.set(id, timeoutId);
    }, [dismissToast]);

    useEffect(() => () => {
        timeoutMapRef.current.forEach((timeoutId) => window.clearTimeout(timeoutId));
        timeoutMapRef.current.clear();
    }, []);

    const value = useMemo<ToastContextValue>(() => ({
        pushToast,
        dismissToast,
    }), [dismissToast, pushToast]);

    return (
        <ToastContext.Provider value={value}>
            {children}
            <ToastViewport toasts={toasts} onDismiss={dismissToast} />
        </ToastContext.Provider>
    );
}

export function ToastBatchSync({ batchKey, messages }: { batchKey: number; messages: ToastMessage[] }) {
    const { pushToast } = useToast();
    const lastBatchRef = useRef<number | null>(null);

    useEffect(() => {
        if (lastBatchRef.current === batchKey || messages.length === 0) {
            return;
        }

        lastBatchRef.current = batchKey;
        messages.forEach((message) => pushToast(message));
    }, [batchKey, messages, pushToast]);

    return null;
}

export function useToast() {
    const context = useContext(ToastContext);

    if (context === null) {
        throw new Error('useToast must be used within a ToastProvider.');
    }

    return context;
}

function ToastViewport({ toasts, onDismiss }: { toasts: ToastItem[]; onDismiss: (id: number) => void }) {
    return (
        <div className="pointer-events-none fixed inset-x-0 top-4 z-[120] flex justify-center px-4 sm:justify-end sm:px-6">
            <div className="flex w-full max-w-md flex-col gap-3">
                {toasts.map((toast) => {
                    const style = toastStyles[toast.variant];

                    return (
                        <div
                            key={toast.id}
                            className={`pointer-events-auto surface-card-strong flex items-start gap-3 border px-4 py-3 shadow-[0_18px_48px_-28px_rgba(15,23,42,0.45)] transition ${style.frame}`}
                            role="status"
                            aria-live={toast.variant === 'error' ? 'assertive' : 'polite'}
                        >
                            <span className={`mt-0.5 shrink-0 rounded-full p-2 ${style.iconFrame}`}>{style.icon}</span>
                            <p className="flex-1 text-sm font-medium leading-6">{toast.message}</p>
                            <button
                                type="button"
                                onClick={() => onDismiss(toast.id)}
                                className="focus-ring rounded-full p-1 text-current/70 transition hover:bg-black/5 hover:text-current dark:hover:bg-white/10"
                                aria-label="Dismiss notification"
                            >
                                <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.8">
                                    <path d="m6 6 12 12M18 6 6 18" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            </button>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
