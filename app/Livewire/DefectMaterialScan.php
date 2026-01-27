<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\TgService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class DefectMaterialScan extends Component
{
    /** код из сканера (например, "DEF-123") */
    public string $scanCode = '';

    /** сообщение статуса */
    public string $statusMessage = '';

    public string $statusType = 'info'; // ok|error|warn|info

    public string $statusClass = 'alert-secondary';

    /** ID отсканированных заявок для отображения в таблице */
    public array $scannedOrderIds = [];

    /** Отсканированные заявки - загружаются в render() */
    public Collection $scannedOrders;

    /** общее количество доступных для сканирования заявок */
    public int $totalAvailableOrders = 0;

    public function mount(): void
    {
        $this->scannedOrders = new Collection;
    }

    public function render(): View
    {
        $this->totalAvailableOrders = Order::query()
            ->where('type_movement', 4)
            ->where('status', 1)
            ->count();

        $this->scannedOrders = Order::with(['movementMaterials.material', 'seamstress', 'cutter'])
            ->whereIn('id', $this->scannedOrderIds)
            ->where('type_movement', 4)
            ->where('status', 1) // только одобренные
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.defect-material-scan');
    }

    public function handleScan(): void
    {
        $code = trim($this->scanCode);
        $this->scanCode = '';

        if ($code === '') {
            return;
        }

        // 1. Парсим код формата "DEF-123"
        if (! preg_match('/^DEF-(\d+)$/i', $code, $matches)) {
            $this->setStatus("Неверный формат штрихкода: $code. Ожидается формат: DEF-123", 'error');
            $this->dispatch('scanError');

            return;
        }

        $orderId = (int) $matches[1];

        // 2. Находим заявку
        $order = Order::with(['movementMaterials.material', 'seamstress', 'cutter'])
            ->where('id', $orderId)
            ->where('type_movement', 4)
            ->first();

        if (! $order) {
            $this->setStatus("Заявка #$orderId не найдена или это не брак", 'error');
            $this->dispatch('scanError');

            return;
        }

        // 3. Проверяем статус
        if ($order->status !== 1) {
            $statusName = $order->status_name;
            $this->setStatus("Заявка #$orderId имеет статус \"$statusName\". Можно сканировать только новые заявки.", 'error');
            $this->dispatch('scanError');

            return;
        }

        // 4. Проверяем, не отсканирована ли уже
        if (in_array($orderId, $this->scannedOrderIds)) {
            $this->setStatus("Заявка #$orderId уже добавлена в список", 'warn');

            return;
        }

        // 5. Добавляем в список
        $this->scannedOrderIds[] = $orderId;

        $materialTitle = $order->movementMaterials->first()->material->title;
        $quantity = $order->movementMaterials->first()->quantity;
        $this->setStatus("Добавлено: $materialTitle ($quantity)", 'ok');
        $this->dispatch('scanSuccess');
    }

    public function removeFromList(int $orderId): void
    {
        $this->scannedOrderIds = array_values(array_filter(
            $this->scannedOrderIds,
            fn ($id) => $id !== $orderId
        ));

        $this->setStatus("Заявка #$orderId удалена из списка", 'ok');
    }

    public function acceptAll(): void
    {
        if ($this->scannedOrders->isEmpty()) {
            $this->setStatus('Нет заявок для принятия', 'error');
            $this->dispatch('scanError');

            return;
        }

        try {
            DB::beginTransaction();

            $list = '';
            foreach ($this->scannedOrders as $order) {
                $order->update(['status' => 3]);

                $material = $order->movementMaterials->first();
                $list .= '• '.$material->material->title.' '.$material->quantity.' '.$material->material->unit."\n";
            }

            DB::commit();

            $text = 'Кладовщик '.auth()->user()->name.' забрал брак с производства:'."\n".$list;

            Log::channel('erp')
                ->notice('Отправляем сообщение в ТГ админу и работающим швеям: '.$text);

            TgService::sendMessage(config('telegram.admin_id'), $text);

            foreach (UserService::getListSeamstressesWorkingToday() as $tgId) {
                TgService::sendMessage($tgId, $text);
            }

            $count = $this->scannedOrders->count();
            $this->scannedOrderIds = [];
            $this->setStatus("Успешно принято заявок: $count", 'ok');
            $this->dispatch('scanSuccess');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->setStatus('Ошибка при сохранении: '.$e->getMessage(), 'error');
            $this->dispatch('scanError');
        }
    }

    protected function setStatus(string $message, string $type = 'info'): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;

        $map = [
            'ok' => 'alert-success',
            'warn' => 'alert-warning',
            'error' => 'alert-danger',
            'info' => 'alert-secondary',
        ];
        $this->statusClass = $map[$type] ?? 'alert-secondary';
    }
}
