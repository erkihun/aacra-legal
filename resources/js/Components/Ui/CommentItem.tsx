export default function CommentItem({
    author,
    body,
    date,
}: {
    author?: string | null;
    body: string;
    date?: string | null;
}) {
    return (
        <article className="surface-muted px-4 py-4">
            <div className="flex items-center justify-between gap-3">
                <p className="font-medium text-[color:var(--text)]">{author}</p>
                {date ? (
                    <p className="text-xs uppercase text-[color:var(--muted)]">{date}</p>
                ) : null}
            </div>
            <p className="mt-3 text-sm leading-6 text-[color:var(--muted-strong)]">{body}</p>
        </article>
    );
}
