<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceSupply;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceSupplyService;
use App\Services\TgService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MarketplaceSupplyController extends Controller
{
    public function index(Request $request)
    {
        $supplies = MarketplaceSupply::query()
            ->orderBy('created_at', 'desc');

        if($request->status == 3) {
            $supplies = $supplies->where('status', 3);
        } else {
            $supplies = $supplies->where('status', '!=', 3);
        }

        if (isset($request->marketplace_id)) {
            $supplies = $supplies->where('marketplace_id', $request->marketplace_id);
        }

        if (isset($request->search)) {
            $supplies = $supplies->where(function ($query) use ($request) {
                $query->where('supply_id', 'like', '%' . $request->search . '%');
            });
        }

        $queryParams = $request->except(['page']);

        return view('marketplace_supply.index', [
            'title' => 'Поставки маркетплейса',
            'marketplace_supplies' => $supplies->paginate(10)->appends($queryParams)
        ]);
    }

    public function show(MarketplaceSupply $marketplaceSupply)
    {
        $marketplaceName = match ($marketplaceSupply->marketplace_id) {
            1 => 'OZON',
            2 => 'WB',
        };

        return view('marketplace_supply.show', [
            'title' => 'Поставка для маркетплейса ' . $marketplaceName,
            'supply' => $marketplaceSupply,
            'supply_orders' => $marketplaceSupply->marketplace_orders()->get(),
        ]);
    }

    public function create(string $marketplace_id)
    {
        if($marketplace_id == 1){
            $openSupplyOzon = MarketplaceSupply::query()
                ->where('marketplace_id', 1)
                ->where('status', 0)
                ->count();

            if($openSupplyOzon > 0) {
                return redirect()
                    ->route('marketplace_supplies.index')
                    ->with('error', 'Уже есть открытая поставка OZON.');
            }
        }

        $marketplaceSupply = MarketplaceSupply::query()->create([
            'marketplace_id' => $marketplace_id,
        ]);

        $marketplaceName = match ($marketplaceSupply->marketplace_id) {
            '1' => 'OZON',
            '2' => 'WB',
            default => '---',
        };

        Log::channel('erp')->notice('    ' . auth()->user()->name . ' создал поставку для маркетплейса ' . $marketplaceName . ' (#' . $marketplaceSupply->id . ').');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка создана.');
    }

    public function destroy(MarketplaceSupply $marketplace_supply)
    {
        if($marketplace_supply->marketplace_orders->count() > 0) {
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

        if ($marketplace_supply->video == null) {
            $text = 'Внимание! Кладовщик ' . auth()->user()->name .
                ' не загрузил видео к поставке № ' . $marketplace_supply->id .
                '. Запросите видео у кладовщика и загрузите его самостоятельно.';

            Log::channel('erp')
                ->error('Отправили сообщение в ТГ админу: ' . $text);

            TgService::sendMessage(config('telegram.admin_id'), $text);
        }

        $result = match ($marketplace_supply->marketplace_id) {
            1 => MarketplaceApiService::ozonSupply($marketplace_supply),
            2 => MarketplaceApiService::wbSupply($marketplace_supply),
        };

        if(!$result) {
            return redirect()
                ->route('marketplace_supplies.index')
                ->with('error', 'Ошибка! Не удалось выполнить сборку поставки.');
        }

        $marketplace_supply->update([
            'status' => 4,
            'completed_at' => now(),
        ]);

        $marketplace_supply->marketplace_orders()->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $marketplaceName = match ($marketplace_supply->marketplace_id) {
            1 => 'OZON',
            2 => 'WB',
            default => '---',
        };

        Log::channel('erp')->notice('    ' . auth()->user()->name . ' передал в отгрузку поставку #' . $marketplace_supply->id . ' для маркетплейса ' . $marketplaceName . '.');

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

        $marketplaceName = match ($marketplace_supply->marketplace_id) {
            '1' => 'OZON',
            '2' => 'WB',
            default => '---',
        };

        Log::channel('erp')->notice('    ' . auth()->user()->name . ' сдал поставку #' . $marketplace_supply->id . ' в маркетплейс ' . $marketplaceName . '.');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка сдана в маркетплейс.');
    }

    public function getDocs(MarketplaceSupply $marketplace_supply)
    {
        $isFormed = MarketplaceApiService::checkStatusSupplyOzon($marketplace_supply);
        if (!$isFormed){
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
        return match ($marketplace_supply->marketplace_id){
            1 => MarketplaceApiService::updateStatusOrderBySupplyOzon($marketplace_supply),
            2 => MarketplaceApiService::updateStatusOrderBySupplyWB($marketplace_supply),
            default => redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'В поставке указан некорректный маркетплейс!'),
        };
    }

    public function delete_video(MarketplaceSupply $marketplace_supply)
    {
        $video = $marketplace_supply->video;

        if (Storage::disk('public')->exists('videos/' . $video)) {
            Storage::disk('public')->delete('videos/' . $video);
        }

        $marketplace_supply->update([
            'video' => null
        ]);

        Log::channel('erp')
            ->notice('    ' . auth()->user()->name . ' удалил видео для поставки #' . $marketplace_supply->id . '.');

        return back()->with('success', 'Видео удалено!');
    }

    public function chunkedUpload(Request $request)
    {
        return MarketplaceSupplyService::chunkedUpload($request);
    }
}
