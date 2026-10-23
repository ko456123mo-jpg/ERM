<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin bypass & dynamic permission checks via Gate
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
            if ($user->hasPermission($ability)) {
                return true;
            }
        });
    }
}
