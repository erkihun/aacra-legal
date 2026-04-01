import { router } from '@inertiajs/react';

type FormLike = any;

type SuccessBehaviorOptions = {
    preserveScroll?: boolean;
    reset?: boolean | string[];
    afterSuccess?: () => void;
    syncDefaults?: boolean;
};

export function extractFirstErrorMessage(errors?: Record<string, string | string[] | undefined>): string | null {
    if (!errors) {
        return null;
    }

    for (const value of Object.values(errors)) {
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }

        if (Array.isArray(value)) {
            const message = value.find((item) => typeof item === 'string' && item.trim() !== '');

            if (message) {
                return message;
            }
        }
    }

    return null;
}

export function finishSuccessfulSubmission(
    form: FormLike,
    {
        preserveScroll = true,
        reset = false,
        afterSuccess,
        syncDefaults = false,
    }: SuccessBehaviorOptions = {},
) {
    if (syncDefaults && typeof form.defaults === 'function' && form.data) {
        form.defaults({ ...form.data });
    }

    if (reset === true && typeof form.reset === 'function') {
        form.reset();
    }

    if (Array.isArray(reset) && reset.length > 0 && typeof form.reset === 'function') {
        form.reset(...reset);
    }

    form.clearErrors?.();
    afterSuccess?.();

    router.reload();
}
