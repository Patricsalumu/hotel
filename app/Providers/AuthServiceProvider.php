<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Policies\ClientPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\HotelPolicy;
use App\Policies\ReservationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Hotel::class => HotelPolicy::class,
        Reservation::class => ReservationPolicy::class,
        Client::class => ClientPolicy::class,
        Expense::class => ExpensePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
