<?php

namespace App\Http\Controllers;

use App\Services\MarketplaceApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OzonReturnsController extends Controller
{
    public function index(Request $request, MarketplaceApiService $apiService): View
    {
        $tab = $request->get('tab', 'returns');

        // Получаем штрих-код выдачи возвратов (массив с png и barcode)
        $returnsBarcodeData = $apiService->getReturnsGiveoutPng();

        // Получаем данные только для активного таба
        $returnsData = $tab === 'returns' ? collect($apiService->getReturnsCompanyFbsInfo()) : collect();
        $deliveriesData = $tab === 'deliveries' ? collect($apiService->getReturnsGiveoutList()) : collect();

        return view('ozon_returns.index', [
            'title' => 'Получение возвратов',
            'tab' => $tab,
            'returnsBarcodeData' => $returnsBarcodeData,
            'returnsData' => $returnsData,
            'deliveriesData' => $deliveriesData,
        ]);
    }

    public function refreshBarcode(MarketplaceApiService $apiService): RedirectResponse
    {
        $newPng = $apiService->resetReturnsGiveoutBarcode();

        if ($newPng) {
            return redirect()
                ->route('ozon_returns.index')
                ->with('success', 'Штрих-код успешно обновлен');
        }

        return redirect()
            ->route('ozon_returns.index')
            ->with('error', 'Не удалось обновить штрих-код');
    }

    public function giveoutInfo(Request $request, MarketplaceApiService $apiService): JsonResponse
    {
        $giveoutId = $request->input('giveout_id');

        if (!$giveoutId) {
            return response()->json(['error' => 'Не указан ID выдачи'], 400);
        }

        $info = $apiService->getReturnsGiveoutInfo((int)$giveoutId);

        if (!$info) {
            return response()->json(['error' => 'Не удалось получить данные'], 500);
        }

        return response()->json($info);
    }

    public function products(Request $request, MarketplaceApiService $apiService): View
    {
        $visualStatusName = $request->get('visual_status_name');

        // Формируем фильтр для API
        $filter = [];
        if ($visualStatusName) {
            $filter['visual_status_name'] = $visualStatusName;
        }

        // Всегда фильтруем за последние 365 дней
        $filter['logistic_return_date'] = [
            'time_from' => now()->subYear()->format('Y-m-d\TH:i:s\Z'),
            'time_to' => now()->format('Y-m-d\TH:i:s\Z'),
        ];

        // Получаем данные из API
        $result = $apiService->getReturnsList($filter, 500);

        // Сортируем по дате возврата (новые сверху)
        $returns = collect($result['returns'])->sortByDesc('logistic.return_date')->values();

        return view('ozon_returns.products', [
            'title' => 'Возвраты товаров',
            'returns' => $returns,
            'hasNext' => $result['has_next'],
            'currentStatus' => $visualStatusName,
        ]);
    }
}
