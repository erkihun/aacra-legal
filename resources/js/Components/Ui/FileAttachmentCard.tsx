import { useI18n } from '@/lib/i18n';

type FileAttachmentCardProps = {
    name: string;
    meta?: string | null;
    viewUrl?: string | null;
    downloadUrl?: string | null;
    canDelete?: boolean;
    deleting?: boolean;
    onDelete?: () => void;
};

export default function FileAttachmentCard({
    name,
    meta,
    viewUrl,
    downloadUrl,
    canDelete = false,
    deleting = false,
    onDelete,
}: FileAttachmentCardProps) {
    const { t } = useI18n();

    return (
        <div className="surface-muted flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0">
                <p className="truncate font-medium text-[color:var(--text)]">{name}</p>
                {meta ? <p className="mt-1 text-sm text-[color:var(--muted)]">{meta}</p> : null}
            </div>

            <div className="flex flex-wrap gap-2">
                {viewUrl ? (
                    <a
                        href={viewUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="btn-base btn-secondary focus-ring"
                    >
                        {t('common.view')}
                    </a>
                ) : null}

                {downloadUrl ? (
                    <a href={downloadUrl} className="btn-base btn-secondary focus-ring">
                        {t('common.download')}
                    </a>
                ) : null}

                {canDelete && onDelete ? (
                    <button
                        type="button"
                        onClick={onDelete}
                        disabled={deleting}
                        className="btn-base bg-rose-500/10 text-rose-600 hover:bg-rose-500/15 focus-ring dark:text-rose-300"
                    >
                        {t('common.delete')}
                    </button>
                ) : null}
            </div>
        </div>
    );
}
