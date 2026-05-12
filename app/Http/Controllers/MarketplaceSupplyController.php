<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceOrderService;
use App\Services\MarketplaceSupplyService;
use App\Services\TgService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MarketplaceSupplyController extends Controller
{
    public function index(Request $request)
    {
        $supplies = MarketplaceSupply::query()
            ->orderBy('created_at', 'desc');

        if ($request->status == 3) {
            $supplies = $supplies->where('status', 3);
        } elseif ($request->status == 0) {
            $supplies = $supplies->whereIn('status', [0, 13]);
        } else {
            $supplies = match (auth()->user()->role->name) {
                'driver' => $supplies->where('status', 4),
                default => $supplies->where('status', '!=', 3),
            };
        }

        if (isset($request->marketplace_id)) {
            $supplies = $supplies->where('marketplace_id', $request->marketplace_id);
        }

        if (isset($request->search)) {
            $supplies = $supplies->where(function ($query) use ($request) {
                $query->where('supply_id', 'like', '%'.$request->search.'%');
            });
        }

        $queryParams = $request->except(['page']);

        return view('marketplace_supply.index', [
            'title' => 'Поставки маркетплейса',
            'marketplace_supplies' => $supplies->paginate(10)->appends($queryParams),
        ]);
    }

    public function show(MarketplaceSupply $marketplaceSupply)
    {
        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceSupply->marketplace_id);

        if ($marketplaceSupply->type === 'FBO') {
            $wbSupplies = ($marketplaceSupply->status === 0 && empty($marketplaceSupply->supply_id))
                ? MarketplaceApiService::getFboSuppliesWb()
                : [];

            $hasOrders = MarketplaceOrder::query()
                ->where('supply_id', $marketplaceSupply->id)
                ->exists();

            $supplyOrders = $hasOrders
                ? MarketplaceOrder::query()
                    ->with('items.item', 'box')
                    ->where('supply_id', $marketplaceSupply->id)
                    ->get()
                : collect();

            return view('marketplace_supply.show-wb-fbo', [
                'title' => 'Поставка для маркетплейса '.$marketplaceName,
                'supply' => $marketplaceSupply,
                'wbSupplies' => $wbSupplies,
                'hasOrders' => $hasOrders,
                'supplyOrders' => $supplyOrders,
            ]);
        }

        return view('marketplace_supply.show', [
            'title' => 'Поставка для маркетплейса '.$marketplaceName,
            'supply' => $marketplaceSupply,
            'hasShippedOrders' => MarketplaceOrderService::hasShippedOrdersBySupply($marketplaceSupply),
            'supply_orders' => $marketplaceSupply->marketplace_orders()->get(),
        ]);
    }

    /**
     * Привязка выбранной FBO-поставки из WB к поставке в системе.
     */
    public function linkWbFbo(Request $request, MarketplaceSupply $marketplaceSupply)
    {
        $validated = $request->validate([
            'wb_supply_id' => 'required|integer',
        ]);

        $exists = MarketplaceSupply::query()
            ->where('supply_id', (string) $validated['wb_supply_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Поставка с номером '.$validated['wb_supply_id'].' уже привязана.');
        }

        $detail = MarketplaceApiService::getFboSupplyDetailWb((int) $validated['wb_supply_id']);

        if (empty($detail)) {
            return back()->with('error', 'Не удалось получить данные поставки из WB.');
        }

        $marketplaceSupply->update([
            'supply_id' => (string) $validated['wb_supply_id'],
            'cluster' => $detail['warehouseName'] ?? null,
            'supply_date' => isset($detail['supplyDate']) ? Carbon::parse($detail['supplyDate']) : null,
        ]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' привязал FBO-поставку WB #'.$validated['wb_supply_id'].' к поставке #'.$marketplaceSupply->id.'.');

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Поставка привязана.');
    }

    /**
     * Загрузка товарного состава FBO-поставки из WB API.
     */
    public function loadFboGoods(MarketplaceSupply $marketplaceSupply)
    {
        $goods = MarketplaceApiService::getFboSupplyGoodsWb((int) $marketplaceSupply->supply_id);

        if (empty($goods)) {
            return back()->with('error', 'Не удалось получить товарный состав из WB.');
        }

        $vendorCodes = collect($goods)->pluck('vendorCode')->unique()->filter()->values()->toArray();

        $items = MarketplaceItem::query()
            ->whereIn('article', $vendorCodes)
            ->get()
            ->keyBy('article');

        $supplyGoods = collect($goods)->map(function (array $good) use ($items) {
            $item = $items->get($good['vendorCode']);

            return [
                'vendorCode' => $good['vendorCode'],
                'name' => $item
                    ? $item->title.' '.$item->width.'x'.$item->height
                    : '-',
                'quantity' => $good['quantity'],
                'found' => $item !== null,
            ];
        });

        $allItemsFound = $supplyGoods->every(fn ($g) => $g['found']);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceSupply->marketplace_id);

        return view('marketplace_supply.show-wb-fbo', [
            'title' => 'Поставка для маркетплейса '.$marketplaceName,
            'supply' => $marketplaceSupply,
            'wbSupplies' => [],
            'supplyGoods' => $supplyGoods,
            'allItemsFound' => $allItemsFound,
            'hasOrders' => false,
            'supplyOrders' => collect(),
        ]);
    }

    /**
     * Форма редактирования полей Газельки для FBO-поставки.
     */
    public function editWbFbo(MarketplaceSupply $marketplaceSupply)
    {
        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceSupply->marketplace_id);

        return view('marketplace_supply.edit-wb-fbo', [
            'title' => 'Редактирование поставки '.$marketplaceName,
            'supply' => $marketplaceSupply,
        ]);
    }

    /**
     * Сохранение полей Газельки для FBO-поставки.
     */
    public function updateWbFbo(Request $request, MarketplaceSupply $marketplaceSupply)
    {
        $validated = $request->validate([
            'gazelka_shipment_id' => 'nullable|string',
            'gazelka_shipment_date' => 'nullable|date',
        ]);

        $marketplaceSupply->update([
            'gazelka_shipment_id' => $validated['gazelka_shipment_id'] ?? null,
            'gazelka_shipment_date' => isset($validated['gazelka_shipment_date']) ? Carbon::parse($validated['gazelka_shipment_date']) : null,
        ]);

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Данные обновлены.');
    }

    /**
     * Формирование заказов из товарного состава FBO-поставки.
     */
    public function confirmFboGoods(MarketplaceSupply $marketplaceSupply)
    {
        $hasOrders = MarketplaceOrder::query()
            ->where('supply_id', $marketplaceSupply->id)
            ->exists();

        if ($hasOrders) {
            return back()->with('error', 'Заказы для этой поставки уже созданы.');
        }

        $goods = MarketplaceApiService::getFboSupplyGoodsWb((int) $marketplaceSupply->supply_id);

        if (empty($goods)) {
            return back()->with('error', 'Не удалось получить товарный состав из WB.');
        }

        $vendorCodes = collect($goods)->pluck('vendorCode')->unique()->filter()->values()->toArray();

        $items = MarketplaceItem::query()
            ->whereIn('article', $vendorCodes)
            ->get()
            ->keyBy('article');

        $notFound = collect($vendorCodes)->diff($items->keys())->values();

        if ($notFound->isNotEmpty()) {
            return back()->with('error', 'Не найдены товары с артикулами: '.$notFound->implode(', '));
        }

        $orderNumber = 1;

        foreach ($goods as $good) {
            $item = $items->get($good['vendorCode']);

            for ($i = 0; $i < $good['quantity']; $i++) {
                $order = MarketplaceOrder::query()->create([
                    'order_id' => $marketplaceSupply->supply_id.'-'.$orderNumber,
                    'marketplace_id' => 2,
                    'supply_id' => $marketplaceSupply->id,
                    'fulfillment_type' => 'FBO',
                    'status' => 0,
                ]);

                MarketplaceOrderItem::query()->create([
                    'marketplace_order_id' => $order->id,
                    'marketplace_item_id' => $item->id,
                    'quantity' => 1,
                    'price' => 0,
                    'status' => 0,
                ]);

                $orderNumber++;
            }
        }

        $marketplaceSupply->update(['status' => 13]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' сформировал FBO-поставку #'.$marketplaceSupply->id.' ('.($orderNumber - 1).' заказов).');

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Поставка сформирована ('.($orderNumber - 1).' заказов).');
    }

    /**
     * Создание новой поставки для маркетплейса.
     */
    public function create(string $marketplace_id)
    {
        $type = request()->input('type', 'FBS');

        if ($marketplace_id == 1) {
            $openSupplyOzon = MarketplaceSupply::query()
                ->where('marketplace_id', 1)
                ->where('status', 0)
                ->count();

            if ($openSupplyOzon > 0) {
                return redirect()
                    ->route('marketplace_supplies.index')
                    ->with('error', 'Уже есть открытая поставка OZON.');
            }
        }

        $marketplaceSupply = MarketplaceSupply::query()->create([
            'marketplace_id' => $marketplace_id,
            'type' => $type,
        ]);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceSupply->marketplace_id);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' создал поставку для маркетплейса '.$marketplaceName.' ('.strtoupper($type).') (#'.$marketplaceSupply->id.').');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка создана.');
    }

    public function destroy(MarketplaceSupply $marketplace_supply)
    {
        if ($marketplace_supply->marketplace_orders->count() > 0) {
            return redirect()
                ->route('marketplace_supplies.index')
                ->with('error', 'Нельзя удалить поставку, которая содержит заказы.');
        }

        MarketplaceSupply::query()->findOrFail($marketplace_supply->id)->delete();

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка удалена.');
    }

    public function complete(MarketplaceSupply $marketplace_supply)
    {
        if ($marketplace_supply->marketplace_orders->count() == 0) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Поставка не содержит заказов.');
        }

        //  обновить статусы заказов в поставке
        $isUpdated = match ($marketplace_supply->marketplace_id) {
            1 => MarketplaceApiService::updateStatusOrderBySupplyOzon($marketplace_supply),
            2 => MarketplaceApiService::updateStatusOrderBySupplyWB($marketplace_supply),
            default => false
        };

        if (! $isUpdated) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось обновить статусы заказов перед формированием поставки! Попробуйте повторить позже.');
        }

        //  Проверить статусы всех заказов в поставке и если есть уже отгруженные - не давать сборку
        $checkStatusOrders = MarketplaceOrderService::hasShippedOrdersBySupply($marketplace_supply);
        if ($checkStatusOrders) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Невозможно сформировать поставку! В поставке есть заказы с неподходящим статусом.');
        }

        $result = match ($marketplace_supply->marketplace_id) {
            1 => MarketplaceApiService::ozonSupply($marketplace_supply),
            2 => MarketplaceApiService::wbSupply($marketplace_supply),
            default => false
        };

        if (! $result) {
            return redirect()
                ->route('marketplace_supplies.index')
                ->with('error', 'Ошибка! Не удалось выполнить сборку поставки.');
        }

        if ($marketplace_supply->video == null) {
            $text = 'Внимание! Кладовщик '.auth()->user()->name.
                ' не загрузил видео к поставке № '.$marketplace_supply->id.
                '. Запросите видео у кладовщика и загрузите его самостоятельно.';

            Log::channel('tg')
                ->error('Отправили сообщение в ТГ админу: '.$text);

            TgService::sendMessage(config('telegram.admin_id'), $text);
        }

        $marketplace_supply->update([
            'status' => 4,
            'completed_at' => now(),
        ]);

        $marketplace_supply->marketplace_orders()->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplace_supply->marketplace_id);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' передал в отгрузку поставку #'.$marketplace_supply->id.' для маркетплейса '.$marketplaceName.'.');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка сформирована.');
    }

    public function done(MarketplaceSupply $marketplace_supply)
    {
        $marketplace_supply->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplace_supply->marketplace_id);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' сдал поставку #'.$marketplace_supply->id.' в маркетплейс '.$marketplaceName.'.');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка сдана в маркетплейс.');
    }

    public function getDocs(MarketplaceSupply $marketplace_supply)
    {
        $isFormed = MarketplaceApiService::checkStatusSupplyOzon($marketplace_supply);
        if (! $isFormed) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Документы еще не сформированы.');
        }

        return MarketplaceApiService::getDocsSupplyOzon($marketplace_supply);
    }

    public function getBarcode(MarketplaceSupply $marketplace_supply)
    {
        return match ($marketplace_supply->marketplace_id) {
            1 => MarketplaceApiService::getBarcodeSupplyOzon($marketplace_supply),
            2 => MarketplaceApiService::getBarcodeSupplyWB($marketplace_supply),
            default => redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'В поставке указан некорректный маркетплейс!'),
        };
    }

    public function updateStatusOrders(MarketplaceSupply $marketplace_supply)
    {
        switch ($marketplace_supply->marketplace_id) {
            case 1:
                $isUpdated = MarketplaceApiService::updateStatusOrderBySupplyOzon($marketplace_supply);
                break;
            case 2:
                $isUpdated = MarketplaceApiService::updateStatusOrderBySupplyWB($marketplace_supply);
                break;
            default:
                return redirect()
                    ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                    ->with('error', 'В поставке указан некорректный маркетплейс!');
        }

        if (! $isUpdated) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось обновить статусы заказов!');
        }

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' обновил статусы заказов для поставки #'.$marketplace_supply->id.'.');

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
            ->with('success', 'Статусы заказов обновлены.');
    }

    public function delete_video(MarketplaceSupply $marketplace_supply)
    {
        $video = $marketplace_supply->video;

        if (Storage::disk('public')->exists('videos/'.$video)) {
            Storage::disk('public')->delete('videos/'.$video);
        }

        $marketplace_supply->update([
            'video' => null,
        ]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' удалил видео для поставки #'.$marketplace_supply->id.'.');

        return back()->with('success', 'Видео удалено!');
    }

    public function chunkedUpload(Request $request)
    {
        return MarketplaceSupplyService::chunkedUpload($request);
    }

    public function close(MarketplaceSupply $marketplace_supply)
    {
        $marketplace_supply->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $marketplace_supply->marketplace_orders()->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplace_supply->marketplace_id);
        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' Вручную закрыл поставку #'.$marketplace_supply->id.' в маркетплейс '.$marketplaceName.'.');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка закрыта вручную.');
    }
}
