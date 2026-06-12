<?php

namespace App\Livewire;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Services\TgService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class WorkshopRollScan extends Component
{
    public Order $order;

    public string $scanCode = '';

    public int $requestedMaterialId;

    public string $requestedMaterialTitle;

    public ?string $message = null;

    public string $messageType = 'success';

    protected $rules = [
        'scanCode' => 'required|string|min:1',
    ];

    /**
     * Загрузить заказ и извлечь запрошенный материал из placeholder MovementMaterial.
     */
    public function mount(Order $order): void
    {
        $this->order = $order->load('movementMaterials.material', 'movementMaterials.roll');

        $placeholder = $this->order->movementMaterials->first();
        $this->requestedMaterialId = $placeholder->material_id;
        $this->requestedMaterialTitle = $placeholder->material->title;
    }

    /**
     * Сканировать рулон и добавить к заказу.
     */
    public function scanRoll(): void
    {
        $this->validate();

        $code = trim($this->scanCode);
        $this->scanCode = '';

        $roll = Roll::where('roll_code', $code)->first();

        if (! $roll) {
            $this->setMessage('Рулон не найден.', 'error');
            $this->dispatch('scanError');

            return;
        }

        if ($roll->status !== Roll::STATUS_IN_STORAGE) {
            $this->setMessage('Рулон не находится на складе.', 'error');
            $this->dispatch('scanError');

            return;
        }

        if ($roll->material_id !== $this->requestedMaterialId) {
            $this->setMessage('Рулон принадлежит другому материалу.', 'error');
            $this->dispatch('scanError');

            return;
        }

        // Ограничение: для упаковочных материалов — только 1 рулон в поставке
        if ($roll->material->type_id === Material::TYPE_PACKAGING) {
            $alreadyScanned = $this->order->movementMaterials()
                ->whereNotNull('roll_id')
                ->exists();

            if ($alreadyScanned) {
                $this->setMessage('Для упаковочных материалов можно добавить только 1 рулон в поставку.', 'error');
                $this->dispatch('scanError');

                return;
            }
        }

        $alreadyAdded = $this->order->movementMaterials()
            ->where('roll_id', $roll->id)
            ->exists();

        if ($alreadyAdded) {
            $this->setMessage('Рулон уже добавлен.', 'error');
            $this->dispatch('scanError');

            return;
        }

        $activeOrder = $roll->movementMaterial?->order;
        if ($activeOrder && $activeOrder->status != 3) {
            $this->setMessage('Поставка с этим рулоном еще не принята.', 'error');
            $this->dispatch('scanError');

            return;
        }

        try {
            $placeholder = $this->order->movementMaterials()
                ->whereNull('roll_id')
                ->first();

            if ($placeholder) {
                $placeholder->update([
                    'roll_id' => $roll->id,
                    'quantity' => $roll->initial_quantity,
                ]);
            } else {
                MovementMaterial::query()->create([
                    'order_id' => $this->order->id,
                    'material_id' => $this->requestedMaterialId,
                    'roll_id' => $roll->id,
                    'quantity' => $roll->initial_quantity,
                    'ordered_quantity' => 0,
                ]);
            }

            $this->order->refresh();
            $this->setMessage('Рулон добавлен!', 'success');
            $this->dispatch('scanSuccess');
        } catch (Throwable $e) {
            Log::channel('materials')->error('Ошибка добавления рулона: '.$e->getMessage());
            $this->setMessage('Ошибка при добавлении рулона.', 'error');
            $this->dispatch('scanError');
        }
    }

    /**
     * Удалить отсканированный рулон из заказа.
     */
    public function removeRoll(int $movementMaterialId): void
    {
        $movementMaterial = MovementMaterial::query()->find($movementMaterialId);

        if (! $movementMaterial || $movementMaterial->order_id !== $this->order->id) {
            $this->setMessage('Запись не найдена.', 'error');
            $this->dispatch('scanError');

            return;
        }

        try {
            $scannedCount = $this->order->movementMaterials()
                ->whereNotNull('roll_id')
                ->count();

            if ($scannedCount <= 1) {
                $movementMaterial->update([
                    'roll_id' => null,
                    'quantity' => 0,
                ]);
            } else {
                $movementMaterial->delete();
            }

            $this->order->refresh();
            $this->setMessage('Рулон удалён.', 'success');
            $this->dispatch('scanSuccess');
        } catch (Throwable $e) {
            Log::channel('materials')->error('Ошибка удаления рулона: '.$e->getMessage());
            $this->setMessage('Ошибка при удалении рулона.', 'error');
            $this->dispatch('scanError');
        }
    }

    /**
     * Подтвердить отгрузку — перевести заказ в статус "Отправлено".
     */
    public function confirmShipment(): void
    {
        // TODO: N+1 — добавить eager loading ->with(['roll', 'material']) к запросу
        $scannedRolls = $this->order->movementMaterials()
            ->whereNotNull('roll_id')
            ->get();

        if ($scannedRolls->isEmpty()) {
            $this->setMessage('Добавьте хотя бы один рулон.', 'error');
            $this->dispatch('scanError');

            return;
        }

        // Защита: для упаковочных материалов — не более 1 рулона в поставке
        $material = Material::find($this->requestedMaterialId);
        if ($material && $material->type_id === Material::TYPE_PACKAGING && $scannedRolls->count() > 1) {
            $this->setMessage('Для упаковочных материалов можно отгрузить только 1 рулон за смену.', 'error');
            $this->dispatch('scanError');

            return;
        }

        try {
            DB::beginTransaction();

            $this->order->update([
                'status' => 2,
                'storekeeper_id' => auth()->user()->id,
            ]);

            $list = '';
            foreach ($scannedRolls as $movementMaterial) {
                $movementMaterial->roll->update([
                    'status' => Roll::STATUS_SHIPPED_TO_WORKSHOP,
                    'shift_id' => $this->order->shift_id,
                ]);

                $list .= '• '.$movementMaterial->material->title
                    .' '.$movementMaterial->quantity
                    .' '.$movementMaterial->material->unit
                    .' (рулон: '.$movementMaterial->roll->roll_code.')'."\n";
            }

            $text = 'Кладовщик '.auth()->user()->name.' отгрузил материал на производство: '."\n".$list;

            Log::channel('tg')
                ->notice('Отправляем сообщение в ТГ админу и работающим швеям: '.$text);

            TgService::sendMessage(config('telegram.admin_id'), $text);

            foreach (UserService::getListSeamstressesWorkingToday() as $tgId) {
                TgService::sendMessage($tgId, $text);
            }

            DB::commit();

            $this->redirectRoute('movements_to_workshop.receive', ['order' => $this->order->id], navigate: true);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::channel('materials')->error('Ошибка подтверждения отгрузки: '.$e->getMessage());
            $this->setMessage('Ошибка при подтверждении отгрузки.', 'error');
            $this->dispatch('scanError');
        }
    }

    /**
     * Сбросить сообщение.
     */
    public function resetMessage(): void
    {
        $this->message = null;
    }

    /**
     * Установить сообщение с автосбросом.
     */
    private function setMessage(string $text, string $type): void
    {
        $this->message = $text;
        $this->messageType = $type;
        $this->dispatch('clearMessage');
    }

    public function render(): View
    {
        $scannedMaterials = $this->order->movementMaterials->filter(fn ($m) => $m->roll_id !== null);

        $scannedRollsCount = $scannedMaterials->count();
        $scannedTotalQuantity = $scannedMaterials->sum('quantity');

        $scannedRollIds = $scannedMaterials->pluck('roll_id')->toArray();

        $storageRolls = Roll::where('material_id', $this->requestedMaterialId)
            ->where('status', Roll::STATUS_IN_STORAGE)
            ->whereNotIn('id', $scannedRollIds)
            ->get();
        $storageRollsCount = $storageRolls->count();
        $storageTotalQuantity = $storageRolls->sum('initial_quantity');

        return view('livewire.workshop-roll-scan', compact(
            'scannedMaterials',
            'scannedRollsCount',
            'scannedTotalQuantity',
            'storageRollsCount',
            'storageTotalQuantity',
        ));
    }
}
