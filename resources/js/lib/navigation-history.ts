const STORAGE_KEY = 'aclcms.navigation.history.v1';
const MAX_ENTRIES = 24;

type NavigationHistoryState = {
    entries: string[];
    index: number;
};

function normalizeUrl(url: string) {
    if (typeof window === 'undefined') {
        return url;
    }

    const normalizedUrl = new URL(url, window.location.origin);

    return `${normalizedUrl.pathname}${normalizedUrl.search}${normalizedUrl.hash}`;
}

function readState(): NavigationHistoryState | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const rawValue = window.sessionStorage.getItem(STORAGE_KEY);

        if (!rawValue) {
            return null;
        }

        const parsedValue = JSON.parse(rawValue) as NavigationHistoryState;

        if (!Array.isArray(parsedValue.entries) || typeof parsedValue.index !== 'number') {
            return null;
        }

        if (parsedValue.entries.length === 0 || parsedValue.index < 0 || parsedValue.index >= parsedValue.entries.length) {
            return null;
        }

        return {
            entries: parsedValue.entries.map((entry) => normalizeUrl(String(entry))),
            index: parsedValue.index,
        };
    } catch {
        return null;
    }
}

function writeState(state: NavigationHistoryState) {
    if (typeof window === 'undefined') {
        return;
    }

    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

function currentBrowserUrl() {
    return normalizeUrl(window.location.href);
}

export function initializeNavigationHistory() {
    if (typeof window === 'undefined') {
        return;
    }

    const currentUrl = currentBrowserUrl();
    const existingState = readState();

    if (existingState && existingState.entries[existingState.index] === currentUrl) {
        writeState(existingState);

        return;
    }

    writeState({
        entries: [currentUrl],
        index: 0,
    });
}

export function syncNavigationHistory(nextUrl: string) {
    if (typeof window === 'undefined') {
        return;
    }

    const normalizedNextUrl = normalizeUrl(nextUrl);
    const existingState = readState();

    if (!existingState) {
        writeState({
            entries: [normalizedNextUrl],
            index: 0,
        });

        return;
    }

    const currentEntry = existingState.entries[existingState.index];

    if (normalizedNextUrl === currentEntry) {
        return;
    }

    if (existingState.index > 0 && existingState.entries[existingState.index - 1] === normalizedNextUrl) {
        writeState({
            entries: existingState.entries,
            index: existingState.index - 1,
        });

        return;
    }

    if (existingState.index + 1 < existingState.entries.length && existingState.entries[existingState.index + 1] === normalizedNextUrl) {
        writeState({
            entries: existingState.entries,
            index: existingState.index + 1,
        });

        return;
    }

    const nextEntries = [...existingState.entries.slice(0, existingState.index + 1), normalizedNextUrl];
    const trimmedEntries = nextEntries.slice(-MAX_ENTRIES);

    writeState({
        entries: trimmedEntries,
        index: trimmedEntries.length - 1,
    });
}

export function hasBackHistory() {
    if (typeof window === 'undefined') {
        return false;
    }

    const state = readState();

    if (!state) {
        return false;
    }

    return state.entries[state.index] === currentBrowserUrl() && state.index > 0;
}
