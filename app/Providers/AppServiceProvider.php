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
            return $user->role->name === 'admin';
        });

        Gate::define('is-storekeeper', function (User $user) {
            return $user->role->name === 'storekeeper';
        });

        Gate::define('is-seamstress', function (User $user) {
            return $user->role->name === 'seamstress';
        });

        Gate::define('is-storekeeper-or-admin', function (User $user) {
            return $user->role->name === 'storekeeper' || $user->role->name === 'admin';
        });

        Gate::define('is-seamstress-or-admin', function (User $user) {
            return $user->role->name === 'seamstress' || $user->role->name === 'admin';
        });

        Gate::define('viewLogViewer', function ($user) {
            return $user->role->name === 'admin';
        });
    }
}
