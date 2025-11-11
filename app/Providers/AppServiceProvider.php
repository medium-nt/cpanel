<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
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
        Paginator::useBootstrapFive();
        Paginator::useBootstrapFour();

        Gate::define('is-admin', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('is-storekeeper', function (User $user) {
            return $user->isStorekeeper();
        });

        Gate::define('is-seamstress', function (User $user) {
            return $user->isSeamstress();
        });

        Gate::define('is-storekeeper-or-admin', function (User $user) {
            return $user->isStorekeeper() || $user->isAdmin();
        });

        Gate::define('is-seamstress-or-admin', function (User $user) {
            return $user->isSeamstress() || $user->isAdmin();
        });

        Gate::define('is-admin-storekeeper-seamstress-cutter', function (User $user) {
            return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter();
        });

        Gate::define('viewLogViewer', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('is-show-finance', function (User $user) {
            return $user->is_show_finance;
        });
    }
}
