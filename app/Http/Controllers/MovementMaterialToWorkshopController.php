<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCollectMovementMaterialToWorkshopRequest;
use App\Http\Requests\SaveWriteOffMovementMaterialToWorkshopRequest;
use App\Http\Requests\StoreMovementMaterialToWorkshopRequest;
use App\Models\Material;
use App\Models\Order;
use App\Services\MovementMaterialToWorkshopService;
use App\Services\ShiftService;
use App\Services\TgService;
use App\Services\UserService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MovementMaterialToWorkshopController extends Controller
{
    public function index(Request $request)
    {
        $paginatedOrders = MovementMaterialToWorkshopService::getOrdersByStatus($request->status, auth()->user())
            ->with(['shift', 'movementMaterials.material', 'movementMaterials.roll', 'seamstress', 'cutter', 'user'])
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
        $user = auth()->user();

        if (in_array($user->role?->name, ShiftService::SHIFT_ROLES)) {
            $userShift = $user->currentShift();
            if ($userShift && $order->shift_id && $order->shift_id !== $userShift->id) {
                abort(403, 'Этот заказ принадлежит другой смене.');
            }
        }

        $order->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $list = '';
        foreach ($order->movementMaterials as $movementMaterial) {
            $list .= '• '.$movementMaterial->material->title.' '.$movementMaterial->quantity.' '.$movementMaterial->material->unit."\n";
        }

        $text = 'Швея '.auth()->user()->name.' приняла поставку в цехе: '."\n".$list;

        Log::channel('tg')
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

    /**
     * Сгенерировать ленту стикеров с названием смены.
     */
    public function printSticker(Order $order)
    {
        $order->load('shift', 'movementMaterials');

        $rollsCount = $order->movementMaterials->whereNotNull('roll_id')->count();

        if ($rollsCount === 0) {
            return back()->with('error', 'Нет рулонов для печати стикера.');
        }

        $pdf = Pdf::loadView('pdf.shift_sticker', [
            'shiftName' => $order->shift?->name ?? 'Без смены',
            'count' => $rollsCount,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('shift_sticker_order_'.$order->id.'.pdf');
    }
}
