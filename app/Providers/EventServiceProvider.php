<?php

namespace App\Providers;

use App\Events\PendingUserRegistered;
use App\Listeners\SendTelegramAdminApproval;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PendingUserRegistered::class => [
            SendTelegramAdminApproval::class,
        ],
    ];
}
