<?php

namespace App\Livewire;

use App\Models\Material;
use App\Services\InventoryService;
use Illuminate\View\View;
use Livewire\Component;

class MaterialForm extends Component
{
    public $selectedMaterialId = '';
    public $materials;
    public $orderedQuantity;
    public $maxQuantity;
    public $sourceType;
    public $typeMovement;
    public $isFirst;

    protected $rules = [
        'selectedMaterialId' => 'required|exists:materials,id',
        'orderedQuantity' => 'nullable|numeric|min:0|max:maxQuantity',
    ];

    public function mount(string $typeMovement = '', string $sourceType = 'warehouse', $isFirst = false): void
    {
        $this->materials = Material::all();
        $this->resetErrorBag();
        $this->sourceType = $sourceType;
        $this->typeMovement = $typeMovement;
        $this->isFirst = $isFirst;
    }

    public function updatedSelectedMaterialId(): void
    {
        if ($this->selectedMaterialId) {
            match ($this->sourceType)
            {
                'warehouse' => $this->maxQuantity = InventoryService::materialInWarehouse($this->selectedMaterialId),
                'workshop' => $this->maxQuantity = InventoryService::materialInWorkshop($this->selectedMaterialId),
                'defect' => $this->maxQuantity = InventoryService::defectMaterialInWarehouse($this->selectedMaterialId),
                'remnants' => $this->maxQuantity = InventoryService::remnantsMaterialInWarehouse($this->selectedMaterialId),
            };

            if ($this->typeMovement === '7') {
                $this->maxQuantity = min(1, $this->maxQuantity);
            }
        } else {
            $this->maxQuantity = null;
        }
    }

    public function render(): View
    {
        return view('livewire.material-form');
    }
}
