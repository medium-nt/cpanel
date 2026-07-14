<?php

namespace App\Services\RatingBoard;

use App\Models\Schedule;
use App\Models\Setting;
use App\Models\ShiftSchedule;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Бизнес-логика мотивационной доски рейтинга сотрудников.
 *
 * Собирает данные из существующих таблиц (без новых миграций):
 * лидеры за сегодня, подиум топ-3, счётчики FBO/FBS на стикеровке,
 * статистика за месяц, победитель прошлой смены, активная смена.
 */
class RatingBoardDataService
{
    /** Роли для таблицы лидеров и подиума: только швеи (1). */
    private const RATING_ROLE_IDS = [1];

    /** Все роли для статистики: швеи, закройщики, ОТК. */
    private const STATISTICS_ROLE_IDS = [1, 4, 5];

    /** Статус заказа «Стикеровка» (для счётчиков FBO/FBS). */
    private const STATUS_STICKERING = 5;

    /** Метрика по роли: [role_id => [fk-колонка сотрудника, колонка даты выполнения]]. */
    private const ROLE_METRIC_MAP = [
        1 => ['seamstress_id', 'completed_at'],
        4 => ['cutter_id', 'cutting_completed_at'],
        5 => ['otk_id', 'packed_at'],
    ];

    /**
     * Собирает полный набор данных для отдачи на доску (JSON-контракт).
     *
     * @return array<string, mixed>
     */
    public function getData(int $workshopId): array
    {
        $leaders = $this->getLeaders($workshopId);

        return [
            'shift' => $this->getShift($workshopId),
            'leaders' => $leaders,
            'podium' => $this->getPodium($leaders),
            'stickers' => $this->getStickers($workshopId),
            'statistics' => $this->getStatistics($workshopId),
            'winner' => $this->getWinner($workshopId),
            'timers' => $this->getTimers($workshopId),
        ];
    }

    /**
     * Лидеры: кол-во выполненных заказов за сегодня (только швеи).
     *
     * @return list<array{id:int,name:string,profession:string,avatar:string,position:int,medal:string|null,count:int,shift_done:bool}>
     */
    public function getLeaders(int $workshopId): array
    {
        $counts = $this->countCompletedByDate($workshopId, Carbon::today(), Carbon::today());

        return $this->buildLeaders($counts);
    }

    /**
     * Подиум топ-3 из списка лидеров: gold/silver/bronze.
     *
     * @param  list<array<string,mixed>>  $leaders
     * @return array{gold:?array, silver:?array, bronze:?array}
     */
    public function getPodium(array $leaders): array
    {
        $podium = ['gold' => null, 'silver' => null, 'bronze' => null];
        $keys = ['gold', 'silver', 'bronze'];

        foreach (array_slice($leaders, 0, 3) as $i => $leader) {
            $key = $keys[$i];
            $podium[$key] = [
                'id' => $leader['id'],
                'name' => $leader['name'],
                'avatar' => $leader['avatar'],
                'text' => $key === 'gold' ? 'Высшая лига портновского искусства.' : null,
            ];
        }

        return $podium;
    }

    /**
     * Стикеры: кол-во заказов цеха на статусе «Стикеровка», разбито по FBO/FBS.
     *
     * @return array{fbo:int, fbs:int}
     */
    public function getStickers(int $workshopId): array
    {
        $rows = DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->where('marketplace_order_items.workshop_id', $workshopId)
            ->where('marketplace_order_items.status', self::STATUS_STICKERING)
            ->selectRaw('marketplace_orders.fulfillment_type as type, COUNT(*) as cnt')
            ->groupBy('marketplace_orders.fulfillment_type')
            ->pluck('cnt', 'type');

        return [
            'fbo' => (int) ($rows['FBO'] ?? 0),
            'fbs' => (int) ($rows['FBS'] ?? 0),
        ];
    }

    /**
     * Статистика за текущий месяц: записи по дням для всех ролей (швеи/закройщики/ОТК).
     * Каждая строка = один день работы одного сотрудника. Сортировка по дате ASC (с первого числа месяца).
     *
     * @return list<array{name:string,profession:string,avatar:string,value:string,shift:string,medal:string|null}>
     */
    public function getStatistics(int $workshopId): array
    {
        $from = Carbon::now()->startOfMonth()->toDateString().' 00:00:00';
        $to = Carbon::yesterday()->toDateString().' 23:59:59';

        $records = [];
        foreach (self::ROLE_METRIC_MAP as [$fk, $dateCol]) {
            $rows = DB::table('marketplace_order_items')
                ->where('workshop_id', $workshopId)
                ->whereNotNull($fk)
                ->whereBetween($dateCol, [$from, $to])
                ->selectRaw("{$fk} as user_id, DATE({$dateCol}) as work_date, COUNT(*) as cnt")
                ->groupBy($fk, DB::raw("DATE({$dateCol})"))
                ->get();

            foreach ($rows as $row) {
                $records[] = [
                    'user_id' => $row->user_id,
                    'work_date' => $row->work_date,
                    'count' => $row->cnt,
                ];
            }
        }

        if (empty($records)) {
            return [];
        }

        // Загружаем пользователей
        $userIds = collect($records)->pluck('user_id')->unique();
        $users = User::query()
            ->whereIn('id', $userIds)
            ->whereIn('role_id', self::STATISTICS_ROLE_IDS)
            ->with('role')
            ->get()
            ->keyBy('id');

        // Сортируем по дате ASC (с первого числа месяца)
        usort($records, fn ($a, $b) => strcmp($a['work_date'], $b['work_date']));

        // Находим рекорд дня для каждой даты (только среди швей)
        $dailyMax = [];
        foreach ($records as $record) {
            $user = $users->get($record['user_id']);
            if ($user && $user->role_id == 1) { // Только швеи
                $date = $record['work_date'];
                if (! isset($dailyMax[$date]) || $record['count'] > $dailyMax[$date]) {
                    $dailyMax[$date] = $record['count'];
                }
            }
        }

        // Мапа смен по индивидуальному расписанию: "user_id|date" => shift.name
        $fromDate = Carbon::now()->startOfMonth()->toDateString();
        $toDate = Carbon::yesterday()->toDateString();
        $shiftMap = [];
        Schedule::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('date', [$fromDate, $toDate])
            ->with('shift')
            ->get()
            ->each(function (Schedule $schedule) use (&$shiftMap) {
                $shiftMap[$schedule->user_id.'|'.$schedule->date] = $schedule->shift?->name;
            });

        $result = [];
        foreach ($records as $record) {
            $user = $users->get($record['user_id']);
            if (! $user) {
                continue;
            }

            // Медаль только швеям за рекорд дня
            $medal = null;
            if ($user->role_id == 1 && $record['count'] === ($dailyMax[$record['work_date']] ?? null)) {
                $medal = 'gold';
            }

            $result[] = [
                'name' => $user->name,
                'profession' => UserService::translateRoleName($user->role?->name),
                'avatar' => $user->adminlte_image(),
                'value' => Carbon::parse($record['work_date'])->format('d.m.y').' выполнено '.$record['count'].' заказ(ов)!',
                'shift' => $shiftMap[$record['user_id'].'|'.$record['work_date']] ?? '',
                'medal' => $medal,
            ];
        }

        return $result;
    }

    /**
     * Победитель за последние 2 смены: топ-швея по лучшему дневному результату (MAX, не сумма).
     *
     * Берёт 2 последние рабочие смены цеха из shift_schedule, считает по каждой швее
     * кол-во выполненных заказов за каждый из этих дней и выбирает швею с максимальным
     * дневным результатом. Если прошлых смен нет — возвращает пустой результат.
     *
     * @return array{name:string|null,avatar:string|null,orders_count:int,date:string|null,description:string|null}
     */
    public function getWinner(int $workshopId): array
    {
        $dates = $this->previousWorkDates($workshopId, 2);

        if ($dates->isEmpty()) {
            return ['name' => null, 'avatar' => null, 'orders_count' => 0, 'date' => null, 'description' => null];
        }

        $counts = $this->maxDailyCountByDates($workshopId, $dates);
        $leaders = $this->buildLeaders($counts);
        $top = $leaders[0] ?? null;

        return [
            'name' => $top['name'] ?? null,
            'avatar' => $top['avatar'] ?? null,
            'orders_count' => $top['count'] ?? 0,
            'date' => $top['date'] ?? null,
            'description' => $top ? 'Мастерство, покоряющее сердца.' : null,
        ];
    }

    /**
     * Активная и предыдущая смены цеха (по календарю shift_schedule).
     *
     * @return array{name:string|null, previous_name:string|null}
     */
    public function getShift(int $workshopId): array
    {
        return [
            'name' => $this->shiftNameAt($workshopId, Carbon::today()),
            'previous_name' => $this->previousShiftName($workshopId),
        ];
    }

    /**
     * Таймеры до открытия/закрытия смены по настройкам цеха working_day_start/working_day_end.
     *
     * До начала смены — morning_seconds_left, в рабочее время — evening_seconds_left (до конца),
     * после закрытия — оба 0 (до следующего дня).
     *
     * @return array{morning_seconds_left:int, evening_seconds_left:int}
     */
    public function getTimers(int $workshopId): array
    {
        $now = Carbon::now();
        $morning = 0;
        $evening = 0;

        $startStr = $this->settingFor($workshopId, 'working_day_start');
        $endStr = $this->settingFor($workshopId, 'working_day_end');

        if ($startStr && $endStr) {
            $start = Carbon::today()->setTimeFromTimeString($startStr);
            $end = Carbon::today()->setTimeFromTimeString($endStr);

            if ($now < $start) {
                $morning = max(0, (int) $now->diffInSeconds($start));
            } elseif ($now < $end) {
                $evening = max(0, (int) $now->diffInSeconds($end));
            }
        }

        return [
            'morning_seconds_left' => $morning,
            'evening_seconds_left' => $evening,
        ];
    }

    /**
     * Значение настройки цеха по имени (приоритет — per-workshop, fallback на глобальную workshop_id=null).
     */
    protected function settingFor(int $workshopId, string $name): ?string
    {
        return Setting::query()
            ->where('name', $name)
            ->where('workshop_id', $workshopId)
            ->value('value')
            ?? Setting::query()
                ->where('name', $name)
                ->whereNull('workshop_id')
                ->value('value');
    }

    /**
     * Кол-во выполненных заказов по сотрудникам за период [start, end] (по ролям).
     *
     * @return Collection<int, object{user_id:int, count:int}>
     */
    protected function countCompletedByDate(int $workshopId, Carbon $start, Carbon $end): Collection
    {
        $from = $start->toDateString().' 00:00:00';
        $to = $end->toDateString().' 23:59:59';

        $queries = [];
        foreach (self::ROLE_METRIC_MAP as [$fk, $dateCol]) {
            $queries[] = DB::table('marketplace_order_items')
                ->where('workshop_id', $workshopId)
                ->whereNotNull($fk)
                ->whereBetween($dateCol, [$from, $to])
                ->selectRaw($fk.' as user_id, COUNT(*) as cnt')
                ->groupBy($fk);
        }

        // Динамический UNION всех запросов (для одной роли — один запрос, для нескольких — unionAll)
        $rows = array_reduce($queries, function ($carry, $query) {
            return $carry ? $carry->unionAll($query) : $query;
        })->get();

        return $rows->groupBy('user_id')
            ->map(fn (Collection $g) => (object) [
                'user_id' => (int) $g->first()->user_id,
                'count' => (int) $g->sum('cnt'),
            ])
            ->values();
    }

    /**
     * Максимальный дневной результат каждого сотрудника за указанные даты (по ролям).
     *
     * В отличие от countCompletedByDate (сумма за период), здесь для каждого user_id
     * берётся максимум по отдельным дням — нужно для выбора победителя по лучшему дню,
     * а не по сумме за несколько смен.
     *
     * @param  Collection<int, string>  $dates  даты Y-m-d
     * @return Collection<int, object{user_id:int, count:int}>
     */
    protected function maxDailyCountByDates(int $workshopId, Collection $dates): Collection
    {
        if ($dates->isEmpty()) {
            return collect();
        }

        $dayList = $dates->values()->all();

        $queries = [];
        foreach (self::ROLE_METRIC_MAP as [$fk, $dateCol]) {
            $queries[] = DB::table('marketplace_order_items')
                ->where('workshop_id', $workshopId)
                ->whereNotNull($fk)
                ->whereIn(DB::raw("DATE({$dateCol})"), $dayList)
                ->selectRaw($fk.' as user_id, DATE('.$dateCol.') as work_date, COUNT(*) as cnt')
                ->groupBy($fk, DB::raw("DATE({$dateCol})"));
        }

        $rows = array_reduce($queries, function ($carry, $query) {
            return $carry ? $carry->unionAll($query) : $query;
        })->get();

        return $rows->groupBy('user_id')
            ->map(function (Collection $g) {
                $best = $g->sortByDesc('cnt')->first();

                return (object) [
                    'user_id' => (int) $g->first()->user_id,
                    'count' => (int) $best->cnt,
                    'date' => $best->work_date, // Y-m-d дня с максимальным count
                ];
            })
            ->values();
    }

    /**
     * Преобразует сырые счётчики в список лидеров с позициями и медалями.
     *
     * @param  Collection<int, object{user_id:int, count:int}>  $counts
     * @return list<array{id:int,name:string,profession:string,avatar:string,position:int,medal:string|null,count:int,shift_done:bool}>
     */
    protected function buildLeaders(Collection $counts): array
    {
        if ($counts->isEmpty()) {
            return [];
        }

        $users = User::query()
            ->whereIn('id', $counts->pluck('user_id'))
            ->whereIn('role_id', self::RATING_ROLE_IDS)
            ->with('role')
            ->get()
            ->keyBy('id');

        // Позиция считается только по реально добавленным швеям:
        // строки не-швей (закройщики/ОТК) пропускаются через continue
        // и не должны сдвигать нумерацию лидеров.
        $leaders = [];
        $added = 0;
        foreach ($counts->sortByDesc('count')->values() as $row) {
            if ($added >= 9) {
                break;
            } // Максимум 9 лидеров
            $user = $users->get($row->user_id);
            if (! $user) {
                continue;
            }
            $added++;
            $position = $added;
            $leaders[] = [
                'id' => $user->id,
                'name' => $user->name,
                'profession' => UserService::translateRoleName($user->role?->name),
                'avatar' => $user->adminlte_image(),
                'position' => $position,
                'medal' => $this->medalForPosition($position),
                'count' => $row->count,
                'date' => $row->date ?? null,
            ];
        }

        $this->applyShiftDone($leaders);

        return $leaders;
    }

    /**
     * Проставляет лидерам признак shift_done = true тем, кто сегодня открыл И закрыл смену.
     *
     * Источник — таблица schedules (персистентна по дате, в отличие от users.shift_is_open,
     * который обнуляется в 00:01). Признак: shift_opened_time и shift_closed_time оба != '00:00:00'.
     *
     * @param  list<array{id:int,...}>  $leaders  изменяется по ссылке
     */
    protected function applyShiftDone(array &$leaders): void
    {
        if (empty($leaders)) {
            return;
        }

        $closedIds = Schedule::query()
            ->whereIn('user_id', array_column($leaders, 'id'))
            ->where('date', Carbon::today()->toDateString())
            ->where('shift_opened_time', '!=', '00:00:00')
            ->where('shift_closed_time', '!=', '00:00:00')
            ->pluck('user_id')
            ->all();

        foreach ($leaders as $i => $leader) {
            $leaders[$i]['shift_done'] = in_array($leader['id'], $closedIds, true);
        }
    }

    /**
     * Медаль по позиции (1=gold, 2=silver, 3=bronze, иначе null).
     */
    protected function medalForPosition(int $position): ?string
    {
        return match ($position) {
            1 => 'gold',
            2 => 'silver',
            3 => 'bronze',
            default => null,
        };
    }

    /**
     * Название смены цеха на указанную дату (null = выходной/нет расписания).
     */
    protected function shiftNameAt(int $workshopId, Carbon $date): ?string
    {
        $record = ShiftSchedule::query()
            ->where('workshop_id', $workshopId)
            ->where('date', $date->toDateString())
            ->first();

        return $record?->shift?->name;
    }

    /**
     * Название предыдущей рабочей смены цеха (shift_id не null, дата раньше сегодня).
     */
    protected function previousShiftName(int $workshopId): ?string
    {
        return $this->shiftRecordBefore($workshopId)?->shift?->name;
    }

    /**
     * Даты N последних рабочих смен цеха (shift_id не null, дата раньше сегодня).
     *
     * @return Collection<int, string>
     */
    protected function previousWorkDates(int $workshopId, int $limit = 2): Collection
    {
        return ShiftSchedule::query()
            ->where('workshop_id', $workshopId)
            ->where('date', '<', Carbon::today()->toDateString())
            ->whereNotNull('shift_id')
            ->orderByDesc('date')
            ->limit($limit)
            ->pluck('date');
    }

    /**
     * Последняя запись shift_schedule с рабочей сменой (shift_id не null) до сегодня.
     */
    protected function shiftRecordBefore(int $workshopId): ?ShiftSchedule
    {
        return ShiftSchedule::query()
            ->where('workshop_id', $workshopId)
            ->where('date', '<', Carbon::today()->toDateString())
            ->whereNotNull('shift_id')
            ->orderByDesc('date')
            ->first();
    }
}
