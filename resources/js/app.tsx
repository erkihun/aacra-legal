import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ComponentType, PropsWithChildren, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { extractFirstErrorMessage } from './lib/form-submission';
import { initializeNavigationHistory, syncNavigationHistory } from './lib/navigation-history';
import { ToastBatchSync, ToastMessage, ToastProvider, useToast } from './lib/toast';
import { initializeTheme } from './lib/theme';

type AppMetaPayload = {
    application_name?: string;
    application_short_name?: string;
    organization_name?: string | null;
    legal_department_name?: string | null;
    tagline?: string | null;
    favicon_url?: string | null;
    stamp_url?: string | null;
    appearance?: {
        default_theme?: 'light' | 'dark';
        allow_user_theme_switching?: boolean;
        table_density?: string;
        primary_color?: string;
        secondary_color?: string;
        accent_color?: string;
        button_style?: 'pill' | 'rounded' | 'square';
        card_radius?: 'soft' | 'rounded' | 'square';
    };
};

type SharedPageProps = {
    locale?: string;
    appMeta?: AppMetaPayload;
    csrf_token?: string;
    status?: string;
    flash?: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
};

function syncCsrfToken(token?: unknown) {
    if (typeof document === 'undefined' || typeof token !== 'string' || token.length === 0) {
        return;
    }

    let meta = document.querySelector("meta[name='csrf-token']") as HTMLMetaElement | null;

    if (!meta) {
        meta = document.createElement('meta');
        meta.name = 'csrf-token';
        document.head.appendChild(meta);
    }

    meta.content = token;

    if (window.axios) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
}

const fallbackAppName =
    (typeof document !== 'undefined' ? document.body?.dataset.appName : undefined) || import.meta.env.VITE_APP_NAME || 'Legal Department Management System';

let currentAppName = fallbackAppName;

initializeTheme();

function syncDocumentLocale(locale?: unknown) {
    const normalizedLocale = typeof locale === 'string' && locale.length > 0 ? locale : 'en';

    document.documentElement.lang = normalizedLocale;
    document.documentElement.dataset.locale = normalizedLocale;
}

function syncFavicon(faviconUrl?: string | null) {
    if (typeof document === 'undefined') {
        return;
    }

    let link = document.querySelector("link[rel='icon']") as HTMLLinkElement | null;

    if (!faviconUrl) {
        link?.remove();

        return;
    }

    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }

    link.type = 'image/png';
    link.href = faviconUrl;
}

function syncAppMeta(appMeta?: AppMetaPayload) {
    if (typeof document === 'undefined' || !appMeta) {
        return;
    }

    currentAppName = appMeta.application_name || currentAppName;
    document.body.dataset.appName = currentAppName;
    document.body.dataset.defaultTheme = appMeta.appearance?.default_theme ?? 'light';
    document.body.dataset.themeSwitchingEnabled = appMeta.appearance?.allow_user_theme_switching === false ? 'false' : 'true';
    document.body.dataset.tableDensity = appMeta.appearance?.table_density ?? 'comfortable';
    document.body.dataset.buttonStyle = appMeta.appearance?.button_style ?? 'pill';
    document.body.dataset.cardRadius = appMeta.appearance?.card_radius ?? 'soft';
    document.body.dataset.applicationShortName = appMeta.application_short_name ?? 'LDMS';
    document.body.dataset.organizationName = appMeta.organization_name ?? '';
    document.body.dataset.legalDepartmentName = appMeta.legal_department_name ?? '';
    document.body.dataset.appTagline = appMeta.tagline ?? '';

    const rootStyle = document.documentElement.style;
    rootStyle.setProperty('--primary', appMeta.appearance?.primary_color ?? '#0f766e');
    rootStyle.setProperty('--secondary', appMeta.appearance?.secondary_color ?? '#0ea5e9');
    rootStyle.setProperty('--accent', appMeta.appearance?.accent_color ?? '#f59e0b');

    syncFavicon(appMeta.favicon_url ?? null);
    initializeTheme();
}

function extractToastMessages(pageProps?: SharedPageProps): ToastMessage[] {
    if (!pageProps) {
        return [];
    }

    const messages: ToastMessage[] = [];

    if (pageProps.flash?.success) {
        messages.push({ variant: 'success', message: pageProps.flash.success });
    }

    if (pageProps.flash?.error) {
        messages.push({ variant: 'error', message: pageProps.flash.error });
    }

    if (pageProps.flash?.warning) {
        messages.push({ variant: 'warning', message: pageProps.flash.warning });
    }

    if (pageProps.flash?.info) {
        messages.push({ variant: 'info', message: pageProps.flash.info });
    }

    if (pageProps.status) {
        messages.push({
            variant: 'success',
            message: pageProps.status === 'verification-link-sent' ? 'Verification Link Sent' : pageProps.status,
        });
    }

    return messages;
}

function AppShell({
    App,
    appProps,
    initialPageProps,
}: PropsWithChildren<{
    App: ComponentType<any>;
    appProps: any;
    initialPageProps: SharedPageProps;
}>) {
    const [toastBatch, setToastBatch] = useState(() => ({
        key: 1,
        messages: extractToastMessages(initialPageProps),
    }));

    useEffect(() => {
        const removeListener = router.on('success', (event) => {
            const pageProps = event.detail.page.props as SharedPageProps;

            syncNavigationHistory(event.detail.page.url);
            syncDocumentLocale(pageProps.locale);
            syncAppMeta(pageProps.appMeta);
            syncCsrfToken(pageProps.csrf_token);

            setToastBatch((current) => ({
                key: current.key + 1,
                messages: extractToastMessages(pageProps),
            }));
        });

        return () => {
            removeListener();
        };
    }, []);

    return (
        <ToastProvider>
            <RouterToastSync />
            <ToastBatchSync batchKey={toastBatch.key} messages={toastBatch.messages} />
            <App {...appProps} />
        </ToastProvider>
    );
}

function RouterToastSync() {
    const { pushToast } = useToast();

    useEffect(() => {
        const removeListener = router.on('error', (event: any) => {
            const message = extractFirstErrorMessage(event?.detail?.errors);

            if (!message) {
                return;
            }

            pushToast({
                variant: 'error',
                message,
            });
        });

        return () => {
            removeListener();
        };
    }, [pushToast]);

    return null;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${currentAppName}` : currentAppName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const initialProps = props.initialPage.props as SharedPageProps;

        syncDocumentLocale(initialProps.locale);
        syncAppMeta(initialProps.appMeta);
        syncCsrfToken(initialProps.csrf_token);
        initializeNavigationHistory();

        const root = createRoot(el);

        root.render(<AppShell App={App} appProps={props} initialPageProps={initialProps} />);
    },
    progress: {
        color: '#4B5563',
    },
});
