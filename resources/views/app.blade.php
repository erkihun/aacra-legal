<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php($appMeta = app(\App\Services\SystemSettingsService::class)->appMeta())
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ $appMeta['application_name'] ?? __('app.name') }}</title>
        @if($appMeta['favicon_url'] ?? null)
            <link rel="icon" type="image/png" href="{{ $appMeta['favicon_url'] }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body
        class="font-sans antialiased"
        data-app-name="{{ $appMeta['application_name'] ?? __('app.name') }}"
        data-default-theme="{{ $appMeta['appearance']['default_theme'] ?? 'light' }}"
        data-theme-switching-enabled="{{ ($appMeta['appearance']['allow_user_theme_switching'] ?? true) ? 'true' : 'false' }}"
        data-table-density="{{ $appMeta['appearance']['table_density'] ?? 'comfortable' }}"
        data-button-style="{{ $appMeta['appearance']['button_style'] ?? 'pill' }}"
        data-card-radius="{{ $appMeta['appearance']['card_radius'] ?? 'soft' }}"
        data-application-short-name="{{ $appMeta['application_short_name'] ?? 'LDMS' }}"
        data-organization-name="{{ $appMeta['organization_name'] ?? __('app.name') }}"
        data-legal-department-name="{{ $appMeta['legal_department_name'] ?? '' }}"
        data-app-tagline="{{ $appMeta['tagline'] ?? '' }}"
        style="
            --primary: {{ $appMeta['appearance']['primary_color'] ?? '#0f766e' }};
            --secondary: {{ $appMeta['appearance']['secondary_color'] ?? '#0ea5e9' }};
            --accent: {{ $appMeta['appearance']['accent_color'] ?? '#f59e0b' }};
        "
    >
        @inertia
    </body>
</html>
