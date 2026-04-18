<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AdvisoryAssigned;
use App\Events\CaseAssigned;
use App\Listeners\NotificationDeliverySubscriber;
use App\Listeners\SendAdvisoryAssignedNotifications;
use App\Listeners\SendCaseAssignedNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AdvisoryAssigned::class => [
            SendAdvisoryAssignedNotifications::class,
        ],
        CaseAssigned::class => [
            SendCaseAssignedNotifications::class,
        ],
    ];

    protected $subscribe = [
        NotificationDeliverySubscriber::class,
    ];
}
