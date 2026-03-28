import Modal from '@/Components/Modal';
import { useI18n } from '@/lib/i18n';

type ConfirmationDialogProps = {
    open: boolean;
    title: string;
    description: string;
    confirmLabel: string;
    onCancel: () => void;
    onConfirm: () => void;
    processing?: boolean;
};

export default function ConfirmationDialog({
    open,
    title,
    description,
    confirmLabel,
    onCancel,
    onConfirm,
    processing = false,
}: ConfirmationDialogProps) {
    const { t } = useI18n();

    return (
        <Modal show={open} onClose={onCancel}>
            <div className="space-y-6 p-6 text-[color:var(--text)]">
                <div>
                    <h3 className="text-xl font-semibold">{title}</h3>
                    <p className="mt-2 text-sm text-[color:var(--muted-strong)]">{description}</p>
                </div>

                <div className="flex justify-end gap-3">
                    <button
                        type="button"
                        onClick={onCancel}
                        className="btn-base btn-secondary"
                    >
                        {t('common.cancel')}
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                        className="btn-base btn-primary"
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </Modal>
    );
}
