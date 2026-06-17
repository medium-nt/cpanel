<?php

namespace App\Http\Controllers;

use App\Jobs\SendTelegramMessageJob;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceSupply;
use App\Models\SupplyBox;
use App\Services\MarketplaceApiService;
use App\Services\UserService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SupplyBoxController extends Controller
{
    /**
     * Список коробов поставки.
     */
    public function index(MarketplaceSupply $marketplaceSupply)
    {
        $boxes = SupplyBox::query()
            ->withCount('orders')
            ->where('marketplace_supply_id', $marketplaceSupply->id)
            ->get();

        $freeOrdersCount = MarketplaceOrder::query()
            ->where('supply_id', $marketplaceSupply->id)
            ->whereNull('box_id')
            ->count();

        return view('supply_box.index', [
            'title' => 'Короба поставки #'.$marketplaceSupply->supply_id,
            'supply' => $marketplaceSupply,
            'boxes' => $boxes,
            'freeOrdersCount' => $freeOrdersCount,
        ]);
    }

    /**
     * Пометить поставку как собранную — все заказы распределены и все короба закрыты.
     */
    public function markAssembled(MarketplaceSupply $marketplaceSupply)
    {
        if ($marketplaceSupply->status === 4) {
            return back()->with('error', 'Поставка уже в отгрузке.');
        }

        $boxes = SupplyBox::query()
            ->where('marketplace_supply_id', $marketplaceSupply->id)
            ->get();

        if ($boxes->isEmpty()) {
            return back()->with('error', 'Нет созданных коробов.');
        }

        if ($boxes->some(fn (SupplyBox $box) => ! $box->closed_at)) {
            return back()->with('error', 'Не все короба закрыты. Закройте все короба перед завершением.');
        }

        $freeOrdersCount = MarketplaceOrder::query()
            ->where('supply_id', $marketplaceSupply->id)
            ->whereNull('box_id')
            ->count();

        if ($freeOrdersCount > 0) {
            return back()->with('error', 'Есть нераспределённые заказы. Распределите все заказы по коробам.');
        }

        $marketplaceSupply->update(['status' => 4]);

        $marketplaceSupply->marketplace_orders()->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' пометил поставку #'.$marketplaceSupply->id.' как собранную.');

        // Отправка уведомлений в ТГ менеджерам и админу
        $marketplaceName = match ($marketplaceSupply->marketplace_id) {
            1 => 'OZON',
            2 => 'Wildberries',
            default => '---',
        };

        $text = sprintf(
            '✅ Поставка #%s (%s) собрана.',
            $marketplaceSupply->supply_id,
            $marketplaceName
        );

        SendTelegramMessageJob::dispatch(config('telegram.admin_id'), $text);

        foreach (UserService::getListManagersWithTg() as $index => $tgId) {
            SendTelegramMessageJob::dispatch($tgId, $text)
                ->delay(now()->addSeconds($index + 1));
        }

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Поставка помечена как собранная.');
    }

    /**
     * Создание нового короба.
     */
    public function store(Request $request, MarketplaceSupply $marketplaceSupply)
    {
        $box = SupplyBox::query()->create([
            'marketplace_supply_id' => $marketplaceSupply->id,
            'number' => '',
        ]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' создал короб #'.$box->number.' для поставки #'.$marketplaceSupply->id.'.');

        return redirect()
            ->route('supply_boxes.show', ['marketplace_supply' => $marketplaceSupply, 'box' => $box])
            ->with('success', 'Короб создан.');
    }

    /**
     * Удаление пустого короба.
     */
    public function destroy(MarketplaceSupply $marketplaceSupply, SupplyBox $box)
    {
        if ($box->orders()->exists()) {
            return back()->with('error', 'Нельзя удалить короб с заказами.');
        }

        Log::channel('marketplace_supplies')->warning('Удалён короб поставки', [
            'supply_id' => $marketplaceSupply->id,
            'box_id' => $box->id,
            'box_number' => $box->number,
            'deleted_by' => auth()->id(),
        ]);

        $box->delete();

        return redirect()
            ->route('supply_boxes.index', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Короб удалён.');
    }

    /**
     * Страница короба — заказы внутри.
     */
    public function show(MarketplaceSupply $marketplaceSupply, SupplyBox $box)
    {
        $box->load('orders.items.item');

        return view('supply_box.show', [
            'title' => 'Короб '.$box->number,
            'supply' => $marketplaceSupply,
            'box' => $box,
        ]);
    }

    /**
     * Убрать заказ из короба.
     */
    public function removeOrder(MarketplaceSupply $marketplaceSupply, SupplyBox $box, MarketplaceOrder $order)
    {
        if ($order->box_id === $box->id) {
            $order->update(['box_id' => null]);

            Log::channel('marketplace_supplies')->info('Заказ убран из короба', [
                'supply_id' => $marketplaceSupply->id,
                'box_id' => $box->id,
                'order_id' => $order->id,
                'removed_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Заказ убран из короба.');
    }

    /**
     * Закрыть короб — запретить добавление и удаление товаров.
     */
    public function closeBox(MarketplaceSupply $marketplaceSupply, SupplyBox $box)
    {
        if ($box->closed_at) {
            return back()->with('error', 'Короб уже закрыт.');
        }

        if ($box->orders()->count() === 0) {
            return back()->with('error', 'Нельзя закрыть пустой короб.');
        }

        $box->update(['closed_at' => now()]);

        Log::channel('marketplace_supplies')->info('Короб закрыт', [
            'supply_id' => $marketplaceSupply->id,
            'box_id' => $box->id,
            'closed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('supply_boxes.show', ['marketplace_supply' => $marketplaceSupply, 'box' => $box])
            ->with('success', 'Короб закрыт.');
    }

    /**
     * Генерация стикера короба — через API для OZON, через PDF для WB.
     */
    public function printSticker(MarketplaceSupply $marketplaceSupply, SupplyBox $box)
    {
        if (! $box->closed_at) {
            return back()->with('error', 'Стикер доступен только для закрытого короба.');
        }

        $box->load('supply');

        if ($box->supply->marketplace_id === 1) {
            return $this->printOzonSticker($marketplaceSupply, $box, request()->boolean('regenerate'));
        }

        $pdf = Pdf::loadView('pdf.box_sticker', ['box' => $box]);
        $pdf->setPaper([0, 0, 75 * 2.83, 120 * 2.83], 'portrait');

        return $pdf->stream('box_sticker_'.$box->number.'.pdf');
    }

    /**
     * Генерация стикера короба OZON FBO через цепочку API-запросов.
     */
    private function printOzonSticker(MarketplaceSupply $marketplaceSupply, SupplyBox $box, bool $regenerate = false)
    {
        if ($regenerate) {
            $box->update(['sticker_url' => null]);
        }

        if ($box->sticker_url) {
            return redirect($box->sticker_url);
        }

        $ozonSupplyId = $box->supply->draft_params['order_number'] ?? null;

        if (! $ozonSupplyId) {
            return back()->with('error', 'Не найден order_number в параметрах поставки.');
        }

        // Если грузоместо уже создано — пропускаем его создание
        if ($box->cargo_id) {
            $cargoId = $box->cargo_id;
        } else {
            $items = $this->buildOzonCargoItems($box);

            if (empty($items)) {
                return back()->with('error', 'Нет товаров в коробе для формирования грузоместа.');
            }

            $payload = [
                'cargoes' => [
                    [
                        'key' => $box->number,
                        'value' => [
                            'items' => $items,
                            'type' => 'BOX',
                        ],
                    ],
                ],
                'delete_current_version' => false,
                'supply_id' => (int) $ozonSupplyId,
            ];

            // Шаг 1: Создание грузоместа
            $cargoResult = MarketplaceApiService::createCargoOzon($payload);

            if ($cargoResult['error']) {
                return back()->with('error', 'Ошибка создания грузоместа: '.$cargoResult['error']);
            }

            // Шаг 2: Ожидание результата создания грузоместа
            $cargoId = $this->pollOperation(
                fn () => MarketplaceApiService::getCargoCreateInfoOzon($cargoResult['operation_id']),
                'cargo_id'
            );

            if (! $cargoId) {
                return back()->with('error', 'Не удалось получить cargo_id от OZON. Попробуйте позже.');
            }

            $box->update(['cargo_id' => $cargoId]);
        }

        // Шаг 3: Создание этикетки
        $labelResult = MarketplaceApiService::createCargoLabelOzon(
            $ozonSupplyId,
            [$cargoId]
        );

        if ($labelResult['error']) {
            return back()->with('error', 'Ошибка создания этикетки: '.$labelResult['error']);
        }

        // Шаг 4: Ожидание результата генерации этикетки
        $fileUrl = $this->pollOperation(
            fn () => MarketplaceApiService::getCargoLabelOzon($labelResult['operation_id']),
            'file_url'
        );

        if (! $fileUrl) {
            return back()->with('error', 'Не удалось получить ссылку на стикер от OZON. Попробуйте позже.');
        }

        $box->update(['sticker_url' => $fileUrl]);

        return redirect($fileUrl);
    }

    /**
     * Формирует массив товаров для API-запроса создания грузоместа OZON.
     */
    private function buildOzonCargoItems(SupplyBox $box): array
    {
        $box->load('orders.items.item.sku');

        $grouped = [];

        foreach ($box->orders as $order) {
            foreach ($order->items as $orderItem) {
                $item = $orderItem->item;
                if (! $item) {
                    continue;
                }

                $article = $item->article;
                $sku = $item->sku->first(fn (mixed $s) => $s->marketplace_id === 1);

                if (! $sku) {
                    continue;
                }

                if (! isset($grouped[$article])) {
                    $grouped[$article] = [
                        'barcode' => 'OZN'.$sku->sku,
                        'offer_id' => $article,
                        'quantity' => 0,
                    ];
                }

                $grouped[$article]['quantity'] += $orderItem->quantity;
            }
        }

        return array_values($grouped);
    }

    /**
     * Опрашивает результат асинхронной операции OZON до получения значения или истечения попыток.
     */
    private function pollOperation(callable $apiCall, string $resultKey, int $maxAttempts = 10, int $sleepSeconds = 1): mixed
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep($sleepSeconds);

            $result = $apiCall();

            if (isset($result['error']) && $result['error']) {
                Log::channel('marketplace_api')->warning(
                    'Ошибка при polling операции OZON',
                    ['attempt' => $i + 1, 'error' => $result['error']]
                );

                continue;
            }

            if (($result['status'] ?? '') === 'SUCCESS' && $result[$resultKey] ?? null) {
                return $result[$resultKey];
            }
        }

        return null;
    }

    /**
     * Экспорт коробов поставки в Excel-файл.
     */
    public function exportExcel(MarketplaceSupply $marketplaceSupply)
    {
        $boxes = SupplyBox::query()
            ->where('marketplace_supply_id', $marketplaceSupply->id)
            ->get();

        $freeOrdersCount = MarketplaceOrder::query()
            ->where('supply_id', $marketplaceSupply->id)
            ->whereNull('box_id')
            ->count();

        if ($boxes->isEmpty() || $boxes->some(fn ($box) => ! $box->closed_at) || $freeOrdersCount > 0) {
            return back()->with('error', 'Экспорт доступен только когда все короба закрыты и нет нераспределённых заказов.');
        }

        $rows = MarketplaceOrder::query()
            ->join('marketplace_order_items', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id')
            ->join('skus', function ($join) use ($marketplaceSupply) {
                $join->on('skus.item_id', '=', 'marketplace_items.id')
                    ->where('skus.marketplace_id', $marketplaceSupply->marketplace_id);
            })
            ->join('supply_boxes', 'marketplace_orders.box_id', '=', 'supply_boxes.id')
            ->where('marketplace_orders.supply_id', $marketplaceSupply->id)
            ->whereNotNull('marketplace_orders.box_id')
            ->selectRaw(
                'skus.sku as barcode, '
                .'SUM(marketplace_order_items.quantity) as total_quantity, '
                .'supply_boxes.number as box_number'
            )
            ->groupBy([
                'skus.sku',
                'supply_boxes.number',
            ])
            ->orderBy('supply_boxes.number')
            ->orderBy('skus.sku')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Баркод товара');
        $sheet->setCellValue('B1', 'Кол-во товаров');
        $sheet->setCellValue('C1', 'ШК короба');
        $sheet->setCellValue('D1', 'Срок годности');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($rows as $item) {
            $sheet->setCellValue('A'.$row, $item->barcode);
            $sheet->setCellValue('B'.$row, $item->total_quantity);
            $sheet->setCellValue('C'.$row, $item->box_number);
            $sheet->setCellValue('D'.$row, '');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            callback: function () use ($writer) {
                $writer->save('php://output');
            },
            name: 'boxes_supply_'.$marketplaceSupply->id.'_'.now()->format('Y-m-d_H-i-s').'.xlsx',
            headers: [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }
}
