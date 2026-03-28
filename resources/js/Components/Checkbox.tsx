import { InputHTMLAttributes } from 'react';

export default function Checkbox({
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'h-4 w-4 rounded border-[color:var(--border-strong)] text-[var(--primary)] shadow-sm focus:ring-[var(--primary)] ' +
                className
            }
        />
    );
}
