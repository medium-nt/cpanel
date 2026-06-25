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

    public $isMovementToWorkshop;

    protected $rules = [
        'selectedMaterialId' => 'required|exists:materials,id',
        'orderedQuantity' => 'nullable|numeric|min:0|max:maxQuantity',
    ];

    /** Инициализирует компонент формой material: загружает материалы, устанавливает параметры движения и источника. */
    public function mount(string $typeMovement = '', string $sourceType = 'warehouse', $isFirst = false, $isMovementToWorkshop = false): void
    {
        $this->materials = Material::all();
        $this->resetErrorBag();
        $this->sourceType = $sourceType;
        $this->typeMovement = $typeMovement;
        $this->isFirst = $isFirst;
        $this->isMovementToWorkshop = $isMovementToWorkshop;
    }

    /** Обновляет максимальное доступное количество материала при выборе материала из выпадающего списка. */
    public function updatedSelectedMaterialId(): void
    {
        if ($this->selectedMaterialId) {
            match ($this->sourceType) {
                'warehouse' => $this->maxQuantity = InventoryService::materialInWarehouse($this->selectedMaterialId),
                'workshop' => $this->maxQuantity = InventoryService::materialInWorkshop($this->selectedMaterialId),
                'defect' => $this->maxQuantity = InventoryService::defectMaterialInWarehouse($this->selectedMaterialId),
                'remnants' => $this->maxQuantity = InventoryService::remnantsMaterialInWarehouse($this->selectedMaterialId),
                default => $this->maxQuantity = null
            };

            if ($this->typeMovement === '7') {
                $this->maxQuantity = min(1, $this->maxQuantity);
            }
        } else {
            $this->maxQuantity = null;
        }
    }

    /** Отображает форму выбора материала и ввода количества. */
    public function render(): View
    {
        return view('livewire.material-form');
    }
}
