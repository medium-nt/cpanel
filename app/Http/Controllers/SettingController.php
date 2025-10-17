<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveSettingRequest;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Setting;
use App\Services\TgService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('settings.index', [
            'title' => 'Настройки системы',
            'settings' => (object)Setting::query()->pluck('value', 'name')->toArray()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function save(SaveSettingRequest $request)
    {
        $data = $request->except(['_method', '_token']);

        foreach ($data as $name => $value) {
            if (is_null($value)) {
                $value = '';
            }
            Setting::query()->where('name', $name)->update(['value' => $value]);
        }

        Log::channel('erp')->info('Настройки сохранены', [
            'data' => print_r($data, true)
        ]);

        return redirect()->route('setting.index')->with('success', 'Изменения сохранены');
    }

    public function test()
    {
//        TransactionService::accrualSeamstressesSalary(true);
        TransactionService::accrualCuttersSalary(true);

        //  тестовая функция для запуска других методов только на development сервере.
        if (!app()->environment('production')) {

            $chatId = 6523232418;

            TgService::sendMessage($chatId, 'Привет! Я работаю!');

//            $client = new Client([
//                'verify' => false,
//                'timeout' => 30,
//                'connect_timeout' => 30,
//                'curl' => [
//                    CURLOPT_SSL_VERIFYPEER => false,
//                    CURLOPT_SSL_VERIFYHOST => false,
//                ]
//            ]);
//
//            $client->post('https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/sendMessage', [
//                'form_params' => [
//                    'chat_id' => $chatId,
//                    'text' => 'Привет! Я работаю!'
//                ]
//            ]);

            dd('Is test server');
        }
        dd('Is no development server');
    }

    public static function duplicates()
    {
        $fullOrders = Order::whereIn('id', function ($query) {
            return $query->select(DB::raw('id'))
                ->from('orders')
                ->whereNotNull('marketplace_order_id')
                ->where('type_movement', 3)
//                ->where('id', '>', 3500)
                ->where('id', '>', 10940)
                ->whereIn('marketplace_order_id', function ($subQuery) {
                    return $subQuery->select('marketplace_order_id')
                        ->from('orders')
                        ->groupBy('marketplace_order_id')
                        ->havingRaw('COUNT(*) > 1');
                });
        })->get();

        // Группируем заказы по marketplace_order_id
        $groupedOrders = $fullOrders->groupBy('marketplace_order_id');

        echo "<h2>Всего заказов: " . $groupedOrders->count() . "</h2>";
        echo "<hr>";

        foreach ($groupedOrders as $marketplaceId => $orders) {
            $count = count($orders);

            // Проверяем только случаи с двумя записями
//            if ($count == 2) {
//                // Получаем первую и вторую запись
//                $first = $orders[0];
//                $second = $orders[1];
//
//                // Проверяем условия для пропуска:
//                // 1. У одной есть seamstress_id и нет cutter_id
//                // 2. У другой есть cutter_id и нет seamstress_id
//                if (
//                    // Первая запись: seamstress есть, cutter отсутствует
//                    ($first->seamstress_id && is_null($first->cutter_id) &&
//                        $second->cutter_id && is_null($second->seamstress_id)) ||
//
//                    // Или наоборот: вторая запись seamstress, первая cutter
//                    ($first->cutter_id && is_null($first->seamstress_id) &&
//                        $second->seamstress_id && is_null($second->cutter_id))
//                ) {
//                    continue;
//                }
//            }

            echo <<<HTML
                <!-- Подключите Bootstrap CSS в head вашего документа -->
                <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

                <style>
                    .table {
                        width: 100%;
                        margin-bottom: 20px;
                    }

                    .table th {
                        background-color: #f8f9fa;
                        font-weight: bold;
                    }

                    .table tbody tr:nth-child(even) {
                        background-color: #f8f9fa;
                    }
                </style>

                <h3>Заказ: {$marketplaceId} (повторений: {$count})</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID заказа</th>
                            <th>Швея</th>
                            <th>Раскройщик</th>
                            <th>Комментарии</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
            HTML;

            $countDeleted = 0;
            foreach ($orders as $order) {
                if ($order->cutter_id) {
//                    echo '<span style="color:red;">Удаляем расход материала: ' . $order->id . '</span><br>';
//
//                    MovementMaterial::query()
//                        ->where('order_id', $order->id)
//                        ->delete();
//
//                    $order->delete();

                    $countDeleted++;
                }

                echo <<<HTML
                    <tr>
                        <td>{$order->id}</td>
                        <td>{$order->seamstress_id}</td>
                        <td>{$order->cutter_id}</td>
                        <td>{$order->comment}</td>
                        <td>{$order->created_at->format('d.m.Y H:i:s')}</td>
                    </tr>
                HTML;
            }

            echo '</tbody></table><hr>';

//            echo "<h3>Всего удалено: {$countDeleted}</h3>";
            echo "<hr>";
        }

        die;
    }
}
