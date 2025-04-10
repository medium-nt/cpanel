<?php

namespace App\Http\Controllers;

use App\Models\MaterialConsumption;

class MaterialConsumptionController extends Controller
{

    public function destroy(MaterialConsumption $materialConsumption)
    {
        $materialConsumption->delete();

        return redirect()->route('marketplace_items.edit', ['marketplace_item' => $materialConsumption->item->id]);
    }
}
