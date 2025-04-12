<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;

class InventoryController extends Controller
{
    public function byWarehouse()
    {
        return view('inventory.warehouse', [
            'title' => 'Материал на складе',
            'materials' => InventoryService::materialsQuantityBy('warehouse'),
        ]);
    }

    public function byWorkshop()
    {
        return view('inventory.workshop', [
            'title' => 'Материал на производстве',
            'materials' => InventoryService::materialsQuantityBy('workhouse'),
        ]);
    }
}
