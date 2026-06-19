<?php

namespace App\Providers;

use App\Modules\Auth\Events\PendingUserRegistered;
use App\Modules\Auth\Listeners\SendTelegramAdminApproval;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PendingUserRegistered::class => [
            SendTelegramAdminApproval::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
