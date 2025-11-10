<?php

namespace Tests\Unit\Services;

use App\Models\MarketplaceOrderItem;
use App\Services\WarehouseOfItemService;
use PHPUnit\Framework\TestCase;

class WarehouseOfItemServiceUnitTest extends TestCase
{
    /** Возвращает уже существующий штрихкод без БД */
    public function test_return_existing_barcode_without_db(): void
    {
        $item = new MarketplaceOrderItem(); // без сохранения в БД
        $item->storage_barcode = 'EXISTING-123';

        $svc = new WarehouseOfItemService();

        $barcode = $svc->getStorageBarcode($item);

        $this->assertSame('EXISTING-123', $barcode);
    }
}
