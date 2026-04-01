<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateSystemSettingsGroupAction;
use App\Enums\LocaleCode;
use App\Enums\SystemSettingGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingsRequest;
use App\Models\SystemSetting;
use App\Services\SystemSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingsController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SystemSetting::class);

        $allSettings = $this->settings->all();
        $allSettings[SystemSettingGroup::GENERAL->value]['system_logo_url'] = $this->settings->appMeta()['logo_url'];
        $allSettings[SystemSettingGroup::GENERAL->value]['favicon_url'] = $this->settings->appMeta()['favicon_url'];
        $allSettings[SystemSettingGroup::TELEGRAM->value] = $this->settings->telegramSettingsForSettings();
        $allSettings[SystemSettingGroup::PUBLIC_WEBSITE->value]['hero_slides'] = $this->settings->publicWebsiteSlidesForSettings();

        return Inertia::render('Admin/SystemSettings/Index', [
            'settingsGroups' => $allSettings,
            'groups' => collect(SystemSettingGroup::cases())->map(fn (SystemSettingGroup $group): array => [
                'key' => $group->value,
                'label' => __($group->labelKey()),
                'update_route' => route('settings.update', $group->value),
            ])->values(),
            'locales' => collect(LocaleCode::cases())->map(fn (LocaleCode $locale): array => [
                'value' => $locale->value,
                'label' => $locale->label(),
            ])->values(),
            'timezones' => collect(timezone_identifiers_list())->values(),
            'dashboardRoutes' => [
                ['value' => 'dashboard', 'label' => __('navigation.dashboard')],
                ['value' => 'reports.index', 'label' => __('navigation.reports')],
                ['value' => 'notifications.index', 'label' => __('navigation.notifications')],
            ],
            'themeOptions' => [
                ['value' => 'light', 'label' => __('common.light')],
                ['value' => 'dark', 'label' => __('common.dark')],
            ],
            'buttonStyleOptions' => [
                ['value' => 'pill', 'label' => __('settings.options.button_style_pill')],
                ['value' => 'rounded', 'label' => __('settings.options.button_style_rounded')],
                ['value' => 'square', 'label' => __('settings.options.button_style_square')],
            ],
            'cardRadiusOptions' => [
                ['value' => 'soft', 'label' => __('settings.options.card_radius_soft')],
                ['value' => 'rounded', 'label' => __('settings.options.card_radius_rounded')],
                ['value' => 'square', 'label' => __('settings.options.card_radius_square')],
            ],
            'tableDensityOptions' => [
                ['value' => 'comfortable', 'label' => __('settings.options.table_density_comfortable')],
                ['value' => 'compact', 'label' => __('settings.options.table_density_compact')],
            ],
        ]);
    }

    public function update(
        UpdateSystemSettingsRequest $request,
        string $group,
        UpdateSystemSettingsGroupAction $action,
    ): RedirectResponse {
        $this->authorize('update', SystemSetting::class);

        $groupKey = SystemSettingGroup::from($group);
        $updated = $action->execute($groupKey, $request->validated(), $request->user());

        if ($groupKey === SystemSettingGroup::PUBLIC_WEBSITE && ! $this->heroSlideUploadsPersisted($request, $updated)) {
            return back()->withErrors([
                'hero_slides' => __('The uploaded hero slide image could not be saved.'),
            ]);
        }

        return back()->with('success', __('Settings updated successfully.'));
    }

    /**
     * @param  array<string, mixed>  $updated
     */
    private function heroSlideUploadsPersisted(UpdateSystemSettingsRequest $request, array $updated): bool
    {
        $uploadedSlides = data_get($request->allFiles(), 'hero_slides', []);

        if (! is_array($uploadedSlides) || $uploadedSlides === []) {
            return true;
        }

        $persistedSlides = [];
        $persistedHeroSlidesSetting = SystemSetting::query()
            ->where('setting_group', SystemSettingGroup::PUBLIC_WEBSITE->value)
            ->where('setting_key', 'hero_slides')
            ->first();

        if ($persistedHeroSlidesSetting !== null && is_array($persistedHeroSlidesSetting->value)) {
            $persistedSlides = $persistedHeroSlidesSetting->value;
        }

        foreach ($uploadedSlides as $index => $slide) {
            if (! is_array($slide)) {
                continue;
            }

            $image = $slide['image'] ?? null;

            if (! $image instanceof UploadedFile) {
                continue;
            }

            $savedPath = $persistedSlides[$index]['image_path']
                ?? $updated['hero_slides'][$index]['image_path']
                ?? null;

            if (! is_string($savedPath) || $savedPath === '' || ! Storage::disk('public')->exists($savedPath)) {
                return false;
            }
        }

        return true;
    }
}
