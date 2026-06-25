<?php

namespace App\Console\Commands;

use App\Services\AutoOrderService;
use Illuminate\Console\Command;

class AutoOrderMaterials extends Command
{
    protected $signature = 'auto-order:materials';

    protected $description = 'Автоматический заказ материалов при падении остатка в цехе ниже порога';

    /** Проверяет остатки материалов в цехе и создаёт автозаказы при падении ниже порога. */
    public function handle(): int
    {
        $this->info('Проверка остатков материалов в цехе...');

        $createdOrderIds = AutoOrderService::checkAndCreateAutoOrders();

        if (empty($createdOrderIds)) {
            $this->info('Автозаказы не требуются.');
        } else {
            $this->info('Создано автозаказов: '.count($createdOrderIds).' (ID: '.implode(', ', $createdOrderIds).')');
        }

        return self::SUCCESS;
    }
}
