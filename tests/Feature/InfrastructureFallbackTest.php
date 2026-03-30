<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->withoutVite();
});

it('falls back from database-backed infrastructure when required tables are missing', function (): void {
    config([
        'session.driver' => 'database',
        'session.table' => 'sessions',
        'cache.default' => 'database',
        'cache.stores.database.table' => 'cache',
        'queue.default' => 'database',
        'queue.connections.database.table' => 'jobs',
    ]);

    Schema::drop('sessions');
    Schema::drop('cache');
    Schema::drop('jobs');

    (new AppServiceProvider(app()))->boot();

    expect(config('session.driver'))->toBe('file')
        ->and(config('cache.default'))->toBe('file')
        ->and(config('queue.default'))->toBe('sync');
});
