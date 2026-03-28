import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

export function useI18n() {
    const { props } = usePage<PageProps>();

    const t = (key: string) => props.translations?.[key] ?? key;

    return {
        locale: props.locale,
        t,
    };
}
