<?php

namespace App\Console\Commands;

use App\Services\ActionAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrualCutting extends Command
{
    protected $signature = 'accrual:cutting {--test : Тестовый режим без создания транзакций}';

    protected $description = 'Начисление за раскрой (cutting)';

    public function handle(ActionAccrualService $service): int
    {
        $date = Carbon::yesterday();
        $test = $this->option('test');

        $this->info("Начисление за раскрой за {$date->format('d.m.Y')}".($test ? ' [ТЕСТ]' : ''));

        $service->accrualForAction('cutting', $date, $test);

        $this->info('Начисление завершено.');

        return self::SUCCESS;
    }
}
