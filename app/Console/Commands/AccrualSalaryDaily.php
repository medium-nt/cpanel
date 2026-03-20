<?php

namespace App\Console\Commands;

use App\Services\ActionAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrualSalaryDaily extends Command
{
    protected $signature = 'accrual:salary-daily {--test : Тестовый режим без создания транзакций}';

    protected $description = 'Начисление дневного оклада (salary_daily)';

    public function handle(ActionAccrualService $service): int
    {
        $date = Carbon::yesterday();
        $test = $this->option('test');

        $this->info("Начисление оклада за {$date->format('d.m.Y')}".($test ? ' [ТЕСТ]' : ''));

        $service->accrualSalaryDaily($date, $test);

        $this->info('Начисление завершено.');

        return self::SUCCESS;
    }
}
