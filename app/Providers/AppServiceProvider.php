<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

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

        $this->registerSlowQueryLogger();

        // Бейдж на пункте «Поддержка»: для админа — счётчик новых тикетов (danger),
        // для сотрудника — счётчик непрочитанных ответов на его тикеты (success).
        Event::listen(BuildingMenu::class, function (BuildingMenu $event): void {
            $user = auth()->user();

            if (! $user || ! $event->menu->itemKeyExists('support')) {
                return;
            }

            if ($user->isAdmin()) {
                $count = Ticket::query()->where('status', Ticket::STATUS_NEW)->count();
                $labelColor = 'danger';
            } else {
                $count = Ticket::query()->unreadAnswers($user)->count();
                $labelColor = 'success';
            }

            if ($count <= 0) {
                return;
            }

            // Пересоздаём пункт с бейджем, сохраняя позицию перед «Просмотр логов».
            $event->menu->remove('support');
            $event->menu->addBefore('logs', [
                'key' => 'support',
                'text' => 'support',
                'url' => 'megatulle/tickets',
                'icon' => 'fas fa-fw fa-bug',
                'label' => $count,
                'label_color' => $labelColor,
            ]);
        });

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

        Gate::define('is-storekeeper-admin-manager', function (User $user) {
            return $user->isStorekeeper() || $user->isAdmin() || $user->isManager();
        });

        Gate::define('is-storekeeper-admin-driver', function (User $user) {
            return $user->isStorekeeper() || $user->isAdmin() || $user->isDriver();
        });

        Gate::define('is-storekeeper-admin-driver-manager', function (User $user) {
            return $user->isStorekeeper() || $user->isAdmin() || $user->isDriver() || $user->isManager();
        });

        Gate::define('is-seamstress-or-admin', function (User $user) {
            return $user->isSeamstress() || $user->isAdmin();
        });

        Gate::define('is-admin-storekeeper-seamstress-cutter', function (User $user) {
            return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter();
        });

        Gate::define('is-admin-storekeeper-seamstress-cutter-otk', function (User $user) {
            return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter() || $user->isOtk();
        });

        Gate::define('is-admin-storekeeper-seamstress-cutter-manager-otk', function (User $user) {
            return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter() || $user->isManager() || $user->isOtk();
        });

        Gate::define('is-admin-storekeeper-seamstress-cutter-otk-manager', function (User $user) {
            return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter() || $user->isOtk() || $user->isManager();
        });

        Gate::define('is-admin-storekeeper-seamstress-cutter-driver', function (User $user) {
            return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter() || $user->isDriver() || $user->isManager();
        });

        Gate::define('is-admin-storekeeper-seamstress-cutter-driver-otk', function (User $user) {
            return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter() || $user->isDriver() || $user->isManager() || $user->isOtk();
        });

        Gate::define('viewLogViewer', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('is-show-finance', function (User $user) {
            return $user->is_show_finance;
        });

        Gate::define('is-manager', function (User $user) {
            return $user->isManager();
        });

        Gate::define('is-admin-or-manager', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });
    }

    /**
     * Регистрирует слушатель медленных SQL-запросов (DB::listen).
     *
     * Пишет в канал slow-query-db записи с SQL, bindings, временем,
     * URL и user_id для каждого запроса длительнее threshold_ms.
     * Полностью пропускается, если выключатель SLOW_QUERY_DB_LOG=false.
     */
    private function registerSlowQueryLogger(): void
    {
        if (! config('slow-query-db.enabled')) {
            return;
        }

        $threshold = (float) config('slow-query-db.threshold_ms', 500);
        $bindingsLimit = (int) config('slow-query-db.bindings_limit', 20);

        DB::listen(function (QueryExecuted $query) use ($threshold, $bindingsLimit): void {
            if ($query->time < $threshold) {
                return;
            }

            Log::channel('slow-query-db')->warning('Slow query', [
                'sql' => $query->sql,
                'bindings' => array_slice($query->bindings, 0, $bindingsLimit),
                'time_ms' => round($query->time, 2),
                'connection' => $query->connectionName,
                'url' => request()?->path(),
                'user_id' => Auth::id(),
            ]);
        });
    }
}
