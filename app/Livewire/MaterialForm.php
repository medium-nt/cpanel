<?php

namespace App\Livewire;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Services\MovementMaterialService;
use Illuminate\View\View;
use Livewire\Component;

class MaterialForm extends Component
{
    public $selectedMaterialId = '';
    public $materials;
    public $orderedQuantity;
    public $maxQuantity;

    protected $rules = [
        'selectedMaterialId' => 'required|exists:materials,id',
        'orderedQuantity' => 'nullable|numeric|min:0|max:maxQuantity',
    ];

    public function mount(): void
    {
        $this->materials = Material::all();
        $this->resetErrorBag();
    }

    public function updatedSelectedMaterialId(): void
    {
        if ($this->selectedMaterialId) {
            $inStock = MovementMaterialService::countMaterial($this->selectedMaterialId, 1, 3);
            $outStockNew = MovementMaterialService::countMaterial($this->selectedMaterialId, 2, 0);
            $outStock = MovementMaterial::query()
                ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
                ->where('material_id', $this->selectedMaterialId)
                ->where('orders.type_movement', 2)
                ->whereNotIn('orders.status_movement', [-1])
                ->sum('quantity');

            $this->maxQuantity = $inStock - $outStock - $outStockNew;
        } else {
            $this->maxQuantity = null;
        }
    }

    public function render(): View
    {
        return view('livewire.material-form');
    }
}
