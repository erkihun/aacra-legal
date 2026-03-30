<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AdvisoryCategory;
use App\Models\AdvisoryRequest;
use App\Models\Attachment;
use App\Models\CaseType;
use App\Models\Comment;
use App\Models\Court;
use App\Models\Department;
use App\Models\LegalCase;
use App\Models\LegalCaseType;
use App\Models\PublicPost;
use App\Models\SystemSetting;
use App\Models\Team;
use App\Models\User;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\NullSmsGateway;
use App\Services\Sms\SmsGateway;
use App\Services\SystemSettingsService;
use App\Services\Telegram\LogTelegramGateway;
use App\Services\Telegram\NullTelegramGateway;
use App\Services\Telegram\TelegramGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SystemSettingsService::class);
        $this->app->bind(SmsGateway::class, fn () => app($this->smsGatewayClass()));
        $this->app->bind(TelegramGateway::class, fn () => app($this->telegramGatewayClass()));
    }

    public function boot(): void
    {
        $this->configureInfrastructureFallbacks();

        Vite::prefetch(concurrency: 3);
        RateLimiter::for('legal-mutations', function (Request $request) {
            $limit = $request->user()?->isSuperAdmin() ? 120 : 40;

            return Limit::perMinute($limit)->by(($request->user()?->id ?? 'guest').'|'.$request->ip());
        });

        Relation::enforceMorphMap([
            'user' => User::class,
            'department' => Department::class,
            'team' => Team::class,
            'advisory_category' => AdvisoryCategory::class,
            'court' => Court::class,
            'case_type' => CaseType::class,
            'legal_case_type' => LegalCaseType::class,
            'advisory_request' => AdvisoryRequest::class,
            'legal_case' => LegalCase::class,
            'attachment' => Attachment::class,
            'comment' => Comment::class,
            'system_setting' => SystemSetting::class,
            'public_post' => PublicPost::class,
        ]);

        $this->app->make(SystemSettingsService::class)->applyRuntimeConfiguration();
        $this->logUnsupportedGatewayDrivers();
    }

    private function smsGatewayClass(): string
    {
        return match (config('services.sms.driver', 'log')) {
            'log' => LogSmsGateway::class,
            'null' => NullSmsGateway::class,
            default => LogSmsGateway::class,
        };
    }

    private function telegramGatewayClass(): string
    {
        return match (config('services.telegram.driver', 'log')) {
            'log' => LogTelegramGateway::class,
            'null' => NullTelegramGateway::class,
            default => LogTelegramGateway::class,
        };
    }

    private function logUnsupportedGatewayDrivers(): void
    {
        $supportedDrivers = ['log', 'null'];

        foreach ([
            'sms' => config('services.sms.driver', 'log'),
            'telegram' => config('services.telegram.driver', 'log'),
        ] as $channel => $driver) {
            if (! in_array($driver, $supportedDrivers, true)) {
                Log::warning('Unsupported notification gateway driver configured. Falling back to log driver.', [
                    'channel' => $channel,
                    'driver' => $driver,
                    'supported_drivers' => $supportedDrivers,
                ]);
            }
        }
    }

    private function configureInfrastructureFallbacks(): void
    {
        $this->fallbackSessionDriver();
        $this->fallbackCacheStore();
        $this->fallbackQueueConnection();
    }

    private function fallbackSessionDriver(): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $table = (string) config('session.table', 'sessions');

        if ($this->databaseTableExists($table)) {
            return;
        }

        Config::set('session.driver', 'file');

        Log::warning('Database session driver unavailable because its table is missing. Falling back to file sessions.', [
            'table' => $table,
        ]);
    }

    private function fallbackCacheStore(): void
    {
        if (config('cache.default') !== 'database') {
            return;
        }

        $table = (string) config('cache.stores.database.table', 'cache');

        if ($this->databaseTableExists($table)) {
            return;
        }

        Config::set('cache.default', 'file');

        Log::warning('Database cache store unavailable because its table is missing. Falling back to file cache.', [
            'table' => $table,
        ]);
    }

    private function fallbackQueueConnection(): void
    {
        if (config('queue.default') !== 'database') {
            return;
        }

        $table = (string) config('queue.connections.database.table', 'jobs');

        if ($this->databaseTableExists($table)) {
            return;
        }

        Config::set('queue.default', 'sync');

        Log::warning('Database queue connection unavailable because its table is missing. Falling back to sync queue execution.', [
            'table' => $table,
        ]);
    }

    private function databaseTableExists(string $table): bool
    {
        if ($table === '') {
            return false;
        }

        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
