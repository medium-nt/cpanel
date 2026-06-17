<?php

namespace App\Http\Controllers;

use App\Models\MaterialConsumption;
use Illuminate\Support\Facades\Log;

class MaterialConsumptionController extends Controller
{
    public function destroy(MaterialConsumption $materialConsumption)
    {
        Log::channel('materials')->warning('Удалён расход материала', [
            'material_consumption_id' => $materialConsumption->id,
            'item_id' => $materialConsumption->item->id,
            'deleted_by' => auth()->id(),
        ]);

        $materialConsumption->delete();

        return redirect()->route('marketplace_items.edit', ['marketplace_item' => $materialConsumption->item->id]);
    }
}
