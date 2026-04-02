export interface User {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    locale?: string;
    roles: string[];
    permissions: string[];
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
    locale: string;
    availableLocales: Array<{
        value: string;
        label: string;
    }>;
    csrf_token: string;
    notificationSummary: {
        unread_count: number;
    };
    appMeta: {
        application_name: string;
        application_short_name: string;
        organization_name?: string | null;
        legal_department_name?: string | null;
        tagline?: string | null;
        default_dashboard_route: string;
        logo_url?: string | null;
        favicon_url?: string | null;
        stamp_url?: string | null;
        support: {
            email?: string | null;
            phone?: string | null;
        };
        footer_text?: string | null;
        organization_description?: string | null;
        organization: {
            name?: string | null;
            office_name?: string | null;
            legal_department_name?: string | null;
            tagline?: string | null;
            address?: string | null;
            contact_email?: string | null;
            contact_phone?: string | null;
            working_hours_text?: string | null;
            description?: string | null;
            footer_text?: string | null;
        };
        localization: {
            default_locale: string;
            supported_locales: string[];
            fallback_locale: string;
            timezone: string;
            date_format: string;
            datetime_format: string;
        };
        security: {
            allowed_file_types: string[];
            max_upload_size_mb: number;
            password_min_length: number;
            password_complexity_enabled: boolean;
            session_timeout_minutes: number;
            maintenance_banner_enabled: boolean;
        };
        appearance: {
            default_theme: 'light' | 'dark';
            allow_user_theme_switching: boolean;
            sidebar_compact_default: boolean;
            table_density: string;
            primary_color: string;
            secondary_color: string;
            accent_color: string;
            button_style: 'pill' | 'rounded' | 'square';
            card_radius: 'soft' | 'rounded' | 'square';
        };
    };
    translations: Record<string, string>;
};
