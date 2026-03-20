<?php

namespace App\Console\Commands;

use App\Services\ActionAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrualSewing extends Command
{
    protected $signature = 'accrual:sewing {--test : Тестовый режим без создания транзакций}';

    protected $description = 'Начисление за пошив (sewing)';

    public function handle(ActionAccrualService $service): int
    {
        $date = Carbon::yesterday();
        $test = $this->option('test');

        $this->info("Начисление за пошив за {$date->format('d.m.Y')}".($test ? ' [ТЕСТ]' : ''));

        $service->accrualForAction('sewing', $date, $test);

        $this->info('Начисление завершено.');

        return self::SUCCESS;
    }
}
