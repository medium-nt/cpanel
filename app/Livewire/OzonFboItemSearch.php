<?php

namespace App\Livewire;

use App\Models\MarketplaceSupply;
use App\Models\MarketplaceWarehouse;
use App\Models\OzonFboDraftSupplyItem;
use App\Models\Sku;
use App\Services\MarketplaceApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class OzonFboItemSearch extends Component
{
    public MarketplaceSupply $supply;

    public $search = '';

    public $results;

    public $quantity = 1;

    public $supply_type = '';

    public $cluster_id = '';

    public $seller_warehouse_id = '';

    public $sellerWarehouses = [];

    public $draftWarehouses = [];

    public $selectedWarehouseId = '';

    public $selectedBundleId = '';

    public $macrolocalClusterId = null;

    public $draftSupplyType = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $timeslotDays = [];

    public $selectedDate = '';

    public $selectedTimeslot = '';

    public function mount($supply): void
    {
        $this->supply = $supply;
        $this->results = collect();
        $this->sellerWarehouses = MarketplaceApiService::getSellerWarehousesOzon();

        if ($supply->draft_params) {
            $this->supply_type = $supply->draft_params['supply_type'] ?? '';
            $this->cluster_id = $supply->draft_params['cluster_id'] ?? '';
            $this->seller_warehouse_id = $supply->draft_params['seller_warehouse_id'] ?? '';
        }
    }

    /**
     * Сохраняет параметры черновика при изменении типа поставки.
     */
    public function updatedSupplyType(): void
    {
        $this->saveDraftParams();
    }

    /**
     * Сохраняет параметры черновика при изменении кластера.
     */
    public function updatedClusterId(): void
    {
        $this->saveDraftParams();
    }

    /**
     * Сохраняет параметры черновика при изменении склада продавца.
     */
    public function updatedSellerWarehouseId(): void
    {
        $this->saveDraftParams();
    }

    public function updatedSearch(): void
    {
        if (mb_strlen(trim($this->search)) < 2) {
            $this->results = collect();

            return;
        }

        $search = trim($this->search);
        $title = $search;
        $width = null;
        $height = null;
        $singleSize = null;

        if (preg_match('/(\d+)\s*[хx]\s*(\d+)/u', $search, $matches)) {
            $width = (int) $matches[1];
            $height = (int) $matches[2];
            $title = trim(preg_replace('/\d+\s*[хx]\s*\d+/u', '', $search));
        } elseif (preg_match('/(\d+)/', $search, $matches)) {
            $singleSize = (int) $matches[1];
            $title = trim(preg_replace('/\d+/', '', $search));
        }

        $this->results = Sku::query()
            ->with('item')
            ->where('marketplace_id', 1)
            ->whereHas('item', function ($query) use ($title, $width, $height, $singleSize) {
                if ($title !== '') {
                    $query->where('title', 'like', '%'.$title.'%');
                }
                if ($width !== null) {
                    $query->where('width', $width);
                }
                if ($height !== null) {
                    $query->where('height', $height);
                }
                if ($singleSize !== null) {
                    $query->where(fn ($q) => $q->where('width', $singleSize)->orWhere('height', $singleSize));
                }
            })
            ->limit(20)
            ->get();
    }

    /**
     * Добавляет товар в черновик поставки.
     */
    public function addItem(int $skuId): void
    {
        $sku = Sku::query()->with('item')->find($skuId);

        if (! $sku) {
            return;
        }

        $existing = OzonFboDraftSupplyItem::query()
            ->where('supply_id', $this->supply->id)
            ->where('sku', $sku->sku)
            ->first();

        if ($existing) {
            $existing->increment('quantity', $this->quantity);
        } else {
            OzonFboDraftSupplyItem::query()->create([
                'supply_id' => $this->supply->id,
                'sku' => $sku->sku,
                'quantity' => $this->quantity,
            ]);
        }

        $this->search = '';
        $this->results = collect();
        $this->quantity = 1;
    }

    /**
     * Удаляет товар из черновика.
     */
    public function removeItem(int $itemId): void
    {
        OzonFboDraftSupplyItem::query()->where('id', $itemId)->delete();
    }

    /**
     * Обновляет количество товара в черновике.
     */
    public function updateQuantity(int $itemId, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        OzonFboDraftSupplyItem::query()->where('id', $itemId)->update(['quantity' => $quantity]);
    }

    /**
     * Создаёт черновик поставки через OZON API.
     */
    public function createDraft(): void
    {
        $this->validate([
            'supply_type' => 'required|in:crossdock,direct',
            'cluster_id' => 'required|integer',
            'seller_warehouse_id' => 'required_if:supply_type,crossdock|nullable|integer',
        ]);

        $macrolocalClusterId = (int) $this->cluster_id;
        $cluster = MarketplaceWarehouse::query()
            ->where('macrolocal_cluster_id', $macrolocalClusterId)
            ->first();

        $draftItems = OzonFboDraftSupplyItem::query()
            ->where('supply_id', $this->supply->id)
            ->get();

        if ($draftItems->isEmpty()) {
            session()->flash('error', 'Добавьте хотя бы один товар в черновик.');

            return;
        }

        $items = $draftItems->map(fn ($item) => [
            'sku' => (int) $item->sku,
            'quantity' => $item->quantity,
        ])->toArray();

        $result = $this->supply_type === 'crossdock'
            ? MarketplaceApiService::createDraftCrossdockOzon(
                $macrolocalClusterId,
                (int) $this->seller_warehouse_id,
                $items
            )
            : MarketplaceApiService::createDraftDirectOzon(
                $macrolocalClusterId,
                $items
            );

        if ($result['error']) {
            session()->flash('error', 'Ошибка OZON: '.$result['error']);

            $this->redirect(route('marketplace_supplies.show', ['marketplace_supply' => $this->supply->id]));

            return;
        }

        $draftId = $result['draft_id'];

        $this->supply->update([
            'draft_id' => $draftId,
            'cluster' => $cluster?->cluster,
            'supply_type' => $this->supply_type === 'crossdock' ? 'Кросс-докинг' : 'Прямая поставка',
            'draft_params' => null,
            'draft_created_at' => now(),
        ]);

        $this->supply->refresh();

        sleep(1);

        $draftInfo = MarketplaceApiService::getDraftInfoOzon($draftId);
        $status = $draftInfo['status'] ?? 'UNKNOWN';

        if ($status === 'FAILED') {
            $errors = collect($draftInfo['errors'] ?? [])
                ->map(fn ($e) => $e['error_message'])
                ->join(', ');

            $this->supply->update(['draft_id' => null, 'supply_id' => null]);

            session()->flash('error', 'Черновик #'.$draftId.' создан с ошибками: '.$errors.'. Исправьте параметры и отправьте повторно.');

            $this->redirect(route('marketplace_supplies.show', ['marketplace_supply' => $this->supply->id]));

            return;
        }

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' создал черновик OZON FBO #'.$draftId.' (тип: '.$this->supply_type.').');

        session()->flash('success', 'Черновик поставки #'.$draftId.' создан.');

        $this->redirect(route('marketplace_supplies.show', ['marketplace_supply' => $this->supply->id]));
    }

    /**
     * Загружает список доступных складов для черновика.
     */
    public function loadDraftWarehouses(): void
    {
        if (! $this->supply->draft_id) {
            return;
        }

        $result = MarketplaceApiService::getDraftWarehousesOzon($this->supply->draft_id);

        if ($result['error']) {
            $this->dispatch('show-error', message: $result['error']);

            return;
        }

        $this->draftWarehouses = $result['warehouses'];
    }

    /**
     * При выборе склада — сохраняет данные.
     */
    public function updatedSelectedWarehouseId(): void
    {
        $warehouse = collect($this->draftWarehouses)
            ->first(fn ($w) => $w['warehouse_id'] == $this->selectedWarehouseId);

        if (! $warehouse) {
            $this->timeslotDays = [];

            return;
        }

        $this->selectedBundleId = $warehouse['bundle_id'];
        $this->macrolocalClusterId = $warehouse['macrolocal_cluster_id'];
        $this->draftSupplyType = $warehouse['supply_type'];
        $this->selectedDate = '';
        $this->selectedTimeslot = '';
        $this->timeslotDays = [];
    }

    /**
     * Загружает таймслоты для выбранного склада и диапазона дат.
     */
    public function loadTimeslots(): void
    {
        if (! $this->selectedWarehouseId || ! $this->macrolocalClusterId || ! $this->dateFrom || ! $this->dateTo) {
            return;
        }

        $days = MarketplaceApiService::getDraftTimeslotsOzon(
            $this->supply->draft_id,
            $this->draftSupplyType,
            $this->macrolocalClusterId,
            (int) $this->selectedWarehouseId,
            $this->dateFrom,
            $this->dateTo
        );

        $dateTo = \Carbon\Carbon::parse($this->dateTo)->endOfDay();

        $this->timeslotDays = collect($days)
            ->filter(fn ($day) => \Carbon\Carbon::parse($day['date_in_timezone'])->lte($dateTo))
            ->values()
            ->toArray();

        $this->selectedDate = '';
        $this->selectedTimeslot = '';
    }

    /**
     * Отправляет заявку на создание поставки из черновика через OZON API.
     */
    public function submitDraft(): void
    {
        if (! $this->selectedWarehouseId || ! $this->selectedDate || ! $this->selectedTimeslot) {
            session()->flash('error', 'Выберите склад, дату и таймслот.');

            return;
        }

        $selectedDay = collect($this->timeslotDays)
            ->first(fn ($d) => $d['date_in_timezone'] === $this->selectedDate);

        $selectedSlot = collect($selectedDay['timeslots'] ?? [])
            ->first(fn ($s) => $s['from_in_timezone'] === $this->selectedTimeslot);

        if (! $selectedSlot) {
            session()->flash('error', 'Не удалось найти выбранный таймслот.');

            return;
        }

        $fromTime = \Carbon\Carbon::parse($selectedSlot['from_in_timezone'])->format('Y-m-d\TH:i:s');
        $toTime = \Carbon\Carbon::parse($selectedSlot['to_in_timezone'])->format('Y-m-d\TH:i:s');

        $result = MarketplaceApiService::createSupplyFromDraftOzon(
            $this->supply->draft_id,
            (int) $this->macrolocalClusterId,
            (int) $this->selectedWarehouseId,
            $fromTime,
            $toTime,
            $this->draftSupplyType
        );

        if ($result['error']) {
            session()->flash('error', 'Ошибка OZON: '.$result['error']);
            $this->redirect(route('marketplace_supplies.show', ['marketplace_supply' => $this->supply->id]));

            return;
        }

        sleep(2);

        $maxAttempts = 5;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $status = MarketplaceApiService::getSupplyCreateStatusOzon($this->supply->draft_id);

            $draftId = $this->supply->draft_id;

            if ($status['status'] === 'SUCCESS') {
                $details = MarketplaceApiService::getSupplyOrderDetailsOzon((int)$status['order_id']);
                $supply = $details['supplies'][0] ?? null;
                $timeslot = $details['timeslot']['value']['timeslot'] ?? null;
                $macrolocalClusterId = $supply['macrolocal_cluster_id'] ?? null;
                $isCrossdock = $supply['is_crossdock'] ?? false;
                $ozonSupplyId = $supply['supply_id'] ?? null;

                $clusterName = null;
                if ($macrolocalClusterId) {
                    $warehouse = MarketplaceWarehouse::query()
                        ->where('macrolocal_cluster_id', $macrolocalClusterId)
                        ->first();

                    $clusterName = $warehouse?->cluster;
                }

                $this->supply->update([
                    'supply_id' => (string) $status['order_id'],
                    'cluster' => $clusterName ?? $macrolocalClusterId,
                    'supply_date' => isset($timeslot['from']) ? \Carbon\Carbon::parse($timeslot['from']) : null,
                    'supply_type' => $isCrossdock ? 'Кросс-докинг' : 'Прямая поставка',
                    'draft_id' => null,
                    'draft_created_at' => null,
                ]);

                if ($ozonSupplyId) {
                    $draftParams = $this->supply->draft_params ?? [];
                    $draftParams['order_number'] = $ozonSupplyId;
                    $draftParams['timeslot_from'] = $timeslot['from'] ?? null;
                    $draftParams['timeslot_to'] = $timeslot['to'] ?? null;
                    $draftParams['macrolocal_cluster_id'] = $macrolocalClusterId;
                    $this->supply->update(['draft_params' => $draftParams]);
                }

                Log::channel('marketplace_supplies')
                    ->notice(auth()->user()->name.' создал заявку OZON FBO #'.$status['order_id'].' из черновика #'.$draftId.'.');

                session()->flash('success', 'Заявка на поставку #'.$status['order_id'].' создана.');
                $this->redirect(route('marketplace_supplies.show', ['marketplace_supply' => $this->supply->id]));

                return;
            }

            if ($status['status'] === 'FAILED') {
                $errors = implode(', ', $status['error_reasons']);
                session()->flash('error', 'Ошибка создания заявки: '.$errors);
                $this->redirect(route('marketplace_supplies.show', ['marketplace_supply' => $this->supply->id]));

                return;
            }

            sleep(2);
        }

        session()->flash('error', 'Не удалось получить статус создания заявки за отведённое время. Проверьте статус вручную.');
        $this->redirect(route('marketplace_supplies.show', ['marketplace_supply' => $this->supply->id]));
    }

    private function saveDraftParams(): void
    {
        $this->supply->update([
            'draft_params' => [
                'supply_type' => $this->supply_type,
                'cluster_id' => $this->cluster_id,
                'seller_warehouse_id' => $this->seller_warehouse_id,
            ],
        ]);
    }

    public function render(): View
    {
        $draftItems = OzonFboDraftSupplyItem::query()
            ->with('skuRecord.item')
            ->where('supply_id', $this->supply->id)
            ->get();

        $clusters = MarketplaceWarehouse::query()
            ->where('marketplace_id', 1)
            ->whereNotNull('macrolocal_cluster_id')
            ->selectRaw('DISTINCT macrolocal_cluster_id, cluster')
            ->orderBy('cluster')
            ->get();

        return view('livewire.ozon-fbo-item-search', compact('draftItems', 'clusters'));
    }
}
