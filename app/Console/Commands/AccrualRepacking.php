<?php

namespace App\Console\Commands;

use App\Services\ActionAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrualRepacking extends Command
{
    protected $signature = 'accrual:repacking {--test : Тестовый режим без создания транзакций}';

    protected $description = 'Начисление за перепаковку (repacking)';

    public function handle(ActionAccrualService $service): int
    {
        $date = Carbon::yesterday();
        $test = $this->option('test');

        $this->info("Начисление за перепаковку за {$date->format('d.m.Y')}".($test ? ' [ТЕСТ]' : ''));

        $service->accrualForAction('repacking', $date, $test);

        $this->info('Начисление завершено.');

        return self::SUCCESS;
    }
}
