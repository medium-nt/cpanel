<?php

namespace App\Console\Commands;

use App\Services\ActionAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrualSticking extends Command
{
    protected $signature = 'accrual:sticking {--test : Тестовый режим без создания транзакций}';

    protected $description = 'Начисление за стикеровку (sticking)';

    public function handle(ActionAccrualService $service): int
    {
        $date = Carbon::yesterday();
        $test = $this->option('test');

        $this->info("Начисление за стикеровку за {$date->format('d.m.Y')}".($test ? ' [ТЕСТ]' : ''));

        $service->accrualForAction('sticking', $date, $test);

        $this->info('Начисление завершено.');

        return self::SUCCESS;
    }
}
