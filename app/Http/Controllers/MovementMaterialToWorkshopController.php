<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCollectMovementMaterialToWorkshopRequest;
use App\Http\Requests\SaveWriteOffMovementMaterialToWorkshopRequest;
use App\Http\Requests\StoreMovementMaterialToWorkshopRequest;
use App\Models\Material;
use App\Models\Order;
use App\Services\MovementMaterialToWorkshopService;
use App\Services\TgService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MovementMaterialToWorkshopController extends Controller
{
    public function index(Request $request)
    {
        $paginatedOrders = MovementMaterialToWorkshopService::getOrdersByStatus($request->status)
            ->latest()
            ->paginate(10);

        $queryParams = $request->except(['page']);

        return view('movements_to_workshop.index', [
            'title' => 'Отгрузка на производство',
            'orders' => $paginatedOrders->appends($queryParams),
        ]);
    }

    public function create()
    {
        return view('movements_to_workshop.create', [
            'title' => 'Заказ новых материалов на производство',
            'materials' => Material::query()->get(),
        ]);
    }

    public function store(StoreMovementMaterialToWorkshopRequest $request)
    {
        if (! MovementMaterialToWorkshopService::store($request)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
            ->route('movements_to_workshop.index')
            ->with('success', 'Заказ сформирован и отправлен на склад');
    }

    public function collect(Order $order)
    {
        return view('movements_to_workshop.collect', [
            'title' => 'Сборка поставки',
            'order' => $order,
        ]);
    }

    public function write_off()
    {
        return view('movements_to_workshop.write_off', [
            'title' => 'Сборка списания',
        ]);
    }

    public function save_write_off(SaveWriteOffMovementMaterialToWorkshopRequest $request)
    {
        if (! MovementMaterialToWorkshopService::save_write_off($request)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
//            ->route('movements_to_workshop.index')
            ->route('inventory.workshop')
            ->with('success', 'Материал списан');
    }

    public function save_collect(SaveCollectMovementMaterialToWorkshopRequest $request, Order $order)
    {
        if (! MovementMaterialToWorkshopService::save_collect($request, $order)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()->route('movements_to_workshop.index')->with('success', 'Отгрузка сформирована');
    }

    public function receive(Order $order)
    {
        return view('movements_to_workshop.receive', [
            'title' => 'Прием поставки',
            'order' => $order,
        ]);
    }

    public function save_receive(Request $request, Order $order)
    {
        $order->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $list = '';
        foreach ($order->movementMaterials as $movementMaterial) {
            $list .= '• '.$movementMaterial->material->title.' '.$movementMaterial->quantity.' '.$movementMaterial->material->unit."\n";
        }

        $text = 'Швея '.auth()->user()->name.' приняла поставку в цехе: '."\n".$list;

        Log::channel('erp')
            ->notice('Отправляем сообщение в ТГ админу и работающим швеям и кладовщикам: '.$text);

        TgService::sendMessage(config('telegram.admin_id'), $text);

        foreach (UserService::getListSeamstressesWorkingToday() as $tgId) {
            TgService::sendMessage($tgId, $text);
        }

        foreach (UserService::getListStorekeepersWorkingToday() as $tgId) {
            TgService::sendMessage($tgId, $text);
        }

        return redirect()->route('movements_to_workshop.index')->with('success', 'Поставка принята');
    }

    public function delete(Order $order)
    {
        if ($order->status != 0) {
            return redirect()
                ->route('movements_to_workshop.index')
                ->with('error', 'Невозможно удалить заказ, так как он уже в работе');
        }

        $order->movementMaterials()->delete();

        $order->delete();

        return redirect()
            ->route('movements_to_workshop.index')
            ->with('success', 'Поставка удалена');
    }
}
