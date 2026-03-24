<?php

namespace App\Console\Commands;

use App\Services\ActionAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AccrualScanning extends Command
{
    protected $signature = 'accrual:scanning {--test : Тестовый режим без создания транзакций}';

    protected $description = 'Начисление за сканирование (scanning)';

    public function handle(ActionAccrualService $service): int
    {
        $date = Carbon::yesterday();
        $test = $this->option('test');

        $this->info("Начисление за сканирование за {$date->format('d.m.Y')}".($test ? ' [ТЕСТ]' : ''));

        // TODO: Определить логику для сканирования
        // Возможно нужно учитывать создание записей в других таблицах
        // или специальные события

        $this->warn('Логика для сканирования не реализована. Требуется уточнение требований.');

        return self::SUCCESS;
    }
}
